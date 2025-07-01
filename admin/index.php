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


$configFile = '../config/rules.php';

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

    // Global ON/OFF
    $config['on_off'] = ($_POST['on_off'] ?? 'off') === 'on' ? 'on' : 'off';

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
        }
        $config['test'][$sec] = $rules;
    }

    // Backup and Save
    if (!is_dir('backup')) mkdir('backup');
    copy($configFile, 'backup/config_'.date('Ymd_His').'.php');
    file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");

    $message = "Config saved successfully!";
}

function escape($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function bool_checked($v) {
    return ($v ? 'checked' : '');
}
function print_global_table($config) {
    ?>
    <table>
        <tr>
            <th>Setting</th><th>Default</th><th>path</th><th>ip</th><th>agent</th>
            <th>ref</th><th>query</th><th>get</th><th>get_blacklist</th><th>get_whitelist</th><th>post</th>
        </tr>
        <?php foreach (['code', 'passthrough', 'response'] as $cat): ?>
        <tr>
            <td><?=ucfirst($cat)?></td>
            <?php
                $row = $config[$cat] ?? [];
                $fields = ['default','path','ip','agent','ref','query','get','get_blacklist','get_whitelist','post'];
                foreach ($fields as $f) {
                    echo '<td><input type="text" name="'.$cat.'['.$f.']" value="'.escape($row[$f] ?? '').'"></td>';
                }
            ?>
        </tr>
        <?php endforeach ?>
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
    </style>
</head>
<body>
    <h1>dadaAntihack Config Editor</h1>
    <?php if (!empty($message)) echo "<div class='msg'>$message</div>"; ?>

    <form method="post" autocomplete="off">
        <div class="onoff">
            <label>
                <input type="checkbox" name="on_off" value="on" <?=($config['on_off']??'')==='on'?'checked':''?>>
                Enable dadaAntihack
            </label>
        </div>

        <h2>Global Settings</h2>
        <?php print_global_table($config); ?>

        <?php foreach ($sections as $sec => $fields): ?>
            <div class="section">
            <h2><?=ucfirst(str_replace('_',' ',$sec))?> Rules</h2>
            <table>
                <tr>
                    <th>Pattern/Param (s)</th>
                    <?php foreach ($fields as $field => $def): if ($field=='s') continue; ?>
                        <th><?=ucfirst($field)?></th>
                    <?php endforeach ?>
                    <th class="delcol">Delete</th>
                </tr>
                <?php
                $rules = $config['test'][$sec] ?? [];
                $rows = max(count($rules), 2); // Always show 2 blank for add
                for ($i=0; $i < $rows+1; $i++) :
                    $r = $rules[$i] ?? [];
                ?>
                <tr>
                    <td>
                        <input type="text" name="<?=$sec?>_s[]" value="<?=escape($r['s']??'')?>">
                    </td>
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
                        <?php else: ?>
                            <td>
                                <input type="<?=is_numeric($def)?'number':'text'?>" name="<?=$sec?>_<?=$field?>[]" value="<?=escape($r[$field]??'')?>">
                            </td>
                        <?php endif;
                    endforeach ?>
                    <td class="delcol"><input type="checkbox" name="<?=$sec?>_delete[<?=$i?>]" value="1"></td>
                </tr>
                <?php endfor; ?>
            </table>
            </div>
        <?php endforeach; ?>
        <input type="submit" value="Save Config">
    </form>
    <p><em>Config file: <code><?=escape($configFile)?></code> &nbsp; | &nbsp; Backups in <code>backup/</code></em></p>
</body>
</html>
