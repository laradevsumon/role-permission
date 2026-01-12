# PKC Role Permission

A lightweight, dynamic role and permission management package for Laravel. This package allows you to manage roles and permissions dynamically via the database, with support for hierarchical permissions and granular access control.

## Features

- **Dynamic Roles & Permissions**: Store and manage roles and permissions in the database.
- **Hierarchical Permissions**: Supports parent-child relationships for permissions (e.g., a "Module" permission containing multiple "Action" permissions).
- **Easy Integration**: Simple traits for `User` and `Role` models.
- **Blade Directives**: Use standard Laravel `@can` or custom checks.
- **Route Access Control**: Helpers to check access based on route names.

## Installation

You can install the package via composer:

```bash
composer require pkc/role-permission
```

## Setup

### 1. Migrations

The package comes with migrations for `roles`, `permissions`, and the pivot `role_permissions` table. Run them using:

```bash
php artisan migrate
```

### 2. Prepare your Models

#### User Model
Add the `HasRoles` trait to your `User` model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Pkc\RolePermission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    
    // ...
}
```

#### Role Model
If you want to extend or customize the `Role` model, you can do so in your application:

```php
namespace App\Models;

use Pkc\RolePermission\Models\Role as BaseRole;

class Role extends BaseRole
{
    // Custom logic here
}
```

## Usage

### Checking Permissions

You can check if a user has a specific permission using the `hasPermission` method:

```php
if ($user->hasPermission('edit-posts')) {
    //
}
```

### Checking Route Access

The package provides a helper to check if a user can access a specific route name:

```php
if ($user->canAccessRoute('posts.edit')) {
    //
}
```

### Hierarchical Permissions

If a permission is a "module" type and has children, checking the parent permission will return `true` if any of its children are assigned to the role. This is useful for sidebar visibility.

```php
// If 'fmdf' has child 'fmdf.stock' and the role has 'fmdf.stock'
$user->hasPermission('fmdf'); // returns true
```

### Assigning Permissions

```php
$role = Role::find(1);
$role->permissions()->sync([$permissionId1, $permissionId2]);

// Or use the granular sync helper (does not auto-include children)
$role->syncPermissionsWithChildren([$id1, $id2]);
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
