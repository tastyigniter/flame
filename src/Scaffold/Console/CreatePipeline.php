<?php

namespace Igniter\Flame\Scaffold\Console;

use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreatePipeline extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:pipeline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new pipeline.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Pipeline';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'pipeline.stub' => 'Pipelines/{{studly_name}}.php',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        if (!$code = $this->getExtensionInput()) {
            $this->error('Invalid extension name, Example name: AuthorName.ExtensionName');

            return;
        }

        [$author, $extension] = $code;
        $pipeline = $this->argument('pipeline');

        $this->vars = [
            'extension' => $extension,
            'lower_extension' => strtolower($extension),
            'title_extension' => title_case($extension),
            'studly_extension' => studly_case($extension),

            'author' => $author,
            'lower_author' => strtolower($author),
            'title_author' => title_case($author),
            'studly_author' => studly_case($author),

            'name' => $pipeline,
            'lower_name' => strtolower($pipeline),
            'title_name' => title_case($pipeline),
            'studly_name' => studly_case($pipeline),
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the pipeline to create. Eg: IgniterLab.Demo'],
            ['pipeline', InputArgument::REQUIRED, 'The name of the pipeline. Eg: Block'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
