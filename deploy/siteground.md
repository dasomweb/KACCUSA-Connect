# SiteGround Deployment Configuration

## SSH Access
- **Port:** 18765 (SiteGround default)
- **WP Path:** ~/public_html
- **WP-CLI:** Pre-installed on SiteGround

## GitHub Secrets Required

| Secret | Value | Notes |
|--------|-------|-------|
| `STAGING_HOST` | staging domain or IP | SiteGround staging site |
| `STAGING_USER` | SSH username | From SiteGround Site Tools > SSH |
| `STAGING_URL` | https://staging.example.com | Full URL |
| `PROD_HOST` | production domain or IP | SiteGround production |
| `PROD_USER` | SSH username | Same or different account |
| `PROD_URL` | https://example.com | Full URL |
| `SSH_PRIVATE_KEY` | Private key content | Generated SSH key pair |
| `SSH_PORT` | 18765 | SiteGround SSH port |
| `SLACK_WEBHOOK` | Slack webhook URL | Optional - for notifications |

## Setup Steps

### 1. Generate SSH Key
```bash
ssh-keygen -t ed25519 -C "github-actions-deploy"
```

### 2. Add Public Key to SiteGround
- SiteGround Site Tools > Devs > SSH Keys Manager
- Import the public key

### 3. Add Private Key to GitHub
- Repository Settings > Secrets and variables > Actions
- Add `SSH_PRIVATE_KEY` with the private key content

### 4. Clone Repo on Server
```bash
ssh -p 18765 user@host
cd ~/public_html
git init
git remote add origin https://github.com/OWNER/KACCUSA-Connect.git
git fetch origin main
git reset --hard origin/main
```

### 5. Plugin Symlink
The dw-core plugin needs to be symlinked or copied to wp-content/plugins/:
```bash
ln -s ~/public_html/plugins/dw-core ~/public_html/wp-content/plugins/dw-core
wp plugin activate dw-core --path=~/public_html
```

### 6. First Sync
```bash
wp eval-file scripts/sync-theme-options.php --path=~/public_html
wp eval-file scripts/import-block-templates.php --path=~/public_html
wp rewrite flush --path=~/public_html
```

## SiteGround-Specific Notes
- SiteGround has its own caching (SG Optimizer) - may need to purge after deploy
- PHP version is managed via SiteGround Site Tools
- Staging sites can be created via SiteGround Site Tools > WordPress > Staging
