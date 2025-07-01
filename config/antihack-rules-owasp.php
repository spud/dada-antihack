<?php
// Example rules to block common OWASP Top Ten attack patterns
return [
    'test' => [
        // Block directory traversal
        'path' => [
            ['s'=>'\.\./',           'code'=>404, 'log'=>true, 'msg'=>'Directory traversal attempt'],
            ['s'=>'/etc/passwd',     'code'=>404, 'log'=>true, 'msg'=>'File inclusion attempt'],
            ['s'=>'\.env',           'code'=>404, 'log'=>true, 'msg'=>'Environment file probe'],
            ['s'=>'\.git',           'code'=>404, 'log'=>true, 'msg'=>'Git repo exposure attempt'],
        ],
        // Block SQL injection
        'query' => [
            ['s'=>'select.+from',    'code'=>404, 'log'=>true, 'msg'=>'SQLi attempt'],
            ['s'=>'union.+select',   'code'=>404, 'log'=>true, 'msg'=>'SQLi attempt'],
            ['s'=>'information_schema', 'code'=>404, 'log'=>true, 'msg'=>'SQLi schema probe'],
        ],
        // Block XSS
        'get' => [
            ['s'=>'<script>',        'code'=>403, 'log'=>true, 'msg'=>'XSS attempt'],
            ['s'=>'javascript:',     'code'=>403, 'log'=>true, 'msg'=>'XSS vector'],
        ],
        // Block common remote file inclusion (RFI) attempts
        'get' => [
            ['s'=>'https?://',       'code'=>403, 'log'=>true, 'msg'=>'Remote file inclusion'],
            ['s'=>'ftp://',          'code'=>403, 'log'=>true, 'msg'=>'Remote file inclusion'],
        ],
        // Block file upload attempts
        'post' => [
            ['s'=>'\.php',           'code'=>403, 'log'=>true, 'msg'=>'PHP file upload'],
            ['s'=>'base64_decode',   'code'=>403, 'log'=>true, 'msg'=>'Obfuscated payload'],
            ['s'=>'eval\(',          'code'=>403, 'log'=>true, 'msg'=>'Obfuscated payload'],
        ],
        // (Optional) Block bots and known scanners
        'agent' => [
            ['s'=>'nikto',           'code'=>403, 'log'=>true],
            ['s'=>'sqlmap',          'code'=>403, 'log'=>true],
            ['s'=>'acunetix',        'code'=>403, 'log'=>true],
        ],
    ],
];
