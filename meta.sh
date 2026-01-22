#!/bin/sh
set -e

clean() {
    (cd apps/flutter; flutter clean)
    rm -rf server/vendor
}

check_server() {
    echo "Checking server formatting..."
    if [ ! -d vendor ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
    if [ ! -f .env ]; then
        cp .env.example .env
        php artisan key:generate
    fi
    ./vendor/bin/pint --test
    ./vendor/bin/phpstan analyse --memory-limit=2G
    php artisan test
}

check() {
    (cd server; check_server)
}

case "${1:-check}" in
    clean)
        clean
        ;;
    check)
        check
        ;;
    *)
        echo "Usage: $0 {clean|check}"
        exit 1
        ;;
esac
