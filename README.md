# piano-booking-deploy

One-click Docker deploy for the [piano-booking](../piano-booking) WordPress site.

What you get:
- WordPress 7 + PHP 8.3 (Apache)
- Custom `piano-booking` plugin + `piano-theme` baked into the image
- MariaDB 11
- GitHub Actions auto-builds ARM64 image → ghcr.io

Designed for **Oracle Cloud Always Free Tier** (VM.Standard.A1.Flex, ARM64).

---

## One-time setup

### 1. Create the GitHub repo

If you haven't already:
```bash
# On your dev machine
cd piano-booking-deploy
git init
git add .
git commit -m "Initial deploy repo"
gh repo create piano-booking-deploy --public --source=. --remote=origin --push
```

(or create the repo on github.com and `git remote add origin …` + `git push -u origin main`)

### 2. Enable GitHub Actions

The workflow at `.github/workflows/docker.yml` builds + pushes to ghcr.io automatically on every push to `main`.

Check that the workflow succeeded:
- Repo → Actions tab → "Build and push Docker image"
- Image will be at `ghcr.io/<your-github-username>/piano-booking:latest`

Make the package public:
- Repo → Packages → `piano-booking` → Package settings → Change visibility → Public

---

## On Oracle Cloud (Always Free ARM VM)

### 1. Install Docker

```bash
sudo dnf install -y dnf-utils
sudo dnf config-manager --add-repo https://download.docker.com/linux/rhel/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker opc
newgrp docker
```

### 2. Open ports in Oracle VCN

In Oracle Cloud Console → Networking → VCN → Security Lists → Default list:
- Ingress rule: `0.0.0.0/0` → TCP `80`
- Ingress rule: `0.0.0.0/0` → TCP `443`

(OS-level firewalld usually isn't installed on Oracle Linux 9 by default; if you installed it during setup, also `firewall-cmd --add-service=http` / `--add-service=https`.)

### 3. Get the stack running

On your dev machine:
```bash
cp .env.example .env
# Edit .env — set strong random passwords
sed -i.bak "s|CHANGE_ME|$GITHUB_USER|" docker-compose.yml
```

Copy `.env` and `docker-compose.yml` to the Oracle VM (scp, or paste):
```bash
scp .env docker-compose.yml opc@<oracle-ip>:~/pb/
```

On the Oracle VM:
```bash
cd ~/pb
docker login ghcr.io -u <your-github-username>   # needs a PAT with read:packages scope
docker compose pull
docker compose up -d
docker compose ps
docker compose logs -f wordpress
```

The site is now reachable at `http://<oracle-public-ip>/`.

---

## HTTPS (Cloudflare Tunnel — free, no domain needed)

Quick tunnel (random trycloudflare.com URL each restart):
```bash
docker run --rm -it --network=host cloudflare/cloudflared:latest \
  tunnel --url http://localhost:80
```

Prints a URL like `https://xxxx-yyyy.trycloudflare.com`. Update `.env`:
```
WP_HOME=https://xxxx-yyyy.trycloudflare.com
```
Then restart: `docker compose restart wordpress`.

For a stable URL with your own domain, set up a named Cloudflare Tunnel:
1. Cloudflare account → Zero Trust → Networks → Tunnels → Create
2. Install connector on Oracle VM via `cloudflared service install <token>`
3. Add public hostname → service `http://wordpress:80`
4. Set `.env` `WP_HOME` to your real domain and restart

---

## Migrating data from local dev

If you already have bookings / pages / users in your local docker (`pb_wp` + `pb_db`):

```bash
./migrate-from-dev.sh opc@<oracle-ip>
```

This:
1. Dumps local DB
2. Copies wp-content (excluding the baked-in plugin/theme)
3. Imports into the prod stack
4. Restores the baked-in plugin/theme over the imported versions
5. Runs wp search-replace to update URLs

---

## Backups

Manual:
```bash
# DB
docker compose exec db sh -c \
  'mysqldump -u root -p"$MARIADB_ROOT_PASSWORD" --all-databases' > backup_$(date +%F).sql

# Uploads
docker compose exec wordpress tar czf - /var/www/html/wp-content/uploads \
  > uploads_$(date +%F).tar.gz
```

Automated (cron on Oracle VM, daily 03:00 HKT, keeps 14 days):
```bash
(crontab -l 2>/dev/null; echo "0 3 * * * cd ~/pb && docker compose exec -T db sh -c 'mysqldump -u root -p\"\$MARIADB_ROOT_PASSWORD\" --all-databases' > ~/backups/db_\$(date +\\%F).sql && find ~/backups -name 'db_*.sql' -mtime +14 -delete") | crontab -
mkdir -p ~/backups
```

Offsite (rclone to Backblaze B2 free tier or Cloudflare R2):
```bash
sudo dnf install -y rclone
rclone config   # follow prompts
rclone sync ~/backups remote:my-bucket/piano-backups
```

---

## Updating

After you push changes to `piano-booking` (plugin) or `piano-theme`:
```bash
# On dev machine
cp -r ../piano-booking/wp-content/plugins/piano-booking wp-content/plugins/
cp -r ../piano-booking/wp-content/themes/piano-theme wp-content/themes/
git add wp-content && git commit -m "Sync plugin/theme"
git push   # triggers GitHub Actions → new image on ghcr.io

# On Oracle VM
docker compose pull
docker compose up -d
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Site returns 502 Bad Gateway | PHP-FPM crashed or DB unreachable | `docker compose logs wordpress db` |
| DB container restarting | Bad root password (mismatched .env) | Edit `.env`, `docker compose up -d` |
| WordPress redirects to localhost:8080 | Search-replace not run | `docker compose exec wordpress wp search-replace 'http://localhost:8080' "$WP_HOME" --allow-root` |
| Image pull fails (denied) | ghcr.io PAT missing `read:packages` | Re-create PAT at github.com/settings/tokens |