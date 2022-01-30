# GitHub Markdown render

Display [Markdown](https://docs.github.com/en/get-started/writing-on-github) formatted documents on your local development web server using GitHub's [Markdown Rendering API](https://docs.github.com/en/rest/reference/markdown) and CSS to mimic the visual display on GitHub itself.

Handy for authoring/previewing `README.md` files (or any Markdown for that matter) in project repositories, hopefully avoiding noisy `git push` actions in commit logs due to excessive typos/errors.

**Note:** this is intended for local development only, probably not a good idea for production use due to GitHub API rate limits per user.

- [Requires](#requires)
- [Usage](#usage)
- [Install](#install)
	- [Configure `index.php`](#configure-indexphp)
	- [Setup URL rewrite rules](#setup-url-rewrite-rules)
	- [Test](#test)
- [CSS style issues](#css-style-issues)

## Requires

- PHP 5.4+
- [PHP cURL extension](https://www.php.net/manual/en/book.curl.php) - more than likely already part of your PHP install/compile.
- Nginx or Apache URL rewrite support.

## Usage

Markdown files are accessible from a local web server and returned in plain text, for example:

```
http://localhost/projects/ghmarkdownrender/README.md
http://localhost/projects/thummer/README.md
http://localhost/projects/unrarallthefiles/README.md
http://localhost/projects/webserverinstall.ubuntu12.04/install.md
```

To view rendered Markdown, request same URIs with a querystring switch:

```
http://localhost/projects/ghmarkdownrender/README.md?ghmd
http://localhost/projects/thummer/README.md?ghmd
http://localhost/projects/unrarallthefiles/README.md?ghmd
http://localhost/projects/webserverinstall.ubuntu12.04/install.md?ghmd
```

Rendered result is cached against the last modification time of each Markdown document to reduce repeated GitHub API calls for identical source content.

## Install

### Configure `index.php`

Generate a new [GitHub OAuth](https://developer.github.com/v3/oauth/) personal access token using either:

- The [generatetoken.sh](generatetoken.sh) bash script.
- Directly from the [Personal access tokens](https://github.com/settings/tokens) page within your GitHub account:
	- Click **Generate new token**.
	- No scope permissions are required.

Note down the token generated.

Update the following constants [within `index.php`](index.php#L11-L12) in the `GitHubMarkdownRender` class:

| Setting                        | Description                                                                                                                                                                                                                                   |
|:-------------------------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `GITHUB_PERSONAL_ACCESS_TOKEN` | Your generated GitHub personal access token. Anonymous GitHub API calls are [limited to 60 per hour](https://docs.github.com/en/rest/reference/rate-limit), providing user credentials ramps this up to a more usable 5000 requests per hour. |
| `DOCUMENT_ROOT`                | Web server document root location on the file system. Assumes you are serving up all your project(s) directories under a default virtual host.                                                                                                |

### Setup URL rewrite rules

- Configure a URL rewrite for your default virtual host so all requests to `/local/path/*.md?ghmd` are rewritten to `/path/to/ghmarkdownrender/index.php`.
- Refer to the supplied [`rewrite.nginx.conf`](rewrite.nginx.conf) & [`rewrite.apache.conf`](rewrite.apache.conf) for examples.

**Note:**

- You may wish to have requested raw Markdown files served up with a MIME type such as `text/plain` for convenience.
	- Nginx by default serves up unknown file types based on extension as `application/octet-stream`, forcing a browser download - see `/etc/nginx/mime.types` within your Nginx installation and modify to suit.
- Haven't tested `rewrite.apache.conf` - it should do the trick, would appreciate a pull-request if it needs fixing.

### Test

You should now be able to call a Markdown document with a querystring of `?ghmd` to receive a familiar GitHub style Markdown display. The page footer will also display the total/available API rate limits, or if rendering was returned from cache.

## CSS style issues

Markdown display CSS has been lifted (deliberately) from GitHub.com. It's quite possible/likely there are some CSS styles missing to make this complete.

If anything missing is noted with your own markdown documents, it would be great to get any source examples or pull requests (add your example(s) to [`test.md`](test.md)) to help make things complete.
