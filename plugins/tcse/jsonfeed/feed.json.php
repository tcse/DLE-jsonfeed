<?php
/**
 * JSON Feed Plugin for DLE (DataLife Engine)
 * Формат соответствует спецификации JSON Feed 1.1
 * Адаптировано для старых версий DLE (без category2, category3)
 * 
 * Доступные параметры:
 * - limit - количество записей (по умолч. 20, макс. 100)
 * - category - фильтр по ID категории (опционально)
 * - order - сортировка (date_desc, date_asc)
 * 
 * Примеры:
 * - /plugins/tcse/jsonfeed/feed.json.php
 * - /plugins/tcse/jsonfeed/feed.json.php?category=20&limit=10
 */

// Константа для защиты
define('JSONFEED_INIT', true);

// Ручная инициализация DLE
if (!defined('DATALIFEENGINE')) {
    define('DATALIFEENGINE', true);
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT']);
    define('ENGINE_DIR', ROOT_DIR . '/engine');
    
    // Подключаем необходимые файлы DLE
    require_once ENGINE_DIR . '/classes/mysql.php';
    require_once ENGINE_DIR . '/data/dbconfig.php';
    require_once ENGINE_DIR . '/modules/functions.php';
    
    // Проверяем, что DLE загрузился корректно
    if (!defined('DATALIFEENGINE') || !isset($db)) {
        die('Unable to initialize DLE engine');
    }
}

// Подключаем конфигурацию плагина (если есть)
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

global $db;

// Проверяем подключение к базе данных
if (!isset($db) || !is_object($db)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Настройки по умолчанию
$default_limit = 20;
$max_limit = 100;

// Получаем параметры запроса
$limit = min($max_limit, max(1, intval($_GET['limit'] ?? $default_limit)));
$categoryFilter = intval($_GET['category'] ?? 0);
$order = $_GET['order'] ?? 'date_desc';

// Формируем условия WHERE для запроса
$whereConditions = ["p.approve = '1'"];

// Фильтр по категории (только основная категория, без category2/3)
if ($categoryFilter > 0) {
    $whereConditions[] = "p.category = '" . $db->safesql($categoryFilter) . "'";
}

// Сортировка
$orderBy = ($order === 'date_asc') ? "p.date ASC" : "p.date DESC";

// Формируем SQL запрос (без category2 и category3)
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
            c.name as category_name,
            c.alt_name as category_alt_name
        FROM " . PREFIX . "_post p
        LEFT JOIN " . PREFIX . "_category c ON c.id = p.category
        WHERE " . implode(" AND ", $whereConditions) . "
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

// Определение названия ленты ИЗ КОНФИГА
$feedTitle = $jsonfeed_config['feed_title_main'] ?? ($config['home_title'] ?? 'Новости сайта');
$feedDescription = $jsonfeed_config['feed_description_main'] ?? ('Новости и публикации с сайта ' . ($config['home_title'] ?? ''));

// Если указан тип ленты и есть настройки для него - переопределяем
if ($typeFilter !== 'main' && isset($jsonfeed_config['feeds'][$typeFilter])) {
    $feedConfig = $jsonfeed_config['feeds'][$typeFilter];
    $feedTitle = $feedConfig['title'] ?? $feedTitle;
    $feedDescription = $feedConfig['description'] ?? $feedDescription;
}

// Если указана категория и есть посты - можно уточнить название
if ($categoryFilter > 0 && !empty($posts)) {
    $categoryName = getCategoryName($categoryFilter);
    if ($categoryName) {
        $feedTitle = $categoryName . ' - ' . $feedTitle;
    }
}

// Формирование JSON Feed
$feed = [
    'version' => 'https://jsonfeed.org/version/1.1',
    'title' => $feedTitle,
    'home_page_url' => $baseUrl,
    'feed_url' => $baseUrl . '/plugins/tcse/jsonfeed/feed.json.php' 
        . ($categoryFilter ? '?category=' . $categoryFilter : ($typeFilter !== 'main' ? '?type=' . $typeFilter : '')),
    'description' => $feedDescription,
    'user_comment' => 'JSON Feed формат для AI-агентов и RSS-ридеров. Спецификация: https://jsonfeed.org/version/1.1',
    'favicon' => $baseUrl . '/favicon.ico',
    'icon' => $baseUrl . '/templates/' . ($config['skin'] ?? 'default') . '/images/logo.png',
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
    // Получаем название категории
    $categoryName = '';
    if ($post['category'] > 0) {
        $categoryName = getCategoryName($post['category']);
    }
    
    // Формируем URL поста
    $postUrl = $baseUrl . '/' . $post['id'] . '-' . $post['alt_name'] . '.html';
    
    // Извлекаем изображение из short_story или xfields
    $image = '';
    preg_match('/<img[^>]+src="([^">]+)"/', $post['short_story'], $matches);
    if (!empty($matches[1])) {
        $image = $matches[1];
        if (strpos($image, 'http') !== 0) {
            $image = $baseUrl . $image;
        }
    }
    
    // Если нет изображения в short_story, проверяем xfields
    if (!$image && isset($post['xfields_array']['pic']) && !empty($post['xfields_array']['pic'])) {
        $image = $post['xfields_array']['pic'];
        if (strpos($image, 'http') !== 0) {
            $image = $baseUrl . $image;
        }
    }
    
    // Формируем описание
    $cleanShort = strip_tags($post['short_story']);
    $cleanShort = preg_replace('/\s+/', ' ', $cleanShort);
    $summary = mb_substr($cleanShort, 0, 300);
    
    if (empty($summary)) {
        $summary = $post['title'];
    }
    
    // Формируем HTML-контент
    $contentHtml = '<h3>' . htmlspecialchars($post['title'], ENT_XML1, 'UTF-8') . '</h3>';
    $contentHtml .= '<p><strong>Дата:</strong> ' . date('d.m.Y', strtotime($post['date'])) . '</p>';
    
    if (!empty($categoryName)) {
        $contentHtml .= '<p><strong>Рубрика:</strong> ' . htmlspecialchars($categoryName, ENT_XML1, 'UTF-8') . '</p>';
    }
    
    if (!empty($post['tags'])) {
        $contentHtml .= '<p><strong>Теги:</strong> ' . htmlspecialchars($post['tags'], ENT_XML1, 'UTF-8') . '</p>';
    }
    
    // Добавляем краткое содержание
    $contentHtml .= '<div class="post-preview">' . $post['short_story'] . '</div>';
    $contentHtml .= '<p><a href="' . $postUrl . '">Читать далее →</a></p>';
    
    // Текстовый контент
    $contentText = $post['title'] . '. ';
    $contentText .= 'Дата: ' . date('d.m.Y', strtotime($post['date'])) . '. ';
    if (!empty($categoryName)) {
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
        'authors' => [['name' => $config['home_title'] ?? 'Администрация', 'url' => $baseUrl]],
        'tags' => $categoryName ? [$categoryName] : [],
        'language' => 'ru'
    ];
    
    // Добавляем теги из поля tags
    if (!empty($post['tags'])) {
        $tagsList = array_map('trim', explode(',', $post['tags']));
        $item['_tags_extended'] = $tagsList;
    }
    
    // Добавляем изображение
    if ($image) {
        $item['image'] = $image;
        $item['attachments'] = [
            [
                'url' => $image,
                'mime_type' => 'image/jpeg',
                'title' => 'Иллюстрация к статье ' . $post['title']
            ]
        ];
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