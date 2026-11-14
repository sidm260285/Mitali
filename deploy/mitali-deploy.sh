#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "usage: mitali-deploy s3://bucket/releases/<sha>.tar.gz" >&2
  exit 1
fi

ARTIFACT="$1"
SHA="$(basename "$ARTIFACT" .tar.gz)"
APP_ROOT=/var/www/mitali
RELEASES="$APP_ROOT/releases"
SHARED="$APP_ROOT/shared"
TARGET="$RELEASES/$SHA"
CURRENT="$APP_ROOT/current"
PHP_USER=apache

mkdir -p "$RELEASES"
rm -rf "$TARGET"
mkdir -p "$TARGET"

TMP="$(mktemp)"
aws s3 cp "$ARTIFACT" "$TMP"
tar -xzf "$TMP" -C "$TARGET"
rm -f "$TMP"

if [[ ! -f "$SHARED/.env" ]]; then
  echo "missing $SHARED/.env" >&2
  exit 1
fi

rm -rf "$TARGET/storage"
ln -sfn "$SHARED/.env" "$TARGET/.env"
ln -sfn "$SHARED/storage" "$TARGET/storage"
mkdir -p "$TARGET/bootstrap/cache" "$SHARED/storage/framework/cache/data" \
  "$SHARED/storage/framework/sessions" "$SHARED/storage/framework/views" \
  "$SHARED/storage/logs" "$SHARED/storage/app/public" "$SHARED/storage/app/private"

chown -R "$PHP_USER:$PHP_USER" "$TARGET" "$SHARED/storage"
find "$TARGET" -type d -exec chmod 755 {} +
find "$TARGET" -type f -exec chmod 644 {} +
chmod -R ug+rwx "$SHARED/storage" "$TARGET/bootstrap/cache"

cd "$TARGET"
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan view:cache
if [[ ! -e public/storage ]]; then
  php artisan storage:link
fi

if [[ -f "$TARGET/deploy/nginx-mitali.conf" ]]; then
  cp "$TARGET/deploy/nginx-mitali.conf" /etc/nginx/conf.d/mitali.conf
  nginx -t
fi

if [[ -f "$TARGET/deploy/mitali-queue.service" ]]; then
  cp "$TARGET/deploy/mitali-queue.service" /etc/systemd/system/mitali-queue.service
  systemctl daemon-reload
  systemctl enable mitali-queue
fi

ln -sfn "$TARGET" "$CURRENT"

systemctl reload php-fpm
systemctl reload nginx
systemctl restart mitali-queue

if [[ -f "$TARGET/deploy/mitali-deploy.sh" ]]; then
  cp "$TARGET/deploy/mitali-deploy.sh" /usr/local/bin/mitali-deploy
  chmod 755 /usr/local/bin/mitali-deploy
fi

mapfile -t OLD < <(ls -1dt "$RELEASES"/* 2>/dev/null | tail -n +4 || true)
if ((${#OLD[@]})); then
  rm -rf "${OLD[@]}"
fi

echo "deployed $SHA"
