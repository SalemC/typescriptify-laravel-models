<?php

namespace SalemC\TypeScriptifyLaravelModels;

use SalemC\TypeScriptifyLaravelModels\Utilities\ModelCollector\ModelCollector;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Support\Str;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;

use ReflectionMethod;
use Exception;
use stdClass;

final class TypeScriptifyModel {
    /**
     * The supported database connections.
     *
     * @var array
     */
    private const SUPPORTED_DATABASE_CONNECTIONS = [
        'mysql',
    ];

    /**
     * The instantiated model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    private readonly Model $model;

    /**
     * The target model's foreign key constraits.
     *
     * @var \Illuminate\Support\Collection
     */
    private readonly Collection $modelForeignKeyConstraints;

    /**
     * Whether to include the model's $hidden properties.
     *
     * @var bool $includeHidden
     */
    private bool $includeHidden = false;

    /**
     * @param string $fullyQualifiedModelName The fully qualified model class name.
     * @param ?array<string,string> $convertedModelsMap The map of `fully qualified model name => interface name` definitions this class can use instead of generating its own definitions.
     */
    public function __construct(
        private string $fullyQualifiedModelName,
        private ?array &$convertedModelsMap = []
    ) {
        // For consistency in comparisons that happen throughout the lifecycle of this class,
        // we need all fully qualified model names to have a leading \.
        $this->fullyQualifiedModelName = Str::of($fullyQualifiedModelName)->start('\\')->toString();

        if (!$this->hasValidModel()) {
            throw new Exception('That\'s not a valid model!');
        }

        if (!$this->hasSupportedDatabaseConnection()) {
            throw new Exception('Your database connection is currently unsupported! The following database connections are supported: ' . implode(', ', self::SUPPORTED_DATABASE_CONNECTIONS));
        }

        $this->model = new $fullyQualifiedModelName;

        $this->modelForeignKeyConstraints = collect(
            Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys($this->model->getTable())
        );
    }

    /**
     * Check if the current database connection type is supported.
     *
     * @return bool
     */
    private function hasSupportedDatabaseConnection(): bool {
        return collect(self::SUPPORTED_DATABASE_CONNECTIONS)->contains(DB::getDefaultConnection());
    }

    /**
     * Check if the model passed to this command is a valid model.
     *
     * @return bool
     */
    private function hasValidModel(): bool {
        if (is_null($this->fullyQualifiedModelName)) return false;
        if (!class_exists($this->fullyQualifiedModelName)) return false;
        if (!is_subclass_of($this->fullyQualifiedModelName, Model::class)) return false;

        return true;
    }

    /**
     * Check if the `$attribute` attribute exists in the protected $dates array.
     *
     * @param string $attribute
     *
     * @return bool
     */
    private function isAttributeCastedInDates(string $attribute): bool {
        return in_array($attribute, $this->model->getDates(), false);
    }

    /**
     * Check if the `$attribute` attribute has a native type cast.
     *
     * @param string $attribute
     *
     * @return bool
     */
    private function isAttributeNativelyCasted(string $attribute): bool {
        // If $columnField exists in the $model->casts array.
        if ($this->model->hasCast($attribute)) return true;

        // If $columnField exists in the $model->dates array.
        if ($this->isAttributeCastedInDates($attribute)) return true;

        return false;
    }

    /**
     * Is `$attribute` a hidden attribute?
     *
     * @param string $attribute
     *
     * @return bool
     */
    private function isAttributeHidden(string $attribute): bool {
        return in_array($attribute, $this->model->getHidden());
    }

    /**
     * Map a native casted attribute (casted via $casts/$dates) to a TypeScript type.
     *
     * @param string $attribute
     *
     * @return string
     */
    private function mapNativeCastToTypeScriptType(string $attribute): string {
        // If the attribute is casted to a date via $model->dates, it won't exist in the underlying $model->casts array.
        // That means if we called `getCastType` with it, it would throw an error because the key wouldn't exist.
        // We know dates get serialized to strings, so we can avoid that by short circuiting here.
        if ($this->isAttributeCastedInDates($attribute)) return 'string';

        // The `getCastType` method is protected, therefore we need to use reflection to call it.
        $getCastType = new ReflectionMethod($this->model, 'getCastType');

        $castType = Str::of($getCastType->invoke($this->model, $attribute));

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
     * @param string $columnType
     *
     * @return string
     */
    private function mapDatabaseTypeToTypeScriptType(string $columnType): string {
        $columnType = Str::of($columnType);

        return match (true) {
            $columnType->startsWith('bit') => 'number',
            $columnType->startsWith('int') => 'number',
            $columnType->startsWith('dec') => 'number',
            $columnType->startsWith('set') => 'string',
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
     * Get a foreign key constraint for `$attribute`.
     *
     * @param string $attribute
     *
     * @return ?\Doctrine\DBAL\Schema\ForeignKeyConstraint
     */
    private function getForeignKeyConstraintForAttribute(string $attribute): ?ForeignKeyConstraint {
        return $this
            ->modelForeignKeyConstraints
            ->first(function ($foreignKeyConstraint) use ($attribute) {
                // Doctrine combines foreign key constraints that point to the same table.
                // That means we have to check if the target column exists in the current
                // foreign key constraint's 'local columns' array.
                return in_array($attribute, $foreignKeyConstraint->getLocalColumns());
            });
    }

    /**
     * Check if `$attribute` is a relation.
     *
     * @param string $attribute
     *
     * @return bool
     */
    private function isAttributeRelation(string $attribute): bool {
        return !!$this->getForeignKeyConstraintForAttribute($attribute);
    }

    /**
     * Convert a foreign key to it's fully qualified model name.
     *
     * @param string $attribute
     *
     * @return string
     */
    private function convertForeignKeyToFullyQualifiedModelName(string $attribute): string {
        $foreignTableName = $this
            ->getForeignKeyConstraintForAttribute($attribute)
            ->getForeignTableName();

        return ModelCollector::getModelsMappedByTable()[$foreignTableName];
    }

    /**
     * Get the mapped TypeScript type of a type.
     *
     * @param stdClass $columnSchema
     *
     * @return string
     */
    private function getTypeScriptType(stdClass $columnSchema): string {
        if ($this->isAttributeRelation($columnSchema->Field)) {
            $fullyQualifiedRelatedModelName = $this->convertForeignKeyToFullyQualifiedModelName($columnSchema->Field);

            if (array_key_exists($fullyQualifiedRelatedModelName, $this->convertedModelsMap)) {
                // If we've already mapped this model to a TypeScript interface definition,
                // we don't want to scan it again, otherwise we could potentially cause
                // an infinite mapping loop.
                $mappedType = $this->convertedModelsMap[$fullyQualifiedRelatedModelName];
            } else {
                // We generate new interfaces for any relational attributes.
                // That means we can recursively instantiate the current class to generate
                // as many interface definitions for relational attributes as we need.
                // We pass our existing convertedModelsMap instance here to prevent this class
                // mapping models we've already mapped in this current class instance.
                $mappedType = (new self($fullyQualifiedRelatedModelName, $this->convertedModelsMap))->generate();
            }
        } else {
            // If the attribute is natively casted, we'll want to perform native cast checking
            // to generate the correct TypeScript type. If it's not natively casted, we can
            // simply map the database type to a TypeScript type.
            $mappedType = $this->isAttributeNativelyCasted($columnSchema->Field)
                ? $this->mapNativeCastToTypeScriptType($columnSchema->Field)
                : $this->mapDatabaseTypeToTypeScriptType($columnSchema->Type);
        }

        // We can't do much with an unknown type.
        if ($mappedType === 'unknown') return $mappedType;

        if ($columnSchema->Null === 'YES') $mappedType .= '|null';

        return $mappedType;
    }

    /**
     * Convert a foreign key to a 'predicted' relation name.
     *
     * @param string $attribute
     *
     * @return string
     */
    private function convertForeignKeyToPredictedRelationName(string $attribute): string {
        return Str::of($attribute)->replaceLast('_id', '')->camel();
    }

    /**
     * Set whether we should include the model's protected $hidden attributes.
     *
     * @param bool $includeHidden
     *
     * @return self
     */
    public function includeHidden(bool $includeHidden): self {
        $this->includeHidden = $includeHidden;

        return $this;
    }

    /**
     * Generate the TypeScript interface.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function generate(): string {
        $tableColumns = collect(DB::select(DB::raw('SHOW COLUMNS FROM ' . $this->model->getTable())));

        $interfaceName = Str::afterLast($this->fullyQualifiedModelName, '\\');

        // At this point, we haven't technically generated the full TypeScript interface definition
        // for the target model. However, if the current model was to reference itself (which is valid),
        // without doing this here, it would cause an infinite loop.
        $this->convertedModelsMap[$this->fullyQualifiedModelName] = $interfaceName;

        // The output buffer always needs to start with the first `interface X {` line.
        $outputBuffer = collect([sprintf('interface %s {', $interfaceName)]);

        $tableColumns->each(function ($columnSchema) use ($outputBuffer) {
            // If this attribute is hidden and we're not including hidden, we'll skip it.
            if (!$this->includeHidden && $this->isAttributeHidden($columnSchema->Field)) return;

            if ($this->isAttributeRelation($columnSchema->Field)) {
                $relationName = $this->convertForeignKeyToPredictedRelationName($columnSchema->Field);
                $generatedTypeScriptType = Str::of($this->getTypeScriptType($columnSchema));

                // We know we've just generated a new interface if the generated TypeScript type
                // starts with 'interface '. If it doesn't, we'll be referring to a TypeScript type
                // that's been previously generated.
                $isRelationInterfaceDefinition = $generatedTypeScriptType->startsWith('interface ');

                if ($isRelationInterfaceDefinition) {
                    // interface User { => User
                    $generatedInterfaceName = $generatedTypeScriptType
                        ->after('interface ')
                        ->before(' {');

                    $fullyQualifiedRelatedModelName = $this->convertForeignKeyToFullyQualifiedModelName($columnSchema->Field);
                    // We've just generated a new interface, we'll want to make sure our class doesn't attempt to
                    // generate it again by adding it to our convertedModelsMap.
                    $this->convertedModelsMap[$fullyQualifiedRelatedModelName] = $generatedInterfaceName->toString();

                    // Columns aren't always required. To make sure we're not losing other type metadata,
                    // we'll append everything after the end of the interface definition onto the new
                    // interface name we've generated.
                    $generatedType = $generatedInterfaceName->append($generatedTypeScriptType->afterLast('}'));
                } else {
                    // If we don't have a new relation interface definition, we'll have the interface name definition,
                    // retrieved from the convertedModelsMap (as well as any type metadata). We can use that value directly.
                    $generatedType = $generatedTypeScriptType;
                }

                // Append the relation to the interface we're generating.
                $outputBuffer->push(sprintf('    %s: %s;', $relationName, $generatedType));

                // If we've generated a new interface, we'll want to append it above the current
                // interface we're in the process of generating.
                if ($isRelationInterfaceDefinition) {
                    // Add an empty line so related interfaces aren't directly after each other.
                    $outputBuffer->prepend('');

                    // Get the TypeScript type of this column. We know it's a relation,
                    // therefore we know the string we get back will be another interface.
                    // Once we've got the interface, we'll want to explode it, then prepend
                    // each piece of the interface to the output buffer.
                    // We reverse the exploded string because we prepend, otherwise we'd prepend backwards.
                    $generatedTypeScriptType
                        ->beforeLast('}')
                        ->append('}')
                        ->explode("\n")
                        ->reverse()
                        ->each(fn ($str) => $outputBuffer->prepend($str));
                }
            } else {
                // Append the column name, and the TypeScript type to the interface we're generating.
                $outputBuffer->push(sprintf('    %s: %s;', $columnSchema->Field, $this->getTypeScriptType($columnSchema)));
            }
        });

        $outputBuffer->push('}');

        return $outputBuffer->join("\n");
    }
}
