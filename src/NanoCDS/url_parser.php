<?php

function parse_friendly_url() {
    if (!empty($_GET)) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $url = $request_uri;
    
    $path = str_replace('/assets/', '', $path);


    $segments = explode('/', trim($path, '/'));

    if (empty($segments[0])) {
        return;
    }

    $page = array_shift($segments);
    $_GET['page'] = $page;

    switch ($page) {
        case 'view':
    $pattern = '/^\/assets\/view\/(?<fullDir>[^\/]+(\/[^\/]+)*)(?:\/v\/(?<version>[^\/]+))?\/f\/(?<name>[^\/]+)$/';

if (preg_match($pattern, $url, $matches)) {
    $_GET['dir'] = str_replace('/v/', '/', $matches['fullDir']);
    $_GET['name'] = $matches['name'];
}

            break;

        case 'dir':
            // Формат для 'dir': dir/folder/subfolder
            $_GET['name'] = implode('/', $segments);
            break;

        case 'latest':
            $_GET['asset'] = array_shift($segments);

            // Возможные дополнительные параметры t, s, a
            while (!empty($segments)) {
                $key = array_shift($segments);
                $value = array_shift($segments);
                switch ($key) {
                    case 't':
                        $_GET['type'] = $value ?? 'any';
                        break;
                    case 's':
                        $_GET['size'] = $value ?? '0';
                        break;
                    case 'a':
                        $_GET['auto'] = $value ?? '1';
                        break;
                }
            }
            break;

        case 'search':
            $_GET['query'] = array_shift($segments);
            break;

        // Обработка других страниц
        default:
            $_GET = [];
            break;
    }
}

// Подключаем функцию
parse_friendly_url();