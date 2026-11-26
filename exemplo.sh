#!/usr/bin/env bash
set -e

docker compose run --rm server sh -c "composer install --no-interaction --quiet && php bin/alertas.php /workspace/exemplo.json"
