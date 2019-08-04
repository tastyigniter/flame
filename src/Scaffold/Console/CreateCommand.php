<?php namespace Igniter\Flame\Scaffold\Console;

use Igniter\Flame\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new console command.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Command';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'command/command.stub' => 'console/{{studly_name}}.php',
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
        $command = $this->argument('command-name');

        $this->vars = [
            'extension' => $extension,
            'lower_extension' => strtolower($extension),
            'title_extension' => title_case($extension),
            'studly_extension' => studly_case($extension),

            'author' => $author,
            'lower_author' => strtolower($author),
            'title_author' => title_case($author),
            'studly_author' => studly_case($author),

            'name' => $command,
            'lower_name' => strtolower($command),
            'title_name' => title_case($command),
            'studly_name' => studly_case($command),
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
            ['extension', InputArgument::REQUIRED, 'The name of the extension. Eg: IgniterLab.Demo'],
            ['command-name', InputArgument::REQUIRED, 'The name of the command. Eg: SendEmails'],
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
