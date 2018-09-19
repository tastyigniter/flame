<?php

namespace Igniter\Flame\Scaffold;

use Igniter\Flame\Support\StringParser;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

abstract class GeneratorCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type;

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [];

    /**
     * An array of variables to use in stubs.
     *
     * @var array
     */
    protected $vars = [];

    protected $destinationPath;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    abstract protected function prepareVars();

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if (!$this->confirmToProceed())
            return;

        $this->prepareVars();

        $this->buildStubs();

        $this->info($this->type.' created successfully.');
    }

    public function buildStubs()
    {
        foreach ($this->stubs as $stub => $class) {
            $this->buildStub($stub, $class);
        }
    }

    public function buildStub($stubName, $className)
    {
        if (
            !isset($this->stubs[$stubName])
            OR $this->stubs[$stubName] != $className
        ) {
            return;
        }

        $stubFile = $this->getStubPath($stubName);
        $destinationFile = $this->parseString($this->getDestinationPath($className));
        $stubContent = $this->files->get($stubFile);

        // Make sure this file does not already exist
        if ($this->files->exists($destinationFile) AND !$this->option('force')) {
            $this->error($this->type.' already exists! '.$destinationFile);

            return;
        }

        $this->makeDirectory($destinationFile);

        $this->files->put($destinationFile, $this->parseString($stubContent));
    }

    protected function getExtensionInput()
    {
        $code = $this->argument('extension');

        if (count($array = explode('.', $code)) != 2) {
            return;
        }

        return $array;
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     *
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, TRUE, TRUE);
        }
    }

    protected function getStubPath($stubName)
    {
        $className = get_class($this);
        $class = new ReflectionClass($className);

        return dirname($class->getFileName()).'/stubs/'.$stubName;
    }

    protected function getDestinationPath($className)
    {
        $code = $this->argument('extension');
        $destinationPath = str_replace('.', '/', strtolower($code));

        return extension_path($destinationPath.'/'.$className);
    }

    protected function parseString($stubContent)
    {
        return (new StringParser('{{', '}}'))->parse($stubContent, $this->vars);
    }
}