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
