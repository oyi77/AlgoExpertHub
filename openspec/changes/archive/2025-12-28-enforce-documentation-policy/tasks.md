# Tasks

- [x] **Create Documentation Rule**
  - [x] Create `.cursor/rules/always-update-docs.md` (or similar) enforcing the "Doc Update" rule.
  - [x] Ensure the rule specifies checking for existing docs to avoid redundancy.

- [x] **Cleanup & Deduplication**
  - [x] Scan `docs/` for obvious duplicates or outdated files.
  - [x] Consolidate information if necessary.

- [x] **Wiki Deployment Workflow**
  - [x] Create a workflow file `.agent/workflows/deploy-wiki.md` that wraps the script.
  - [x] Execute `scripts/deploy-github-wiki.sh` to ensure current state is synced.

- [x] **Verify**
  - [x] Check that `openspec list` reflects the changes (if applicable).
  - [x] Verify wiki is updated.
