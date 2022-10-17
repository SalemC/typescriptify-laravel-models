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
        'castBool' => 'bool',
        'castDate' => 'date',
        'castArray' => 'array',
        'castFloat' => 'float',
        'castDouble' => 'double',
        'castObject' => 'object',
        'castString' => 'string',
        'castDecimal' => 'decimal',
        'castBoolean' => 'boolean',
        'castInteger' => 'integer',
        'castDateTime' => 'datetime',
        'castEncrypted' => 'encrypted',
        'castTimestamp' => 'timestamp',
        'castImmutableDate' => 'immutable_date',
        'castAsStringable' => AsStringable::class,
        'castImmutableDateTime' => 'immutable_datetime',
    ];
}
