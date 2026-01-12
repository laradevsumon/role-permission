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

## 💡 Complete Example Flow

Here's a complete step-by-step example of setting up and using the package in a blog application:

### Step 1: Setup and Migration

```bash
# Install the package
composer require pkc/role-permission

# Run migrations
php artisan migrate
```

### Step 2: Configure User Model

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Pkc\RolePermission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    
    // Your existing code...
}
```

### Step 3: Create Roles

```php
use Pkc\RolePermission\Models\Role;

// Create roles
$admin = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin',
    'description' => 'Full system access',
    'status' => true,
]);

$editor = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Can create and edit posts',
    'status' => true,
]);

$author = Role::create([
    'name' => 'Author',
    'slug' => 'author',
    'description' => 'Can create posts',
    'status' => true,
]);
```

### Step 4: Create Permissions (Hierarchical Structure)

```php
use Pkc\RolePermission\Models\Permission;

// Create Posts Module (parent permission)
$postsModule = Permission::create([
    'name' => 'Posts',
    'slug' => 'posts',
    'type' => 'module',
    'route_name' => 'posts.index',
    'order' => 1,
    'status' => true,
]);

// Create action permissions (children)
$viewPosts = Permission::create([
    'name' => 'View Posts',
    'slug' => 'posts.view',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.show',
    'order' => 1,
    'status' => true,
]);

$createPosts = Permission::create([
    'name' => 'Create Posts',
    'slug' => 'posts.create',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.create',
    'order' => 2,
    'status' => true,
]);

$editPosts = Permission::create([
    'name' => 'Edit Posts',
    'slug' => 'posts.edit',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.edit',
    'order' => 3,
    'status' => true,
]);

$deletePosts = Permission::create([
    'name' => 'Delete Posts',
    'slug' => 'posts.delete',
    'type' => 'action',
    'parent_id' => $postsModule->id,
    'route_name' => 'posts.destroy',
    'order' => 4,
    'status' => true,
]);
```

### Step 5: Assign Permissions to Roles

```php
// Admin gets all permissions
$admin->syncPermissions([
    $postsModule->id,
    $viewPosts->id,
    $createPosts->id,
    $editPosts->id,
    $deletePosts->id,
]);

// Editor can view, create, and edit (but not delete)
$editor->syncPermissions([
    $viewPosts->id,
    $createPosts->id,
    $editPosts->id,
]);

// Author can only view and create
$author->syncPermissions([
    $viewPosts->id,
    $createPosts->id,
]);
```

### Step 6: Assign Roles to Users

```php
use App\Models\User;

// Assign admin role to a user
$user1 = User::find(1);
$user1->update(['role_id' => $admin->id]);

// Assign editor role
$user2 = User::find(2);
$user2->update(['role_id' => $editor->id]);

// Assign author role
$user3 = User::find(3);
$user3->update(['role_id' => $author->id]);
```

### Step 7: Use in Controller

```php
// app/Http/Controllers/PostController.php
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
        
        $posts = Post::all();
        return view('posts.index', compact('posts'));
    }
    
    public function create()
    {
        if (!auth()->user()->hasPermission('posts.create')) {
            abort(403);
        }
        
        return view('posts.create');
    }
    
    public function store(Request $request)
    {
        if (!auth()->user()->canAccessRoute('posts.create')) {
            abort(403);
        }
        
        // Create post logic...
    }
    
    public function edit($id)
    {
        if (!auth()->user()->hasPermission('posts.edit')) {
            abort(403);
        }
        
        $post = Post::findOrFail($id);
        return view('posts.edit', compact('post'));
    }
    
    public function destroy($id)
    {
        if (!auth()->user()->hasPermission('posts.delete')) {
            abort(403);
        }
        
        // Delete post logic...
    }
}
```

### Step 8: Use in Blade Templates

```blade
{{-- resources/views/posts/index.blade.php --}}

{{-- Show posts menu if user has posts permission --}}
@if(auth()->user()->hasPermission('posts'))
    <li><a href="{{ route('posts.index') }}">Posts</a></li>
@endif

{{-- Show create button if user has create permission --}}
@if(auth()->user()->hasPermission('posts.create'))
    <a href="{{ route('posts.create') }}" class="btn btn-primary">Create Post</a>
@endif

<table>
    @foreach($posts as $post)
        <tr>
            <td>{{ $post->title }}</td>
            <td>
                {{-- Show edit button if user has edit permission --}}
                @if(auth()->user()->hasPermission('posts.edit'))
                    <a href="{{ route('posts.edit', $post) }}">Edit</a>
                @endif
                
                {{-- Show delete button if user has delete permission --}}
                @if(auth()->user()->hasPermission('posts.delete'))
                    <form action="{{ route('posts.destroy', $post) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
</table>
```

### Step 9: Testing the Flow

```php
// Example: Testing permissions
$user = User::find(2); // Editor user

// These will return true
$user->hasPermission('posts.view');     // true
$user->hasPermission('posts.create');   // true
$user->hasPermission('posts.edit');     // true
$user->hasPermission('posts');          // true (parent module)

// This will return false
$user->hasPermission('posts.delete');   // false

// Check role
$user->hasRole('editor');               // true
$user->hasAnyRole(['admin', 'editor']); // true

// Check multiple permissions
$user->hasAnyPermission(['posts.edit', 'posts.delete']); // true (has edit)
$user->hasAllPermissions(['posts.edit', 'posts.delete']); // false (missing delete)
```

### Step 10: Dynamic Permission Management (Optional)

You can also manage permissions dynamically through your admin panel:

```php
// In your admin controller
public function updateRolePermissions(Request $request, Role $role)
{
    $permissionIds = $request->input('permissions', []);
    
    // Validate permission IDs exist
    $validIds = Permission::whereIn('id', $permissionIds)->pluck('id')->toArray();
    
    // Sync permissions
    $role->syncPermissions($validIds);
    
    // Clear cache
    $role->clearPermissionCache();
    
    return redirect()->back()->with('success', 'Permissions updated successfully');
}
```

This complete flow demonstrates:
- ✅ Setting up the package
- ✅ Creating roles and permissions
- ✅ Building hierarchical permission structure
- ✅ Assigning permissions to roles
- ✅ Assigning roles to users
- ✅ Using permissions in controllers
- ✅ Using permissions in Blade templates
- ✅ Testing and verification

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

## 📊 Hierarchical Permission Structure Example

Here's an example of a real-world hierarchical permission structure:

```
FMDF
├── Stock Management
│   ├── Lot Entry
│   │   ├── Add
│   │   ├── Edit
│   │   ├── Delete
│   │   └── View
├── Order Management
    ├── Add
    ├── Edit
    ├── Delete
    └── View

```

**Flow Example:**
```
FMDF -> Stock Management -> Tree Stock -> Add

FMDF -> Order Management -> Add
```

This means:
- **FMDF** is the root module
- **Stock Management** is a child module of FMDF
- **Tree Stock** is a child module of Stock Management
- **Add** is an action permission under Tree Stock

When a user has the "Tree Stock -> Add" permission, they automatically get access to:
- Tree Stock (parent module)
- Stock Management (grandparent module)
- FMDF (root module)

This hierarchical structure allows for:
- ✅ Flexible permission management
- ✅ Easy sidebar menu visibility control
- ✅ Granular access control at each level
- ✅ Automatic parent permission inheritance

## 🔗 Links

- [GitHub Repository](https://github.com/laradevsumon/role-permission)
- [Packagist](https://packagist.org/packages/pkc/role-permission)
