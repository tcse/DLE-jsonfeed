<?php
if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
    die('Hacking attempt!');
}

define('JSONFEED_MODULE_DIR', ENGINE_DIR . '/modules/jsonfeed');

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    
    // Собираем настройки из формы
    $jsonfeedConfig = [
        'items_per_page' => intval($_POST['items_per_page'] ?? 20),
        'max_items' => intval($_POST['max_items'] ?? 100),
        'cache_time' => intval($_POST['cache_time'] ?? 3600),
        'enable_cache' => isset($_POST['enable_cache']) ? true : false,
        'image_quality' => intval($_POST['image_quality'] ?? 80),
        'enable_categories' => isset($_POST['enable_categories']) ? true : false,
        'enable_tags' => isset($_POST['enable_tags']) ? true : false,
        'enable_images' => isset($_POST['enable_images']) ? true : false
    ];
    
    // Типы контента (сохраняем как JSON)
    $content_types = [];
    if (isset($_POST['content_type_name']) && is_array($_POST['content_type_name'])) {
        foreach ($_POST['content_type_name'] as $idx => $name) {
            if (!empty($name) && !empty($_POST['content_type_cats'][$idx])) {
                $cats = explode(',', $_POST['content_type_cats'][$idx]);
                $cats = array_map('intval', $cats);
                $content_types[trim($name)] = $cats;
            }
        }
    }
    
    // Настройки для разных типов лент
    $feeds = [];
    if (isset($_POST['feed_type']) && is_array($_POST['feed_type'])) {
        foreach ($_POST['feed_type'] as $idx => $type) {
            if (!empty($type)) {
                $feeds[$type] = [
                    'title' => trim($_POST['feed_title'][$idx] ?? ''),
                    'description' => trim($_POST['feed_description'][$idx] ?? ''),
                    'limit' => intval($_POST['feed_limit'][$idx] ?? 20)
                ];
            }
        }
    }
    
    // Формируем содержимое конфиг-файла
    $configContent = "<?php\n";
    $configContent .= "/**\n";
    $configContent .= " * JSON Feed Plugin for DLE - Configuration\n";
    $configContent .= " * Формат соответствует спецификации JSON Feed 1.1\n";
    $configContent .= " * \n";
    $configContent .= " * @version 1.0\n";
    $configContent .= " * @author TCSE CMS\n";
    $configContent .= " * @copyright " . date('Y') . "\n";
    $configContent .= " */\n\n";
    $configContent .= "if (!defined('DATALIFEENGINE') && !defined('JSONFEED_INIT')) {\n";
    $configContent .= "    die('Hacking attempt!');\n";
    $configContent .= "}\n\n";
    $configContent .= "\$jsonfeed_config = [\n";
    $configContent .= "    // Основные настройки\n";
    $configContent .= "    'items_per_page' => " . $jsonfeedConfig['items_per_page'] . ",\n";
    $configContent .= "    'max_items' => " . $jsonfeedConfig['max_items'] . ",\n";
    $configContent .= "    'cache_time' => " . $jsonfeedConfig['cache_time'] . ",\n";
    $configContent .= "    'enable_cache' => " . ($jsonfeedConfig['enable_cache'] ? 'true' : 'false') . ",\n";
    $configContent .= "    \n";
    $configContent .= "    // Качество изображений\n";
    $configContent .= "    'image_quality' => " . $jsonfeedConfig['image_quality'] . ",\n";
    $configContent .= "    \n";
    $configContent .= "    // Включение/выключение элементов\n";
    $configContent .= "    'enable_categories' => " . ($jsonfeedConfig['enable_categories'] ? 'true' : 'false') . ",\n";
    $configContent .= "    'enable_tags' => " . ($jsonfeedConfig['enable_tags'] ? 'true' : 'false') . ",\n";
    $configContent .= "    'enable_images' => " . ($jsonfeedConfig['enable_images'] ? 'true' : 'false') . ",\n";
    $configContent .= "    \n";
    $configContent .= "    // Типы контента и соответствующие категории\n";
    $configContent .= "    'content_types' => " . var_export($content_types, true) . ",\n";
    $configContent .= "    \n";
    $configContent .= "    // Настройки для разных типов лент\n";
    $configContent .= "    'feeds' => " . var_export($feeds, true) . "\n";
    $configContent .= "];\n";
    
    // Путь к файлу конфига
    $configFile = $_SERVER['DOCUMENT_ROOT'] . '/plugins/tcse/jsonfeed/config.php';
    
    // Создаем папку если её нет
    $configDir = dirname($configFile);
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    // Записываем новый конфиг
    if (file_put_contents($configFile, $configContent)) {
        $success_message = '✅ Настройки успешно сохранены!';
    } else {
        $error_message = '❌ Ошибка при записи конфига. Проверьте права доступа к папке /plugins/tcse/jsonfeed/';
    }
}

// Загружаем текущий конфиг
$configFile = $_SERVER['DOCUMENT_ROOT'] . '/plugins/tcse/jsonfeed/config.php';
if (file_exists($configFile)) {
    include_once($configFile);
}

// Устанавливаем значения по умолчанию, если конфиг не загрузился
if (!isset($jsonfeed_config)) {
    $jsonfeed_config = [
        'items_per_page' => 20,
        'max_items' => 100,
        'cache_time' => 3600,
        'enable_cache' => false,
        'image_quality' => 80,
        'enable_categories' => true,
        'enable_tags' => true,
        'enable_images' => true,
        'content_types' => [
            'blog' => [1, 19, 20],
            'portfolio' => [2],
            'works' => [21, 31, 34, 37],
            'faq' => [40],
            'tmh' => [41]
        ],
        'feeds' => [
            'main' => [
                'title' => 'Новости сайта',
                'description' => 'Новости и публикации сайта',
                'limit' => 20
            ],
            'blog' => [
                'title' => 'Блог - статьи и новости',
                'description' => 'Статьи из блога сайта',
                'limit' => 30
            ],
            'portfolio' => [
                'title' => 'Портфолио - наши работы',
                'description' => 'Проекты из портфолио',
                'limit' => 20
            ]
        ]
    ];
}

// Заголовок страницы
$dle_module_title = "JSON Feed - Настройки генерации AI-фида";
echoheader('jsonfeed', $dle_module_title);
include JSONFEED_MODULE_DIR . '/main.php';
echofooter();