<?php

chdir(__DIR__ . '/..');

$is_server = isset($_SERVER['REQUEST_URI']);
$request_url = $is_server ? $_SERVER['REQUEST_URI'] : ('/nouislider/' . $argv[1]);

$url = strtolower($request_url);

// mjs mime type is not built-in
if (strpos($url, '.mjs')) {
    header('Content-Type: text/javascript');
    readfile($_SERVER["SCRIPT_FILENAME"]);

    return;
}

if (strpos($url, '.js') || strpos($url, '.css') || strpos($url, '.html')) {
    return false;
}

$request = parse_url($url);
$page = rtrim(substr($request['path'], strlen('/nouislider/')), '/');

if (!$page) {
    $page = 'index';
}

$file = $page . '.php';
$file_menu = '_run/menu.php';

require '_run/helpers.php';

if (!file_exists($file)) {
    header('HTTP/1.0 404 Not Found');
    $file = '_run/404.php';
}

// Defaults
$title = "";
$description = "";
$canonical = "";

$package = json_decode(file_get_contents('./../package.json'));
$version = $package->version;
$plain_version = str_replace('.', '', $version);

ob_start();

include $file;

$content = ob_get_contents();

ob_end_clean();

$distribute = '/nouislider/dist';

if (!$is_server) {
    echo "---\n";
    echo "permalink: " . $canonical . "\n";
    echo "---\n";
}

if ($canonical) {
    $canonical = 'https://refreshless.com/' . $canonical;
}

include '_run/index.php';
