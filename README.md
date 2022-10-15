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

## How It Works

### Database

**TypeScriptify Laravel Models** works primarily by gathering column data from the database your Laravel instance is setup with. Once gathered, it maps column types to known TypeScript types. This means if you don't have a database column for a property you want converted, it won't exist in the final TypeScript interface definition.

### Casts

**TypeScriptify Laravel Models** also respects _predictable_ Laravel casts (specifically `protected $casts` and `protected $dates`) you've setup in the model being converted. It will map all known casts to TypeScript types.

## Caveats

**TypeScriptify Laravel Models** is only able to map _predictable_ data types to TypeScript types. [Custom Casts](https://laravel.com/docs/9.x/eloquent-mutators#custom-Casts) and [Custom Accessors](https://laravel.com/docs/9.x/eloquent-mutators#accessors-and-mutators) are not, and cannot be supported.

If **TypeScriptify Laravel Models** fails to map a type to a TypeScript type, it will set the value to `unknown` in the TypeScript interface definition.

