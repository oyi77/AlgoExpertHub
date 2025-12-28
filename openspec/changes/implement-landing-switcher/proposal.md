# Proposal: Implement Landing Page Switcher and Bot Sales Landing Page

## Problem Statement

Currently, the platform manages themes globally. While a user might want to use the `trading-v1` theme for the dashboard and overall look, they might want to swap the landing page (homepage) for specific marketing goals (e.g., selling automated trading bots) without changing the entire theme.

## Proposed Solution

1.  **Landing Page Switcher**: Implement a mechanism to select a specific landing page independent of the active theme.
2.  **Bot Sales Landing Page**: Create a new, modern landing page focused on automated bot trading for Crypto and Forex, inspired by market leaders like Cornix, 3Commas, Pionex, and Coinrule.

## Scope

-   **Switcher Logic**: Update `FrontendController` and `Helper::themeView` to support landing page overrides.
-   **Configuration**: Add a new configuration setting for the active landing page.
-   **Admin UI**: Add a selector in the Admin Panel to choose the active landing page.
-   **New Landing Page**: A new Blade template and associated assets for the Bot Sales landing page.

## Success Criteria

-   Admins can switch the landing page from the Admin Panel.
-   The selected landing page is rendered correctly at the root URL (`/`).
-   The rest of the theme (dashboard, auth, etc.) remains unchanged.
-   The new Bot Sales landing page is visually appealing and converts well.
