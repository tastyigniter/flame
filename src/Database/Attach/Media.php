<?php

namespace Igniter\Flame\Database\Attach;

use Exception;
use File;
use FilesystemIterator;
use Igniter\Flame\Database\Model;
use Log;
use Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use URL;

class Media extends Model
{
    use \Igniter\Flame\Database\Traits\Sortable;

    const SORT_ORDER = 'priority';

    protected $table = 'media_attachments';

    public $timestamps = TRUE;

    protected $guarded = ['disk'];

    /**
     * @var array Known image extensions.
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array Hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    public $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
    ];

    /**
     * @var array Mime types
     */
    protected $autoMimeTypes = [
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
    ];

    public $fileToAdd;

    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function attachment()
    {
        return $this->morphTo('attachment');
    }

    /**
     * Creates a file object from a file an uploaded file.
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
     * @param null $tag
     * @return self
     */
    public function addFromRequest(UploadedFile $uploadedFile, $tag = null)
    {
        $this->getMediaAdder()
             ->performedOn($this->attachment)
             ->useMediaTag($tag)
             ->fromFile($uploadedFile);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk.
     * @param $filePath
     * @param null $tag
     * @return self|void
     */
    public function addFromFile($filePath, $tag = null)
    {
        if (is_null($filePath))
            return;

        $this->getMediaAdder()
             ->performedOn($this->attachment)
             ->useMediaTag($tag)
             ->fromFile(new SymfonyFile($filePath));

        return $this;
    }

    /**
     * Creates a file object from raw data.
     *
     * @param $rawData string Raw data
     * @param $filename string Filename
     *
     * @param null $tag
     * @return $this|void
     */
    public function addFromRaw($rawData, $filename, $tag = null)
    {
        if (is_null($rawData))
            return;

        $tempPath = temp_path($filename);
        File::put($tempPath, $rawData);

        $this->addFromFile($tempPath, $tag);
        File::delete($tempPath);

        return $this;
    }

    /**
     * Creates a file object from url
     * @param $url string URL
     * @param $filename string Filename
     * @param null $tag
     * @return $this
     * @throws \Exception
     */
    public function addFromUrl($url, $filename = null, $tag = null)
    {
        if (!$stream = @fopen($url, 'rb'))
            throw new Exception(sprintf('Error opening file "%s"', $url));

        return $this->addFromRaw(
            $stream,
            !empty($filename) ? $filename : File::basename($url),
            $tag
        );
    }

    //
    // Events
    //

    protected function beforeSave()
    {
        if (!is_null($this->fileToAdd)) {
            if ($this->fileToAdd instanceof UploadedFile) {
                $this->addFromRequest($this->fileToAdd);
            }
            else {
                $this->addFromFile($this->fileToAdd);
            }

            $this->fileToAdd = null;
        }
    }

    /**
     * After model is deleted
     * - clean up it's thumbnails
     */
    protected function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        }
        catch (Exception $ex) {
            Log::error($ex);
        }
    }

    //
    // Attribute mutators
    //

    /**
     * Determine the type of a file.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return $this->getMimeType();
    }

    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * Helper attribute for get image width.
     * @return string
     */
    public function getWidthAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[0];
        }
    }

    /**
     * Helper attribute for get image height.
     * @return string
     */
    public function getHeightAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[1];
        }
    }

    public function getHumanReadableSizeAttribute()
    {
        return $this->sizeToString($this->size);
    }

    //
    // Getters
    //

    /**
     * Returns the file name without path
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * Returns the file extension.
     */
    public function getExtension()
    {
        return File::extension($this->file_name);
    }

    /**
     * Returns the last modification date as a UNIX timestamp.
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        if (!$fileName)
            $fileName = $this->disk_name;

        return $this->getStorageDisk()->lastModified($this->getStoragePath().$fileName);
    }

    /**
     * Returns the public address to access the file.
     */
    public function getPath()
    {
        return $this->getPublicPath().$this->getPartitionDirectory().$this->name;
    }

    /**
     * Returns a local path to this file. If the file is stored remotely,
     * it will be downloaded to a temporary directory.
     */
    public function getFullDiskPath()
    {
        return $this->getStorageDisk()->path($this->getDiskPath());
    }

    /**
     * Returns the path to the file, relative to the storage disk.
     * @return string
     */
    public function getDiskPath()
    {
        return $this->getStoragePath().$this->name;
    }

    /**
     * Determines if the file is flagged "public" or not.
     */
    public function isPublic()
    {
        if (is_null($this->is_public))
            return TRUE;

        return $this->is_public;
    }

    /**
     * Returns the file size as string.
     * @return string Returns the size as string.
     */
    public function sizeToString()
    {
        return File::sizeToString($this->file_size);
    }

    public function getMimeType()
    {
        if (!is_null($this->mime_type))
            return $this->mime_type;

        if ($type = $this->getTypeFromExtension())
            return $this->mime_type = $type;

        return null;
    }

    public function getTypeFromExtension()
    {
        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->autoMimeTypes[$ext];
        }
    }

    /**
     * Generates a unique name from the supplied file name.
     */
    public function getUniqueName()
    {
        if (!is_null($this->name))
            return $this->name;

        $ext = strtolower($this->getExtension());

        $name = str_replace('.', '', uniqid(null, TRUE));

        return $this->name = $name.(strlen($ext) ? '.'.$ext : '');
    }

    public function getDiskName()
    {
        if (!is_null($this->disk))
            return $this->disk;

        $diskName = config('system.assets.attachment.disk');
        if (is_null(config("filesystems.disks.{$diskName}")))
            throw new Exception("There is no filesystem disk named '{$diskName}''");

        return $this->disk = $diskName;
    }

    public function getDiskDriverName()
    {
        return strtolower(config("filesystems.disks.{$this->disk}.driver"));
    }

    //
    //
    //

    /**
     * Delete all thumbnails for this file.
     */
    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStoragePath();
        $allFiles = $this->getStorageDisk()->files($directory);
        $paths = array_filter($allFiles, function ($file) use ($pattern) {
            return starts_with(basename($file), $pattern);
        });

        $this->getStorageDisk()->delete($paths);
    }

    /**
     * Delete file contents from storage device.
     * @param null $fileName
     * @return void
     */
    protected function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->name;
        }

        $directory = $this->getStoragePath();
        $filePath = $directory.$fileName;

        if ($this->getStorageDisk()->exists($filePath)) {
            $this->getStorageDisk()->delete($filePath);
        }

        $this->deleteEmptyDirectory($directory);
    }

    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     * @param string $directory
     * @return void
     */
    protected function deleteEmptyDirectory($directory = null)
    {
        if (!$this->isDirectoryEmpty($directory))
            return;

        $this->getStorageDisk()->deleteDirectory($directory);

        $directory = dirname($directory);
        if (!$this->isDirectoryEmpty($directory))
            return;

        $this->getStorageDisk()->deleteDirectory($directory);

        $directory = dirname($directory);
        if (!$this->isDirectoryEmpty($directory))
            return;

        $this->getStorageDisk()->deleteDirectory($directory);
    }

    /**
     * Returns true if a directory contains no files.
     * @param $directory
     * @return bool|null
     */
    protected function isDirectoryEmpty($directory)
    {
        $path = $this->getStorageDisk()->path($directory);

        return !(new FilesystemIterator($path))->valid();
    }

    /**
     * Check file exists on storage device.
     * @param string $fileName
     * @return bool
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getStoragePath().$fileName;

        return $this->getStorageDisk()->exists($filePath);
    }

    //
    // Image handling
    //

    /**
     * Checks if the file extension is an image and returns true or false.
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * Generates and returns a thumbnail url.
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    public function getThumb($options = [])
    {
        if (!$this->isImage())
            return $this->getPath();

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($options);
        if (!$this->hasFile($thumbFile))
            $this->makeThumb($thumbFile, $options);

        return $this->getPublicPath().$this->getPartitionDirectory().$thumbFile;
    }

    public function outputThumb($options = [])
    {

    }

    public function getDefaultThumbPath($thumbPath, $default = null)
    {
        if (!$default) {
            $this->getStorageDisk()->put($thumbPath, Manipulator::decodedBlankImage());
            $default = $thumbPath;
        }

        return $this->getStorageDisk()->path($default);
    }

    /**
     * Get image dimensions
     * @return array|bool
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getFullDiskPath());
    }

    /**
     * Generates a thumbnail filename.
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    protected function getThumbFilename($options)
    {
        return 'thumb_'
            .$this->id.'_'
            .$options['width'].'_'.$options['height'].'_'.$options['fit'].'_'
            .substr(md5(serialize(array_except($options, ['width', 'height', 'fit']))), 0, 8).
            '.'.$options['extension'];
    }

    /**
     * Returns the default thumbnail options.
     * @param array $override
     * @return array
     */
    protected function getDefaultThumbOptions($override = [])
    {
        $defaultOptions = [
            'fit' => 'contain',
            'width' => 0,
            'height' => 0,
            'quality' => 90,
            'sharpen' => 0,
            'extension' => 'auto',
        ];

        if (!is_array($override))
            $override = ['fit' => $override];

        $options = array_merge($defaultOptions, $override);

        if (strtolower($options['extension']) == 'auto')
            $options['extension'] = strtolower($this->getExtension());

        return $options;
    }

    /**
     * Generate the thumbnail
     * @param $thumbFile
     * @param array $options
     */
    protected function makeThumb($thumbFile, $options)
    {
        $thumbFile = $this->getStoragePath().$thumbFile;
        $thumbPath = $this->getStorageDisk()->path($thumbFile);
        $filePath = $this->getStorageDisk()->path($this->getDiskPath());

        if (!$this->hasFile($this->name))
            $filePath = $this->getDefaultThumbPath($thumbFile, array_get($options, 'default'));

        Manipulator::make($filePath)
                   ->manipulate(array_except($options, ['extension', 'default']))
                   ->save($thumbPath);
    }

    //
    // Custom Properties
    //

    public function getCustomProperties()
    {
        return $this->custom_properties;
    }

    /*
     * Determine if the media item has a custom property with the given name.
     */
    public function hasCustomProperty($propertyName)
    {
        return array_has($this->custom_properties, $propertyName);
    }

    /**
     * Get if the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty($propertyName, $default = null)
    {
        return array_get($this->custom_properties, $propertyName, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomProperty($name, $value)
    {
        $customProperties = $this->custom_properties;

        array_set($customProperties, $name, $value);

        $this->custom_properties = $customProperties;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function forgetCustomProperty($name)
    {
        $customProperties = $this->custom_properties;

        array_forget($customProperties, $name);

        $this->custom_properties = $customProperties;

        return $this;
    }

    //
    // Configuration
    //

    /**
     * Define the internal storage path, override this method to define.
     */
    public function getStoragePath()
    {
        return $this->getStorageDirectory().$this->getPartitionDirectory();
    }

    /**
     * Define the public address for the storage path.
     */
    public function getPublicPath()
    {
        $mediaPath = config('system.assets.attachment.path', '/storage/app/attachments');
        $mediaPath = $this->isPublic()
            ? $mediaPath.'/public'
            : $mediaPath.'/protected';

        return URL::asset($mediaPath).'/';
    }

    /**
     * Define the internal working path, override this method to define.
     */
    public function getTempPath()
    {
        $path = temp_path().'/attachments';

        if (!File::isDirectory($path))
            File::makeDirectory($path, 0777, TRUE, TRUE);

        return $path;
    }

    /**
     * Define the internal storage folder, override this method to define.
     */
    public function getStorageDirectory()
    {
        $mediaFolder = config('system.assets.attachment.folder', 'attachments');

        return $this->isPublic() ? $mediaFolder.'/public/' : $mediaFolder.'/protected/';
    }

    /**
     * Generates a partition for the file.
     * @return mixed
     */
    public function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->name, 3), 0, 3)).'/';
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     * @throws \Exception
     */
    protected function getStorageDisk()
    {
        return Storage::disk($this->getDiskName());
    }

    protected function getMediaAdder()
    {
        return app(MediaAdder::class)->on($this);
    }
}