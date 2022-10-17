# TypeScriptify Laravel Models

Effortlessly generate TypeScript interface definitions from Laravel Model classes.

## Usage

The main invocation of `php artisan typescriptify:model` with --help lists sub-commands available:

```
php artisan typescriptify:model --help

Description:
  Convert a model to it's TypeScript definition.

Usage:
  typescriptify:model [options] [--] <model>

Arguments:
  model The fully qualified class name for the model - e.g. App\Models\User

Options:
      --includeHidden[=INCLUDEHIDDEN] Include the protected $hidden properties [default: "false"]
      --includeRelations[=INCLUDERELATIONS] Map foreign key columns to interface definitions [default: "true"]
```

## Example Usage

Invocating `php artisan typescriptify:model \App\Models\User` on a fresh Laravel installation will produce:

```ts
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string|null;
    created_at: string|null;
    updated_at: string|null;
}
```

Or if you prefer, you can instantiate your own version of the `TypeScriptifyModel` class:

```php
use SalemC\TypeScriptifyLaravelModels\TypeScriptifyModel;

echo (new TypeScriptifyModel(\App\Models\User::class))->generate();
```

## Relation Mapping

**TypeScriptify Laravel Models** supports `belongsTo` relation mapping.

Imagine you have the following scenario:

```php
// app/Models/User.php
// columns: id, name, email, password, role_id

public function role(): BelongsTo {
    return $this->belongsTo(Role::class);
}

// app/Models/Role.php
// columns: id, name

public function users(): HasMany {
    return $this->hasMany(User::class);
}
```

With a foreign key (**and a foreign key constraint**) `role_id` on the `users` table.

It would be nice if instead of having a `role_id: number` attribute on the generated `User` interface, it was instead the full relational dataset, right? Well, **TypeScriptify Laravel Models** is able to recognise that the `role_id` column should be the `Role` model, and will map it to a reusable interface definition for you. Unfortunately, we're not able to determine the exact relation name; instead we attempt to guess it for you, based on the foreign key name:

```ts
// Automatically generated Role interface.
interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    password: string;
    role: Role; // 'guessed' attribute name of 'role' (from 'role_id') with the interface Role, generated above.
}
```

## How It Works

### Database

**TypeScriptify Laravel Models** works primarily by gathering column data from the database your Laravel instance is setup with. Once gathered, it maps column types to known TypeScript types. This means if you don't have a database column for a property you want converted, it won't exist in the final TypeScript interface definition.

### Casts

**TypeScriptify Laravel Models** also respects _predictable_ Laravel casts (specifically `protected $casts` and `protected $dates`) you've setup in the model being converted. It will map all known casts to TypeScript types.

## Caveats

**TypeScriptify Laravel Models** is only able to map _predictable_ data types to TypeScript types. [Custom Casts](https://laravel.com/docs/9.x/eloquent-mutators#custom-Casts) and [Custom Accessors](https://laravel.com/docs/9.x/eloquent-mutators#accessors-and-mutators) are not, and cannot be supported.

If **TypeScriptify Laravel Models** fails to map a type to a TypeScript type, it will set the value to `unknown` in the TypeScript interface definition.

