#!/bin/bash
# Tự động kéo code mới từ GitHub và cập nhật ứng dụng (chạy bằng cron).
# Đặt file này ở thư mục gốc app (cạnh file "artisan").

# Chống 2 lần chạy chồng nhau
exec 9>"$HOME/deploy.lock"
flock -n 9 2>/dev/null || exit 0

# Tự nhận thư mục app (nơi đặt deploy.sh) — không cần hardcode đường dẫn
APP="$(cd "$(dirname "$0")" && pwd)"
cd "$APP" || exit 1

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*"; }

# --- Tìm PHP CLI phù hợp (>= 8.0.2) ---
# Nếu bạn biết chắc đường dẫn PHP, đặt vào biến PHP_BIN dưới đây để bỏ qua bước dò.
PHP_BIN=""
PHP=""
for cand in \
    "$PHP_BIN" \
    /usr/local/bin/ea-php84 /usr/local/bin/ea-php83 /usr/local/bin/ea-php82 /usr/local/bin/ea-php81 /usr/local/bin/ea-php80 \
    /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php81/root/usr/bin/php /opt/cpanel/ea-php80/root/usr/bin/php \
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
    log "ERROR: không tìm thấy PHP CLI >= 8.0.2. Hãy đặt PHP_BIN trong deploy.sh."
    exit 1
fi

# --- Kéo code mới ---
git fetch origin main --quiet || { log "ERROR: git fetch thất bại"; exit 1; }
git reset --hard origin/main --quiet

# --- Cập nhật ứng dụng (migrate an toàn, idempotent) ---
"$PHP" artisan migrate --force
"$PHP" artisan config:clear
"$PHP" artisan view:clear
"$PHP" artisan cache:clear

log "OK $(git rev-parse --short HEAD) (php: $PHP)"
