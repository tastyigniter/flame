<?php namespace Igniter\Flame\Pagic\Source;

use Exception;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Pagic\Exception\CreateDirectoryException;
use Igniter\Flame\Pagic\Exception\CreateFileException;
use Igniter\Flame\Pagic\Exception\DeleteFileException;
use Igniter\Flame\Pagic\Exception\FileExistsException;
use Igniter\Flame\Pagic\Processors\Processor;
use Symfony\Component\Finder\Finder;

/**
 * File based source.
 */
class FileSource extends AbstractSource implements SourceInterface
{
    /**
     * The local path where the source can be found.
     * @var string
     */
    protected $basePath;

    /**
     * The filesystem instance.
     * @var \Igniter\Flame\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    public $finder;

    /**
     * Create a new source instance.
     *
     * @param $basePath
     * @param \Igniter\Flame\Filesystem\Filesystem $files
     * @param $fallbackPath
     */
    public function __construct($basePath, Filesystem $files)
    {
        $this->basePath = $basePath;

        $this->files = $files;
        $this->finder = new Finder;
        $this->processor = new Processor;
    }

    /**
     * Returns a single template.
     *
     * @param  string $dirName
     * @param  string $fileName
     * @param $extension
     *
     * @return mixed
     */
    public function select($dirName, $fileName, $extension)
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return [
                'fileName' => $fileName.'.'.$extension,
                'mTime' => $this->files->lastModified($path),
                'content' => $this->files->get($path),
            ];
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Returns all templates.
     *
     * @param  string $dirName
     * @param array $options
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function selectAll($dirName, array $options = [])
    {
        $columns = array_get($options, 'columns', null);  // Only return specific columns (fileName, mTime, content)
        $extensions = array_get($options, 'extensions', null);  // Match specified extensions
        $fileMatch = array_get($options, 'fileMatch', null);  // Match the file name using fnmatch()

        $result = [];
        $dirPath = $this->basePath.'/'.$dirName;

        if (!$this->files->isDirectory($dirPath)) {
            return $result;
        }

        if ($columns === ['*'] OR !is_array($columns)) {
            $columns = null;
        }
        else {
            $columns = array_flip($columns);
        }

        $iterator = $this->finder->create()
                                 ->files()
                                 ->ignoreVCS(TRUE)
                                 ->ignoreDotFiles(TRUE)
                                 ->depth('<= 1');  // Support only a single level of subdirectories

        $iterator->filter(function (\SplFileInfo $file) use ($extensions, $fileMatch) {
            // Filter by extension
            $fileExt = $file->getExtension();
            if (!is_null($extensions) AND !in_array($fileExt, $extensions))
                return FALSE;

            // Filter by file name match
            if (!is_null($fileMatch) AND !fnmatch($file->getBasename(), $fileMatch))
                return FALSE;
        });

        $files = iterator_to_array($iterator->in($dirPath), FALSE);

        foreach ($files as $file) {
            $item = [];

            $path = $file->getPathName();

            $item['fileName'] = $file->getRelativePathName();

            if (!$columns OR array_key_exists('mTime', $columns)) {
                $item['mTime'] = $this->files->lastModified($path);
            }

            if (!$columns OR array_key_exists('content', $columns)) {
                $item['content'] = $this->files->get($path);
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Creates a new template.
     *
     * @param  string $dirName
     * @param  string $fileName
     * @param $extension
     * @param  string $content
     *
     * @return bool
     */
    public function insert($dirName, $fileName, $extension, $content)
    {
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if ($this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        try {
            return $this->files->put($path, $content);
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * Updates an existing template.
     *
     * @param  string $dirName
     * @param  string $fileName
     * @param string $extension
     * @param  string $content
     * @param string $oldFileName
     * @param string $oldExtension
     *
     * @return int
     */
    public function update($dirName, $fileName, $extension, $content, $oldFileName = null, $oldExtension = null)
    {
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        /*
         * The same file is safe to rename when the case is changed
         * eg: FooBar -> foobar
         */
        $iFileChanged = ($oldFileName !== null AND strcasecmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null AND strcasecmp($oldExtension, $extension) !== 0);

        if ($iFileChanged AND $this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        /*
         * File to be renamed, as delete and recreate
         */
        $fileChanged = ($oldFileName !== null AND strcmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null AND strcmp($oldExtension, $extension) !== 0);

        if ($fileChanged) {
            $this->delete($dirName, $oldFileName, $oldExtension);
        }

        try {
            return $this->files->put($path, $content);
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * Run a delete statement against the source.
     *
     * @param  string $dirName
     * @param  string $fileName
     * @param  string $extension
     *
     * @return int
     */
    public function delete($dirName, $fileName, $extension)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        try {
            return $this->files->delete($path);
        }
        catch (Exception $ex) {
            throw (new DeleteFileException)->setInvalidPath($path);
        }
    }

    /**
     * Run a delete statement against the source.
     *
     * @param  string $dirName
     * @param  string $fileName
     * @param  string $extension
     *
     * @return int
     */
    public function lastModified($dirName, $fileName, $extension)
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->lastModified($path);
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Ensure the requested file can be created in the requested directory.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return void
     */
    protected function validateDirectoryForSave($dirName, $fileName, $extension)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);
        $dirPath = $this->basePath.'/'.$dirName;

        // Create base directory
        if (
            (!$this->files->exists($dirPath) || !$this->files->isDirectory($dirPath)) AND
            !$this->files->makeDirectory($dirPath, 0777, TRUE, TRUE)
        ) {
            throw (new CreateDirectoryException)->setInvalidPath($dirPath);
        }

        // Create base file directory
        if (strpos($fileName, '/') !== FALSE) {
            $fileDirPath = dirname($path);

            if (
                !$this->files->isDirectory($fileDirPath) AND
                !$this->files->makeDirectory($fileDirPath, 0777, TRUE, TRUE)
            ) {
                throw (new CreateDirectoryException)->setInvalidPath($fileDirPath);
            }
        }
    }

    /**
     * Helper to make file path.
     *
     * @param $dirName
     * @param $fileName
     * @param $extension
     *
     * @return string
     */
    protected function makeFilePath($dirName, $fileName, $extension)
    {
        return $this->basePath.'/'.$dirName.'/'.$fileName.'.'.$extension;
    }

    /**
     * Generate a cache key unique to this source.
     *
     * @param string $name
     *
     * @return string
     */
    public function makeCacheKey($name = '')
    {
        return crc32($this->basePath.$name);
    }

    /**
     * Returns the base path for this source.
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Generate a paths cache key unique to this source
     *
     * @return string
     */
    public function getPathsCacheKey()
    {
        return 'pagic-source-file-'.$this->basePath;
    }

    /**
     * Get all available paths within this source
     *
     * @return array $paths
     */
    public function getAvailablePaths()
    {
        $iterator = $this->finder->create();
        $iterator->files();
        $iterator->ignoreVCS(TRUE);
        $iterator->ignoreDotFiles(TRUE);
        $iterator->exclude('node_modules');
        $iterator->in($this->basePath);

        return collect($iterator)->map(function (\SplFileInfo $fileInfo) {
            return $fileInfo->getRelativePathName();
        })->values()->all();
    }
}
