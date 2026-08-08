#!/usr/bin/env bash
#
# Deploys this app to an Ubuntu 24.04 server over SSH.
# Run from your local machine / CI: ./scripts/deploy.sh
#
# All provisioning (Apache2, PHP-FPM, MySQL, Certbot, Supervisor queue
# worker) and the actual deploy happen remotely via remote-provision.sh,
# piped over the SSH connection opened here. Re-running is safe: package
# installs, the vhost, the DB, and the Supervisor conf are all idempotent,
# and certbot skips renewal if the cert is still valid.

set -euo pipefail

# ---- Configuration — edit these for your server ---------------------------
SSH_HOST="159.223.42.143"
SSH_USER="root"
SSH_PORT="22"

APP_NAME="gender-reveal"
APP_DIR="/var/www/${APP_NAME}"
DOMAIN="gender.welory.com.my"
EMAIL="ryan.liew@welory.com.my"          # for Let's Encrypt renewal notices

REPO_URL="git@github.com:ryanliew/gender-reveal.git"
BRANCH="main"

DB_NAME="gender_reveal"
DB_USER="gender_reveal"
DB_PASS="genderreveal888"              # set a strong password before running
# -----------------------------------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Deploying ${APP_NAME} to ${SSH_USER}@${SSH_HOST}:${APP_DIR}"

ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" \
    "sudo env \
        APP_NAME='${APP_NAME}' \
        APP_DIR='${APP_DIR}' \
        DOMAIN='${DOMAIN}' \
        EMAIL='${EMAIL}' \
        REPO_URL='${REPO_URL}' \
        BRANCH='${BRANCH}' \
        DB_NAME='${DB_NAME}' \
        DB_USER='${DB_USER}' \
        DB_PASS='${DB_PASS}' \
        bash -s" < "${SCRIPT_DIR}/remote-provision.sh"

echo "==> Deploy complete: https://${DOMAIN}"
