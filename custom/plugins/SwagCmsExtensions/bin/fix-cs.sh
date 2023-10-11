#!/usr/bin/env bash
echo "Fix php files"
make ecs-fix

echo "Fix javascript files"
make administration-fix
make storefront-fix
