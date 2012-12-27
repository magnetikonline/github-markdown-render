#!/bin/bash


if [ -z "$1" ]; then
	echo "Usage: $0 <github username>"
	exit
fi

curl -u "$1" -#d '{"scopes": [],"note": "GitHub Markdown render","note_url": "https://github.com/magnetikonline/ghmarkdownrender"}' \
	https://api.github.com/authorizations | grep token
