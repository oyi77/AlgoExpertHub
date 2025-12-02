# Theme Development Guide

## Overview
The platform supports custom themes for the frontend user interface. Themes are self-contained packages containing assets (CSS, JS, images, fonts) and Blade view templates. This guide explains how to create and upload custom themes.

---

## Theme Structure

### Required Directory Structure

```
your-theme-name/
├── assets/              # Theme assets (will be moved to asset/frontend/{theme-name}/)
│   ├── css/            # Stylesheets
│   │   ├── main.css    # Main stylesheet
│   │   └── ...
│   ├── js/             # JavaScript files
│   │   ├── main.js     # Main JavaScript
│   │   └── ...
│   ├── images/         # Images
│   ├── fonts/          # Custom fonts
│   └── webfonts/       # Web fonts
└── views/              # Blade view templates (will be moved to resources/views/frontend/{theme-name}/)
    ├── layout/         # Layout templates
    │   └── master.blade.php  # REQUIRED - Main layout
    ├── auth/           # Authentication views
    ├── user/           # User dashboard views
    └── widgets/        # Widget components
```

---

## Creating a Theme

### Step 1: Download Template

1. Go to **Admin Panel > Manage Theme**
2. Click **"Download Theme Template"**
3. Extract the ZIP file
4. Rename the template folder to your theme name (e.g., `my-custom-theme`)

### Step 2: Customize Assets

#### CSS Customization

Edit `assets/css/main.css`:

```css
/* Theme Customizations */
body {
    font-family: 'Your Font', sans-serif;
    background-color: #f5f5f5;
}

/* Override default styles */
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Add your custom styles */
```

#### JavaScript Customization

Edit `assets/js/main.js`:

```javascript
// Theme-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Your custom initialization code
});
```

#### Images

Place your images in `assets/images/`:
- Logo images
- Background images
- Icons
- Any theme-specific graphics

---

### Step 3: Customize Views

#### Master Layout (REQUIRED)

Edit `views/layout/master.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name'))</title>
    
    <!-- Theme CSS -->
    <link href="{{ asset('asset/frontend/my-theme-name/css/main.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div id="app">
        @include('frontend.my-theme-name.layout.header')
        
        <main>
            @yield('content')
        </main>
        
        @include('frontend.my-theme-name.layout.footer')
    </div>
    
    <!-- Theme JS -->
    <script src="{{ asset('asset/frontend/my-theme-name/js/main.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
```

**Important Notes**:
- Replace `my-theme-name` with your actual theme name
- Use `asset()` helper for asset URLs
- Use `Helper::theme()` for view paths in code

---

### Step 4: Required Views

At minimum, your theme should include:

**Required**:
- `views/layout/master.blade.php` - Main layout template

**Recommended**:
- `views/layout/header.blade.php` - Header component
- `views/layout/footer.blade.php` - Footer component
- `views/auth/login.blade.php` - Login page
- `views/user/dashboard.blade.php` - User dashboard

**Optional** (will use default theme views if missing):
- Other authentication views
- User dashboard pages
- Widget components

---

## Theme Upload

### ZIP Structure Options

**Option 1: Standard Structure (Recommended)**
```
your-zip.zip
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── ...
└── views/
    ├── layout/
    └── ...
```

**Option 2: Theme-Named Folder**
```
your-zip.zip
└── my-theme-name/
    ├── assets/
    └── views/
```

### Upload Process

1. Go to **Admin Panel > Manage Theme**
2. Click **"Upload Theme ZIP"**
3. Select your theme ZIP file (max 10MB)
4. Click **"Upload Theme"**
5. System validates and extracts theme
6. Theme assets moved to `asset/frontend/{theme-name}/`
7. Theme views moved to `resources/views/frontend/{theme-name}/`

---

## Theme Naming

### Naming Rules

- Use lowercase letters
- Use hyphens or underscores (e.g., `my-theme`, `my_theme`)
- Avoid spaces and special characters
- Theme name will be automatically detected from directory structure

**Examples**:
- ✅ `my-custom-theme`
- ✅ `dark-mode-theme`
- ✅ `premium_theme`
- ❌ `My Theme` (spaces)
- ❌ `theme@v2` (special characters)

---

## Asset Paths

### Using Assets in Views

Always use the `asset()` helper with theme path:

```blade
<!-- CSS -->
<link href="{{ asset('asset/frontend/my-theme-name/css/main.css') }}" rel="stylesheet">

<!-- JavaScript -->
<script src="{{ asset('asset/frontend/my-theme-name/js/main.js') }}"></script>

<!-- Images -->
<img src="{{ asset('asset/frontend/my-theme-name/images/logo.png') }}" alt="Logo">
```

### Dynamic Theme Name

If you need dynamic theme name in code:

```php
$themeName = config('app.theme', 'default');
$cssPath = asset("asset/frontend/{$themeName}/css/main.css");
```

Or use Helper:
```php
Helper::cssLib('frontend', 'main.css'); // Automatically uses active theme
```

---

## View Paths

### Extending Layouts

When creating views, extend the master layout:

```blade
@extends('frontend.my-theme-name.layout.master')

@section('content')
    <!-- Your page content -->
@endsection
```

### Including Components

```blade
@include('frontend.my-theme-name.layout.header')
@include('frontend.my-theme-name.widgets.banner')
```

---

## Testing Your Theme

### Before Upload

1. ✅ Test all CSS styles
2. ✅ Verify JavaScript functionality
3. ✅ Check responsive design (mobile/tablet/desktop)
4. ✅ Test all required views render correctly
5. ✅ Verify asset paths are correct

### After Upload

1. Activate theme from **Manage Theme** page
2. Test user-facing pages:
   - Homepage
   - Login/Register
   - User Dashboard
   - Signal pages
3. Check for broken links/images
4. Verify theme works with all addon views

---

## Theme Configuration

### theme.json (Optional)

You can include a `theme.json` manifest file:

```json
{
    "name": "my-custom-theme",
    "version": "1.0.0",
    "description": "My custom theme description",
    "author": "Your Name",
    "screenshot": "screenshot.png"
}
```

**Location**: Root of your ZIP or in theme directory

---

## Theme Activation

### Activate Theme

1. Go to **Admin Panel > Manage Theme**
2. Find your uploaded theme
3. Click **"Active"** button
4. Theme becomes active immediately
5. All users see the new theme

### Theme Switching

- Only one theme can be active at a time
- Switching themes affects all users immediately
- Previous theme remains installed (can be reactivated)

---

## Best Practices

### Performance

1. **Minify CSS/JS** - Reduce file sizes for faster loading
2. **Optimize Images** - Compress images before including
3. **Lazy Loading** - Load images/widgets on demand
4. **Cache Assets** - Use appropriate cache headers

### Compatibility

1. **Browser Support** - Test in Chrome, Firefox, Safari, Edge
2. **Responsive Design** - Ensure mobile-friendly layout
3. **Accessibility** - Follow WCAG guidelines
4. **Core Integration** - Don't break core functionality

### Maintenance

1. **Version Control** - Keep theme code in version control
2. **Documentation** - Document custom features
3. **Backup** - Backup before major changes
4. **Testing** - Test after platform updates

---

## Common Issues

### Issue: Theme Not Appearing

**Solution**:
- Check ZIP structure is correct
- Verify `assets/` and `views/` directories exist
- Check theme name is valid (lowercase, no spaces)

### Issue: Assets Not Loading

**Solution**:
- Verify asset paths use `asset()` helper
- Check file permissions on asset directory
- Clear browser cache

### Issue: Views Not Rendering

**Solution**:
- Ensure `layout/master.blade.php` exists
- Check view paths match theme name
- Verify Blade syntax is correct

---

## Files Reference

- **Theme Manager**: `main/app/Services/ThemeManager.php`
- **Controller**: `main/app/Http/Controllers/Backend/ConfigurationController.php`
- **Routes**: `main/routes/admin.php` (theme upload/download)
- **Theme View**: `main/resources/views/backend/setting/theme.blade.php`

---

## Example Theme

See default themes for reference:
- `asset/frontend/default/` - Default theme assets
- `resources/views/frontend/default/` - Default theme views

These serve as excellent examples for:
- Layout structure
- View organization
- Asset usage patterns
- Component includes
