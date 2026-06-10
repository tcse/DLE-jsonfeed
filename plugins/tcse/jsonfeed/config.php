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
 Файл: plugins/tcse/jsonfeed/config.php
-----------------------------------------------------
 ПРЕДНАЗНАЧЕНИЕ:
   Конфигурационный файл плагина. Создаётся автоматически
   при сохранении настроек в админ-панели DLE.
   
   Содержит все настройки плагина в виде массива $jsonfeed_config.
   Файл перезаписывается при каждом сохранении настроек.
-----------------------------------------------------
 СТРУКТУРА КОНФИГУРАЦИИ:
   $jsonfeed_config = [
       // Основные настройки ленты
       'feed_title_main'        => 'Название главной ленты',
       'feed_description_main'  => 'Описание главной ленты',
       
       // Иконки (рекомендуемые размеры)
       'feed_favicon_url'       => 'URL иконки 32×32',
       'feed_icon_url'          => 'URL иконки 512×512',
       
       // Технические параметры
       'items_per_page'         => 20,  // Количество записей по умолчанию
       'max_items'              => 100, // Максимальное количество
       'cache_time'             => 3600,// Время кэширования (сек)
       'enable_cache'           => false,// Включить кэширование
       'image_quality'          => 80,  // Качество изображений (%)
       
       // Включение/выключение элементов
       'enable_categories'      => true,// Показывать категории
       'enable_tags'            => true,// Показывать теги
       'enable_images'          => true,// Показывать изображения
       
       // Типы контента для фильтрации
       'content_types'          => [ 'blog' => [1,2,3] ],
       
       // Настройки отдельных лент (ЧПУ)
       'feeds'                  => [ 'blog' => [...] ]
   ];
=====================================================
*/
/**
 * JSON Feed Plugin for DLE - Configuration
 * Формат соответствует спецификации JSON Feed 1.1
 * 
 * @version 1.0
 * @author TCSE CMS
 * @copyright 2026
 */

if (!defined('DATALIFEENGINE') && !defined('JSONFEED_INIT')) {
    die('Hacking attempt!');
}

$jsonfeed_config = array (
  'feed_title_main' => '',
  'feed_description_main' => '',
  'items_per_page' => 20,
  'max_items' => 100,
  'cache_time' => 3600,
  'enable_cache' => false,
  'image_quality' => 80,
  'enable_categories' => true,
  'enable_tags' => true,
  'enable_images' => false,
  'content_types' => 
  array (
    'news' => 
    array (
      0 => 1,
    ),
    'blog' => 
    array (
      0 => 2,
    ),
  ),
  'feeds' => 
  array (
    'news' => 
    array (
      'title' => 'Новости - статьи и новости',
      'description' => 'Новости сайта',
      'limit' => 30,
    ),
    'blog' => 
    array (
      'title' => 'Блог',
      'description' => 'Заметки',
      'limit' => 20,
    ),
  ),
);
