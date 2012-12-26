<?php
// index.php



class GitHubMarkdownRender {

	const API_URL = 'https://api.github.com/markdown/raw';
	const CONTENT_TYPE = 'text/x-markdown';
	const USER_AGENT = 'magnetikonline/ghmarkdownrender 1.0';
	const MARKDOWN_EXT = '.md';
	const CACHE_SESSION_KEY = 'ghmarkdownrender';

	const GITHUB_USERNAME = 'username';
	const GITHUB_PASSWORD = 'password';
	const DOC_ROOT = '/path/to/docroot';



	public function execute() {

		// validate DOC_ROOT exists
		if (!is_dir(self::DOC_ROOT)) {
			$this->renderErrorMessage(
				'<p>Given <strong>DOC_ROOT</strong> of <strong>' . htmlspecialchars(self::DOC_ROOT) . '</strong> ' .
				'is not a valid directory, ensure it matches that of your local web server.</p>'
			);

			return;
		}

		// get requested local markdown page and check file exists
		if (($markdownFilePath = $this->getRequestedPageFilePath()) === false) {
			$this->renderErrorMessage(
				'<p>Unable to determine requested Markdown page.</p>' .
				'<p>URI must end with an <strong>' . self::MARKDOWN_EXT . '</strong> file extension.</p>'
			);

			return;
		}

		if (!is_file($markdownFilePath)) {
			// can't find markdown file on disk
			$this->renderErrorMessage(
				'<p>Unable to open <strong>' . htmlspecialchars($markdownFilePath) . '</strong></p>' .
				'<p>Ensure <strong>DOC_ROOT</strong> matches that of your local web server.</p>'
			);

			return;
		}

		// check PHP session for cached markdown response
		$html = $this->getMarkdownHtmlFromCache($markdownFilePath);
		if ($html !== false) {
			// render markdown HTML from cache
			echo(
				$this->getHtmlPageHeader() .
				$html .
				$this->getHtmlPageFooter('Rendered from cache')
			);

			return;
		}

		// make request to GitHub API passing markdown file source
		$response = $this->parseGitHubMarkdownResponse(
			$this->doGitHubMarkdownRequest(file_get_contents($markdownFilePath))
		);

		if (!$response['ok']) {
			// error calling API
			$this->renderErrorMessage(
				'<p>Unable to access GitHub API</p>' .
				'<ul>' .
					'<li>Check your <strong>GITHUB_USERNAME</strong> and <strong>GITHUB_PASSWORD</strong> are correct</li>' .
					'<li>Is GitHub/GitHub API endpoint <strong>' . htmlspecialchars(self::API_URL) . '</strong> accessable?</li>' .
					'<li>Has rate limit been exceeded? If so, wait until next hour</li>' .
				'</ul>'
			);

			return;
		}

		// save markdown HTML back to cache
		$this->setMarkdownHtmlToCache($markdownFilePath,$response['html']);

		// render markdown HTML from API response
		echo(
			$this->getHtmlPageHeader() .
			$response['html'] .
			$this->getHtmlPageFooter(
				'Rendered from GitHub Markdown API. ' .
				'<strong>Rate limit:</strong> ' . $response['rateLimit'] . ' // ' .
				'<strong>Rate remain:</strong> ' . $response['rateRemain']
			)
		);
	}

	private function getRequestedPageFilePath() {

		// get request URI, strip any querystring from end (used to trigger Markdown rendering from web server rewrite rule)
		$requestURI = trim($_SERVER['REQUEST_URI']);
		$requestURI = preg_replace('/\?.+$/','',$requestURI);

		// request URI must end with self::MARKDOWN_EXT
		return (preg_match('/\\' . self::MARKDOWN_EXT . '$/',$requestURI))
			? self::DOC_ROOT . $requestURI
			: false;
	}

	private function renderErrorMessage($errorHtml) {

		echo(
			$this->getHtmlPageHeader() .
			'<h1>Error</h1>' .
			$errorHtml .
			$this->getHtmlPageFooter()
		);
	}

	private function getHtmlPageHeader() {

		return <<<EOT
<!DOCTYPE html>

<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>GitHub Markdown render</title>

	<style>
		body {
			background: #fff;
			color: #333;
			font: 14px/1.6 Helvetica,arial,freesans,clean,sans-serif;
			margin: 20px;
			padding: 0;
		}

		#frame {
			background: #eee;
			border-radius: 3px;
			margin: 0 auto;
			padding: 3px;
			width: 914px;
		}

		#markdown {
			background: #fff;
			border: 1px solid #cacaca;
			padding: 30px;
		}

		#markdown > :first-child {
			margin-top: 0;
		}

		#markdown > :last-child {
			margin-bottom: 0;
		}

		h1,h2,h3,h4,h5,h6 {
			font-weight: bold;
			margin: 20px 0 10px;
			padding: 0;
		}

		h1 {
			color: #000;
			font-size: 28px;
		}

		h2 {
			border-bottom: 1px solid #ccc;
			color: #000;
			font-size: 24px;
		}

		h3 {
			font-size: 18px;
		}

		h4 {
			font-size: 18px;
		}

		h5,h6 {
			font-size: 14px;
		}

		h6 {
			color: #777;
		}

		#markdown > h1:first-child,
		#markdown > h2:first-child,
		#markdown > h1:first-child + h2,
		#markdown > h3:first-child,
		#markdown > h4:first-child,
		#markdown > h5:first-child,
		#markdown > h6:first-child {
			margin-top: 0;
		}

		blockquote,dl,ol,p,pre,table,ul {
			border: 0;
			margin: 15px 0;
			padding: 0;
		}

		ul,ol {
			padding-left: 30px;
		}

		ol li > :first-child,
		ol li ul:first-of-type,
		ul li > :first-child,
		ul li ul:first-of-type {
			margin-top: 0;
		}

		ol ol,ol ul,ul ol,ul ul {
			margin-bottom: 0;
		}

		h1 + p,h2 + p,h3 + p,h4 + p,h5 + p,h6 + p {
			margin-top: 0;
		}

		table {
			border-collapse: collapse;
			border-spacing: 0;
			font-size: 100%;
			font: inherit;
		}

		table tr {
			border-top: 1px solid #ccc;
			background-color: #fff;
		}

		table tr:nth-child(2n) {
			background-color: #f8f8f8;
		}

		table th,
		table td {
			border: 1px solid #ccc;
			padding: 6px 13px;
		}

		table th {
			font-weight: bold;
		}

		pre,code,tt {
			font-family: Consolas,"Liberation Mono",Courier,monospace;
			font-size: 12px;
		}

		code,tt {
			background-color: #f8f8f8;
			border-radius: 3px;
			border: 1px solid #eaeaea;
			margin: 0 2px;
			padding: 0 5px;
		}

		pre {
			background-color: #f8f8f8;
			border-radius: 3px;
			border: 1px solid #ccc;
			font-size: 13px;
			line-height: 19px;
			overflow: auto;
			padding: 6px 10px;
		}

		pre > code,pre > tt {
			background: transparent;
			border: 0;
			margin: 0;
			padding: 0;
		}

		pre > code {
			white-space: pre;
		}

		a {
			color: #4183c4;
			text-decoration: none;
		}

		a:hover {
			text-decoration: underline;
		}

		#footer {
			color: #777;
			font-size: 11px;
			margin: 10px auto;
			text-align: right;
			white-space: nowrap;
			width: 914px;
		}
	</style>
</head>

<body>

<div id="frame"><div id="markdown">
EOT;
	}

	private function getHtmlPageFooter($footerMessageHtml = false) {

		return
			'</div></div>' .
			(($footerMessageHtml !== false)
				? '<p id="footer">' . $footerMessageHtml . '</p>'
				: ''
			) .
			'</body></html>';
	}

	private function getMarkdownHtmlFromCache($markdownFilePath) {

		// start session, look for file path in session space
		session_start();
		if (!isset($_SESSION[self::CACHE_SESSION_KEY][$markdownFilePath])) return false;

		// file path exists - compare file modification time to that in cache
		$cacheData = $_SESSION[self::CACHE_SESSION_KEY][$markdownFilePath];
		return ($cacheData['timestamp'] == filemtime($markdownFilePath))
			? $cacheData['html']
			: false;
	}

	private function setMarkdownHtmlToCache($markdownFilePath,$html) {

		if (!isset($_SESSION[self::CACHE_SESSION_KEY])) {
			// create new session cache structure
			$_SESSION[self::CACHE_SESSION_KEY] = [];
		}

		$_SESSION[self::CACHE_SESSION_KEY][$markdownFilePath] = [
			'timestamp' => filemtime($markdownFilePath),
			'html' => $html
		];
	}

	private function doGitHubMarkdownRequest($markdownSource) {

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			[
				CURLOPT_HEADER => true,
				CURLOPT_HTTPHEADER => [
					'Content-Type: ' . self::CONTENT_TYPE,
					'User-Agent: ' . self::USER_AGENT
				],
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $markdownSource,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_URL => self::API_URL,
				CURLOPT_USERPWD => sprintf('%s:%s',self::GITHUB_USERNAME,self::GITHUB_PASSWORD)
			]
		);

		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}

	private function parseGitHubMarkdownResponse($response) {

		$seenHeader = false;
		$httpStatusOk = false;
		$rateLimit = 0;
		$rateRemain = 0;

		while (true) {
			// seek next CRLF, if not found bail out
			$nextEOLpos = strpos($response,"\r\n");
			if ($nextEOLpos === false) break;

			// extract header line and pop off from $response
			$headerLine = substr($response,0,$nextEOLpos);
			$response = substr($response,$nextEOLpos + 2);

			if ($seenHeader && (trim($headerLine) == '')) {
				// end of HTTP headers, bail out
				break;
			}

			if (!$seenHeader && preg_match('/^[a-zA-Z-]+:/',$headerLine)) {
				// have seen a header item - able to bail out once next blank line detected
				$seenHeader = true;
			}

			if (preg_match('/^Status: (\d+)/',$headerLine,$match)) {
				// save HTTP response status, expecting 200 (OK)
				$httpStatusOk = (intval($match[1]) == 200);
			}

			if (preg_match('/^X-RateLimit-Limit: (\d+)$/',$headerLine,$match)) {
				// save total allowed request count
				$rateLimit = intval($match[1]);
			}

			if (preg_match('/^X-RateLimit-Remaining: (\d+)$/',$headerLine,$match)) {
				// save request count remaining
				$rateRemain = intval($match[1]);
			}
		}

		return [
			'ok' => ($httpStatusOk && $rateLimit && $rateRemain),
			'rateLimit' => $rateLimit,
			'rateRemain' => $rateRemain,
			'html' => $response
		];
	}
}


$gitHubMarkdownRender = new GitHubMarkdownRender();
$gitHubMarkdownRender->execute();
