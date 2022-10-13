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
                                                {--includeHidden : Include the protected $hidden properties}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a model to it\'s TypeScript definition.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        echo (new TypeScriptifyModel($this->argument('model')))
            ->includeHidden($this->option('includeHidden'))
            ->generate();

        return Command::SUCCESS;
    }
}
