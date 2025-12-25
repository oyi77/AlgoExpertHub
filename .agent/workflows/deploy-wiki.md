---
description: Deploy documentation to GitHub Wiki
---

# Deploy GitHub Wiki

This workflow executes the deployment script to sync local `docs/` and `repowiki/` to the GitHub Wiki.

## Steps

1. Run the deployment script
// turbo
```bash
./scripts/deploy-github-wiki.sh
```

2. Validate output
- Ensure the script returns "Deployment Complete!"
- Check the "Total pages deployed" count.
