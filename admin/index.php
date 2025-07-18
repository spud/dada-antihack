<?php
// Basic authentication: set your own username/password here
$USERNAME = 'antihacker';
$PASSWORD = 'goAwayHacker';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $USERNAME || $_SERVER['PHP_AUTH_PW'] !== $PASSWORD) {
    header('WWW-Authenticate: Basic realm="Antihack Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

$configDir = realpath(__DIR__ . '/../config/');
if (!$configDir) die('Config dir not found');

$files = glob($configDir . '/antihack-rules-*.php');
$fileList = [];
foreach ($files as $f) {
    $fileList[] = basename($f);
}

// Handle file selection (GET or POST for flexibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['configFile'])) {
    $configFileBase = basename($_POST['configFile']);
} elseif (isset($_GET['file'])) {
    $configFileBase = basename($_GET['file']);
} else {
    $configFileBase = $fileList[0] ?? 'antihack-rules-default.php';
}
$configFile = $configDir . '/' . $configFileBase;

// Fall back if missing
if (!is_file($configFile)) {
    $configFile = $configDir . '/antihack-rules-default.php';
    $configFileBase = basename($configFile);
}

// Sections to show in the UI, with optional field defaults
$sections = [
    'path'          => ['s'=>'', 'code'=>404, 'log'=>false, 'msg'=>''],
    'ip'            => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    'agent'         => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    'ref'           => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    'query'         => ['s'=>'', 'code'=>404, 'log'=>false, 'msg'=>''],
    'get'           => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    'get_blacklist' => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    'get_whitelist' => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>''],
    // POST gets extra fields for special checks
    'post'          => ['s'=>'', 'code'=>403, 'log'=>false, 'msg'=>'', 'check'=>'', 'limit'=>''],
];

// Load for display
$config = include $configFile;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Codes, passthrough, response
    foreach (['code', 'passthrough', 'response'] as $cat) {
        if (isset($_POST[$cat]) && is_array($_POST[$cat])) {
            foreach ($_POST[$cat] as $k => $v) {
                // Boolean for passthrough
                if ($cat === 'passthrough') {
                    $config[$cat][$k] = ($v === '1' || $v === 'on');
                } else {
                    $config[$cat][$k] = $v;
                }
            }
        }
    }

    // Each test section
    foreach ($sections as $sec => $defaults) {
        $rules = [];
        if (isset($_POST[$sec.'_s'])) {
            foreach ($_POST[$sec.'_s'] as $i => $pattern) {
                // Deleted row
                if (!empty($_POST[$sec.'_delete'][$i])) continue;
                if (trim($pattern) === '') continue; // skip empty

                $rule = ['s'=>trim($pattern)];
                // Fill other fields
                foreach ($defaults as $field => $def) {
                    if ($field == 's') continue;
                    // POST section gets extra check/limit fields
                    if ($sec === 'post' && in_array($field, ['check','limit'])) {
                        $val = $_POST["{$sec}_{$field}"][$i] ?? '';
                        if ($val !== '') $rule[$field] = $val;
                        continue;
                    }
                    // log is a checkbox
                    if ($field == 'log') {
                        $rule[$field] = !empty($_POST["{$sec}_log"][$i]);
                    } else {
                        $rule[$field] = $_POST["{$sec}_$field"][$i] ?? $def;
                    }
                }
                $rules[] = $rule;
            }
        } elseif (isset($_POST[$sec.'_f'])) {
            foreach ($_POST[$sec.'_f'] as $i => $pattern) {
                // Deleted row
                if (!empty($_POST[$sec.'_delete'][$i])) continue;
                if (trim($pattern) === '') continue; // skip empty

                $rule = ['f'=>trim($pattern)];
                // Fill other fields
                foreach ($defaults as $field => $def) {
                    if ($field == 'f') continue;
                    // POST section gets extra check/limit fields
                    if ($sec === 'post' && in_array($field, ['check','limit'])) {
                        $val = $_POST["{$sec}_{$field}"][$i] ?? '';
                        if ($val !== '') $rule[$field] = $val;
                        continue;
                    }
                    // log is a checkbox
                    if ($field == 'log') {
                        $rule[$field] = !empty($_POST["{$sec}_log"][$i]);
                    } else {
                        $rule[$field] = $_POST["{$sec}_$field"][$i] ?? $def;
                    }
                }
                $rules[] = $rule;
            }
        }
        $config['test'][$sec] = $rules;
    }

    // Save as new file if requested
    $newFile = trim($_POST['newConfigFile'] ?? '');
    if ($newFile !== '') {
        // Sanitize filename: allow only safe characters, force .php
        $newFile = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $newFile);
        if (substr($newFile, -4) !== '.php') $newFile .= '.php';
        $saveFile = $configDir . '/' . $newFile;
        $configFileBase = $newFile;
    } else {
        $saveFile = $configFile;
    }

    // Backup and Save
    if (!is_dir($configDir . '/backup')) mkdir($configDir . '/backup', 0777, true);
    if (is_file($saveFile)) {
        copy($saveFile, $configDir . '/backup/' . basename($saveFile) . '_' . date('Ymd_His') . '.php');
    }
    file_put_contents($saveFile, "<?php\nreturn " . var_export($config, true) . ";\n");

    $message = "Config saved successfully to <code>" . escape(basename($saveFile)) . "</code>!";
}

function escape($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function bool_checked($v) {
    return ($v ? 'checked' : '');
}
function print_global_table($config) {
    $fields = ['default','path','ip','agent','ref','query','get','get_blacklist','get_whitelist','post'];
    $cats = ['code', 'passthrough', 'response'];

    ?>
    <table>
        <tr>
            <th>Field</th>
            <?php foreach ($cats as $cat): ?>
                <th><?=ucfirst($cat)?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($fields as $field): ?>
            <tr>
                <td><?=ucfirst(str_replace('_',' ',$field))?></td>
                <?php foreach ($cats as $cat):
                    $value = $config[$cat][$field] ?? '';
                    $name = $cat.'['.$field.']';

                    // "response" field gets a textarea for longer messages
                    if ($cat === 'response') { ?>
                        <td><textarea name="<?=escape($name)?>" rows="2" cols="50" style="width:99%"><?=escape($value)?></textarea></td>
                    <?php }
					elseif ($cat === 'code') { ?>
						<td>
							<select name="<?=$sec?>_<?=$field?>[]">
								<option <?=($r[$field]??'')==='403'?'selected':''?>>403</option>
								<option <?=($r[$field]??'')==='404'?'selected':''?>>404</option>
							</select>
						</td>
                    <?php }
                    // "passthrough" gets a checkbox (bool)
                    elseif ($cat === 'passthrough') { ?>
                        <td style="text-align:center;">
                            <input type="checkbox" name="<?=escape($name)?>" value="1" <?=($value?'checked':'')?>>
                        </td>
                    <?php }
                    // code is just number/text (there are none of these now)
                    else { ?>
                        <td><input type="text" name="<?=escape($name)?>" value="<?=escape($value)?>"></td>
                    <?php }
                endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>dadaAntihack Config Editor</title>
    <style>
        body { font-family: sans-serif; max-width: 1100px; margin: 1.5em auto; }
        table { border-collapse: collapse; margin-bottom: 2em; }
        th,td { border: 1px solid #bbb; padding: 5px 8px; }
        th { background: #eee; }
        h2 { margin-bottom: 0.4em; }
        .section { margin-bottom: 2em; }
        input[type="text"], input[type="number"] { width: 98%; }
        .delcol { width: 48px; }
        .msg { color: green; }
        .onoff { margin-bottom: 1em; }
		.responsive-table {
		  width: 100%;
		  overflow-x: auto;
		  display: block;
		}
		.responsive-table table {
		  width: 100%;
		  min-width: 600px; /* or however wide your widest table is */
		}
    </style>
</head>
<body>
    <h1>dadaAntihack Config Editor</h1>
    <?php if (!empty($message)) echo "<div class='msg'>$message</div>"; ?>

	<form method="get" style="margin-bottom:1em">
		<label>Config file:
			<select name="file" onchange="this.form.submit()">
				<?php foreach ($fileList as $fname): ?>
					<option value="<?=escape($fname)?>" <?=$fname==$configFileBase?'selected':''?>><?=escape($fname)?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<noscript><input type="submit" value="Load"></noscript>
	</form>
    <form method="post" autocomplete="off">

        <input type="hidden" name="configFile" value="<?=escape($configFileBase)?>">
        <h2>Global Settings</h2>
        <?php print_global_table($config); ?>

        <?php foreach ($sections as $sec => $fields): ?>
		<div class="section">
			<h2><?=ucfirst(str_replace('_',' ',$sec))?> Rules</h2>
			<table id="table-<?=$sec?>">
				<tr>
					<th>Pattern</th>
					<?php if ($sec === 'post') { ?>
						<th>Field (for checks)</th>
					<?php } ?>
					<?php foreach ($fields as $field => $def): if ($field=='s') continue; ?>
						<th><?=ucfirst($field)?></th>
					<?php endforeach ?>
					<th class="delcol">Delete</th>
				</tr>
				<?php
				$rules = $config['test'][$sec] ?? [];
				$rows = count($rules);
				for ($i=0; $i < $rows; $i++) :
					$r = $rules[$i] ?? [];
				?>
				<tr>
					<td>
						<input type="text" name="<?=$sec?>_s[]" value="<?=escape($r['s']??'')?>">
					</td>
					<?php if ($sec === 'post') { ?>
					<td>
						<input type="text" name="<?=$sec?>_f[]" value="<?=escape($r['f']??'')?>">
					</td>
					<?php } ?>
					<?php foreach ($fields as $field => $def):
						if ($field=='s') continue;
						if ($sec === 'post' && $field==='check'): ?>
							<td>
								<select name="<?=$sec?>_check[]">
									<option value="">(none)</option>
									<?php foreach(['','length','links','empty'] as $v): ?>
										<option value="<?=$v?>" <?=($r['check']??'')===$v?'selected':''?>><?=$v?></option>
									<?php endforeach ?>
								</select>
							</td>
						<?php elseif ($sec === 'post' && $field==='limit'): ?>
							<td>
								<input type="number" name="<?=$sec?>_limit[]" value="<?=escape($r['limit']??'')?>">
							</td>
						<?php elseif ($field === 'log'): ?>
							<td>
								<input type="checkbox" name="<?=$sec?>_log[<?=$i?>]" value="1" <?=bool_checked($r['log']??false)?>>
							</td>
						<?php elseif ($field === 'code'): ?>
							<td>
								<select name="<?=$sec?>_<?=$field?>[]">
									<option <?=($r[$field]??'')==='403'?'selected':''?>>403</option>
									<option <?=($r[$field]??'')==='404'?'selected':''?>>404</option>
								</select>
							</td>
						<?php else: ?>
							<td>
								<input type="<?=is_numeric($def)?'number':'text'?>" name="<?=$sec?>_<?=$field?>[]" value="<?=escape($r[$field]??'')?>">
							</td>
						<?php endif;
					endforeach ?>
					<td class="delcol"><input type="checkbox" name="<?=$sec?>_delete[<?=$i?>]" value="1"></td>
				</tr>
				<?php endfor; ?>
				<!-- Template row for adding new ones -->
				<tr class="template-row" id="tpl-<?=$sec?>" style="display:none">
					<td><input type="text" name="<?=$sec?>_s[]" value=""></td>
					<?php if ($sec === 'post') { ?>
						<td><input type="text" name="<?=$sec?>_f[]" value=""></td>
					<?php } ?>
					<?php foreach ($fields as $field => $def):
						if ($field=='s') continue;
						if ($sec === 'post' && $field==='check'): ?>
							<td>
								<select name="<?=$sec?>_check[]">
									<option value="">(none)</option>
									<?php foreach(['','length','links','empty'] as $v): ?>
										<option value="<?=$v?>"><?=$v?></option>
									<?php endforeach ?>
								</select>
							</td>
						<?php elseif ($sec === 'post' && $field==='limit'): ?>
							<td>
								<input type="number" name="<?=$sec?>_limit[]" value="">
							</td>
						<?php elseif ($field === 'log'): ?>
							<td>
								<input type="checkbox" name="<?=$sec?>_log[]" value="1">
							</td>
						<?php else: ?>
							<td>
								<input type="<?=is_numeric($def)?'number':'text'?>" name="<?=$sec?>_<?=$field?>[]" value="">
							</td>
						<?php endif;
					endforeach ?>
					<td class="delcol"><input type="checkbox" name="<?=$sec?>_delete[]" value="1"></td>
				</tr>
			</table>
			<button type="button" onclick="addRow('<?=$sec?>')">Add Row</button>
		</div>
        <?php endforeach; ?>
		<label>Save as new file:
			<input type="text" name="newConfigFile" placeholder="antihack-rules-new.php" style="width:220px">
		</label>
		<br>
		<input type="submit" value="Save Config">
    </form>
    <p><em>Config file: <code><?=escape($configFile)?></code> &nbsp; | &nbsp; Backups in <code>backup/</code></em></p>
	<script>
	function addRow(section) {
		var tpl = document.getElementById('tpl-' + section);
		var clone = tpl.cloneNode(true);
		clone.style.display = '';
		clone.classList.remove('template-row');
		// Place at end, but before the template row itself
		var table = document.getElementById('table-' + section);
		table.tBodies[0].insertBefore(clone, tpl);
	}
	</script>

</body>
</html>
