---
description: Always update documentation when modifying code or adding features
globs: **/*.php, **/*.js, **/*.vue, **/*.blade.php
alwaysApply: true
---

# Documentation Update Policy

## 1. Traceability
- **Code Changes**: If you modify logic, parameters, or behavior, you MUST update the corresponding documentation in `docs/` immediately.
- **New Features**: Every new feature requires a new or updated section in the user guides or API references.

## 2. Redundancy
- **Single Source**: Do NOT create duplicate files. Check `docs/` first.
- **Cross-Reference**: If a topic is covered, link to it instead of repeating it.

## 3. Deployment
- **Sanity Check**: Verify your doc changes are accurate against the code.
- **Wiki Update**: Run `/openspec-apply enforce-documentation-policy` (or the wiki deploy script directly once configured) to sync changes to the public wiki.
