#!/bin/bash -e

if [[ -z $1 ]]; then
	echo "Usage: $(basename $0) <GitHub username>"
	exit 1
fi

curl \
	--user "$1" \
	--data '{"scopes": [],"note": "GitHub Markdown render","note_url": "https://github.com/magnetikonline/ghmarkdownrender"}' \
	"https://api.github.com/authorizations" | \
		grep "token"
