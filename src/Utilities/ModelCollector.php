<?php

namespace SalemC\TypeScriptifyLaravelModels\Utilities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use ReflectionClass;

final class ModelCollector {
    /**
     * The cache for all models.
     *
     * @var ?array
     */
    private ?array $modelsCache = null;

    /**
     * The cache for all models mapped by table.
     *
     * @var ?array
     */
    private ?array $modelsMappedByTableCache = null;

    /**
     * Construct this class.
     *
     * @param string $path The path to start scanning models at.
     */
    public function __construct(private readonly string $path) {
        //
    }

    /**
     * Get all existing models.
     *
     * @return array
     */
    public function getModels(): array {
        return $this->modelsCache ??= collect(File::allFiles($this->path))
            ->map(function ($item) {
                return Str::of($item->getContents())
                    ->match('/namespace (.*);/')
                    ->start('\\')
                    ->finish('\\' . Str::of($item->getRelativePathName())->afterLast('\\')->beforeLast('.'))
                    ->toString() ?? '\\';
            })->filter(function ($className) {
                if (!class_exists($className)) return false;

                $reflection = new ReflectionClass($className);

                return $reflection->isSubclassOf(Model::class) && !$reflection->isAbstract();
            })->values()->all();
    }

    /**
     * Get all existing models, mapped with their table.
     *
     * @return array
     */
    public function getModelsMappedByTable(): array {
        return $this->modelsMappedByTableCache ??= collect($this->getModels())
            ->mapWithKeys(fn ($className) => [(new $className)->getTable() => $className])
            ->all();
    }
}
