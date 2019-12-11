<?php namespace Igniter\Flame\Pagic\Processors;

use Igniter\Flame\Pagic\Finder;
use Igniter\Flame\Pagic\Parsers\FileParser;

class Processor
{
    /**
     * Process the results of a singular "select" query.
     *
     * @param  \Igniter\Flame\Pagic\Finder $finder
     * @param  array $result
     *
     * @return array
     */
    public function processSelect(Finder $finder, $result)
    {
        if ($result === null) {
            return null;
        }

        $fileName = array_get($result, 'fileName');

        return [$fileName => $this->parseTemplateContent($result, $fileName)];
    }

    /**
     * Process the results of a "select" query.
     *
     * @param  \Igniter\Flame\Pagic\Finder $finder
     * @param  array $results
     *
     * @return array
     */
    public function processSelectAll(Finder $finder, $results)
    {
        if (!count($results)) {
            return [];
        }

        $items = [];

        foreach ($results as $result) {
            $fileName = array_get($result, 'fileName');
            $items[$fileName] = $this->parseTemplateContent($result, $fileName);
        }

        return $items;
    }

    /**
     * Helper to break down template content in to a useful array.
     *
     * @param $result
     * @param $fileName
     *
     * @return array
     */
    protected function parseTemplateContent($result, $fileName)
    {
        $content = array_get($result, 'content');

        $processed = FileParser::parse($content);

        $content = [
            'fileName' => $fileName,
            'mTime' => array_get($result, 'mTime'),
            'content' => $content,
            'markup' => $processed['markup'],
            'code' => $processed['code'],
        ];

        if (!empty($processed['settings']))
            $content = $content + $processed['settings'];

        return $content;
    }

    /**
     * Process the data in to an insert action.
     *
     * @param  \Igniter\Flame\Pagic\Finder $finder
     * @param  array $data
     *
     * @return string
     */
    public function processInsert(Finder $finder, $data)
    {
        return FileParser::render($data);
    }

    /**
     * Process the data in to an update action.
     *
     * @param  \Igniter\Flame\Pagic\Finder $finder
     * @param  array $data
     *
     * @return string
     */
    public function processUpdate(Finder $finder, $data)
    {
        $existingData = $finder->getModel()->attributesToArray();

        return FileParser::render($data + $existingData);
    }
}
