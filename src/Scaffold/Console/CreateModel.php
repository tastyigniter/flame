<?php

namespace Igniter\Flame\Scaffold\Console;

use Carbon\Carbon;
use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateModel extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new model.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'model/model.stub' => 'models/{{studly_name}}.php',
        'model/create_table.stub' => 'database/migrations/{{timestamp}}_create_{{snake_plural_name}}_table.php',
        'model/config.stub' => 'models/config/{{lower_name}}.php',
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
        $model = $this->argument('model');

        $this->vars = [
            'timestamp' => Carbon::now()->format('Y_m_d_Hmi'),
            'extension' => $extension,
            'lower_extension' => strtolower($extension),
            'title_extension' => title_case($extension),
            'studly_extension' => studly_case($extension),

            'author' => $author,
            'lower_author' => strtolower($author),
            'title_author' => title_case($author),
            'studly_author' => studly_case($author),

            'name' => $model,
            'lower_name' => strtolower($model),
            'title_name' => title_case($model),
            'studly_name' => studly_case($model),
            'plural_name' => str_plural($model),
            'studly_plural_name' => studly_case(str_plural($model)),
            'snake_plural_name' => snake_case(str_plural($model)),
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
            ['model', InputArgument::REQUIRED, 'The name of the model. Eg: Block'],
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
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.']
        ];
    }
}