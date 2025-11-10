# dadaAntihack

**dadaAntihack** is a lightweight early-request filter designed for use in the Manifesto CMS or similar frameworks. It blocks malicious input patterns (SQL injection, XSS, etc.) before the app boots, allowing you to block invalid requests without the overhead of loading your entire application framework. (For this reason, it cannot be implemented as a plugin, as it needs to precede the loading of the application framework).

## Features

- Pluggable config file
- Blocks based on HTTP vector (GET, POST, QUERY_STRING, etc.)
- Sends appropriate HTTP status codes and messages

## Standalone (WordPress or plain PHP) Usage

1. Download [dadaAntihack.php](standalone/dadaAntihack.php) and [rules.php](config/antihack-rules-default.php).
2. Upload both files to a folder in your web project (e.g., `wp-content/dada-antihack/`).
3. In your `wp-config.php` (WordPress) or early in your app’s entry point, add:

    ```
    require_once \__DIR\__ . "/wp-content/dada-antihack/dadaAntihack.php";
	$config = require \__DIR\__ . "/wp-content/dada-antihack/antihack-rules-default.php";
	$firewall = new dadaAntihack($config);
	$firewall->inspect($_SERVER, $_GET, $_POST);
	```

You may want to copy other rulesets from the `/config/` directory, or build your own.

## Via Composer ##

### 1. Install

    composer require dadatypo/dada-antihack

### 2. Copy the default configuration file

Never edit files inside `/vendor/`. Composer may overwrite your changes during updates. For the purposes of example, these docs assume the `config` directory is at the same level as the index or bootstrap file, and paths are relative to the bootstrap file. So from the root-level of your website

    cp vendor/dadatypo/dada-antihack/config/antihack-rules-default.php config/antihack-default-rules.php

resulting in

	index.php <-- or bootstrap file where Antihack is instantiated
	config/antihack-rules-default.php

### 3. (Optional) Install the admin config GUI

This admin GUI lets you edit firewall rules via a simple web interface.
It's **strongly** recommended to protect this folder with HTTP authentication.

    cp -r vendor/dadatypo/dada-antihack/admin/ antihack-admin/

### 4. Include your config and initialize the firewall

Add the following to your application bootstrap (The routes.php file of Manifesto CMS will automatically detect the presence of /site/dada-antihack-rules.php):

    require_once __DIR__ . '/vendor/autoload.php';
    $config = require __DIR__ . '/config/antihack-rules-default.php';
    $antihack = new \dadaTypo\dadaAntihack\Antihack($config);
    $antihack->inspect($_SERVER, $_GET, $_POST);

### Example: Combining rulesets

You can mix and match any rule files. For example:

```php
$firewall = new Antihack(
    require __DIR__ . '/config/antihack-rules-default.php',
    require __DIR__ . '/config/antihack-rules-owasp.php',
    require __DIR__ . '/config/antihack-rules-not-wordpress.php'
);
```

### 5. Access the admin GUI in your browser

The `index.php` file contains an HTTP username and password for basic security.
 **Open and edit the credentials to something of your own choosing.**

Visit:
`https://yourdomain.com/antihack-admin/index.php`


## Config File Structure

The admin GUI gives you an interface to generate the config file, but you can also simply edit the file manually. Create your own `/config/antihack-rules-mine.php` to add your own patterns. You can even maintain different sets of rules and load them selectively from your bootstrap file.

The **`log_file`** setting allows you to configure a custom log file for dadaAntihack logging. It defaults to the current PHP error_log setting.

dadaAntihack can analyze 8 vectors of attack:

+ **Path** values, meaning any string within the URL request
+ **IP Address**, which allows you to block specific IP addresses
+ **User Agent**, so you can block bad bots and crawlers
+ **Referer**, allowing you to block requests coming from a particular URL
+ **GET values**, the <em>value</em> of any $_GET parameter
+ **GET whitelist**, allowing you to restrict permissible $_GET parameter names
+ **GET blacklist**, allowing you to block requests containing certain $_GET parameter names
+ **POST values**, which analyzes submitted POST values and can block requests based on their content, length, or even the number of links contained within

Each vector can have its own set of rules, meaning you can configure dozens of checks on every page request.

When a dadaAntihack rule is matched, there are a few options you can configure for the manner of response:

+ **`code`**: Whether to respond with a 403 (Forbidden) or 404 (Page Not Found). Obvious hacking attempts usually deserve a 403, and mere nuisances like category-guessing usually respond with a 404.
+ **`passthrough`**: In addition to sending an HTTP status code, you have the option of either returning whatever output your server naturally sends after interpreting the request, or returning a blank page containing only a default message, e.g. "Blocked"
+ **`response`**: The custom response message you want to show in place of the blocked request message.

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

The `s` value is a regular expression (_excluding_ the outer delimiter), so be sure to escape things like slashes, but do take advantage of features like `[0-9]+` to match patterns. The regex in the rule uses the non-standard `#` hash symbol for a delimiter which means you do _not_ need to escape forward slashes (but you must escape hash marks in your `s` value).

For example,

`['s'=>'/wp-includes', 'code'=>403, 'log'=>false, 'msg'=>'This is not WordPress']`

This rule would be matched if it encountered a URL like

`https://www.example.com/wp-includes/cache.php`

In this case, dadaAntihack would issue a **`403 Forbidden`** status code, and would either

1. Return a bare bones HTML document reading <h3>This is not WordPress</h3>
if `passthrough` is set to **false** for the `path` vector, or

2. If `passthrough` is set to true, dadaAntihack will return the 403 status code, but it will still display whatever 404 template is configured for your site, since continuing to process the page as-is would have resulted in a 404 page anyway.

Because `log` is set to _false_, the block will not be logged in the error log, since it is simply a nuisance skript kiddie request. Don't waste the bits.

The config file is full of examples, most of which are active, so definitely review them before implementing the system (especially if you're using WordPress).

The GET whitelist and GET blacklist cannot, of course, be used at the same time. If there _is_ one or more rules in the GET blacklist, they will be used, and the whitelist will be skipped entirely.

The POST values vector has a couple of unique rule elements:

The **`check`** element, if present, may be set to "`empty`" or "`length`" or "`links`". If any of those are set, then `s` is no longer a string to match, but the name of a POST field, e.g. "`body`" or "`first_name`".

+ If **`check`** is set to "`empty`" field named by `s` must **not be empty**. If it is empty, the request is blocked.
+ If **`check`** is "`length`", the character length of the value of the field named by `s` is evaluated. If the length exceeds the value defined by a "`limit`" element in the rule, e.g. 8000. If no "`limit`" is defined, dadaAntihack defaults to a 5000 character limit.
+ Finally, if the check is "`links`", dadaAntihack will parse the value of the field named by `s`, and will pattern-match to find URLs in the content. If the number of URLs in the content exceeds the value defined by the "`limit`" element (e.g. 2), the request is blocked. This can be used to filter out spam comments, for example, that often include many links.

