# GitHub Markdown render
Display [Markdown](http://github.github.com/github-flavored-markdown/) formatted documents on your local development web server using GitHub's [Markdown Rendering API](http://developer.github.com/v3/markdown/) and CSS to mimic the visual display on GitHub itself.

Handy for authoring/previewing `README.md` files (or any Markdown for that matter) in project repositories, hopefully avoiding noisy `git push` actions in commit logs due to excessive typos/errors.

**Note:** this is intended for local development only, probably not a good idea for production use due to GitHub API rate limits per user.

## Requires
- PHP 5.4+
- [PHP cURL extension](http://php.net/manual/en/book.curl.php) (more than likely already part of your PHP install)
- Nginx or Apache URL rewrite support

## Usage
Your project(s) Markdown files are accessible on your local web server in plain text, for example:

	http://localhost/projects/ghmarkdownrender/README.md
	http://localhost/projects/thummer/README.md
	http://localhost/projects/unrarallthefiles/README.md
	http://localhost/projects/webserverinstall.ubuntu12.04/install.md

To view rendered Markdown using the same parsing and styling as GitHub project pages, request files with querystring switch:

	http://localhost/projects/ghmarkdownrender/README.md?ghmd
	http://localhost/projects/thummer/README.md?ghmd
	http://localhost/projects/unrarallthefiles/README.md?ghmd
	http://localhost/projects/webserverinstall.ubuntu12.04/install.md?ghmd

Rendered HTML is cached in a PHP session based on markdown file modification time to reduce repeated GitHub API calls for the same file content.

## Install

### Configure index.php
Generate a new [GitHub OAuth token](http://developer.github.com/v3/oauth/#create-a-new-authorization) using either:
- The supplied [generatetoken.sh](generatetoken.sh) bash script.
- Directly from the [Personal access tokens](https://github.com/settings/tokens) settings page for your GitHub account - click **Generate new token**, no scope permissions are required.

Make a note of the token generated.

Update the following constants at the top of `index.php` in the `GitHubMarkdownRender` class:

<table>
	<tr>
		<td>GITHUB_PERSONAL_ACCESS_TOKEN</td>
		<td>Your generated GitHub personal access token. Anonymous GitHub API calls are <a href="http://developer.github.com/v3/#rate-limiting">limited to 60 per hour</a>, providing user credentials ramps this up to a more usable 5000 requests per hour.</td>
	</tr>
	<tr>
		<td>DOCUMENT_ROOT</td>
		<td>Your local web server document root. Assuming you are serving up all your project(s) directories over your default virtual host.</td>
	</tr>
</table>

### Setup URL rewrite rules
Next, setup URL rewrite for your default virtual host so all requests to `/local/path/*.md?ghmd` are rewritten to `/path/to/ghmarkdownrender/index.php`. Refer to the supplied `rewrite.nginx.conf` & `rewrite.apache.conf` for examples.

**Note:**
- You may want to have requested raw Markdown files (e.g. `http://localhost/projects/ghmarkdownrender/README.md`) served up with a MIME type such as `text/plain` for convenience.
	- Nginx by default serves up unknown file types based on extension as `application/octet-stream`, forcing a browser download - see `/etc/nginx/mime.types` and modify to suit.
- I haven't had a chance to test `rewrite.apache.conf` it should do the trick, would appreciate a pull-request if it needs fixing.

### Test
You should now be able to call a Markdown document with a querystring of `?ghmd` to receive a familiar GitHub style Markdown display. The page footer will also display the total/available API rate limits, or if rendering was cached based on file modification time.

## CSS style issues
Markdown display CSS has been lifted (deliberately) from GitHub.com. It's quite possible/likely there are some GitHub markdown CSS styles missing to make this complete.

If anything missing is noted with your own markdown documents, it would be great to get any source examples or pull requests (add your example(s) to [test.md](test.md)) to help make things complete.
