<?php

namespace Igniter\Flame\Scaffold\Console;

use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateComponent extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new extension component.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Component';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'component/component.stub' => 'components/{{studly_name}}.php',
        'component/default.stub'   => 'components/{{lower_name}}/default.php',
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

        list($author, $extension) = $code;
        $component = $this->argument('component');

        $this->vars = [
            'extension'        => $extension,
            'lower_extension'  => strtolower($extension),
            'title_extension'  => title_case($extension),
            'studly_extension' => studly_case($extension),

            'author'        => $author,
            'lower_author'  => strtolower($author),
            'title_author'  => title_case($author),
            'studly_author' => studly_case($author),

            'name'        => $component,
            'lower_name'  => strtolower($component),
            'title_name'  => title_case($component),
            'studly_name' => studly_case($component),
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
            ['extension', InputArgument::REQUIRED, 'The name of the extension to create. Eg: IgniterLab.Demo'],
            ['component', InputArgument::REQUIRED, 'The name of the component. Eg: Block'],
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