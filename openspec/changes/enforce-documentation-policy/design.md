# Design: Enforce Documentation Policy

## Philosophy
"Documentation is Code." It should be treated with the same rigor as source code.
- **Atomic Commits**: Doc changes should ideally accompany code changes in the same commit or PR.
- **Single Source of Truth**: Information should live in one place. If multiple pages need to reference it, they should link to the canonical source rather than copy-pasting.

## Implementation Details

### Rules Engine
Values in `.cursor/rules` will be used to prompt the agent (and human developers) to:
1.  Check for existing documentation before creating new ones.
2.  Update related documentation when modifying logic.

### Wiki Deployment
The `scripts/deploy-github-wiki.sh` script currently handles the mechanics of pushing to the wiki repo. This process should be triggered frequently to keep the public-facing documentation in sync with the repository state.
