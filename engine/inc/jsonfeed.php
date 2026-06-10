<?php
/*
=====================================================
 DLE Json Feed - by TCSE 
-----------------------------------------------------
 Версия: 0.1.4 от 2026-06-10
-----------------------------------------------------
 Страница продукта: https://tcse-cms.com
-----------------------------------------------------
 Репозиторий: https://github.com/tcse/DLE-jsonfeed
-----------------------------------------------------
 Copyright (c) 2026 Vitaly V. Chuyakov
=====================================================
 This code is protected by copyright
=====================================================
 Файл: engine/inc/jsonfeed.php
-----------------------------------------------------
 ПРЕДНАЗНАЧЕНИЕ:
   Файл инициализации плагина в админ-панели DLE.
   Обрабатывает POST-запросы из админки, собирает настройки
   из формы и сохраняет их в конфигурационный файл.
   
   Также подключает основной модуль отображения настроек
   (engine/modules/jsonfeed/main.php) и выводит страницу
   в админ-панели DLE.
-----------------------------------------------------
 ЗАДАЧИ ФАЙЛА:
   1. Защита от прямого обращения (DATALIFEENGINE)
   2. Получение и валидация данных из POST-формы
   3. Формирование массива конфигурации $jsonfeed_config
   4. Сохранение конфигурации в /plugins/tcse/jsonfeed/config.php
   5. Подключение файла отображения админ-интерфейса
   6. Вывод заголовка и подвала страницы админ-панели
=====================================================
*/
if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
    die('Hacking attempt!');
}

define('JSONFEED_MODULE_DIR', ENGINE_DIR . '/modules/jsonfeed');

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    
    // Собираем настройки из формы
    $jsonfeedConfig = [
        // НОВЫЕ ПОЛЯ: название и описание главной ленты
        'feed_title_main' => trim($_POST['feed_title_main'] ?? ($config['home_title'] ?? 'Новости сайта')),
        'feed_description_main' => trim($_POST['feed_description_main'] ?? ('Новости и публикации с сайта ' . ($config['home_title'] ?? ''))),
        
        // НОВЫЕ ПОЛЯ: иконки
        'feed_icon_url' => trim($_POST['feed_icon_url'] ?? ''),
        'feed_favicon_url' => trim($_POST['feed_favicon_url'] ?? ''),
        
        // Основные настройки
        'items_per_page' => intval($_POST['items_per_page'] ?? 20),
        'max_items' => intval($_POST['max_items'] ?? 100),
        'cache_time' => intval($_POST['cache_time'] ?? 3600),
        'enable_cache' => isset($_POST['enable_cache']) ? true : false,
        'image_quality' => intval($_POST['image_quality'] ?? 80),
        'enable_categories' => isset($_POST['enable_categories']) ? true : false,
        'enable_tags' => isset($_POST['enable_tags']) ? true : false,
        'enable_images' => isset($_POST['enable_images']) ? true : false
    ];
    
    // Типы контента (сохраняем как массив)
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
    $jsonfeedConfig['content_types'] = $content_types;
    
    // Настройки для разных типов лент
    $feeds = [];
    if (isset($_POST['feed_type']) && is_array($_POST['feed_type'])) {
        foreach ($_POST['feed_type'] as $idx => $type) {
            if (!empty($type)) {
                $feeds[trim($type)] = [
                    'title' => trim($_POST['feed_title'][$idx] ?? ''),
                    'description' => trim($_POST['feed_description'][$idx] ?? ''),
                    'limit' => intval($_POST['feed_limit'][$idx] ?? 20)
                ];
            }
        }
    }
    $jsonfeedConfig['feeds'] = $feeds;
    
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
    $configContent .= "\$jsonfeed_config = " . var_export($jsonfeedConfig, true) . ";\n";
    
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
        'feed_title_main' => $config['home_title'] ?? 'Новости сайта',
        'feed_description_main' => 'Новости и публикации с сайта ' . ($config['home_title'] ?? ''),
        'feed_icon_url' => '',
        'feed_favicon_url' => '',
        'items_per_page' => 20,
        'max_items' => 100,
        'cache_time' => 3600,
        'enable_cache' => false,
        'image_quality' => 80,
        'enable_categories' => true,
        'enable_tags' => true,
        'enable_images' => true,
        'content_types' => [],
        'feeds' => []
    ];
}

// Заголовок страницы
$dle_module_title = "JSON Feed - Настройки генерации AI-фида";
echoheader('jsonfeed', $dle_module_title);
include JSONFEED_MODULE_DIR . '/main.php';
echofooter();