#!/bin/bash
# Quickstart script for Oracle Cloud Always Free ARM VM (Oracle Linux 9).
#
# Run as:  bash oracle-quickstart.sh
#
# What it does:
#   1. Install Docker + compose plugin
#   2. Open firewall ports 80 / 443
#   3. Pull + run the WordPress + MariaDB stack
#
# Prerequisites:
#   - You created the .env file in the same directory as this script
#     (use the values you generated; .env.example is a template).

set -euo pipefail

GITHUB_USER="${GITHUB_USER:-CHANGE_ME}"
REPO_DIR="${REPO_DIR:-$(cd "$(dirname "$0")" && pwd)}"

echo "=== 1. Install Docker ==="
sudo dnf install -y dnf-utils
sudo dnf config-manager --add-repo https://download.docker.com/linux/rhel/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker "$USER"
echo "✅ Docker installed: $(docker --version)"

echo "=== 2. Open firewall ==="
if command -v firewall-cmd >/dev/null; then
  sudo firewall-cmd --permanent --add-service=http
  sudo firewall-cmd --permanent --add-service=https
  sudo firewall-cmd --reload
  sudo firewall-cmd --list-all | head -10
fi
# NOTE: also need to open ports in Oracle Cloud VCN security list (done in console).

echo "=== 3. Verify .env exists ==="
if [ ! -f "$REPO_DIR/.env" ]; then
  echo "❌ Missing .env — copy .env.example to .env and fill in real passwords."
  exit 1
fi
set -a; . "$REPO_DIR/.env"; set +a
: "${WORDPRESS_DB_PASSWORD:?Set WORDPRESS_DB_PASSWORD in .env}"
: "${MARIADB_ROOT_PASSWORD:?Set MARIADB_ROOT_PASSWORD in .env}"

echo "=== 4. Update image reference ==="
sed -i.bak "s|ghcr.io/CHANGE_ME/|ghcr.io/${GITHUB_USER}/|" "$REPO_DIR/docker-compose.yml"

echo "=== 5. Login to ghcr.io + pull image ==="
echo "You'll need a GitHub Personal Access Token (read:packages scope) for the next step."
echo "Get one at: https://github.com/settings/tokens/new (scopes: read:packages)"
echo ""
echo -n "GitHub username: "; read -r GH_USER
echo -n "GitHub PAT (read:packages): "; read -rs GH_TOKEN; echo
echo "$GH_TOKEN" | docker login ghcr.io -u "$GH_USER" --password-stdin

docker compose -f "$REPO_DIR/docker-compose.yml" pull
docker compose -f "$REPO_DIR/docker-compose.yml" up -d

echo ""
echo "=== ✅ Stack is up ==="
echo "  WordPress: http://$(curl -s ifconfig.me 2>/dev/null || echo '<public-ip>')/"
echo "  Logs:      docker compose -f $REPO_DIR/docker-compose.yml logs -f wordpress"
echo ""
echo "Next: open a Cloudflare Tunnel for HTTPS — see README.md"