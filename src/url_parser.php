<?php

function parse_friendly_url() {
    // Если параметры уже переданы через $_GET, пропускаем разбор URL
    if (!empty($_GET)) {
        return;
    }

    // Получаем путь после /assets/
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = str_replace('/assets/', '', $path);

    // Разбиваем путь на части
    $segments = explode('/', trim($path, '/'));

    // Проверяем первый сегмент, который должен быть page
    if (empty($segments[0])) {
        return;
    }

    // Извлекаем значение page
    $page = array_shift($segments);
    $_GET['page'] = $page;

    // Обрабатываем различные страницы
    switch ($page) {
        case 'view':
            // Для 'view' формат: view/dir/v/version/f/file
            if (count($segments) < 5) {
                break;
            }
            $_GET['dir'] = $segments[0] . '/' . $segments[2];  // dir = "tailwind.css/2.0.2"
            $_GET['name'] = $segments[4];  // name = "tailwind.min.css"
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