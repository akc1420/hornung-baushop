#!/usr/bin/env bash
echo "Fix php files"
php ../../../vendor/bin/ecs check --fix --config=../../../easy-coding-standard.php src tests
php ../../../vendor/bin/ecs check --fix --config=easy-coding-standard.php

echo "Fix javascript files"
cd ../../.. && composer run npm:admin clean-install && composer run eslint:admin
