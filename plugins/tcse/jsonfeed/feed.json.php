<?php
/**
 * JSON Feed Plugin for DLE (DataLife Engine)
 * Формат соответствует спецификации JSON Feed 1.1
 * 
 * Доступные параметры:
 * - limit - количество записей (по умолч. из конфига, макс. из конфига)
 * - category - фильтр по ID категории (опционально)
 * - type - тип контента (main, blog, portfolio, works и т.д.)
 * - order - сортировка (date_desc, date_asc)
 * 
 * Красивые URL через .htaccess:
 * - /jsonfeed.json → основная лента
 * - /jsonfeed-aeolus.json → лента типа "aeolus"
 * - /jsonfeed-multimedia.json → лента типа "multimedia"
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

// Формирование JSON Feed
$feed = [
    'version' => 'https://jsonfeed.org/version/1.1',
    'title' => $feedTitle,
    'home_page_url' => $baseUrl,
    'feed_url' => $baseUrl . '/plugins/tcse/jsonfeed/feed.json.php' 
        . ($typeFilter !== 'main' ? '?type=' . $typeFilter : ''),
    'description' => $feedDescription,
    'user_comment' => 'JSON Feed формат для AI-агентов и RSS-ридеров. Спецификация: https://jsonfeed.org/version/1.1',
    'favicon' => $baseUrl . '/favicon.ico',
    'icon' => $baseUrl . '/uploads/jsonfeed-logo.png',
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
    if (!empty($post['author'])) {
        // Пытаемся получить имя автора из таблицы users
        $authorInfo = $db->super_query("SELECT name FROM " . PREFIX . "_users WHERE user_id = '" . $db->safesql($post['author']) . "'");
        $authorName = $authorInfo['name'] ?? $post['author'];
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
    
    // Изображение
    $image = '';
    if ($jsonfeed_config['enable_images'] ?? true) {
        preg_match('/<img[^>]+src="([^">]+)"/', $post['short_story'], $matches);
        if (!empty($matches[1])) {
            $image = $matches[1];
            if (strpos($image, 'http') !== 0) {
                $image = $baseUrl . $image;
            }
        }
        if (!$image && isset($post['xfields_array']['pic']) && !empty($post['xfields_array']['pic'])) {
            $image = $post['xfields_array']['pic'];
            if (strpos($image, 'http') !== 0) {
                $image = $baseUrl . $image;
            }
        }
    }
    
    // Формируем описание
    $cleanShort = strip_tags($post['short_story']);
    $cleanShort = preg_replace('/\s+/', ' ', $cleanShort);
    $summary = mb_substr($cleanShort, 0, 300);
    if (empty($summary)) {
        $summary = $post['title'];
    }
    
    // HTML-контент
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
    
    // Добавляем теги из поля tags
    if (($jsonfeed_config['enable_tags'] ?? true) && !empty($post['tags'])) {
        $tagsList = array_map('trim', explode(',', $post['tags']));
        $item['_tags_extended'] = $tagsList;
    }
    
    // Добавляем изображение
    if ($image && ($jsonfeed_config['enable_images'] ?? true)) {
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