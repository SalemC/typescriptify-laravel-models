<?php

namespace SalemC\TypeScriptifyLaravelModels\Utilities\ModelCollector;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Container\Container;

use ReflectionClass;

final class ModelCollector {
    /**
     * The cache for all models.
     *
     * @var ?array
     */
    private static ?array $modelsCache = null;

    /**
     * The cache for all models mapped by table.
     *
     * @var ?array
     */
    private static ?array $modelsMappedByTableCache = null;

    /**
     * Get all existing models.
     *
     * @return array
     */
    public static function getModels(): array {
        return self::$modelsCache ??= collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();

                $className = sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );

                return $className;
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
    public static function getModelsMappedByTable(): array {
        return self::$modelsMappedByTableCache ??= collect(self::getModels())
            ->mapWithKeys(fn ($className) => [(new $className)->getTable() => $className])
            ->all();
    }
}
