# Page Builder Addon

Drag-and-drop page builder with Laravel-Pagebuilder integration for AlgoExpertHub.

## Features

- Drag-and-drop page editing using GrapesJS
- Theme template editing
- Menu management
- Section builder
- Page templates
- Backward compatibility with existing Manage Pages, Manage Theme, and Manage Frontend

## Installation

1. The addon is automatically registered when active in `addon.json`
2. Run migrations: `php artisan migrate`
3. Ensure Laravel-Pagebuilder is installed: `composer require hansschouten/laravel-pagebuilder`

## Menu Structure

The addon integrates into the "UI Manager" menu section with:
- **Manage Pages**: Existing page management + Page Builder access
- **Manage Theme**: Theme management + Theme Builder
- **Manage Frontend**: Section management + Section Builder
- **Page Builder**: Unified page builder interface

## Usage

### Accessing Page Builder

1. From **Manage Pages**: Click "Page Builder" submenu or "Edit in Page Builder" button
2. From **Manage Theme**: Click "Theme Builder" to edit theme templates
3. From **Manage Frontend**: Click "Section Builder" to edit sections
4. Direct access: `/admin/page-builder`

### Creating Pages

1. Go to **UI Manager > Page Builder > Create Page**
2. Fill in page details
3. Edit in page builder interface

### Editing Theme Templates

1. Go to **UI Manager > Page Builder > Edit Theme**
2. Select theme and template file
3. Edit using page builder interface

## Backward Compatibility

All existing interfaces remain functional:
- `/admin/pages` - Manage Pages (legacy)
- `/admin/manage-theme` - Manage Theme (legacy)
- `/admin/manage/section/{name}` - Manage Frontend (legacy)

Each interface has buttons/links to access the page builder.

## Development

### Service Classes

- `PageBuilderService`: Core page operations
- `ThemeIntegrationService`: Theme management integration
- `MenuManagerService`: Menu structure management
- `TemplateService`: Page template management
- `ThemeTemplateService`: Theme template editing

### Controllers

- `PageBuilderController`: Main page builder interface
- `ThemeController`: Theme builder integration
- `MenuController`: Menu management
- `TemplateController`: Template management
- `SectionController`: Section builder

## TODO

- [ ] Integrate GrapesJS editor in edit views
- [ ] Implement pagebuilder page creation/linking
- [ ] Add menu drag-and-drop interface
- [ ] Complete template system
- [ ] Add section builder integration
- [ ] Theme template conversion (Blade ↔ PageBuilder)
