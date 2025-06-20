# dadaAntihack

**dadaAntihack** is a lightweight early-request filter designed for use in the Manifesto CMS or similar frameworks. It blocks malicious input patterns (SQL injection, XSS, etc.) before the app boots.

## Features

- Pluggable config file
- Blocks based on HTTP vector (GET, POST, QUERY_STRING, etc.)
- Sends appropriate HTTP status codes and messages

## Usage

```php
use dadaTypo\dadaAntihack\Antihack;

require 'vendor/autoload.php';
$firewall = new DadaAntihack(require '../rules/config.php');
$firewall->inspect($_SERVER, $_GET, $_POST);
```

## Config

Edit `config/rules.php` to add your own patterns.
