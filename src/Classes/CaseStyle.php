<?php

namespace SalemC\TypeScriptifyLaravelModels\Classes;

use ReflectionClass;

final class CaseStyle {
    /**
     * The 'camel' case style.
     *
     * camelCase
     *
     * @var string
     */
    public const CAMEL = 'camel';

    /**
     * The 'kebab' case style.
     *
     * kebab-case
     *
     * @var string
     */
    public const KEBAB = 'kebab';

    /**
     * The 'snake' case style.
     *
     * snake_case
     *
     * @var string
     */
    public const SNAKE = 'snake';

    /**
     * The 'pascal' case style.
     *
     * PascalCase
     *
     * @var string
     */
    public const PASCAL = 'pascal';

    /**
     * The 'default' case style.
     *
     * This is reserved, indicating to do nothing.
     *
     * @var string
     */
    public const DEFAULT = 'default';

    /**
     * Get all the case styles.
     *
     * @return array
     */
    public static function all(): array {
        return collect((new ReflectionClass(self::class))->getConstants())
            ->map(fn ($constant) => $constant->getValue())
            ->toArray();
    }
}
