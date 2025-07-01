# dadaAntihack

**dadaAntihack** is a lightweight early-request filter designed for use in the Manifesto CMS or similar frameworks. It blocks malicious input patterns (SQL injection, XSS, etc.) before the app boots, allowing you to block invalid requests without the overload of loading your entire application framework.

## Features

- Pluggable config file
- Blocks based on HTTP vector (GET, POST, QUERY_STRING, etc.)
- Sends appropriate HTTP status codes and messages

## Usage

```php
use dadaTypo\dadaAntihack\Antihack;

require 'vendor/autoload.php';
$firewall = new Antihack(require '../rules/config.php');
$firewall->inspect($_SERVER, $_GET, $_POST);
```

## Config

Edit `config/rules.php` to add your own patterns.

The **on_off*** setting is a simple toggle to disable running Antihack. You could also just remove or comment out the initialization code in your application.

The **log_file** setting allows you to configure a custom log file for dadaAntihack logging. It defaults to the current PHP error_log setting.

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

+ **Code**: Whether to respond with a 403 (Forbidden) or 404 (Page Not Found). Obvious hacking attempts usually deserve a 403, and mere nuisances like category-guessing usually respond with a 404.
+ **Passthrough**: In addition to sending an HTTP status code, you have the option of either returning whatever output your output sends after interpreting the request, or returning a blank page containing only a default message, e.g. "Blocked"
+ **Response**: The custom response message you want to show in place of the blocked request.

There are user-configurable default values for each of these, so you can define clear defaults and leave it at that, or you can customize the behavior on a per-rule basis.

### Rules

A ##rule## is an associative array that describes a regular expression string "s" to search for, and optional overrides to the default action to take. Rules are created _for_ a particular vector, e.g. PATH, and only applies to that vector. 

The full structure of a rule is 

`['s'=>'[regex to match]', 'code'=>[403|404], 'log'=>[boolean], 'msg'=>'[response string]'`

The **s** value is a regular expression (_excluding_ the outer delimiter), so be sure to escape things like slashes, but do take advantage of features like [0-9]+. The value in the rule is delimited (somewhat unusually) by a # hash symbol, which means you do not need to escape forward slashes (but you must escape hash marks in your "s" value).

For example, 

`['s'=>'/wp-includes', 'code'=>403, 'log'=>false, 'msg'=>'This is not WordPress']`

This rule would be matched if it encountered a URL like

`https://www.example.com/wp-includes/cache.php`

In this case, dadaAntihack would issue a 403 Forbidden status code, and would either

1. Return a bare bones HTML document reading <h1>This is not WordPress</h1>
if "passthrough" is set to **false** for the _path_ vector, or
2. If "passthrough" is set to true, dadaAntihack will still return the 403 status code, but it will still display whatever 404 page is configured for your site, assuming there is no `wp-includes/cache.php` file on your site.

Because "log" is set to _false_, the block will not be logged in the error log, since it is simply a nuisance skript kiddie request. Don't waste your bits.

The config file is full of examples, most of which are active, so definitely review them before implementing the system (especially if you're using WordPress).

The GET whitelist and GET blacklist cannot, of course, be used at the same time. If there _is_ one or more rules in the GET blacklist, they will be used, and the whitelist will be skipped entirely.

The POST values vector has a couple of unique rule elements:

The ##check## element, if present, may be set to "empty" or "length" or "links". If any of those are set, then "s" is no longer a string to match, but the name of a POST field, e.g. "body" or "first_name".

+ If "check" is set to "empty" field named by "s" must ##not be empty##. If it is empty, the request is blocked.
+ If "check" is "length", the character length of the value of the field named by "s" is evaluated. If the length exceeds the value defined by a "limit" element in the rule, e.g. 8000. If no "limit" is defined, dadaAntihack defaults to a 5000 character limit.
+ Finally, if the check is "links", dadaAntihack will parse the value of the field named by "s", and will pattern-match to find URLs in the content. If the number of URLs in the content exceeds the value defined by the "limit" element (e.g. 2), the request is blocked. This can be used to filter out spam comments, for example, that often include many links.

