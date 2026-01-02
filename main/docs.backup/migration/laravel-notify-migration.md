# Laravel Notify Migration Guide

## Overview

This document outlines the migration from the old notification system (toastr, SweetAlert, iziToast) to Laravel Notify.

## Installation Status

✅ **Completed:**
- Laravel Notify package installed (`mckenziearts/laravel-notify`)
- Configuration file created (`config/notify.php`)
- Backend and frontend alert templates updated
- NotificationHelper class created for backward compatibility

## Migration Strategy

### Phase 1: Backward Compatibility (Current)

The system now supports **both** old and new notification methods:

**Old Method (Still Works):**
```php
return redirect()->back()->with('success', 'User updated successfully');
return redirect()->back()->with('error', 'Something went wrong');
```

**New Method (Recommended):**
```php
use Mckenziearts\Notify\Facades\Notify;

// Simple notification
notify()->success()->title('Success')->message('User updated successfully')->send();
return redirect()->back();

// Or using helper
return redirect()->back()->with('notify', [
    'type' => 'success',
    'title' => 'Success',
    'message' => 'User updated successfully'
]);
```

### Phase 2: Gradual Migration

Controllers can be migrated one at a time. The old `->with('success')` pattern will continue to work during migration.

## Usage Examples

### Basic Notifications

```php
// Success
notify()->success()->title('Success')->message('Operation completed')->send();

// Error
notify()->error()->title('Error')->message('Operation failed')->send();

// Warning
notify()->warning()->title('Warning')->message('Please check your input')->send();

// Info
notify()->info()->title('Info')->message('New features available')->send();
```

### With Redirect

```php
// Method 1: Using session
return redirect()->route('users.index')->with('notify', [
    'type' => 'success',
    'title' => 'User Created',
    'message' => 'The user has been created successfully.'
]);

// Method 2: Using helper (recommended)
use App\Helpers\NotificationHelper;

return redirect()->route('users.index')
    ->with('notify', NotificationHelper::success('User created successfully', 'User Created'));
```

### Using Presets

```php
// Define in config/notify.php
'preset-messages' => [
    'user-updated' => [
        'type' => NotificationType::Success,
        'model' => NotificationModel::Toast,
        'title' => 'User Updated',
        'message' => 'The user has been updated successfully.',
    ],
],

// Use in controller
notify()->preset('user-updated')->send();
return redirect()->back();
```

### With Actions

```php
use Mckenziearts\Notify\Action\NotifyAction;

notify()
    ->success()
    ->title('User deleted')
    ->message('The user has been moved to trash')
    ->actions([
        NotifyAction::make()
            ->label('Undo')
            ->action(route('users.restore', $user->id))
            ->method('POST'),
        NotifyAction::make()
            ->label('View Trash')
            ->url(route('users.trash'))
            ->openUrlInNewTab(),
    ])
    ->send();
```

### Different Notification Models

```php
// Toast (default)
notify()->model(NotificationModel::Toast)->success()->send();

// Connect
notify()->model(NotificationModel::Connect)->success()->send();

// Drake (simple alert)
notify()->model(NotificationModel::Drake)->success()->send();

// Smiley
notify()->model(NotificationModel::Smiley)->success()->send();

// Emotify
notify()->model(NotificationModel::Emotify)->success()->send();
```

## Migration Checklist

### Controllers to Migrate

- [ ] `app/Http/Controllers/Backend/ManageUserController.php`
- [ ] `app/Http/Controllers/Backend/ManagePlanController.php`
- [ ] `app/Http/Controllers/Backend/SignalController.php`
- [ ] `app/Http/Controllers/PaymentController.php`
- [ ] `app/Http/Controllers/User/Trading/*Controller.php`
- [ ] All addon controllers

### Steps for Each Controller

1. **Find old patterns:**
   ```bash
   grep -r "->with('success')" app/Http/Controllers/
   grep -r "->with('error')" app/Http/Controllers/
   ```

2. **Replace with new pattern:**
   ```php
   // Old
   return redirect()->back()->with('success', 'Message');
   
   // New
   return redirect()->back()->with('notify', [
       'type' => 'success',
       'title' => 'Success',
       'message' => 'Message'
   ]);
   ```

3. **Test the notification appears correctly**

## Configuration

The notification system can be configured in `config/notify.php`:

- **Default Model**: Toast, Connect, Drake, Smiley, or Emotify
- **Default Type**: Success, Error, Warning, or Info
- **Timeout**: Duration in milliseconds (default: 5000)
- **Preset Messages**: Reusable notification templates

## Asset Publishing

Laravel Notify assets need to be published:

```bash
php artisan vendor:publish --tag=notify-assets
```

This will copy CSS and JS files to `public/vendor/notify/`.

## Backward Compatibility

The system maintains backward compatibility:

1. **Old session keys** (`success`, `error`, `warning`, `info`) still work
2. **Legacy JavaScript libraries** are still loaded if `alert` config is set to `toast`, `izi`, or `sweetalert`
3. **New notifications** use the `notify` session key and Laravel Notify JavaScript

## Testing

After migration, test:

1. ✅ Success notifications appear
2. ✅ Error notifications appear
3. ✅ Validation errors display correctly
4. ✅ Notifications auto-close after timeout
5. ✅ Action buttons work (if used)
6. ✅ Different notification models render correctly

## Rollback Plan

If issues occur:

1. Set `alert` config back to `toast`, `izi`, or `sweetalert`
2. Old notification system will be used
3. No code changes needed - backward compatible

## Next Steps

1. **Publish assets**: Run `php artisan vendor:publish --tag=notify-assets`
2. **Test notifications**: Verify they appear correctly
3. **Migrate controllers**: Gradually update controllers to use new pattern
4. **Remove old libraries**: Once migration complete, remove toastr/SweetAlert/iziToast assets
5. **Update config**: Set default `alert` to `notify` in configuration

## Support

For issues or questions:
- Laravel Notify Docs: https://github.com/mckenziearts/laravel-notify
- Check `config/notify.php` for configuration options
- Review `app/Helpers/NotificationHelper.php` for helper methods

