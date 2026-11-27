<?php
$root = isset($argv[1]) ? $argv[1] : __DIR__ . '/../..';

$failures = 0;
function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
        echo "ok - $name\n";
    } else {
        echo "FAIL - $name (got " . var_export($got, true) . ", want " . var_export($want, true) . ")\n";
        $failures++;
    }
}

define('ZM_OPT_USE_AUTH', true);

$GLOBALS['userObj'] = new stdClass();

eval('namespace ZM; function Warning($m){} function Debug($m){} function Error($m){} function Info($m){}');
function Warning($m) {}
function Debug($m) {}
function Error($m) {}
function Info($m) {}

function validateToken($token, $allowed = 'access') {
    global $userObj;
    $types = array('access-tok' => 'access', 'refresh-tok' => 'refresh');
    if (!isset($types[$token])) {
        return array(false, 'Invalid token');
    }
    if ($allowed !== 'any' && $types[$token] !== $allowed) {
        return array(false, 'Incorrect token type');
    }
    return array($userObj, 'OK');
}

$authPath = $root . '/web/includes/auth.php';
if (!is_file($authPath)) {
    fwrite(STDERR, "missing $authPath\n");
    exit(1);
}
$src = file_get_contents($authPath);
$needle = 'function zm_authenticate_request() {';
$start = strpos($src, $needle);
if ($start === false) {
    fwrite(STDERR, "function zm_authenticate_request() not found\n");
    exit(1);
}

$i = $start + strlen($needle) - 1;
$len = strlen($src);
$depth = 0;
$end = false;
$inSingle = false;
$inDouble = false;
$inLineComment = false;
$inBlockComment = false;
$escaped = false;
for (; $i < $len; $i++) {
    $c = $src[$i];
    $n = ($i + 1 < $len) ? $src[$i + 1] : '';
    if ($inLineComment) {
        if ($c === "\n") $inLineComment = false;
        continue;
    }
    if ($inBlockComment) {
        if ($c === '*' && $n === '/') { $inBlockComment = false; $i++; }
        continue;
    }
    if ($inSingle) {
        if ($escaped) $escaped = false;
        elseif ($c === '\\') $escaped = true;
        elseif ($c === "'") $inSingle = false;
        continue;
    }
    if ($inDouble) {
        if ($escaped) $escaped = false;
        elseif ($c === '\\') $escaped = true;
        elseif ($c === '"') $inDouble = false;
        continue;
    }
    if ($c === '/' && $n === '/') { $inLineComment = true; $i++; continue; }
    if ($c === '/' && $n === '*') { $inBlockComment = true; $i++; continue; }
    if ($c === '#') { $inLineComment = true; continue; }
    if ($c === "'") { $inSingle = true; continue; }
    if ($c === '"') { $inDouble = true; continue; }
    if ($c === '{') {
        $depth++;
    } elseif ($c === '}') {
        $depth--;
        if ($depth === 0) { $end = $i; break; }
    }
}
if ($end === false) {
    fwrite(STDERR, "could not find end of zm_authenticate_request()\n");
    exit(1);
}

$code = substr($src, $start, $end - $start + 1);
eval($code);

$_REQUEST = array();
$_SESSION = array();
$GLOBALS['user'] = null;
$_REQUEST['token'] = 'access-tok';
zm_authenticate_request();
check('access token sets global user', $GLOBALS['user'] !== null, true);

$_REQUEST = array();
$_SESSION = array();
$GLOBALS['user'] = null;
$_REQUEST['token'] = 'refresh-tok';
zm_authenticate_request();
check('refresh token does not set global user', $GLOBALS['user'] === null, true);

if ($failures > 0) {
    exit(1);
}
echo "All tests passed\n";
