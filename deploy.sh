#!/bin/bash
# Tự động kéo code mới từ GitHub và cập nhật ứng dụng (chạy bằng cron).
# Đặt file này ở thư mục gốc app (cạnh file "artisan").

# Bảo đảm cron tìm được git/php/flock (cron chạy với PATH tối giản)
export PATH="/usr/local/bin:/usr/local/sbin:/usr/bin:/bin:$PATH"

# Chống 2 lần chạy chồng nhau — chỉ khi có flock, không có thì bỏ qua (không chặn deploy)
if command -v flock >/dev/null 2>&1; then
    exec 9>"$HOME/deploy.lock"
    flock -n 9 || exit 0
fi

# Tự nhận thư mục app (nơi đặt deploy.sh) — không cần hardcode đường dẫn
APP="$(cd "$(dirname "$0")" && pwd)"
cd "$APP" || exit 1

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*"; }

# --- 1) Kéo code mới (không cần php) ---
git fetch origin main --quiet || { log "ERROR: git fetch thất bại (kiểm tra git/mạng)"; exit 1; }
git reset --hard origin/main --quiet

# --- 2) Xoá cache config/route bằng cách xoá file (KHÔNG cần php) ---
# Bảo đảm route/config mới có hiệu lực kể cả khi php artisan lỗi.
rm -f bootstrap/cache/config.php bootstrap/cache/routes.php

# --- 3) Tìm PHP CLI phù hợp (>= 8.0.2) ---
# Nếu biết chắc đường dẫn PHP, đặt vào PHP_BIN dưới đây để bỏ qua bước dò.
PHP_BIN=""
PHP=""
for cand in \
    "$PHP_BIN" \
    /usr/local/bin/ea-php84 /usr/local/bin/ea-php83 /usr/local/bin/ea-php82 /usr/local/bin/ea-php81 /usr/local/bin/ea-php80 \
    /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php81/root/usr/bin/php /opt/cpanel/ea-php80/root/usr/bin/php \
    /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /opt/alt/php81/usr/bin/php /opt/alt/php80/usr/bin/php \
    /usr/local/bin/php php
do
    [ -z "$cand" ] && continue
    command -v "$cand" >/dev/null 2>&1 || continue
    vid="$("$cand" -r 'echo PHP_VERSION_ID;' 2>/dev/null)"
    if [ -n "$vid" ] && [ "$vid" -ge 80002 ]; then
        PHP="$cand"
        break
    fi
done

if [ -z "$PHP" ]; then
    # Code + cache đã cập nhật ở bước 1-2; chỉ thiếu migrate.
    log "WARN: không tìm thấy PHP CLI >= 8.0.2. Đã kéo code + xoá cache; CHƯA chạy migrate. Hãy đặt PHP_BIN."
    exit 1
fi

# --- 4) Cập nhật ứng dụng bằng artisan ---
"$PHP" artisan migrate --force
"$PHP" artisan route:clear
"$PHP" artisan config:clear
"$PHP" artisan view:clear
"$PHP" artisan cache:clear

log "OK $(git rev-parse --short HEAD) (php: $PHP)"
