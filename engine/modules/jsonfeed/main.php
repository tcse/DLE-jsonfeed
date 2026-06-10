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
 Файл: engine/modules/jsonfeed/main.php
-----------------------------------------------------
 ПРЕДНАЗНАЧЕНИЕ:
   Основной модуль админ-панели плагина. Отвечает за
   визуальное отображение всех настроек и интерфейса
   управления плагином в админ-панели DLE.
   
-----------------------------------------------------
 ЗАДАЧИ ФАЙЛА:
   1. Отображение формы настроек с группами параметров:
      - Основные настройки (количество записей, кэширование)
      - Настройки иконок (favicon, icon)
      - Типы контента (content types)
      - Настройки отдельных лент (feeds)
   2. Вывод примеров использования URL для AI-агентов
   3. Инструкция по настройке .htaccess для ЧПУ
   4. Генерация динамических примеров на основе текущего URL
   5. JavaScript для динамического добавления/удаления строк
=====================================================
*/
if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
    die('Hacking attempt!');
}

// Выводим сообщения об ошибках/успехе
if (isset($success_message)) {
    echo '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' . $success_message . '</div>';
}
if (isset($error_message)) {
    echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' . $error_message . '</div>';
}
?>

<style>
    .jsonfeed-settings .panel {
        margin-bottom: 25px;
    }
    .jsonfeed-settings .panel-heading {
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    .jsonfeed-settings h4 {
        font-size: 16px;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
    }
    .jsonfeed-settings h5 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .jsonfeed-settings .text-muted {
        font-size: 12px;
        color: #777;
    }
    .jsonfeed-settings .help-block {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
    }
    .jsonfeed-settings pre {
        background: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        font-size: 12px;
    }
    .jsonfeed-settings .example-url {
        font-family: monospace;
        background: #f0f0f0;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 12px;
    }
    .jsonfeed-settings .badge-cat {
        display: inline-block;
        background: #337ab7;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        margin: 2px;
    }
    .content-type-row {
        background: #f9f9f9;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 4px;
        border-left: 3px solid #337ab7;
    }
    .feed-row {
        background: #f9f9f9;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 4px;
        border-left: 3px solid #5cb85c;
    }
    .btn-add-row {
        margin-top: 10px;
    }
    .remove-row {
        margin-top: 20px;
    }
    .my-3 {
        margin-top: 2.5rem;
        margin-bottom: 2.5rem;
    }
</style>

<div class="jsonfeed-settings">
    <form action="" method="post" class="systemsettings">
        <input type="hidden" name="save_settings" value="1">
        
<!-- ========== ОСНОВНЫЕ НАСТРОЙКИ ========== -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-sliders"></i> Основные настройки JSON Feed
    </div>
    <div class="panel-body">
        
        <!-- НОВЫЕ ПОЛЯ: Название и описание ленты -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>📌 Название ленты (feed title)</label>
                    <input type="text" class="form-control" name="feed_title_main" 
                           value="<?php echo isset($jsonfeed_config['feed_title_main']) ? htmlspecialchars($jsonfeed_config['feed_title_main']) : ($config['home_title'] ?? 'Новости сайта'); ?>">
                    <span class="help-block">Заголовок, который будет отображаться в JSON Feed. По умолчанию: название сайта</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>📝 Описание ленты (feed description)</label>
                    <textarea class="form-control" name="feed_description_main" rows="2"><?php echo isset($jsonfeed_config['feed_description_main']) ? htmlspecialchars($jsonfeed_config['feed_description_main']) : ('Новости и публикации с сайта ' . ($config['home_title'] ?? '')); ?></textarea>
                    <span class="help-block">Описание ленты. По умолчанию: "Новости и публикации с сайта НАЗВАНИЕ_САЙТА"</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>🖼 URL иконки ленты (icon)</label>
                    <input type="text" class="form-control" name="feed_icon_url" 
                           value="<?php echo isset($jsonfeed_config['feed_icon_url']) ? htmlspecialchars($jsonfeed_config['feed_icon_url']) : ''; ?>"
                           placeholder="/uploads/jsonfeed-logo.png">
                    <span class="help-block">Рекомендуемый размер: 512×512 px (квадратное изображение). Оставьте пустым для использования логотипа сайта.</span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>⭐ URL favicon</label>
                    <input type="text" class="form-control" name="feed_favicon_url" 
                           value="<?php echo isset($jsonfeed_config['feed_favicon_url']) ? htmlspecialchars($jsonfeed_config['feed_favicon_url']) : ''; ?>"
                           placeholder="/favicon.ico">
                    <span class="help-block">Рекомендуемый размер: 32×32 px. Обычно используется /favicon.ico</span>
                </div>
            </div>
        </div>
        
        <hr class="my-3">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>📄 Количество записей по умолчанию</label>
                    <input type="number" class="form-control" name="items_per_page" 
                           value="<?php echo $jsonfeed_config['items_per_page']; ?>" min="1" max="50">
                    <span class="help-block">Сколько записей показывать в ленте по умолчанию (1-50)</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>📊 Максимальное количество записей</label>
                    <input type="number" class="form-control" name="max_items" 
                           value="<?php echo $jsonfeed_config['max_items']; ?>" min="10" max="500">
                    <span class="help-block">Максимальное количество записей через параметр &limit= (10-500)</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>⏱ Время кэширования (секунд)</label>
                    <input type="number" class="form-control" name="cache_time" 
                           value="<?php echo $jsonfeed_config['cache_time']; ?>" min="0" max="86400">
                    <span class="help-block">0 - кэширование отключено. Рекомендуется 3600 (1 час)</span>
                </div>
            </div>
            <!-- <div class="col-md-6">
                <div class="form-group">
                    <label>🖼 Качество изображений (%)</label>
                    <input type="number" class="form-control" name="image_quality" 
                           value="<?php echo $jsonfeed_config['image_quality']; ?>" min="30" max="100">
                    <span class="help-block">Качество JPEG-изображений в ленте (30-100)</span>
                </div>
            </div> -->
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="enable_cache" value="1" <?php echo $jsonfeed_config['enable_cache'] ? 'checked' : ''; ?>>
                        💾 Включить кэширование
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="enable_categories" value="1" <?php echo $jsonfeed_config['enable_categories'] ? 'checked' : ''; ?>>
                        📂 Показывать категории
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="enable_tags" value="1" <?php echo $jsonfeed_config['enable_tags'] ? 'checked' : ''; ?>>
                        🏷 Показывать теги
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
        
        <!-- ========== ТИПЫ КОНТЕНТА ========== -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-tags"></i> Типы контента (фильтрация по категориям)
            </div>
            <div class="panel-body">
                <p class="text-muted">Настройте типы контента для фильтрации через параметр <code>&type=название</code>. 
                Например: <span class="example-url">/jsonfeed.json?type=blog</span> покажет только записи из указанных категорий.</p>
                
                <div id="content-types-container">
                    <?php 
                    $content_types = $jsonfeed_config['content_types'];
                    if (!empty($content_types)):
                        foreach ($content_types as $type_name => $categories):
                    ?>
                    <div class="content-type-row">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Название типа</label>
                                    <input type="text" class="form-control" name="content_type_name[]" 
                                           value="<?php echo htmlspecialchars($type_name); ?>" placeholder="blog, portfolio, works">
                                    <span class="help-block">Используется в URL: <code>&type=<?php echo htmlspecialchars($type_name); ?></code></span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>ID категорий (через запятую)</label>
                                    <input type="text" class="form-control" name="content_type_cats[]" 
                                           value="<?php echo implode(',', $categories); ?>" placeholder="1,2,3">
                                    <span class="help-block">ID категорий, которые будут включены в этот тип</span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest('.content-type-row').remove()">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="content-type-row">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Название типа</label>
                                    <input type="text" class="form-control" name="content_type_name[]" placeholder="blog">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>ID категорий</label>
                                    <input type="text" class="form-control" name="content_type_cats[]" placeholder="1,19,20">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest('.content-type-row').remove()">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-default btn-sm btn-add-row" onclick="addContentTypeRow()">
                    <i class="fa fa-plus"></i> Добавить тип контента
                </button>
            </div>
        </div>
        
        <!-- ========== НАСТРОЙКИ ЛЕНТ ========== -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-rss-square"></i> Настройки лент (feed)
            </div>
            <div class="panel-body">
                <p class="text-muted">Настройте отдельные ленты для красивых URL. Например: 
                <span class="example-url">/jsonfeed-blog.json</span> → будет использовать настройки ленты "blog"</p>
                
                <div id="feeds-container">
                    <?php 
                    $feeds = $jsonfeed_config['feeds'];
                    if (!empty($feeds)):
                        foreach ($feeds as $feed_type => $feed_data):
                    ?>
                    <div class="feed-row">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Тип ленты (используется в URL)</label>
                                    <input type="text" class="form-control" name="feed_type[]" 
                                           value="<?php echo htmlspecialchars($feed_type); ?>" placeholder="main, blog, portfolio">
                                    <span class="help-block">URL: <code>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json</code></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Заголовок ленты (title)</label>
                                    <input type="text" class="form-control" name="feed_title[]" 
                                           value="<?php echo htmlspecialchars($feed_data['title']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Лимит записей</label>
                                    <input type="number" class="form-control" name="feed_limit[]" 
                                           value="<?php echo $feed_data['limit']; ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest('.feed-row').remove()">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Описание ленты (description)</label>
                                    <textarea class="form-control" name="feed_description[]" rows="2"><?php echo htmlspecialchars($feed_data['description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="feed-row">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Тип ленты</label>
                                    <input type="text" class="form-control" name="feed_type[]" placeholder="main">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Заголовок ленты</label>
                                    <input type="text" class="form-control" name="feed_title[]" placeholder="Новости сайта">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Лимит записей</label>
                                    <input type="number" class="form-control" name="feed_limit[]" value="20">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest('.feed-row').remove()">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Описание ленты</label>
                                    <textarea class="form-control" name="feed_description[]" rows="2" placeholder="Описание ленты..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-default btn-sm btn-add-row" onclick="addFeedRow()">
                    <i class="fa fa-plus"></i> Добавить ленту
                </button>
            </div>
        </div>
        
<!-- ========== ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ ========== -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-code"></i> Примеры URL для использования
    </div>
    <div class="panel-body">
        
        <?php
        // Динамический адрес сайта для примеров
        $siteUrl = $config['http_home_url'] ?? ((isSSL() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
        $siteUrl = rtrim($siteUrl, '/');
        ?>
        
        <h4>📡 Основные ссылки JSON Feed</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th style="width: 40%;">Описание</th><th style="width: 60%;">URL</th></tr>
            </thead>
            <tbody>
                <tr><td>📄 Основная лента (все записи)</td><td><code><?php echo $siteUrl; ?>/plugins/tcse/jsonfeed/feed.json.php</code></td></tr>
                <tr><td>📄 Основная лента (через ЧПУ)</td><td><code><?php echo $siteUrl; ?>/jsonfeed.json</code></td></tr>
                <?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
                <tr>
                    <td>📄 Лента "<?php echo htmlspecialchars($feed_type); ?>"</td>
                    <td><code><?php echo $siteUrl; ?>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json</code></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        
        <h4>🔧 Параметры фильтрации</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th style="width: 40%;">Параметр</th><th style="width: 60%;">Пример</th></tr>
            </thead>
            <tbody>
                <tr><td><code>&limit=N</code> — количество записей</td>
                    <td><code><?php echo $siteUrl; ?>/jsonfeed.json?limit=30</code></td>
                </tr>
                <tr><td><code>&category=ID</code> — фильтр по категории</td>
                    <td><code><?php echo $siteUrl; ?>/jsonfeed.json?category=20</code></td>
                </tr>
                <tr>
                    <td><code>&type=НАЗВАНИЕ</code> — фильтр по типу контента</td>
                    <td>
                        <?php if (!empty($jsonfeed_config['content_types'])): 
                            foreach ($jsonfeed_config['content_types'] as $type_name => $cats): ?>
                                <code><?php echo $siteUrl; ?>/jsonfeed.json?type=<?php echo htmlspecialchars($type_name); ?></code><br>
                            <?php endforeach; 
                        else: ?>
                            — (нет настроенных типов)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>&order=date_asc|date_desc</code> — сортировка</td>
                    <td>
                        <code><?php echo $siteUrl; ?>/jsonfeed.json?order=date_asc</code>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h4>🌐 Примеры для AI-агентов</h4>
        <pre>
# Для ChatGPT, Claude, Perplexity AI можно использовать прямые ссылки:
<?php echo $siteUrl; ?>/jsonfeed.json

<?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
# Лента "<?php echo htmlspecialchars($feed_data['title']); ?>":
<?php echo $siteUrl; ?>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json
<?php endforeach; endif; ?>
# Подписка в RSS-ридерах (Feedly, Inoreader):
<?php echo $siteUrl; ?>/jsonfeed.json
<?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
<?php echo $siteUrl; ?>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json
<?php endforeach; endif; ?>
# Импорт в Telegram-боты:
<?php echo $siteUrl; ?>/jsonfeed.json?limit=20
        </pre>
        
        <h4>📋 HTML-код для вставки в шаблон</h4>
        <pre>
&lt;!-- JSON Feed для AI-агентов — добавить в &lt;head&gt; --&gt;
&lt;link rel="alternate" type="application/feed+json" title="JSON Feed - Все записи" href="<?php echo $siteUrl; ?>/jsonfeed.json"&gt;
<?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
&lt;link rel="alternate" type="application/feed+json" title="JSON Feed - <?php echo htmlspecialchars($feed_data['title']); ?>" href="<?php echo $siteUrl; ?>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json"&gt;
<?php endforeach; endif; ?>
        </pre>
        
        <!-- ========== БЛОК НАСТРОЙКИ .HTACCESS ========== -->
        <h4>⚙️ Настройка ЧПУ (красивых URL) в .htaccess</h4>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            <strong>Важно:</strong> Для работы красивых URL вида <code>/jsonfeed.json</code> и <code>/jsonfeed-blog.json</code> 
            необходимо добавить правила в файл <strong>.htaccess</strong> в корне сайта.
        </div>
        
        <p><strong>Инструкция:</strong></p>
        <ol>
            <li>Откройте файл <code>.htaccess</code> в корне вашего сайта</li>
            <li>Найдите блок с правилами для статических файлов (обычно выглядит так):
                <pre>RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \.(jpg|jpeg|gif|ico|png|svg|webp|avif|js|css|mp3|ogg|mp4|mkv|avi|zip|rar|woff|woff2)(\?|$) - [L,NC,R=404]</pre>
            </li>
            <li><strong>ВЫШЕ</strong> этого блока (перед ним) вставьте следующие правила:
                <pre># ===== JSON Feed (AI-формат) =====
# Полная лента
RewriteRule ^jsonfeed\.json$ /plugins/tcse/jsonfeed/feed.json.php?limit=20 [L,QSA]

<?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
# Лента "<?php echo htmlspecialchars($feed_type); ?>"
RewriteRule ^jsonfeed-<?php echo htmlspecialchars($feed_type); ?>\.json$ /plugins/tcse/jsonfeed/feed.json.php?type=<?php echo htmlspecialchars($feed_type); ?> [L,QSA]
<?php endforeach; endif; ?>

# Динамическая категория
RewriteRule ^jsonfeed/category/([0-9]+)/?$ /plugins/tcse/jsonfeed/feed.json.php?category=$1&limit=30 [L,QSA]</pre>
            </li>
            <li>Сохраните файл <code>.htaccess</code></li>
            <li>Проверьте работу ссылок:
                <ul>
                    <li><code><?php echo $siteUrl; ?>/jsonfeed.json</code></li>
                    <?php if (!empty($jsonfeed_config['feeds'])): foreach ($jsonfeed_config['feeds'] as $feed_type => $feed_data): ?>
                    <li><code><?php echo $siteUrl; ?>/jsonfeed-<?php echo htmlspecialchars($feed_type); ?>.json</code></li>
                    <?php endforeach; endif; ?>
                </ul>
            </li>
        </ol>
        
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            <strong>Примечание:</strong> Если в вашем <code>.htaccess</code> нет указанного выше блока, 
            просто добавьте правила JSON Feed в самое начало файла, после строки <code>RewriteEngine On</code>.
        </div>
        
    </div>
</div>
        
        <!-- ========== КНОПКА СОХРАНЕНИЯ ========== -->
        <div class="panel panel-default">
            <div class="panel-body">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-floppy-o"></i> Сохранить настройки
                </button>
                <a href="/plugins/tcse/jsonfeed/feed.json.php" class="btn btn-default btn-lg" target="_blank">
                    <i class="fa fa-external-link"></i> Просмотреть JSON Feed
                </a>
                <a href="/jsonfeed.json" class="btn btn-default btn-lg" target="_blank">
                    <i class="fa fa-external-link"></i> Просмотреть через ЧПУ
                </a>
            </div>
        </div>
    </form>
</div>

<script>
// Функция добавления нового типа контента
function addContentTypeRow() {
    var html = '<div class="content-type-row">' +
        '<div class="row">' +
        '<div class="col-md-3">' +
        '<div class="form-group">' +
        '<label>Название типа</label>' +
        '<input type="text" class="form-control" name="content_type_name[]" placeholder="blog">' +
        '<span class="help-block">Используется в URL: <code>&type=...</code></span>' +
        '</div>' +
        '</div>' +
        '<div class="col-md-8">' +
        '<div class="form-group">' +
        '<label>ID категорий (через запятую)</label>' +
        '<input type="text" class="form-control" name="content_type_cats[]" placeholder="1,2,3">' +
        '<span class="help-block">ID категорий, которые будут включены в этот тип</span>' +
        '</div>' +
        '</div>' +
        '<div class="col-md-1">' +
        '<button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest(\'.content-type-row\').remove()">' +
        '<i class="fa fa-trash"></i>' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    $('#content-types-container').append(html);
}

// Функция добавления новой ленты
function addFeedRow() {
    var html = '<div class="feed-row">' +
        '<div class="row">' +
        '<div class="col-md-12">' +
        '<div class="form-group">' +
        '<label>Тип ленты (используется в URL)</label>' +
        '<input type="text" class="form-control" name="feed_type[]" placeholder="main, blog, portfolio">' +
        '<span class="help-block">URL: <code>/jsonfeed-ИМЯ.json</code></span>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6">' +
        '<div class="form-group">' +
        '<label>Заголовок ленты (title)</label>' +
        '<input type="text" class="form-control" name="feed_title[]" placeholder="Новости сайта">' +
        '</div>' +
        '</div>' +
        '<div class="col-md-4">' +
        '<div class="form-group">' +
        '<label>Лимит записей</label>' +
        '<input type="number" class="form-control" name="feed_limit[]" value="20">' +
        '</div>' +
        '</div>' +
        '<div class="col-md-2">' +
        '<button type="button" class="btn btn-danger btn-sm remove-row" style="margin-top: 24px;" onclick="$(this).closest(\'.feed-row\').remove()">' +
        '<i class="fa fa-trash"></i>' +
        '</button>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-12">' +
        '<div class="form-group">' +
        '<label>Описание ленты (description)</label>' +
        '<textarea class="form-control" name="feed_description[]" rows="2" placeholder="Описание ленты..."></textarea>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    $('#feeds-container').append(html);
}
</script>