#!/bin/sh


if [ -z "$1" ]; then
   echo "Usage: $0 <github username>" >&2
   exit 1
fi

curl -u "$1" -d '{"scopes":[],"note":"ghmarkdownrender"}' \
        https://api.github.com/authorizations | grep token
