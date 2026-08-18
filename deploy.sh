#!/bin/bash
# Tự động kéo code mới từ GitHub và cập nhật ứng dụng (chạy bằng cron).
# Đặt file này ở thư mục gốc app (cạnh file "artisan").

# Bảo đảm cron tìm được git/php/flock (cron chạy với PATH tối giản)
export PATH="/usr/local/bin:/usr/local/sbin:/usr/bin:/bin:$PATH"

# Chống 2 lần chạy chồng nhau — chỉ khi có flock
if command -v flock >/dev/null 2>&1; then
    exec 9>"$HOME/deploy.lock"
    flock -n 9 || exit 0
fi

# Tự nhận thư mục app (nơi đặt deploy.sh)
APP="$(cd "$(dirname "$0")" && pwd)"
cd "$APP" || exit 1

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*"; }

STATE="$HOME/.tibastore_deployed_head"

# --- 1) Kéo code mới ---
git fetch origin main --quiet || { log "ERROR: git fetch thất bại (kiểm tra git/mạng)"; exit 1; }
git reset --hard origin/main --quiet

CURRENT="$(git rev-parse --short HEAD)"
DEPLOYED="$(cat "$STATE" 2>/dev/null)"

# Không có commit mới -> thoát im lặng (không ghi log, không chạy artisan)
[ "$CURRENT" = "$DEPLOYED" ] && exit 0

# --- 2) Có commit mới: xoá cache config/route bằng rm (KHÔNG cần php) ---
rm -f bootstrap/cache/config.php bootstrap/cache/routes.php

# --- 3) Tìm PHP CLI phù hợp (>= 8.0.2) ---
# Ưu tiên PHP mặc định của tài khoản (khớp bản web); nếu < 8.0.2 sẽ tự dò tiếp.
PHP_BIN="/usr/local/bin/php"
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
    # Đã kéo code + xoá cache; chỉ thiếu migrate. KHÔNG ghi STATE -> lần sau tự thử lại.
    log "WARN: không tìm thấy PHP CLI >= 8.0.2 ($CURRENT). Đã kéo code + xoá cache; CHƯA migrate."
    exit 1
fi

# --- 4) Cập nhật ứng dụng bằng artisan ---
"$PHP" artisan migrate --force
"$PHP" artisan route:clear
"$PHP" artisan config:clear
"$PHP" artisan view:clear
"$PHP" artisan cache:clear

echo "$CURRENT" > "$STATE"
log "OK $CURRENT (php: $PHP)"
