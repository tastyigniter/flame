<?php

namespace Igniter\Flame\Pagic;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\View\ViewFinderInterface;

class Factory
{
    /**
     * Register a view extension with the finder.
     *
     * @var array
     */
    protected $extensions = ['php', 'htm'];

    /**
     * Create a new view factory instance.
     *
     * @param \Igniter\Flame\Pagic\PagicFrontMatter $frontmatter
     * @param  \Illuminate\View\ViewFinderInterface $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function __construct($frontmatter, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->frontmatter = $frontmatter;
        $this->finder = $finder;
        $this->files = $this->finder->getFileSystem();
        $this->events = $events;
    }

    public function allFiles($directory)
    {
        // get cached files

        foreach ($this->finder->getPaths() as $path) {
            if ($this->files->isDirectory($full = "{$path}/{$directory}"))
                return $this->makeFileModels($full);
        }

        // return cached files
        return [];
    }

    public function loadCached($theme, $fileName)
    {
        $page = Model::on($theme->getPath().'/'.$fileName);

        return $page->parse();
//        return $this->finder->find(rtrim($theme->getName().'/'.$fileName, '.php'));
//        return static::on($theme)
//                     ->remember(Config::get('cms.parsedPageCacheTTL', 1440))
//                     ->find($fileName);
    }

    protected function makeFileModels($directory)
    {
        $models = [];
        foreach ($this->files->allFiles($directory) as $file) {
            // make sure file has .php or .htm extension,
            if (!in_array($file->getExtension(), $this->extensions))
                continue;

            // make sure file is a page file, i.e not within _folder
            if (starts_with($file->getRelativePath(), '_'))
                continue;

            if (in_array($file->getFilename(), ['theme.json', 'theme_config.php']))
                continue;

            $page = Model::on($file);

            // check if file has been modified since last cached
            if (!$page->wasModified())
                continue;

            // if cache doesn't exists or file has been modified
            // parse the file content and cache the result
            $models[] = $page->parse();
        }

        return $models;
    }
}