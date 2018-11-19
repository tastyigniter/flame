<?php

namespace Igniter\Flame\Database\Attach;

use Event;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Filesystem\Filesystem;
use Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaAdder
{
    /**
     * @var \Igniter\Flame\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Igniter\Flame\Database\Attach\Media
     */
    protected $media;

    /**
     * @var Model
     */
    protected $performedOn;

    /**
     * @var string
     */
    protected $tag = 'default';

    /**
     * @var string
     */
    protected $diskName;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $properties;

    /**
     * @var array
     */
    protected $customProperties = [];

    /** @var array */
    protected $manipulations = [];

    /** @var string */
    protected $pathToFile;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function on(Media $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @param \Igniter\Flame\Database\Model $model
     * @return $this
     */
    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    /**
     * @param string $disk
     *
     * @return $this
     */
    public function useDisk($disk)
    {
        $this->diskName = $disk;

        return $this;
    }

    /**
     * @param string $tag
     * @return \Igniter\Flame\Database\Attach\MediaAdder
     */
    public function useMediaTag($tag = 'default')
    {
        $this->tag = $tag;

        return $this;
    }

    public function fromFile($file)
    {
        $media = $this->media;

        $this->setFile($media, $file);

        $media->name = $media->getUniqueName();
        $media->disk = $this->diskName ?? $media->getDiskName();
        $media->tag = $this->tag ?? $this->performedOn->getDefaultTagName();

        $media->custom_properties = $this->customProperties;

        $this->attachMedia($media);

        return $media;
    }

    protected function setFile(Media $media, $file)
    {
        if ($file instanceof UploadedFile) {
            $media->file_name = $file->getClientOriginalName();
            $media->mime_type = $file->getMimeType();
            $media->size = $file->getClientSize();
            $this->pathToFile = $file->getPath().DIRECTORY_SEPARATOR.$file->getFilename();
        }

        if ($file instanceof SymfonyFile) {
            $media->file_name = $file->getFilename();
            $media->mime_type = $file->getMimeType();
            $media->size = $file->getSize();
            $this->pathToFile = $file->getRealPath();
        }

        return $this->pathToFile;
    }

    /**
     * @param Media $media
     */
    protected function attachMedia(Media $media)
    {
        if ($this->performedOn->exists)
            return $this->processMediaItem($media, $this);

        $this->performedOn->prepareUnattachedMedia($media, $this);

        $class = get_class($this->performedOn);
        $class::created(function (Model $model) {
            $model->processUnattachedMedia(function (Media $media, MediaAdder $mediaAdder) {
                $this->processMediaItem($media, $mediaAdder);
            });
        });
    }

    /**
     * @param Media $media
     * @param $mediaAdder
     * @return bool
     */
    protected function processMediaItem(Media $media, self $mediaAdder)
    {
        $mediaAdder->performedOn->media()->save($media);

        $sourcePath = $mediaAdder->pathToFile;
        $destinationFileName = $media->getDiskPath();

        $fileStream = fopen($sourcePath, 'rb');

        Storage::disk($media->getDiskName())->put($destinationFileName, $fileStream);

        Event::fire('attach.mediaAdded', $media);
    }
}