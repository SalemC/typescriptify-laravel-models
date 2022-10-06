<?php

namespace SalemC\TypeScriptifyLaravelModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Exception;
use stdClass;

class TypeScriptifyModel {
    /**
     * The fully qualified model name.
     *
     * @var string|null
     */
    private static string|null $fullyQualifiedModelName = null;

    /**
     * The supported database connections.
     *
     * @var array
     */
    private const SUPPORTED_DATABASE_CONNECTIONS = [
        'mysql',
    ];

    /**
     * Check if the current database connection type is supported.
     *
     * @return bool
     */
    private static function hasSupportedDatabaseConnection(): bool {
        return collect(self::SUPPORTED_DATABASE_CONNECTIONS)->contains(DB::getDefaultConnection());
    }

    /**
     * Check if the model passed to this command is a valid model.
     *
     * @return bool
     */
    private static function hasValidModel(): bool {
        $className = self::$fullyQualifiedModelName;

        if (is_null($className)) return false;
        if (!class_exists($className)) return false;
        if (!is_subclass_of($className, Model::class)) return false;

        return true;
    }

    /**
     * Get the table name for the supplied model.
     *
     * @return string
     */
    private static function getTableName(): string {
        return (new (self::$fullyQualifiedModelName))->getTable();
    }

    /**
     * Get the mapped TypeScript type of a type.
     *
     * @param stdClass $columnSchema
     *
     * @return string
     */
    private static function getTypeScriptType(stdClass $columnSchema): string {
        $columnType = Str::of($columnSchema->Type);

        // @todo sets
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
            $columnType->startsWith('enum') => $columnType->after('enum(')->before(')')->replace(',', '|'),

            default => 'unknown',
        };

        if ($mappedType === 'unknown') return $mappedType;

        if ($columnSchema->Null === 'YES') $mappedType .= '|null';

        return $mappedType;
    }

    /**
     * Generate the interface.
     *
     * @return string
     */
    private static function generateInterface(): string {
        $tableColumns = collect(DB::select(DB::raw('SHOW COLUMNS FROM ' . self::getTableName())));

        $str = 'interface ' . (Str::of(self::$fullyQualifiedModelName)->afterLast('\\')) . " {\n";

        $tableColumns->each(function ($column) use (&$str) {
            $str .= ('    ' . $column->Field . ': ' . self::getTypeScriptType($column) . ";\n");
        });

        $str .= "}\n";

        return $str;
    }

    /**
     * Reset this class.
     *
     * @return void
     */
    private static function reset(): void {
        self::$fullyQualifiedModelName = null;
    }

    /**
     * Generate the TypeScript interface.
     *
     * @return string
     */
    public static function generate(string $fullyQualifiedModelName): string {
        self::$fullyQualifiedModelName = $fullyQualifiedModelName;

        if (!self::hasValidModel()) {
            throw new Exception('That\'s not a valid model!');
        }

        if (!self::hasSupportedDatabaseConnection()) {
            throw new Exception('Your database connection is currently unsupported! The following database connections are supported: ' . collect(self::SUPPORTED_DATABASE_CONNECTIONS)->join(', '));
        }

        $interface = self::generateInterface();

        self::reset();

        return $interface;
    }
}
