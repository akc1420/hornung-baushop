#!/bin/bash

PATTERN='shopware\.config'
RESULT=$(grep -inRw "${PATTERN}" --include=\*.twig ./src/Resources/views/storefront)

if [[ -n "$RESULT" ]]; then
    echo -e "\e[1;101mFound old config handling\e[0m"
    grep -inRw "${PATTERN}" --include=\*.twig ./src/Resources/views/storefront | awk -F":" '{print "\033[1;37m"$1"\n\033[0;31m"$2":\t"$3"\033[0m\n"}'
    exit 1
else
    echo "Everything is A-OK"
fi