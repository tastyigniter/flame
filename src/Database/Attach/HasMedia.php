<?php

namespace Igniter\Flame\Database\Attach;

use Event;
use Exception;
use Igniter\Flame\Database\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection as BaseCollection;

trait HasMedia
{
    /**
     * @var array
     */
    protected $unAttachedMediaItems = [];

    public static function bootHasMedia()
    {
        static::deleting(function (Model $model) {
            $model->handleHasMediaDeletion();
        });
    }

    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'attachment')->sorted();
    }

    /**
     * Query scope to detect the presence of one or more attached media for a given tag.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|string[] $tags
     * @return void
     */
    public function scopeWhereHasMedia(Builder $query, $tags)
    {
        if (!is_array($tags))
            $tags = [$tags];

        $query->whereHas('media', function (Builder $q) use ($tags) {
            $q->whereIn('tag', (array)$tags);
        });
    }

    public function newMediaInstance()
    {
        $newMedia = new Media;
        $newMedia->setRelation('attachment', $this);

        return $newMedia;
    }

    public function getAttribute($key)
    {
        if (
            !array_key_exists($key, $mediable = $this->mediable())
            OR $this->hasGetMutator($key)
        ) return parent::getAttribute($key);

        $mediableConfig = array_get($mediable, $key, []);
        if (array_get($mediableConfig, 'multiple', FALSE))
            return $this->getMedia($key);

        return $this->getFirstMedia($key);
    }

    public function setAttribute($key, $value)
    {
        if (
            !array_key_exists($key, $mediable = $this->mediable())
            OR $this->hasSetMutator($key)
        ) return parent::setAttribute($key, $value);

        // Do nothing
    }

    public function getDefaultTagName()
    {
        return ($mediable = $this->mediable()) ? key($mediable) : 'default';
    }

    //
    // Media handling
    //

    /**
     * Get the thumbnail of the first media item of a default tag.
     *
     * @param array $options
     * @param string $tag
     * @return string
     */
    public function getThumb($options = [], $tag = null)
    {
        return $this->getFirstMedia($tag)->getThumb($options);
    }

    /**
     * Get a collection of media attachments by its tag.
     *
     * @param string $tag
     * @param array|callable $filters
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMedia($tag = null, $filters = [])
    {
        $collection = $this->loadMedia($tag ?? $this->getDefaultTagName());

        if (is_array($filters))
            $filters = $this->buildMediaPropertiesFilter($filters);

        return $collection->filter($filters);
    }

    /**
     * Get the first media item of a media tag.
     *
     * @param string $tag
     * @param array $filters
     *
     * @return Media|null
     */
    public function getFirstMedia($tag = null, array $filters = [])
    {
        return $this->getMedia($tag, $filters)->first();
    }

    public function findMedia($mediaId)
    {
        if (!$media = $this->media->find($mediaId)) {
            throw new Exception(sprintf(
                "Media with id '%s' cannot be deleted because it does not exist or does not belong to model %s with id %s",
                $mediaId, get_class($this), $this->getKey()
            ));
        }

        return $media;
    }

    /**
     * Lazy eager load attached media relationships.
     *
     * @param $tag
     * @return \Illuminate\Support\Collection
     */
    public function loadMedia($tag)
    {
        $collection = $this->exists
            ? $this->media
            : collect($this->unAttachedMediaItems)->pluck('media');

        return collect($collection)
            ->filter(function (Media $mediaItem) use ($tag) {
                return $tag === '*' OR $mediaItem->tag === $tag;
            })
            ->sortBy('priority')->values();
    }

    /**
     * Determine if the specified tag contains media.
     * @param string $tag
     * @return bool
     */
    public function hasMedia($tag = null)
    {
        return count($this->getMedia($tag)) > 0;
    }

    /**
     * Replace the existing media collection for the specified tag(s).
     *
     * @param mixed $media
     * @param string $tag
     *
     * @return \Illuminate\Support\Collection
     */
    public function syncMedia($media, $tag = null)
    {
        $this->deleteMediaExcept($media, $tag);

        $tag = $tag ?? $this->getDefaultTagName();
        $newMediaIds = $this->parseIds($media);

        return collect($newMediaIds)
            ->map(function (array $newMedia) use ($tag) {
                $foundMedia = Media::findOrFail($newMedia['id']);

                if ($tag !== '*' AND $foundMedia->tag !== $tag)
                    throw new Exception("Media id {$foundMedia->getKey()} is not part of collection '{$tag}''");

                $foundMedia->fill($newMedia);
                $foundMedia->save();

                return $foundMedia;
            });
    }

    /**
     * Detach a media item from the model.
     * @param mixed $mediaId
     * @return void
     */
    public function deleteMedia($mediaId)
    {
        if ($mediaId instanceof Media) {
            $mediaId = $mediaId->id;
        }

        $media = $this->findMedia($mediaId);

        $media->delete();
    }

    /**
     * Delete all media with the given tag except some.
     *
     * @param mixed $media
     * @param string $tag
     */
    protected function deleteMediaExcept($media, $tag = null)
    {
        $newMediaIds = $this->parseIds($media);
        $this->getMedia($tag)
             ->reject(function (Media $tagMedia) use ($newMediaIds) {
                 return in_array($tagMedia->getKey(), array_column($newMediaIds, 'id'));
             })
            ->each->delete();
    }

    /**
     * Remove all media with the given tag.
     *
     * @param string $tag
     * @return void
     */
    public function clearMediaTag($tag = null)
    {
        $this->getMedia($tag)->each->delete();

        Event::fire('attach.mediaTagCleared', $this, $tag);

        if ($this->mediaWasLoaded())
            unset($this->media);
    }

    public function prepareUnattachedMedia(Media $media, MediaAdder $mediaAdder)
    {
        $this->unAttachedMediaItems[] = compact('media', 'mediaAdder');
    }

    public function processUnattachedMedia(callable $callable)
    {
        foreach ($this->unAttachedMediaItems as $item) {
            $callable($item['media'], $item['mediaAdder']);
        }

        $this->unAttachedMediaItems = [];
    }

    public function mediable()
    {
        $result = [];
        $mediable = $this->mediable ?? [];
        foreach ($mediable as $attribute => $config) {
            if (is_numeric($attribute)) {
                $attribute = $config;
                $config = [];
            }

            $result[$attribute] = $config;
        }

        return $result;
    }

    protected function mediaWasLoaded()
    {
        return $this->relationLoaded('media');
    }

    /**
     * Delete media relationships when the model is deleted. Ignore on soft deletes.
     * @return void
     */
    protected function handleHasMediaDeletion()
    {
        // only cascade soft deletes when configured
        if (static::hasGlobalScope(SoftDeletingScope::class) AND !$this->forceDeleting)
            return;

        $this->media()->get()->each->delete();
    }

    /**
     * Convert the given array to a filter closure.
     * @param $filters
     * @return \Closure
     */
    protected function buildMediaPropertiesFilter(array $filters)
    {
        return function (Media $media) use ($filters) {
            foreach ($filters as $property => $value) {
                if (!array_has($media->custom_properties, $property))
                    return FALSE;

                if (array_get($media->custom_properties, $property) !== $value)
                    return FALSE;
            }

            return TRUE;
        };
    }

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param mixed $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return [$value->{$this->relatedKey}];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->relatedKey)->all();
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();
        }

        return (array)$value;
    }
}