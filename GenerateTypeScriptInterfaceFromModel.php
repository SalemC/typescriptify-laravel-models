<?php

namespace App\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use stdClass;

class GenerateTypeScriptInterfaceFromModel extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typescript:generate-interface-from-model {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a TypeScript interface from a model';

    /**
     * Check if the current database connection type is supported.
     *
     * @return bool
     */
    private function hasSupportedDatabaseConnection(): bool {
        return collect(['mysql'])->contains(DB::getDefaultConnection());
    }

    /**
     * Check if the model passed to this command is a valid model.
     *
     * @return bool
     */
    private function hasValidModel(): bool {
        $className = $this->getFullyQualifiedModelName();

        if (!class_exists($className)) return false;
        if (!is_subclass_of($className, Model::class)) return false;

        return true;
    }

    /**
     * Get the fully qualified model name passed as an argument to this command.
     *
     * @return string
     */
    private function getFullyQualifiedModelName(): string {
        return $this->argument('model');
    }

    /**
     * Get the table name for the supplied model.
     *
     * @return string
     */
    private function getTableName(): string {
        return (new ($this->getFullyQualifiedModelName()))->getTable();
    }

    /**
     * Get the mapped TypeScript type of a type.
     *
     * @param stdClass $columnSchema
     *
     * @return string
     */
    private function getTypeScriptType(stdClass $columnSchema): string {
        $columnType = Str::of($columnSchema->Type);

        // @todo sets
        // @todo enums
        $mappedType = match (true) {
            $columnType->startsWith('bit') => 'number',
            $columnType->startsWith('int') => 'number',
            $columnType->startsWith('dec') => 'number',
            $columnType->startsWith('char') => 'string',
            $columnType->startsWith('text') => 'string',
            $columnType->startsWith('blob') => 'string',
            $columnType->startsWith('date') => 'string',
            $columnType->startsWith('time') => 'string',
            $columnType->startsWith('year') => 'string',
            $columnType->startsWith('bool') => 'boolean',
            $columnType->startsWith('float') => 'number',
            $columnType->startsWith('bigint') => 'number',
            $columnType->startsWith('double') => 'number',
            $columnType->startsWith('binary') => 'string',
            $columnType->startsWith('bigint') => 'number',
            $columnType->startsWith('decimal') => 'number',
            $columnType->startsWith('integer') => 'number',
            $columnType->startsWith('varchar') => 'string',
            $columnType->startsWith('boolean') => 'boolean',
            $columnType->startsWith('tinyblob') => 'string',
            $columnType->startsWith('tinytext') => 'string',
            $columnType->startsWith('longtext') => 'string',
            $columnType->startsWith('longblob') => 'string',
            $columnType->startsWith('datetime') => 'string',
            $columnType->startsWith('smallint') => 'boolean',
            $columnType->startsWith('varbinary') => 'string',
            $columnType->startsWith('mediumint') => 'number',
            $columnType->startsWith('timestamp') => 'string',
            $columnType->startsWith('mediumtext') => 'string',
            $columnType->startsWith('mediumblob') => 'string',

            default => 'unknown',
        };

        if ($mappedType === 'unknown') return $mappedType;

        if ($columnSchema->Null === 'YES') $mappedType .= '|null';

        return $mappedType;
    }

    /**
     * Print the generated interface.
     *
     * @return void
     */
    private function printGeneratedInterface(): void {
        $tableColumns = collect(DB::select(DB::raw('SHOW COLUMNS FROM ' . $this->getTableName())));

        $this->info('interface ' . (Str::of($this->getFullyQualifiedModelName())->afterLast('\\')) . ' {');

        $tableColumns->each(fn ($column) => $this->info('    ' . $column->Field . ': ' . $this->getTypeScriptType($column) . ';'));

        $this->info('}');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        if (!$this->hasValidModel()) {
            $this->error('That\'s not a valid model!');

            return Command::FAILURE;
        }

        if (!$this->hasSupportedDatabaseConnection()) {
            $this->error('We currently only support a MySQL connection!');

            return Command::FAILURE;
        }

        $this->printGeneratedInterface();

        return Command::SUCCESS;
    }
}
