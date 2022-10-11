<?php

namespace SalemC\TypeScriptifyLaravelModels;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Stringable;
use Illuminate\Support\Str;

use ReflectionMethod;
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
     * The instantiated model.
     *
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    private static Model|null $model = null;

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
        return (self::$model)->getTable();
    }

    /**
     * Check if the `$columnField` attribute exists in the protected $dates array.
     *
     * @param string $columnField
     *
     * @return bool
     */
    private static function isAttributeCastedInDates(string $columnField): bool {
        return in_array($columnField, (self::$model)->getDates(), false);
    }

    /**
     * Check if the `$columnField` attribute has a native type cast.
     *
     * @param string $columnField
     *
     * @return bool
     */
    private static function isAttributeNativelyCasted(string $columnField): bool {
        $model = self::$model;

        // If $columnField exists in the $model->casts array.
        if ($model->hasCast($columnField)) return true;

        // If $columnField exists in the $model->dates array.
        if (self::isAttributeCastedInDates($columnField)) return true;

        return false;
    }

    /**
     * Map a native casted attribute (casted via $casts/$dates) to a TypeScript type.
     *
     * @param string $columnField
     *
     * @return string
     */
    private static function mapNativeCastToTypeScriptType(string $columnField): string {
        // If the attribute is casted to a date via $model->dates, it won't exist in the underlying $model->casts array.
        // That means if we called `getCastType` with it, it would throw an error because the key wouldn't exist.
        // We know dates get serialized to strings, so we can avoid that by short circuiting here.
        if (self::isAttributeCastedInDates($columnField)) return 'string';

        // The `getCastType` method is protected, therefore we need to use reflection to call it.
        $getCastType = new ReflectionMethod(self::$model, 'getCastType');

        $castType = Str::of($getCastType->invoke(self::$model, $columnField));

        return match (true) {
            $castType->is('int') => 'number',
            $castType->is('real') => 'number',
            $castType->is('date') => 'string',
            $castType->is('float') => 'number',
            $castType->is('bool') => 'boolean',
            $castType->is('double') => 'number',
            $castType->is('string') => 'string',
            $castType->is('integer') => 'number',
            $castType->is('datetime') => 'string',
            $castType->is('boolean') => 'boolean',
            $castType->is('array') => 'unknown[]',
            $castType->is('encrypted') => 'string',
            $castType->is('timestamp') => 'string',
            $castType->is('immutable_date') => 'string',
            $castType->is(AsStringable::class) => 'string',
            $castType->is('immutable_datetime') => 'string',
            $castType->is('object') => 'Record<string, unknown>',

            $castType->startsWith('decimal') => 'number',

            default => 'unknown',
        };
    }

    /**
     * Map a database column type to a TypeScript type.
     *
     * @param \Illuminate\Support\Stringable $columnType
     *
     * @return string
     */
    private static function mapDatabaseTypeToTypeScriptType(Stringable $columnType): string {
        return match (true) {
            $columnType->startsWith('bit') => 'number',
            $columnType->startsWith('int') => 'number',
            $columnType->startsWith('dec') => 'number',
            $columnType->startsWith('set') => 'string', // @todo generate the exact TypeScript type this can be.
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

        if (self::isAttributeNativelyCasted($columnSchema->Field)) {
            $mappedType = self::mapNativeCastToTypeScriptType($columnSchema->Field);
        } else {
            $mappedType = self::mapDatabaseTypeToTypeScriptType($columnType);
        }

        // We can't do much with an unknown type.
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
     * Initialize this class.
     *
     * @param string $fullyQualifiedModelName
     *
     * @return void
     */
    private static function initialize(string $fullyQualifiedModelName): void {
        self::$fullyQualifiedModelName = $fullyQualifiedModelName;
        self::$model = new (self::$fullyQualifiedModelName);
    }

    /**
     * Reset this class.
     *
     * @return void
     */
    private static function reset(): void {
        self::$fullyQualifiedModelName = null;
        self::$model = null;
    }

    /**
     * Generate the TypeScript interface.
     *
     * @param string $fullyQualifiedModelName
     *
     * @return string
     */
    public static function generate(string $fullyQualifiedModelName): string {
        self::initialize($fullyQualifiedModelName);

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
