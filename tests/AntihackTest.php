<?php
use PHPUnit\Framework\ExpectationFailedException;

require_once __DIR__.'/../src/Antihack.php';

function runFirewall(array $overrides = [], array $get = [], array $post = [], array $server = []): string {
	$config = array_replace_recursive(require __DIR__ . '/../config/rules.php', $overrides);
    $fw = new \DadaTypo\DadaAntihack\Antihack($config);

    ob_start();
    try {
        $fw->inspect(
            array_merge(['REMOTE_ADDR' => '127.0.0.1', 'REQUEST_METHOD' => 'GET'], $server),
            $get,
            $post
        );
    } catch (Throwable $e) {
        // Allow for fatal exit (i.e., after echo)
    }
    return ob_get_clean();
}

test('blocks WordPress URLs', function () {
    $output = runFirewall([], [], [], ['TEST_PATH' => '//news/wp-includes/wlwmanifest.xml']);
    expect($output)->toContain('WordPress');
});

test('blocks non-existent modules', function () {
    $output = runFirewall([], [], [], ['TEST_PATH' => '/blog/theapcblog/index.php?d=&limit_start=130']);
    expect($output)->toContain('invalid strings');
});

test('blocks SQL injection in query string', function () {
    $output = runFirewall([], ['id' => '1union%20select%20*%20FROM%20users']);
    expect($output)->toContain('SQL Injection');
});

test('blocks script tag in POST body', function () {
    $output = runFirewall([], [], ['comment' => '<script>alert(1)</script>'], ['REQUEST_METHOD' => 'POST']);
    expect($output)->toContain('not permitted');
});

test('blocks base64 in GET', function () {
    $output = runFirewall([], ['q' => 'base64_decode(ZWNobyB0ZXN0)']);
    expect($output)->toContain('command attempt');
});

test('blocks long POST body by length', function () {
    $longPost = str_repeat('A', 2000);
    $output = runFirewall([], [], ['body' => $longPost], ['REQUEST_METHOD' => 'POST']);
    expect($output)->toContain('too long');
});

test('blocks excessive URLs in POST', function () {
    $urls = 'http://a.com http://b.com http://c.com';
    $output = runFirewall([], [], ['body' => $urls], ['REQUEST_METHOD' => 'POST']);
    expect($output)->toContain('Too many URLs');
});

test('blocks libwww-perl user-agent', function () {
    $output = runFirewall([], [], [], ['HTTP_USER_AGENT' => 'libwww-perl']);
    expect($output)->toContain('blocked');
});

test('blocks forbidden referrer', function () {
    $output = runFirewall([], [], [], ['HTTP_REFERER' => 'http://evilsite.com']);
    expect($output)->toContain('Bad referrer');
});

test('blocks IP in blacklist', function () {
    $output = runFirewall([], [], [], ['REMOTE_ADDR' => '10.0.0.9']);
    expect($output)->toContain('blocked');
});
