# Design: Landing Page Switcher

## Architecture

The Landing Page Switcher will work by overriding the default theme resolution for the homepage view.

### Configuration

A new column `landing_page` will be added to the `configurations` table.
-   **Type**: string
-   **Default**: `null` (means use theme default)
-   **Values**: `null`, `bot-sales`, etc.

### View Resolution

The `Helper::themeView` method will be modified or a new method will be created to handle landing page specific resolution.

```php
public static function landingView()
{
    $config = self::config();
    $landing = $config->landing_page;

    if ($landing && view()->exists("frontend.landings.{$landing}.index")) {
        return "frontend.landings.{$landing}.index";
    }

    return self::themeView('home');
}
```

### Directory Structure

New landing pages will be stored in `resources/views/frontend/landings/{landing_name}/`.
Assets will be in `public/asset/frontend/landings/{landing_name}/`.

## Admin UI

The "Manage Theme" or "General Setting" page will be updated with a "Landing Page" dropdown.
This dropdown will list available folders in `resources/views/frontend/landings/`.

## Bot Sales Landing Page

This page will be designed as a single-page marketing layout with:
-   Hero section with catchy headline and CTA.
-   Features section (Automated Trading, Multi-Exchange, AI Analysis).
-   Pricing/Plans section.
-   Testimonials/Social Proof.
-   FAQ.
-   Footer.

Reference: Cornix, 3Commas style (Dark mode, neon highlights, glassmorphism).
