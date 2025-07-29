<?php

function fgc($name)
{
    return file_get_contents('./' . $GLOBALS['page'] . '/' . $name);
}

function code($name, $encode = false)
{
    $c = fgc($name . '.js');

    if ($encode) {
        $c = htmlentities($c);
    }

    echo "\r\n" . '<pre><code>' . $c . '</code></pre>';
}

function run($name)
{
    echo "\r\n" . '<script>' . "\r\n" . fgc($name . '.js') . '</script>';
}

function loadShowCSS($name)
{
    $content = fgc($name . '.css');

    echo "\r\n" . '<style>' . $content . '</style>' .
        '<pre class="language-css"><code>' . $content . '</code></pre>';
}

function showCSS($name)
{
    $content = fgc($name . '.css');

    echo "\r\n" . '<pre class="language-css"><code>' . $content . '</code></pre>';
}

function sect($title)
{
    echo '<a href="#section-' . $title . '" id="section-' . $title . '" class="section-link">&sect;</a>';
}
