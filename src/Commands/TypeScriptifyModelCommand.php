<?php

namespace SalemC\TypeScriptifyLaravelModels\Commands;

use SalemC\TypeScriptifyLaravelModels\TypeScriptifyModel;

use Illuminate\Console\Command;

class TypeScriptifyModelCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typescriptify:model
                                                {model : The fully qualified class name for the model - e.g. App\Models\User}
                                                {--includeHidden : Include the protected $hidden properties}
                                                {--includeRelations=true : Map foreign key columns to interface definitions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a model to it\'s TypeScript definition.';

    /**
     * Cast the `$name` option to a boolean.
     *
     * @param string $name
     *
     * @return bool
     */
    private function boolOption(string $name): bool {
        return filter_var($this->option($name), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        echo (new TypeScriptifyModel($this->argument('model')))
            ->includeHidden($this->boolOption('includeHidden'))
            ->includeRelations($this->boolOption('includeRelations'))
            ->generate();

        return Command::SUCCESS;
    }
}
