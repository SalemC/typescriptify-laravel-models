<?php

namespace SalemC\TypeScriptifyLaravelModels\Providers;

use Illuminate\Support\ServiceProvider;

use SalemC\TypeScriptifyLaravelModels\Commands\TypeScriptifyModelCommand;

class TypeScriptifyModelsServiceProvider extends ServiceProvider {
    /**
     * Register all commands.
     *
     * @return void
     */
    private function registerCommands(): void {
        if (!$this->app->runningInConsole()) return;

        $this->commands([
            TypeScriptifyModelCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void {
        $this->registerCommands();
    }
}
