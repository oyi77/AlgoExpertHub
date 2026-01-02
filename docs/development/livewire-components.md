# Livewire Components Documentation

This document provides an overview of the shared Livewire components available in the application and how to use them.

## Shared Components

### DataTable

A reusable table component with search, sorting, and pagination.

**Usage:**

Create a component extending `App\Livewire\Shared\DataTable` and implement `columns()` and `getQueryProperty()`.

```php
use App\Livewire\Shared\DataTable;

class UsersTable extends DataTable
{
    public function getQueryProperty()
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ];
    }
}
```

**Blade View:**

```blade
<livewire:admin.users.users-table />
```

### Modal

A versatile modal component for dialogs, forms, and confirmations.

**Usage:**

Dispatch the `openModal` event with parameters.

```php
$this->dispatch('openModal', [
    'title' => 'Edit User',
    'component' => 'admin.users.edit-user-form',
    'params' => ['userId' => 1]
]);
```

**Blade View:**

Ensure `<livewire:shared.modal />` is in your layout.

### Notifications

Toast notifications for user feedback.

**Usage:**

Dispatch the `notify` event.

```php
$this->dispatch('notify', [
    'type' => 'success', // success, error, info, warning
    'message' => 'Operation successful!'
]);
```

**Blade View:**

Ensure `<livewire:shared.notifications />` is in your layout.

### ToggleSwitch

A boolean toggle switch for status updates.

**Usage:**

```blade
<livewire:shared.toggle-switch 
    :model="$user" 
    field="is_active" 
/>
```

### FormWizard

A multi-step form wizard base component.

**Usage:**

Extend `App\Livewire\Shared\FormWizard` and implement `steps()`, `rules()`, and `submit()`.

## Best Practices

- **Service Layer**: Do not put business logic in components. Use Service classes.
- **Validation**: Use `$rules` or `rules()` method for validation. Real-time validation is enabled via `updated($propertyName)`.
- **Authorization**: Use `authorize()` in methods or middleware.
