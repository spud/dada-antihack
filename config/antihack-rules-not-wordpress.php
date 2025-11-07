<?php
// Example rules for sites NOT running WordPress
return [
	// Block any request for common WordPress core files/paths
	'path' => [
		['s'=>'wp-login\.php',     'code'=>404, 'log'=>true, 'msg'=>'No WP here'],
		['s'=>'wp-admin',          'code'=>404, 'log'=>true, 'msg'=>'No WP here'],
		['s'=>'wp-includes',       'code'=>404, 'log'=>true, 'msg'=>'No WP here'],
		['s'=>'wp-content',        'code'=>404, 'log'=>true, 'msg'=>'No WP here'],
		['s'=>'xmlrpc\.php',       'code'=>404, 'log'=>true, 'msg'=>'No WP here'],
		['s'=>'/blog/',            'code'=>404, 'log'=>false],
		['s'=>'wp-json',           'code'=>404, 'log'=>true, 'msg'=>'No WP REST API'],
	],
	'query' => [
		['s'=>'author=\d+',        'code'=>404, 'log'=>true, 'msg'=>'No authors'],
	],
	'get' => [
		['s'=>'wp-',               'code'=>404, 'log'=>true, 'msg'=>'WordPress probe'],
		['s'=>'xmlrpc',            'code'=>404, 'log'=>true, 'msg'=>'WordPress probe'],
	],
	// Block known bad user-agents (optional)
	'agent' => [
		['s'=>'WordPress',         'code'=>403, 'log'=>true],
	]
];
