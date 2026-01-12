# PKC Role Permission

A lightweight, dynamic role and permission management package for Laravel. This package allows you to manage roles and permissions dynamically via the database, with support for hierarchical permissions and granular access control.

## ✨ Features

- **🔐 Dynamic Roles & Permissions**: Store and manage roles and permissions in the database
- **🌳 Hierarchical Permissions**: Supports parent-child relationships for permissions (e.g., a "Module" permission containing multiple "Action" permissions)
- **⚡ Performance Optimized**: Built-in caching support for faster permission checks
- **🚀 Easy Integration**: Simple traits for `User` and `Role` models
- **🎯 Route Access Control**: Helpers to check access based on route names
- **🔒 Production Ready**: Validation, error handling, and security features

## 📋 Requirements

- PHP >= 8.2
- Laravel >= 11.0
- Composer

## 📦 Installation

You can install the package via composer:

```bash
composer require pkc/role-permission
```

## 🔧 Setup

### 1. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=role-permission-config
```

This will create `config/role-permission.php` where you can customize settings.

### 2. Run Migrations

The package comes with migrations for `roles`, `permissions`, and the pivot `role_permissions` table. Run them using:

```bash
php artisan migrate
```

### 3. Prepare your Models

#### User Model

Add the `HasRoles` trait to your `User` model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Pkc\RolePermission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    
    // Your existing code...
}
```

Make sure your `users` table has a `role_id` foreign key column.

#### Role Model (Optional)

If you want to extend or customize the `Role` model:

```php
namespace App\Models;

use Pkc\RolePermission\Models\Role as BaseRole;

class Role extends BaseRole
{
    // Your custom logic here
}
```

## 💡 Usage Examples

### Creating Roles

```php
use Pkc\RolePermission\Models\Role;

// Create a new role
$adminRole = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin',
    'description' => 'Full system access',
    'status' => true,
]);

$editorRole = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Can edit content',
    'status' => true,
]);
```

### Creating Permissions

```php
use Pkc\RolePermission\Models\Permission;

// Create a parent permission (module)
$postsModule = Permission::create([
    'name' => 'Posts Management',
    'slug' => 'posts',
    'type' => 'module',
    'route_name' => 'posts.index',
    'order' => 1,
    'status' => true,
]);

// Create child permissions (actions)
$viewPosts = Permission::create([
    'name' => 'View Posts',
    'slug' => 'posts.view',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.show',
    'order' => 1,
    'status' => true,
]);

$editPosts = Permission::create([
    'name' => 'Edit Posts',
    'slug' => 'posts.edit',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.edit',
    'order' => 2,
    'status' => true,
]);

$deletePosts = Permission::create([
    'name' => 'Delete Posts',
    'slug' => 'posts.delete',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.destroy',
    'order' => 3,
    'status' => true,
]);
```

### Assigning Roles to Users

```php
$user = User::find(1);
$user->role_id = $adminRole->id;
$user->save();

// Or using update
$user->update(['role_id' => $editorRole->id]);
```

### Assigning Permissions to Roles

```php
$editorRole = Role::find(2);

// Sync permissions (replaces all existing permissions)
$editorRole->permissions()->sync([$viewPosts->id, $editPosts->id]);

// Or use the helper method
$editorRole->syncPermissions([$viewPosts->id, $editPosts->id]);
```

### Checking Permissions in Code

```php
// Check if user has a specific permission
if ($user->hasPermission('posts.edit')) {
    // User can edit posts
}

// Check if user can access a route
if ($user->canAccessRoute('posts.edit')) {
    // User can access this route
}

// Check if user has any of the given permissions
if ($user->hasAnyPermission(['posts.edit', 'posts.view'])) {
    // User has at least one permission
}

// Check if user has all of the given permissions
if ($user->hasAllPermissions(['posts.edit', 'posts.delete'])) {
    // User has all permissions
}

// Check if user has a specific role
if ($user->hasRole('admin')) {
    // User is an admin
}

// Check if user has any of the given roles
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User has at least one role
}
```

### Using in Blade Templates

```blade
{{-- Check permission --}}
@if(auth()->user()->hasPermission('posts.edit'))
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endif

{{-- Check route access --}}
@if(auth()->user()->canAccessRoute('posts.index'))
    <a href="{{ route('posts.index') }}">All Posts</a>
@endif

{{-- Check multiple permissions --}}
@if(auth()->user()->hasAnyPermission(['posts.edit', 'posts.delete']))
    <div class="actions">
        <a href="{{ route('posts.edit', $post) }}">Edit</a>
        <a href="{{ route('posts.delete', $post) }}">Delete</a>
    </div>
@endif

{{-- Show sidebar menu based on permission --}}
@if(auth()->user()->hasPermission('posts'))
    <li><a href="{{ route('posts.index') }}">Posts</a></li>
@endif
```

### Using in Controllers

```php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        // Check permission
        if (!auth()->user()->hasPermission('posts.view')) {
            abort(403, 'You do not have permission to view posts.');
        }
        
        // Your code...
    }
    
    public function edit($id)
    {
        if (!auth()->user()->canAccessRoute('posts.edit')) {
            abort(403);
        }
        
        // Your code...
    }
}
```

### Using in Middleware

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->user()->hasPermission($permission)) {
            abort(403, 'Access denied');
        }
        
        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:

```php
$middleware->alias([
    'permission' => \App\Http\Middleware\CheckPermission::class,
]);
```

Use in routes:

```php
Route::get('/posts', [PostController::class, 'index'])->middleware('permission:posts.view');
```

### Hierarchical Permissions

The package supports hierarchical permissions. If a permission is a "module" type and has children, checking the parent permission will return `true` if any of its children are assigned to the role.

```php
// If 'posts' is a module and 'posts.edit' is assigned to the role
$user->hasPermission('posts'); // returns true (because descendant 'posts.edit' is assigned)
$user->hasPermission('posts.edit'); // returns true (directly assigned)

// This is useful for sidebar visibility
// If user has access to any action within a module, the module appears in the sidebar
```

### Querying Roles and Permissions

```php
use Pkc\RolePermission\Models\Role;
use Pkc\RolePermission\Models\Permission;

// Get only active roles (excludes master-admin by default)
$roles = Role::visible()->active()->get();

// Get root permissions (no parent)
$rootPermissions = Permission::root()->get();

// Get only active permissions
$activePermissions = Permission::active()->get();

// Get permissions with their children
$permissions = Permission::root()->with('children')->get();
```

### Cache Management

Permission checks are cached by default for better performance. To clear cache:

```php
// Clear cache for a specific role
$role->clearPermissionCache();

// Or clear all cache
php artisan cache:clear
```

You can configure caching in `config/role-permission.php`:

```php
'cache_enabled' => true,
'cache_ttl' => 3600, // Cache for 1 hour
```

## 🔒 Security Features

- **Validation**: Permission assignments are validated before saving
- **Circular Reference Prevention**: Automatic detection and prevention of circular permission hierarchies
- **Error Handling**: Proper exceptions for better error handling

## 📝 Configuration

You can customize the package behavior by publishing the config file:

```bash
php artisan vendor:publish --tag=role-permission-config
```

Available configuration options:

- `user_model`: Your User model class
- `master_admin_slug`: Slug for master admin role (default: 'master-admin')
- `cache_enabled`: Enable/disable caching (default: true)
- `cache_ttl`: Cache time-to-live in seconds (default: 3600)
- `table_names`: Custom table names (optional)

## 🐛 Error Handling

The package provides specific exceptions:

```php
use Pkc\RolePermission\Exceptions\CircularReferenceException;
use Pkc\RolePermission\Exceptions\InvalidPermissionAssignmentException;
use Pkc\RolePermission\Exceptions\PermissionNotFoundException;

try {
    $role->syncPermissions([1, 2, 3]);
} catch (InvalidPermissionAssignmentException $e) {
    // Handle invalid permission IDs
}
```

## 📚 API Reference

### User Model Methods (HasRoles trait)

- `hasPermission(string $slug): bool` - Check if user has a permission
- `canAccessRoute(string $routeName): bool` - Check if user can access a route
- `hasAnyPermission(array $slugs): bool` - Check if user has any of the given permissions
- `hasAllPermissions(array $slugs): bool` - Check if user has all of the given permissions
- `hasRole(string $slug): bool` - Check if user has a specific role
- `hasAnyRole(array $slugs): bool` - Check if user has any of the given roles
- `role(): BelongsTo` - Get the user's role relationship

### Role Model Methods

- `hasPermission(string $slug): bool` - Check if role has a permission
- `permissions(): BelongsToMany` - Get permissions relationship
- `syncPermissions(array $permissionIds, bool $recursive = false): void` - Sync permissions
- `clearPermissionCache(): void` - Clear permission cache for this role
- `isMasterAdmin(): bool` - Check if role is master admin
- `scopeVisible($query)` - Exclude master admin from queries
- `scopeActive($query)` - Get only active roles

### Permission Model Methods

- `parent(): BelongsTo` - Get parent permission
- `children(): HasMany` - Get child permissions
- `getAllDescendantIds(): array` - Get all descendant permission IDs
- `getAncestors(): Collection` - Get all ancestor permissions
- `toTreeArray(): array` - Get permission as tree structure
- `scopeRoot($query)` - Get only root permissions
- `scopeActive($query)` - Get only active permissions

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👤 Author

**PKC**

- Email: laradev.sumon@gmail.com

## 🔗 Links

- [GitHub Repository](https://github.com/laradevsumon/role-permission)
- [Packagist](https://packagist.org/packages/pkc/role-permission)
