<?php

namespace Igniter\Flame\Scaffold\Console;

use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateController extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new controller.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'controller/controller.stub' => 'controllers/{{studly_name}}.php',
        'controller/index.stub' => 'views/{{lower_name}}/index.php',
        'controller/create.stub' => 'views/{{lower_name}}/create.php',
        'controller/edit.stub' => 'views/{{lower_name}}/edit.php',
        'controller/preview.stub' => 'views/{{lower_name}}/preview.php',
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
        $controller = $this->argument('controller');

        $this->vars = [
            'extension' => $extension,
            'lower_extension' => strtolower($extension),
            'title_extension' => title_case($extension),
            'studly_extension' => studly_case($extension),

            'author' => $author,
            'lower_author' => strtolower($author),
            'title_author' => title_case($author),
            'studly_author' => studly_case($author),

            'name' => $controller,
            'lower_name' => strtolower($controller),
            'title_name' => title_case($controller),
            'studly_name' => studly_case($controller),
            'singular_name' => str_singular($controller),
            'studly_singular_name' => studly_case(str_singular($controller)),
            'snake_singular_name' => snake_case(str_singular($controller)),
            'plural_name' => str_plural($controller),
            'studly_plural_name' => studly_case(str_plural($controller)),
            'snake_plural_name' => snake_case(str_plural($controller)),
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
            ['controller', InputArgument::REQUIRED, 'The name of the model. Eg: Blocks'],
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