#!/bin/bash

#
# Wrapper for running docker-compose
#

PROGNAME="$(basename "$0")"
cd "$(dirname "$0")/.."

if test $? -ne 0; then
    echo "${PROGNAME}: could not cd to $(dirname $0)" >&2
    exit 1
fi

usage() {
    cat <<EOF
usage: $0 [OPTION]... -- [extra docker-compose]
EOF
}

help() {
    usage
    cat <<EOF
Options:
  --env ENV     Compose environment to run.
EOF
}

: "${ENV:=}"

while test $# -gt 0; do
    case $1 in
        # split --flag=arg
        --*=*)
            flag=${1/=*/}
            arg=${1/*=/}
            shift 1
            set -- ${flag} ${arg} "$@"
            ;;
        --env)
            ENV=$2
            shift 2
            ;;
        --help)
            help
            exit 0
            ;;
        --)
            shift 1
            break
            ;;
        *)
            echo "${PROGNAME}: Unknown option $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

if test -n "${ENV}"; then
    ENV_FILE=docker-compose.${ENV}.yml
    if ! test -e ${ENV_FILE}; then
        echo "${PROGNAME}: Unknown environment '${ENV}'" >&2
        exit 1
    fi

    set -- -f docker-compose.yml -f ${ENV_FILE} "$@"
fi

set -ex

docker-compose "$@"
