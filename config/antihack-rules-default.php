<?php
// Example rules for sites
return [
	// 'path' allows you to block requests that contain a particular string in the URL
	// e.g. any request that includes "wp-includes" is, in anything other than WP, a hacking attempt
	'path'=>[
		['s'=>'cgi-bin', 'code'=>404, 'log'=>false],
		['s'=>'\.env', 'code'=>404, 'log'=>false],
		['s'=>'\.git', 'code'=>404, 'log'=>false],
		['s'=>'/blog/', 'code'=>404, 'log'=>false],
		['s'=>'/_ignition/', 'code'=>404, 'log'=>false],
	],

	// REMOTE ADDRESS (User IP address) matches
	/* ----------------------------------------------------------------
	// Matches any part of the REMOTE_ADDR of the page request, e.g. "1\.2\.3\.4"
	//
	// YOU MUST ESCAPE CERTAIN CHARACTERS, NOTABLY "#" => "\#" and "." => "\."
	-------------------------------------------------------------------*/
	'ip'=>[
		['s'=>'^10\.0\.0\.', 'code'=>403, 'log'=>true],
	],

	// USER-AGENT matches
	/* ----------------------------------------------------------------
	// Matches any part of the USER-AGENT of the page request, e.g. "Googlebot"
	//
	// YOU MUST ESCAPE CERTAIN CHARACTERS, NOTABLY "#" => "\#" and "." => "\."
	-------------------------------------------------------------------*/
	'agent'=>[
		['s'=>'libwww-perl', 'code'=>403, 'log'=>true],
	],

	// REFERRER (referring page) matches
	/* ----------------------------------------------------------------
	// Matches any part of the REFERER of the page request
	-------------------------------------------------------------------*/
	'ref'=>[
		['s'=>'example\.[com|org]', 'code'=>403, 'log'=>true],
	],

	// QUERY STRING matches
	/* ----------------------------------------------------------------
	// Matches ANY PART of the QUERY_STRING e.g. "index.php?anything_here=the_query_string"
	-------------------------------------------------------------------*/
	'query'=>[
		['s'=>'\.passwd', 'code'=>403, 'log'=>true],
		['s'=>'\.env', 'code'=>403, 'log'=>true],
		['s'=>'select.*%20from', 'code'=>403, 'log'=>true],
	],

	// $_GET VALUES (Illegal GET values)
	/* ----------------------------------------------------------------
	// If a page request GET parameter value equals a value in this list,
	// the entire request will be rejected with HTTP status code 'code'
	//
	// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
	// ESCAPE CERTAIN CHARACTERS, NOTABLY "#" => "\#" and "." => "\."
	-------------------------------------------------------------------*/
	'get'=>[
		['s'=>'base64_decode', 'code'=>403, 'log'=>true, 'msg'=>'Shell command attempt'],
		['s'=>'\.\./', 'code'=>'404', 'log'=>true, 'msg'=>'File system hack'],
		['s'=>'https?://', 'code'=>'404', 'log'=>false],
		['s'=>'ftp://', 'code'=>'404', 'log'=>false],
		// Stupid WP attempts
		['s'=>'wp-', 'code'=>'404', 'log'=>true, 'msg'=>'File system hack'],
		// One should never be passing web server root path parameters either
		['s'=>'/var/www/html', 'code'=>'404', 'log'=>true, 'msg'=>'File system hack'],
		// An easy way to protect against attempts at executing a remote download
		['s'=>'wget', 'code'=>'404', 'log'=>true, 'msg'=>'Shell command attempt'],
		// An easy way to protect against attempts executing shell commands
		['s'=>'passthru', 'code'=>'404', 'log'=>true, 'msg'=>'Shell command attempt'],
		// An easy way to protect against attempts at reaching /etc/passwd
		['s'=>'passwd', 'code'=>'404', 'log'=>true, 'msg'=>'File system hack'],
		// An easy way to protect against attempts at SQL injection
		['s'=>'union%20select', 'code'=>'404', 'log'=>true, 'msg'=>'SQL Injection hack'],
	],

	// $_GET NAME BLACKLIST (Illegal GET parameter names)
	/* ----------------------------------------------------------------
	// If a page request GET parameter NAME equals a value in this list,
	// the entire request will be rejected with HTTP status code 'code'
	// Used when parameter name should NEVER appear in normal usage
	-------------------------------------------------------------------*/
	'get_blacklist'=>[
//			Block any request that includes Google Analytics tracking parameters (unless you've set them up!)
//			['s'=>'^__utm', 'code'=>403, 'log'=>false, 'msg'=>'Google Analytics tracking not permitted'],
	],

	// $_GET NAME WHITELIST (Only allowed GET parameter names)
	/* ----------------------------------------------------------------
	// If a page request GET parameter NAME does NOT equal a value in this list,
	// the entire request will be rejected with HTTP status code 'code'
	-------------------------------------------------------------------*/
	'get_whitelist'=>[
//			If 'id', 'page' and 'sort' should be the only GET params ever...
//			['s'=>'^(id|page|sort)$', 'code'=>403, 'log'=>true],
	],

	// $_POST VALUES (Illegal POST values)
	/* ----------------------------------------------------------------
	// If a page request POST parameter value equals a value in this list,
	// the entire request will be rejected with HTTP status code 'code'
	-------------------------------------------------------------------*/
	'post'=>[
		[
			// Never allow <script> as part of a submitted value
			's'=>'<script>',
			'code'=>403,
			'log'=>true,
			'msg'=>'Function or tag not permitted in submission'
		],
		[
			// Never allow eval() as part of a submitted value
			's'=>'eval\(',
			'code'=>403,
			'log'=>true,
			'msg'=>'Function or tag not permitted in submission'
		],
		[
			// Set a character limit of 50000 on "body" field submissions (change to suit your needs)
			'check'=>'length',
			'f'=>'body',
			'limit'=>50000,
			'code'=>403,
			'log'=>true,
			'msg'=>'POST too long'
		],
		[
			// Limit the number of URLs that can appear in the "summary" field (change to suit your needs)
			'check'=>'links',
			'f'=>'summary',
			'limit'=>3,
			'code'=>403,
			'log'=>true,
			'msg'=>'Too many URLs in POST summary'
		]
	]
];
