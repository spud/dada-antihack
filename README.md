# dadaAntihack

**dadaAntihack** is a lightweight early-request filter designed for use in the Manifesto CMS or similar frameworks. It blocks malicious input patterns (SQL injection, XSS, etc.) before the app boots, allowing you to block invalid requests without the overload of loading your entire application framework.

## Features

- Pluggable config file
- Blocks based on HTTP vector (GET, POST, QUERY_STRING, etc.)
- Sends appropriate HTTP status codes and messages

## Usage

### 1. Install via Composer

    composer require dadatypo/dada-antihack

### 2. Copy the default configuration file

Never edit files inside `/vendor/`. Composer may overwrite your changes during updates.

    cp vendor/dadatypo/dada-antihack/config/rules.php site/config/rules.php

### 3. (Optional) Install the admin config GUI

This lets you edit firewall rules via a simple web interface.  
It's strongly recommended to protect this folder with HTTP authentication.

    cp -r vendor/dadatypo/dada-antihack/admin/ site/dada-antihack-admin/

### 4. Include your config and initialize the firewall

Add the following to your application bootstrap (The routes.php file of Manifesto CMS will automatically detect the presence of /site/dada-antihack-rules.php):

    require_once __DIR__ . '/../vendor/autoload.php';
    $config = require __DIR__ . '/../site/config/dada-antihack-rules.php';
    $antihack = new \dadaTypo\dadaAntihack\dadaAntihack($config);
    $antihack->inspect($_SERVER, $_GET, $_POST);

### 5. Access the admin GUI in your browser

The `index.php` file contains an HTTP username and password for basic security. Open and edit the credentials to something of your own choosing.

Visit:  
`https://yourdomain.com/site/dada-antihack-admin/index.php`


## Config File Structure

The admin GUI gives you an interface to generate the config file, but you can also simply edit the file manually. Edit `/site/config/dada-antihack-rules.php`
to add your own patterns.

The **`on_off`** setting is a simple toggle to disable running Antihack. You could also just remove or comment out the initialization code in your application.

The **`log_file`** setting allows you to configure a custom log file for dadaAntihack logging. It defaults to the current PHP error_log setting.

dadaAntihack can analyze 8 vectors of attack: 

+ **Path** values, meaning any string within the URL request
+ **IP Address**, which allows you to block specific IP addresses
+ **User Agent**, so you can block bad bots and crawlers
+ **Referer**, allowing you to block requests coming from a particular URL
+ **GET values**, the <em>value</em> of any $_GET parameter
+ **GET whitelist**, allowing you to restrict permissible $_GET parameters
+ **GET blacklist**, allowing you to block requests containing certain $_GET parameters
+ **POST values**, which analyzes submitted POST values and can block requests based on their content, length, or even the number of links contained within

Each vector can have its own set of rules, meaning you can configure dozens of checks on every page request.

When a dadaAntihack rule is matched, there are a few options you can configure to respond:

+ **`code`**: Whether to respond with a 403 (Forbidden) or 404 (Page Not Found). Obvious hacking attempts usually deserve a 403, and mere nuisances like category-guessing usually respond with a 404.
+ **`passthrough`**: In addition to sending an HTTP status code, you have the option of either returning whatever output your output sends after interpreting the request, or returning a blank page containing only a default message, e.g. "Blocked"
+ **`response`**: The custom response message you want to show in place of the blocked request.

There are user-configurable default values for each of these, so you can define clear defaults and leave it at that, or you can customize the behavior on a per-rule basis.

### Rules

A **rule** is an associative array that describes a regular expression string "`s`" to search for, and optional overrides to the default action to take. Rules are created for a _specific_ vector, e.g. `path`, and only applies to that vector. 

The full structure of a rule is 

	[
	's' => '[regex to match]',
	'code' => 403|404,
	'log' => true|false,
	'msg' => '[response string]'
	]

The `s` value is a regular expression (_excluding_ the outer delimiter), so be sure to escape things like slashes, but do take advantage of features like `[0-9]+` to match patterns. The regex in the rule is delimited (somewhat unusually) by a `#` hash symbol, which means you do not need to escape forward slashes (but you must escape hash marks in your `s` value).

For example, 

`['s'=>'/wp-includes', 'code'=>403, 'log'=>false, 'msg'=>'This is not WordPress']`

This rule would be matched if it encountered a URL like

`https://www.example.com/wp-includes/cache.php`

In this case, dadaAntihack would issue a **`403 Forbidden`** status code, and would either

1. Return a bare bones HTML document reading <h3>This is not WordPress</h3>
if `passthrough` is set to **false** for the `path` vector, or

2. If `passthrough` is set to true, dadaAntihack will return the 403 status code, but it will still display whatever 404 page is configured for your site, since continuing to process the page resulted in a 404.

Because `log` is set to _false_, the block will not be logged in the error log, since it is simply a nuisance skript kiddie request. Don't waste your bits.

The config file is full of examples, most of which are active, so definitely review them before implementing the system (especially if you're using WordPress).

The GET whitelist and GET blacklist cannot, of course, be used at the same time. If there _is_ one or more rules in the GET blacklist, they will be used, and the whitelist will be skipped entirely.

The POST values vector has a couple of unique rule elements:

The **`check`** element, if present, may be set to "`empty`" or "`length`" or "`links`". If any of those are set, then `s` is no longer a string to match, but the name of a POST field, e.g. "`body`" or "`first_name`".

+ If **`check`** is set to "`empty`" field named by `s` must **not be empty**. If it is empty, the request is blocked.
+ If **`check`** is "`length`", the character length of the value of the field named by `s` is evaluated. If the length exceeds the value defined by a "`limit`" element in the rule, e.g. 8000. If no "`limit`" is defined, dadaAntihack defaults to a 5000 character limit.
+ Finally, if the check is "`links`", dadaAntihack will parse the value of the field named by `s`, and will pattern-match to find URLs in the content. If the number of URLs in the content exceeds the value defined by the "`limit`" element (e.g. 2), the request is blocked. This can be used to filter out spam comments, for example, that often include many links.

