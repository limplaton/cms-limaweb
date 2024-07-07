<?php
 

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Commands\GeneratorCommand;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ModuleMailableTemplateMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument being used.
     *
     * @var string
     */
    protected $argumentName = 'template';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-mailable-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new mailable template for the specified module.';

    /**
     * Get controller name.
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $factoryPath = GenerateConfigReader::read('mailable-template');

        return $path.$factoryPath->getPath().'/'.$this->getFileName();
    }

    /**
     * Get the template contents.
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
            'LOWER_NAME' => $module->getLowerName(),
        ]))->render();
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['template', InputArgument::REQUIRED, 'The name of the template.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the filename of the template being created.
     */
    private function getFileName(): string
    {
        return Str::studly($this->argument('template')).'.php';
    }

    /**
     * Get the default namespace for the template.x
     */
    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.mailable-template.namespace') ?: $module->config('paths.generator.mailable-template.path', 'App/Mail');
    }

    /**
     * Get the stub file name based on the options
     */
    protected function getStubName(): string
    {
        return '/mailable-template.stub';
    }
}
