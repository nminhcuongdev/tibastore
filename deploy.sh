#!/bin/bash
# Tự động kéo code mới từ GitHub và cập nhật ứng dụng (chạy bằng cron).
# Đặt file này ở thư mục gốc app (cạnh file "artisan").

# Chống 2 lần chạy chồng nhau
exec 9>"$HOME/deploy.lock"
flock -n 9 || exit 0

# Tự nhận thư mục app (nơi đặt deploy.sh) — không cần hardcode đường dẫn
APP="$(cd "$(dirname "$0")" && pwd)"

# PHP CLI. Nếu "php" sai version, đổi thành đường dẫn đầy đủ, ví dụ:
# PHP=/opt/cpanel/ea-php81/root/usr/bin/php
PHP=php

cd "$APP" || exit 1

BEFORE=$(git rev-parse HEAD)
git fetch origin main --quiet
git reset --hard origin/main --quiet
AFTER=$(git rev-parse HEAD)

# Chỉ migrate + clear cache khi thực sự có commit mới
if [ "$BEFORE" != "$AFTER" ]; then
    "$PHP" artisan migrate --force
    "$PHP" artisan config:clear
    "$PHP" artisan view:clear
    "$PHP" artisan cache:clear
    echo "$(date '+%Y-%m-%d %H:%M:%S') deployed $AFTER"
fi
