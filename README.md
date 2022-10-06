# typescriptify-laravel-models

Effortlessly generate TypeScript interface definitions from Laravel models.

## Example Usage

```php
<?php

use SalemC\TypeScriptifyLaravelModels\TypeScriptInterfaceGenerator;

echo TypeScriptInterfaceGenerator::generate(\App\Models\User::class);
```
Will produce:
```ts
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string|null;
    password: string;
    remember_token: string|null;
    created_at: string|null;
    updated_at: string|null;
}
```
