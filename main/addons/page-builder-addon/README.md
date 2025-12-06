# Page Builder Addon

Elementor-like drag-and-drop page builder with comprehensive theme and layout management for AlgoExpertHub.

## Features

### Core Features
- Drag-and-drop page editing using GrapesJS
- Theme template editing with visual builder
- Create and manage custom themes
- Menu management with drag-and-drop interface
- Section builder
- Page templates library

### Advanced Features (Elementor-like)
- **Layout Manager**: Create and manage page layouts (header, footer, sidebar, content, full page)
- **Widget Library**: Custom widget system with categories (general, form, media, social, navigation, content)
- **Global Styles**: Manage global CSS/SCSS/LESS styles across all pages
- **Theme Builder**: Visual theme creation and editing
- **Menu Builder**: Advanced menu management inside UI Manager
- **Template System**: Reusable page templates

### Backward Compatibility
- Existing Manage Pages, Manage Theme, and Manage Frontend interfaces remain functional
- Seamless integration with core theme system

## Installation

1. The addon is automatically registered when active in `addon.json`
2. Run migrations: `php artisan migrate`
3. Ensure Laravel-Pagebuilder is installed: `composer require hansschouten/laravel-pagebuilder`

## Menu Structure

The addon integrates into the "UI Manager" menu section with comprehensive Elementor-like features:

### UI Manager Section
- **Manage Pages**: Existing page management + Page Builder access
- **Manage Theme**: Theme management + Theme Builder
- **Manage Frontend**: Section management + Section Builder
- **Page Builder** (Comprehensive):
  - All Pages
  - Create Page
  - Templates
  - Widget Library
  - Manage Layouts
  - Manage Menus
  - Theme Builder
  - Create Theme
  - Global Styles

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
- `LayoutManagerService`: Layout management (header, footer, sidebar, etc.)
- `WidgetLibraryService`: Widget library management
- `GlobalStylesService`: Global CSS/SCSS/LESS management

### Controllers

- `PageBuilderController`: Main page builder interface
- `ThemeController`: Theme builder integration + Create theme
- `MenuController`: Menu management with drag-and-drop
- `TemplateController`: Template management
- `SectionController`: Section builder
- `LayoutController`: Layout management
- `WidgetController`: Widget library management
- `GlobalStylesController`: Global styles management

### Models

- `PageBuilderPage`: Page builder pages
- `PageBuilderMenu`: Menu structures
- `PageBuilderTemplate`: Page templates
- `PageBuilderLayout`: Page layouts
- `PageBuilderWidget`: Widget library
- `PageBuilderGlobalStyle`: Global styles

## Database Tables

- `pagebuilder_pages`: Links pages to pagebuilder
- `pagebuilder_menus`: Menu structures
- `pagebuilder_templates`: Reusable templates
- `pagebuilder_layouts`: Page layouts (header, footer, etc.)
- `pagebuilder_widgets`: Widget library
- `pagebuilder_global_styles`: Global CSS styles

## Usage Examples

### Creating a Layout
1. Go to **UI Manager > Page Builder > Manage Layouts**
2. Click "Create Layout"
3. Choose layout type (header, footer, sidebar, content, full)
4. Configure structure and settings
5. Set as default if needed

### Creating a Widget
1. Go to **UI Manager > Page Builder > Widget Library**
2. Click "Create Widget"
3. Define widget name, category, and templates
4. Add HTML/CSS/JS templates
5. Configure default settings

### Managing Global Styles
1. Go to **UI Manager > Page Builder > Global Styles**
2. Create or edit global CSS styles
3. View compiled CSS
4. Styles are automatically applied to all pages

### Creating a Theme
1. Go to **UI Manager > Page Builder > Create Theme**
2. Enter theme details (name, display name, author, version)
3. Optionally clone from existing theme
4. Theme structure is created automatically
5. Edit templates using Theme Builder
