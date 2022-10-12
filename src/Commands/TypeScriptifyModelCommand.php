<?php

namespace SalemC\TypeScriptifyLaravelModels\Commands;

use Illuminate\Console\Command;

use SalemC\TypeScriptifyLaravelModels\TypeScriptifyModel;

class TypeScriptifyModelCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typescriptify:model {model : The fully qualified class name for the model - e.g. App\Models\User}';

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
        echo TypeScriptifyModel::generate($this->argument('model'));

        return Command::SUCCESS;
    }
}
