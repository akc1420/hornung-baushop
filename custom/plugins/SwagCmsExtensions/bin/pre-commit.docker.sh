#!/usr/bin/env bash
set -euo pipefail

function compose_exec() {
    docker-compose -f ../../../docker-compose.yml exec -u "$(id -u):$(id -g)" -T -w /app/custom/plugins/SwagCmsExtensions app_server "$@"
}

compose_exec ./.git/hooks/pre-commit
compose_exec ./bin/static-analyze.sh
compose_exec ./bin/phpunit.sh
