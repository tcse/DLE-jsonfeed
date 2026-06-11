<?php
/*
=====================================================
 DLE Json Feed - by TCSE 
-----------------------------------------------------
 Версия: 0.1.5 от 2026-06-11
-----------------------------------------------------
 Страница продукта: https://tcse-cms.com
-----------------------------------------------------
 Репозиторий: https://github.com/tcse/DLE-jsonfeed
-----------------------------------------------------
 Copyright (c) 2026 Vitaly V. Chuyakov
=====================================================
 This code is protected by copyright
=====================================================
 Файл: plugins/tcse/jsonfeed/feed.json.php
-----------------------------------------------------
 ПРЕДНАЗНАЧЕНИЕ:
   Основной скрипт генерации JSON Feed. Обращается к базе
   данных DLE, выбирает публикации согласно настройкам
   и фильтрам, формирует JSON в формате JSON Feed 1.1.
   
   Поддерживает параметры URL для фильтрации:
   - limit    : количество записей (1-100)
   - category : ID категории для фильтрации
   - type     : тип контента (из настроек content_types)
   - order    : сортировка (date_asc / date_desc)
   
   Поддерживает красивые URL через .htaccess:
   - /jsonfeed.json               → основная лента
   - /jsonfeed-{type}.json        → лента определённого типа
   - /jsonfeed/category/{id}/     → лента категории
-----------------------------------------------------
 АЛГОРИТМ РАБОТЫ:
   1. Инициализация DLE и подключение к БД
   2. Загрузка конфигурации из config.php
   3. Определение параметров фильтрации из URL
   4. Формирование SQL-запроса к таблице dle_post
   5. Получение данных о публикациях
   6. Для каждой публикации:
      - Получение автора из dle_users
      - Извлечение изображений из short_story
      - Формирование summary (краткого описания)
      - Сборка HTML и текстового контента
      - Добавление тегов и категорий
   7. Формирование JSON Feed 1.1
   8. Вывод JSON с заголовком application/feed+json
=====================================================
*/

// Константа для защиты
define('JSONFEED_INIT', true);

// Ручная инициализация DLE
if (!defined('DATALIFEENGINE')) {
    define('DATALIFEENGINE', true);
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT']);
    define('ENGINE_DIR', ROOT_DIR . '/engine');
    
    require_once ENGINE_DIR . '/classes/mysql.php';
    require_once ENGINE_DIR . '/data/dbconfig.php';
    require_once ENGINE_DIR . '/modules/functions.php';
    
    if (!defined('DATALIFEENGINE') || !isset($db)) {
        die('Unable to initialize DLE engine');
    }
}

// Подключаем конфигурацию плагина
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

global $db;

if (!isset($db) || !is_object($db)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== ОПРЕДЕЛЕНИЕ ТИПА ЛЕНТЫ ИЗ URL ==========
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$typeFilter = $_GET['type'] ?? 'main';

// Автоопределение типа из красивых URL (jsonfeed-XXX.json)
if (preg_match('#/jsonfeed-([a-zA-Z0-9_-]+)\.json#', $requestUri, $matches)) {
    $typeFilter = $matches[1];
} elseif (preg_match('#/jsonfeed/category/([0-9]+)#', $requestUri, $matches)) {
    $_GET['category'] = $matches[1];
}

// Параметры запроса
$limit = min(100, max(1, intval($_GET['limit'] ?? ($jsonfeed_config['items_per_page'] ?? 20))));
$categoryFilter = intval($_GET['category'] ?? 0);
$order = $_GET['order'] ?? 'date_desc';

// ========== НАСТРОЙКИ ЛЕНТЫ В ЗАВИСИМОСТИ ОТ ТИПА ==========
// Базовые настройки из конфига
$feedTitle = $jsonfeed_config['feed_title_main'] ?? ($config['home_title'] ?? 'Новости сайта');
$feedDescription = $jsonfeed_config['feed_description_main'] ?? ('Новости и публикации с сайта ' . ($config['home_title'] ?? ''));

// Если указан тип ленты и есть настройки для него - переопределяем
if ($typeFilter !== 'main' && isset($jsonfeed_config['feeds'][$typeFilter])) {
    $feedConfig = $jsonfeed_config['feeds'][$typeFilter];
    $feedTitle = $feedConfig['title'] ?? $feedTitle;
    $feedDescription = $feedConfig['description'] ?? $feedDescription;
}

// ========== ФОРМИРОВАНИЕ SQL ЗАПРОСА ==========
$whereConditions = ["p.approve = '1'"];

// Фильтр по категории
if ($categoryFilter > 0) {
    $whereConditions[] = "p.category = '" . $db->safesql($categoryFilter) . "'";
}

// Фильтр по типу контента (из настроек content_types)
if ($typeFilter !== 'main' && isset($jsonfeed_config['content_types'][$typeFilter])) {
    $allowedCats = $jsonfeed_config['content_types'][$typeFilter];
    if (!empty($allowedCats)) {
        $catConditions = [];
        foreach ($allowedCats as $catId) {
            $catConditions[] = "p.category = '" . $db->safesql($catId) . "'";
        }
        $whereConditions[] = "(" . implode(" OR ", $catConditions) . ")";
    }
}

// Сортировка
$orderBy = ($order === 'date_asc') ? "p.date ASC" : "p.date DESC";

// SQL запрос с добавлением p.autor
$sql = "SELECT 
            p.id,
            p.title,
            p.short_story,
            p.full_story,
            p.date,
            p.category,
            p.alt_name,
            p.tags,
            p.xfields,
            p.autor,
            c.name as category_name,
            c.alt_name as category_alt_name
        FROM " . PREFIX . "_post p
        LEFT JOIN " . PREFIX . "_category c ON c.id = p.category
        WHERE p.approve = '1' 
            AND p.date <= NOW()           -- <-- ДОБАВЛЕННОЕ УСЛОВИЕ
            " . ($categoryFilter > 0 ? "AND p.category = '" . $db->safesql($categoryFilter) . "'" : "") . "
            " . ($typeFilter !== 'main' && isset($jsonfeed_config['content_types'][$typeFilter]) ? "AND (" . implode(" OR ", $catConditions) . ")" : "") . "
        ORDER BY " . $orderBy . "
        LIMIT " . intval($limit);

$result = $db->query($sql);

if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database query failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Собираем посты
$posts = [];
while ($row = $db->get_array($result)) {
    // Распаковываем xfields
    $xfields = [];
    if (!empty($row['xfields'])) {
        $xf_data = explode("||", $row['xfields']);
        foreach ($xf_data as $item) {
            $parts = explode("|", $item, 2);
            if (count($parts) == 2) {
                $xfields[$parts[0]] = stripcslashes($parts[1]);
            }
        }
    }
    $row['xfields_array'] = $xfields;
    $posts[] = $row;
}

// Базовый URL
$baseUrl = $config['http_home_url'] ?? ((isSSL() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);

// Формирование URL иконок из конфига
$faviconUrl = $jsonfeed_config['feed_favicon_url'] ?? ($baseUrl . '/favicon.ico');
$iconUrl = $jsonfeed_config['feed_icon_url'] ?? '';

// Если иконка не задана в конфиге, используем favicon (как fallback)
if (empty($iconUrl)) {
    $iconUrl = $faviconUrl;
}

// Формирование JSON Feed
$feed = [
    'version' => 'https://jsonfeed.org/version/1.1',
    'title' => $feedTitle,
    'home_page_url' => $baseUrl,
    'feed_url' => $baseUrl . '/plugins/tcse/jsonfeed/feed.json.php' 
        . ($typeFilter !== 'main' ? '?type=' . $typeFilter : ''),
    'description' => $feedDescription,
    'user_comment' => 'JSON Feed формат для AI-агентов и RSS-ридеров. Спецификация: https://jsonfeed.org/version/1.1',
    'favicon' => $faviconUrl,
    'icon' => $iconUrl,
    'authors' => [
        [
            'name' => $config['home_title'] ?? 'Администрация',
            'url' => $baseUrl
        ]
    ],
    'language' => 'ru',
    'expired' => false,
    'items' => []
];

// Формирование элементов
foreach ($posts as $post) {
    // Получаем автора из БД
    $authorName = '';
    if (!empty($post['autor'])) {
        // Пытаемся получить имя автора из таблицы users
        $authorInfo = $db->super_query("SELECT name FROM " . PREFIX . "_users WHERE user_id = '" . $db->safesql($post['autor']) . "'");
        $authorName = $authorInfo['name'] ?? $post['autor'];
    }
    
    // Если автор не найден, используем название сайта
    if (empty($authorName)) {
        $authorName = $config['home_title'] ?? 'Администрация';
    }
    
    // Название категории
    $categoryName = '';
    if ($post['category'] > 0) {
        $categoryName = getCategoryName($post['category']);
    }
    
    // URL поста
    $postUrl = $baseUrl . '/' . $post['id'] . '-' . $post['alt_name'] . '.html';
    
    // Преобразуем HTML-сущности в теги для корректного поиска (DLE 20.0)
    $shortStory = htmlspecialchars_decode($post['short_story'], ENT_QUOTES);
    
    // Изображение
    $image = '';
    if (isset($xfields['pic']) && !empty($xfields['pic'])) {
        $image = $xfields['pic'];
        if (strpos($image, 'http') !== 0) {
            $image = $baseUrl . $image;
        }
    } elseif (isset($xfields['manyfotos']) && !empty($xfields['manyfotos'])) {
        $firstImage = explode(',', $xfields['manyfotos'])[0];
        $image = strpos($firstImage, 'http') === 0 ? $firstImage : $baseUrl . '/uploads/posts/' . $firstImage;
    } else {
        // Извлекаем первое изображение из short_story
        preg_match('/<img[^>]+src="([^">]+)"/', $post['short_story'], $matches);
        if (!empty($matches[1])) {
            $image = $matches[1];
            if (strpos($image, 'http') !== 0) {
                $image = $baseUrl . $image;
            }
        }
    }

    // Собираем все изображения из поста для attachments
    $attachments = [];
    if ($jsonfeed_config['enable_images'] ?? true) {
        // Ищем все ссылки на изображения в short_story
        $allImages = [];
        preg_match_all('/https?:\/\/[^\s"\']+\.(jpg|jpeg|png|webp|gif)/i', $post['short_story'], $matches);
        if (!empty($matches[0])) {
            $allImages = $matches[0];
        } else {
            // Если не нашли, пробуем в декодированной
            $shortStory = htmlspecialchars_decode($post['short_story'], ENT_QUOTES);
            preg_match_all('/https?:\/\/[^\s"\']+\.(jpg|jpeg|png|webp|gif)/i', $shortStory, $matches);
            if (!empty($matches[0])) {
                $allImages = $matches[0];
            }
        }
        
        // Убираем дубликаты
        $allImages = array_unique($allImages);
        
        foreach ($allImages as $idx => $imgUrl) {
            // Пропускаем служебные иконки
            if (strpos($imgUrl, 'icon') !== false || strpos($imgUrl, 'avatar') !== false) {
                continue;
            }
            $attachments[] = [
                'url' => $imgUrl,
                'mime_type' => 'image/jpeg',
                'title' => ($idx === 0 ? 'Иллюстрация к статье ' : 'Фото ') . $post['title']
            ];
            if (count($attachments) >= 5) break;
        }
    }

    
    
    // Формируем описание (summary)
    $cleanShort = strip_tags($shortStory);
    $cleanShort = preg_replace('/\s+/', ' ', $cleanShort);
    $summary = mb_substr($cleanShort, 0, 300);
    if (empty($summary)) {
        $summary = $post['title'];
    }
    
    // HTML-контент (используем оригинальный short_story с сущностями)
    $contentHtml = '<h3>' . htmlspecialchars($post['title'], ENT_XML1, 'UTF-8') . '</h3>';
    $contentHtml .= '<p><strong>Дата:</strong> ' . date('d.m.Y', strtotime($post['date'])) . '</p>';
    
    if ($jsonfeed_config['enable_categories'] ?? true) {
        $contentHtml .= '<p><strong>Рубрика:</strong> ' . htmlspecialchars($categoryName, ENT_XML1, 'UTF-8') . '</p>';
    }
    
    if (($jsonfeed_config['enable_tags'] ?? true) && !empty($post['tags'])) {
        $contentHtml .= '<p><strong>Теги:</strong> ' . htmlspecialchars($post['tags'], ENT_XML1, 'UTF-8') . '</p>';
    }
    
    $contentHtml .= '<div class="post-preview">' . $post['short_story'] . '</div>';
    $contentHtml .= '<p><a href="' . $postUrl . '">Читать далее →</a></p>';
    
    // Текстовый контент
    $contentText = $post['title'] . '. ';
    $contentText .= 'Дата: ' . date('d.m.Y', strtotime($post['date'])) . '. ';
    if ($jsonfeed_config['enable_categories'] ?? true) {
        $contentText .= 'Рубрика: ' . $categoryName . '. ';
    }
    $contentText .= $summary;
    
    // Формируем item
    $item = [
        'id' => (string)$post['id'],
        'url' => $postUrl,
        'title' => $post['title'],
        'content_html' => $contentHtml,
        'content_text' => $contentText,
        'summary' => $summary,
        'date_published' => date('c', strtotime($post['date'])),
        'date_modified' => date('c', strtotime($post['date'])),
        'authors' => [
            [
                'name' => $authorName,
                'url' => $baseUrl
            ]
        ],
        'tags' => $categoryName ? [$categoryName] : [],
        'language' => 'ru'
    ];

    // Добавляем изображение
    if ($image) {
        $item['image'] = $image;
    }
    
    

    // Добавляем теги из поля tags
    if (($jsonfeed_config['enable_tags'] ?? true) && !empty($post['tags'])) {
        $tagsList = array_map('trim', explode(',', $post['tags']));
        $item['_tags_extended'] = $tagsList;
    }

    $feed['items'][] = $item;
}

// Отдаем результат
header('Content-Type: application/feed+json; charset=utf-8');
echo json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$db->free();

/**
 * Получение имени категории по ID
 */
function getCategoryName($catId) {
    global $db;
    static $categoryCache = [];
    
    if (!$catId) return '';
    if (isset($categoryCache[$catId])) {
        return $categoryCache[$catId];
    }
    
    $row = $db->super_query("SELECT name FROM " . PREFIX . "_category WHERE id = '" . $db->safesql($catId) . "'");
    $categoryCache[$catId] = $row['name'] ?? '';
    return $categoryCache[$catId];
}