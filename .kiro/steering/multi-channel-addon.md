---
inclusion: always
---

# Multi-Channel Signal Addon Rules

- Ensure all addon code resides within `main/addons/multi-channel-signal-addon/` and uses the `Addons\\MultiChannelSignalAddon` namespace.
- Keep addon functionality modular: avoid modifying core app behavior unless routed through service providers, events, or documented extension points.
- Prefer configuration, service providers, and route files inside the addon; do not register addon routes or bindings directly in the core app.
- When interacting with core models/services (e.g., `App\\Models\\Signal`, `App\\Services\\SignalService`), add integration points via the addon layer to preserve decoupling.
- Maintain documentation updates alongside code changes in `specs/active/multi-channel-signal-addon/`.
- Follow Laravel best practices: queued jobs for async processing, config-driven credentials, and guarded secrets (use Laravel encryption helpers).
- Keep admin/user UI components under addon views (or published stubs) and expose navigation links conditionally based on permissions.
