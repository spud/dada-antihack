<?php
namespace dadaTypo\dadaAntihack;

/**
 * Antihack class
 * Refactored from procedural version to a modern, standalone class.
 * Uses unified nested configuration array.
 */
class Antihack {

	protected array $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Do the work
	 *
	 * @param array $server PHP $_SERVER array
	 * @param array $get PHP $_GET array
	 * @param array $post PHP $_POST array
	 * @return void
	 */
	public function inspect(array $server, array $get, array $post): void
	{

		// Master on/off toggle switch
		if (($this->config['on_off'] ?? 'on') !== 'on') return;

		// Check request path first
		if (!empty($this->config['test']['path'])) {
			$this->checkPath($server);
		}
		// Check remote IP addresses first
		$this->checkVector('ip', $server['REMOTE_ADDR'] ?? '');
		// Check User Agent next
		$this->checkVector('agent', $server['HTTP_USER_AGENT'] ?? '');
		// Check Referrers next
		$this->checkVector('ref', $server['HTTP_REFERER'] ?? '');
		// Check query strings next
		$this->checkVector('query', $this->decodeString($server['QUERY_STRING'] ?? ''));

		// For GET values, first look for matching values, then look for matching parameter names
		foreach ($get as $key => $val) {
			$this->checkVector('get', $this->decodeString($val));
		}

		// For GET whitelist, look at every GET parameter and reject the request if the parameter name is NOT on the whitelist
		if (!empty($this->config['test']['get_blacklist'])) {
			foreach (array_keys($get) as $key) {
				$this->checkVector('get_blacklist', $key);
			}
		} else {
			if (!empty($this->config['test']['get_whitelist'])) {
				foreach (array_keys($get) as $key) {
					$this->checkWhitelist('get_whitelist', $key);
				}
			}
		}
		
		// For POST values, first look for matching values, then look for special cases (too long, )
		if (($server['REQUEST_METHOD'] ?? '') === 'POST') {
			foreach ($post as $key => $val) {
				$this->checkVector('post', $this->decodeString($val));
				$this->checkPostSpecials($key,$val);
			}
		}
	}

	 /**
	 * Simple string matching for most checks
	 * Blocks the request when the tested string matches a rule
	 *
	 * @param array $type Which test to run
	 * @param array $value $_GET array
	 * @return void
	 **/
	protected function checkVector(string $type, string $value): void
	{
		$rules = $this->config['test'][$type] ?? [];
		// Convert a single string rule to array of rule
		if (is_string($rules)) {
			$rules = [['s' => $rules]];
		}
		foreach ($rules as $rule) {
			$s = '/'.$rule['s'].'/i';
			if (isset($rule['s']) && @preg_match($s, $value)) {
			   $this->block($rule, $type, $value);
			}
		}
	}

	 /**
	 * Simple string matching for request path
	 * Blocks the request when the tested string matches a rule
	 *
	 * @param array $value $_GET array
	 * @return void
	 **/
	protected function checkPath($server): void
	{
		$rules = $this->config['test']['path'] ?? [];
		// Convert a single string rule to array of rule
		if (is_string($rules)) {
			$rules = [['s' => $rules]];
		}
		$baseurl = '';
		if (isset($server['TEST_PATH'])) {
			$baseurl = $server['TEST_PATH'];
		} elseif (isset($server['SCRIPT_URL'])) {
			$baseurl = $server['SCRIPT_URL'];
		} elseif (isset($server['REQUEST_URI'])) {
			$baseurl = $server['REQUEST_URI'];
		} elseif (isset($server['REDIRECT_URL'])) {
			$baseurl = $server['REDIRECT_URL'];
		} elseif (isset($server['PHP_SELF'])) {
			$baseurl = $server['PHP_SELF'];
		}
		if ($baseurl === '') return;
		foreach ($rules as $rule) {
			$s = '/'.$rule['s'].'/i';
			if (isset($rule['s']) && @preg_match($s, $baseurl)) {
			   $this->block($rule, 'path', $baseurl);
			}
		}
	}

	protected function checkWhitelist(string $type, string $key): void {
		$rules = $this->config['test'][$type] ?? [];
		// Convert a single string rule to array of rule
		if (is_string($rules)) {
			$rules = [['s' => $rules]];
		}
		foreach ($rules as $rule) {
			$s = '#'.$rule['s'].'#i';
			if (!@preg_match($s, $key)) {
				$this->block($rule, $type, $key);
			}
		}
	}

	protected function checkPostSpecials(string $key, string $val): void {
		$rules = $this->config['test']['post'] ?? [];
		foreach ($rules as $rule) {
			// We're only checking special checks, so if this isn't special, it's already been handled
			if (!isset($rule['check'])) continue;

			// If this key should NOT be empty but is, block the request
			if ($rule['s'] == $key && $rule['check'] === 'empty' && empty($val)) {
				$this->block($rule, 'post_is_empty', $val);
			}

			// If this key should NOT exceed $rule['limit'] characters, but does, reject the request
			if ($rule['s'] == $key && $rule['check'] === 'length' && strlen($val) > ($rule['limit'] ?? 5000)) {
				$this->block($rule, 'post_length', $val);
			}

			// If this key should NOT contain more than $rule['limit'] URLs, but does, reject the request
			if ($rule['s'] == $key && $rule['check'] === 'links') {
				$s = '#https?://#i';
				$count = preg_match_all($s, $val, $m);
				if ($count > ($rule['limit'] ?? 1)) {
					$this->block($rule, 'post_links', $val);
				}
			}
		}
	}

	protected function block(array $rule, string $type, string $value): void {
	
		// Get default values or overrides
		$code = (int)($rule['code'] ?? $this->config['code'][$type] ?? $this->config['code']['default'] ?? 403);
		$msg  = $rule['msg'] ?? $this->config['response'][$type] ?? $this->config['response']['default'] ?? '<h1>Blocked</h1>';
		$pass = $this->config['passthrough'][$type] ?? $this->config['passthrough']['default'] ?? false;

		if (!empty($rule['log'])) {
			error_log("[Antihack] Blocked $type: value=$value | rule=" . ($rule['s'] ?? $rule['check'] ?? 'unknown'),3,$this->config['log_file']);
		}

		if (!$pass) {
			if (!headers_sent()) {
				http_response_code($code);
				header("Content-Type: text/html; charset=UTF-8");
			}
			echo 'Custom thing with msg '.$msg.PHP_EOL;
			echo htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5);
//			exit;
		}
	}

	protected function decodeString(string $str): string {
		$original = $str;
		$normalized = str_replace('%22', '___dt_quoteholder___', $original);
		$normalized = trim($normalized);
		$normalized = preg_replace('/\0+/', '', $normalized);
		$normalized = preg_replace('/(\\0)+/', '', $normalized);
		$normalized = str_replace('&', '&amp;', $normalized);
		$normalized = preg_replace('/&amp;([a-z][a-z0-9]{0,19});/i', '&$1;', $normalized);

		$normalized = preg_replace_callback('/&amp;#0*([0-9]{1,5});/', fn($m) => $this->checkDec((int)$m[1]), $normalized);
		$normalized = preg_replace_callback('/&amp;#x0*(([0-9a-f]{2}){1,2});/i', fn($m) => $this->checkHex($m[1]), $normalized);
		$normalized = str_replace('___dt_quoteholder___', '%22', $normalized);

		return $normalized !== $original ? $this->decodeString($normalized) : $normalized;
	}

	protected function checkDec(int $int): string {
		return ($int <= 127 && !in_array($int, [9, 10, 13])) ? chr($int) : '';
	}

	protected function checkHex(string $hex): string {
		return $this->checkDec(hexdec($hex));
	}

	protected function dadaQuickDecode(string $str): string {
		return html_entity_decode(urldecode($str), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

}
