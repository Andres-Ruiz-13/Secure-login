#!/usr/bin/env bash
FECHA=$(date +%Y-%m-%d)
DIR=auditorias/$FECHA
mkdir -p "$DIR"

php -v                         > "$DIR/php.txt"
composer --version             > "$DIR/composer.txt"
php artisan about              > "$DIR/artisan-about.txt" 2>&1
composer validate --strict     > "$DIR/validate.txt"      2>&1
composer outdated --direct     > "$DIR/outdated.txt"      2>&1
composer audit                 > "$DIR/audit.txt"         2>&1
composer audit --format=json   > "$DIR/audit.json"        2>&1
composer licenses              > "$DIR/licencias.txt"     2>&1

echo "Auditoría guardada en $DIR"
