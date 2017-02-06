#!/bin/bash -e

API_VERSION_HEADER="Accept: application/vnd.github.v3+json"
API_ENDPOINT="https://api.github.com/authorizations"


if [[ -z $1 ]]; then
	echo "Usage: $(basename "$0") <GitHub username> <2FA code>"
	echo "Note: <2FA code> required only if enabled for given username"

	exit 1
fi

# note: https://developer.github.com/v3/oauth_authorizations/#create-a-new-authorization
postData="{\"scopes\": [],\"note\": \"GitHub Markdown render\",\"note_url\": \"https://github.com/magnetikonline/ghmarkdownrender\"}"

if [[ -n $2 ]]; then
	# username and 2FA code
	curl \
		--data "$postData" \
		--header "$API_VERSION_HEADER" \
		--header "X-GitHub-OTP: $2" \
		--request "POST" \
		--user "$1" \
		$API_ENDPOINT

else
	# username only
	curl \
		--data "$postData" \
		--header "$API_VERSION_HEADER" \
		--request "POST" \
		--user "$1" \
		$API_ENDPOINT
fi

echo "Save [token] value returned above"
