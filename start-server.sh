#!/bin/bash
# اجرای دائمی سرور توسعه لاراول (کافی‌نت آنلاین) روی پورت 8000
# + زمان‌بند (schedule:work) برای موتور تخصیص سفارش (فاز ۶)
# اگر از قبل در حال اجرا باشند، کاری نمی‌کند.
#
# استفاده:
#   bash start-server.sh
# PHP پیش‌فرض از PATH؛ برای مسیر خاص: PHP_BIN=/usr/bin/php8.4 bash start-server.sh

set -u

PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
if [ -z "$PHP_BIN" ]; then
    echo "ERROR: php not found in PATH — set PHP_BIN manually" >&2
    exit 1
fi

# مسیر پروژه = همان پوشهٔ این اسکریپت
PROJECT="$(cd "$(dirname "$0")" && pwd)"
PORT="${PORT:-8000}"
LOG="$PROJECT/server.log"
SCHED_LOG="$PROJECT/scheduler.log"

cd "$PROJECT" || exit 1

# ۱) سرور وب
# فاز ۱۲ (Realtime SSE): هر جریان SSE یک پروسهٔ PHP اشغال می‌کند؛ سرور
# داخلی PHP به‌طور پیش‌فرض تک‌کاربر است و با چند اتصال هم‌زمان بلاک می‌شد.
# artisan serve متغیر PHP_CLI_SERVER_WORKERS را همیشه پاس نمی‌دهد → مستقیم
# php -S با فایل router خود لاراول اجرا می‌شود (روی پروداکشن PHP-FPM است
# و این اسکریپت فقط برای توسعه است).
if ! pgrep -f "php -S 0.0.0.0:$PORT" > /dev/null 2>&1; then
    # setsid -f باعث می‌شود پروسه از والد جدا شود و پس از پایان دستور زنده بماند
    # router فایل لاراول publicPath را از getcwd() می‌گیرد → CWD باید public باشد
    (
        cd "$PROJECT/public" || exit 1
        PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}" \
            setsid -f "$PHP_BIN" -S "0.0.0.0:$PORT" \
            "$PROJECT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" \
            > "$LOG" 2>&1 < /dev/null
    )
fi

# ۲) زمان‌بند (هر دقیقه: انقضای پخش سفارش‌ها + کارهای آتی)
if ! pgrep -f "artisan schedule:work" > /dev/null 2>&1; then
    setsid -f "$PHP_BIN" artisan schedule:work > "$SCHED_LOG" 2>&1 < /dev/null
fi

for i in $(seq 1 20); do
    if curl -s -o /dev/null --connect-timeout 1 "http://127.0.0.1:$PORT/up" 2>/dev/null; then
        echo "Laravel server ready on port $PORT (serve: $(pgrep -f "artisan serve.*--port=$PORT" | head -1), scheduler: $(pgrep -f "artisan schedule:work" | head -1))"
        exit 0
    fi
    sleep 1
done

echo "ERROR: Laravel server failed to start on port $PORT" >&2
exit 1
