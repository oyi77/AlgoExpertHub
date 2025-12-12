# Asset Symlink Setup

The `asset` directory is a symbolic link pointing to `main/public/asset` to avoid duplicating large asset files in the repository.

## Automatic Setup

The symlink is automatically created after `git pull` or `git checkout` via Git hooks.

## Manual Setup

If automatic setup doesn't work, run:

```bash
./setup-asset-symlink.sh
```

## Custom Target Path

If you need a different target path, set the `ASSET_TARGET` environment variable:

```bash
export ASSET_TARGET=/path/to/your/asset
./setup-asset-symlink.sh
```

## For New Developers

After cloning the repository:

1. Run the setup script:
   ```bash
   ./setup-asset-symlink.sh
   ```

2. Or install Git hooks manually (hooks are already in `.git/hooks/` but you may need to copy them):
   ```bash
   chmod +x .git/hooks/post-checkout
   chmod +x .git/hooks/post-merge
   ```

## Troubleshooting

- **Symlink not created**: Check that `main/public/asset` directory exists
- **Permission denied**: Run `chmod +x setup-asset-symlink.sh`
- **Wrong target**: Set `ASSET_TARGET` environment variable to correct path

