# Refactor SoC to Service-Repository Pattern

This proposal aims to enforce a strict Separation of Concerns (SoC) across the codebase, aligning with the project's architectural standards and introducing Livewire for modern interactivity.

## Driver

The codebase currently suffers from mixed content (HTML/JS/CSS tightly coupled in Views), leaky abstractions (Controllers handling business logic and DB operations), and a lack of modern reactive components. This refactor is driven by the need to:
1.  **Enforce SoC**: Strict separation of View, Logic, and Data.
2.  **Modernize**: Introduce Livewire for reactive UI components.
3.  **Standardize**: Ensure all business logic resides in Services and data access in Repositories.

## Change ID
`refactor-soc`

## Summary

This change will:
1.  **Install & Configure Livewire**: Enable modern, reactive components.
2.  **Clean Views**: Extract inline `<script>` and `<style>` to dedicated assets.
3.  **Refactor Logic**: Move business logic from Controllers to Service classes.
4.  **Refactor Data**: Move DB operations from Controllers to Repository classes.
