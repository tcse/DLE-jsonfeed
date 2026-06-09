<?php
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

$jsonfeed_config = [
    // Основные настройки
    'items_per_page' => 20,
    'max_items' => 100,
    'cache_time' => 3600,
    'enable_cache' => false,
    
    // Качество изображений
    'image_quality' => 80,
    
    // Включение/выключение элементов
    'enable_categories' => true,
    'enable_tags' => true,
    'enable_images' => false,
    
    // Типы контента и соответствующие категории
    'content_types' => array (
  'blog' => 
  array (
    0 => 2,
  ),
  'techlib' => 
  array (
    0 => 3,
  ),
),
    
    // Настройки для разных типов лент
    'feeds' => array (
  'blog' => 
  array (
    'title' => 'Блог - статьи и новости',
    'description' => 'Статьи из блога ПромИндуктора',
    'limit' => 30,
  ),
  'techlib' => 
  array (
    'title' => 'Техническая библиотека',
    'description' => 'Документация по продукции ПромТВЧ',
    'limit' => 20,
  ),
)
];
