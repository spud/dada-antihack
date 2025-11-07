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
	]

];
