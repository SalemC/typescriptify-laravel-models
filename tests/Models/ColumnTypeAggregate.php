<?php

namespace SalemC\TypeScriptifyLaravelModels\Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;

class ColumnTypeAggregate extends Model {
    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'castInt' => 'int',
        'castReal' => 'real',
        'castDate' => 'date',
        'castFloat' => 'float',
        'castBool' => 'bool',
        'castDouble' => 'double',
        'castString' => 'string',
        'castInteger' => 'integer',
        'castDateTime' => 'datetime',
        'castBoolean' => 'boolean',
        'castArray' => 'array',
        'castEncrypted' => 'encrypted',
        'castTimestamp' => 'timestamp',
        'castImmutableDate' => 'immutable_date',
        'castAsStringable' => AsStringable::class,
        'castImmutableDateTime' => 'immutable_datetime',
        'castObject' => 'object',
        'castDecimal' => 'decimal',
    ];
}
