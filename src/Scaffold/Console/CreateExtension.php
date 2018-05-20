<?php

namespace Igniter\Flame\Scaffold\Console;

use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateExtension extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:extension';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new extension.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Extension';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'extension.stub' => 'Extension.php',
    ];

    protected function prepareVars()
    {
        if (!$code = $this->getExtensionInput()) {
            $this->error('Invalid extension name, Example name: AuthorName.ExtensionName');

            return;
        }

        list($author, $name) = $code;

        $this->vars = [
            'name'        => $name,
            'lower_name'  => strtolower($name),
            'title_name'  => title_case($name),
            'studly_name' => studly_case($name),

            'author'        => $author,
            'lower_author'  => strtolower($author),
            'title_author'  => title_case($author),
            'studly_author' => studly_case($author),
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