#!/usr/bin/env bash
set -e

ficheiro="${1:-exemplo.json}"

docker compose run --rm server sh -c 'composer install --no-interaction --quiet && php bin/alertas.php "/workspace/$1"' sh "$ficheiro"
