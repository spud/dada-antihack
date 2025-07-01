<?php
/* ----------------------------------------------------------------
// THIS IS THE DEFAULT SETTINGS FILE FOR DADAANTIHACK
// It is designed to provide a useful example of the config
// file structure, and the only active rules are uncontroversial
// skript kiddie blocks for proof-of-concept.
//
// Include this file (or your own version) when initializing
// the firewall with `new Antihack(include __DIR__.'antihack_rules.php')`
-------------------------------------------------------------------*/
return [

	// Alternate log file to use
	'log_file'=>ini_get('error_log'),

	// Default HTTP status codes to return, per test
	// "403" (returns a "403 Forbidden" code AND logs the user as abusive) or
	// "404" (returns a "404 Page Not Found" code)
	'code'=>[
		'default'		=>404,
		'path'			=>404,
		'ip'			=>403,
		'user_agents'	=>403,
		'referrers'		=>403,
		'remote_ips'	=>403,
		'get_values'	=>404,
		'get_blacklist'	=>403,
		'get_whitelist'	=>403,
		'post_values'	=>403,
	],

	// The sort of page you want to display to the user upon blockage
	// "false" => means that the user sees NO page other than the 'response' below
	// "true" => means that the user will see whatever page your site would have displayed without dadaAntihack
	// "/path/to/404.php" => If you specify a path on your site for a custom 404 page, that will be displayed
	// the first variable is the default for all the others if unspecified
	'passthrough'=>[
		'default'=>false,
		'path'=>false,
		'get_values'=>false,
		'get_whitelist'=>true,
	],

	// The text string on the next line will be returned as the only response to the user
	'response'=>[
		'default'=>'<h1>Your page request was not permitted.</h1>',
		'path'=>'Request path contained invalid strings',
		'ip'=>'This IP address has been blocked',
		'agent'=>'This User-Agent has been blocked',
		'ref'=>'Bad referrer',
		'query'=>'Request query string contained invalid strings',
	],

	'test'=>[

		// 'path' allows you to block requests that contain a particular string in the URL
		// e.g. any request that includes "wp-includes" is, in anything other than WP, a hacking attempt
		'path'=>[
//			['s'=>'wp-', 'code'=>404, 'log'=>false, 'msg'=>'This is not WordPress'],
//			['s'=>'xmlrpc\.php', 'code'=>404, 'log'=>false, 'msg'=>'This is not WordPress'],
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
				's'=>'body',
				'limit'=>50000,
				'code'=>403,
				'log'=>true,
				'msg'=>'POST too long'
			],
			[
				// Limit the number of URLs that can appear in the "summary" field (change to suit your needs)
				'check'=>'links',
				's'=>'summary',
				'limit'=>3,
				'code'=>403,
				'log'=>true,
				'msg'=>'Too many URLs in POST summary'
			]
		]
	]
];
