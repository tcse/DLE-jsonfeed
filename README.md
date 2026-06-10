# DLE-jsonfeed

**JSON Feed формат для AI-агентов и RSS-ридеров**

Плагин для CMS DataLife Engine (DLE), который генерирует ленту в формате **JSON Feed 1.1** — современный аналог RSS для AI-агентов (ChatGPT, Claude, Perplexity), нейросетевых поисковых систем и RSS-агрегаторов.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![DLE Version](https://img.shields.io/badge/DLE-13.x%2B-green.svg)](https://dle-news.ru)
[![License](https://img.shields.io/badge/License-MIT-orange.svg)](LICENSE)

---

## 📌 Оглавление

- [Описание](#-описание)
- [Возможности](#-возможности)
- [Установка](#-установка)
- [Настройка](#-настройка)
- [Использование](#-использование)
- [Настройка .htaccess (ЧПУ)](#-настройка-htaccess-чпу)
- [Примеры URL](#-примеры-url)
- [Структура плагина](#-структура-плагина)
- [Лицензия](#-licensemedia)

---

## 📖 Описание

**JSON Feed** — это формат для представления лент (feeds) в формате JSON, разработанный как современная альтернатива RSS. Плагин `DLE-jsonfeed` позволяет экспортировать публикации вашего сайта на DLE в этот формат, что делает контент доступным для:

- **AI-агентов** (ChatGPT, Claude, Perplexity AI, Google SGE, Yandex GPT)
- **Современных RSS-ридеров** (Feedly, Inoreader, NetNewsWire)
- **Telegram-ботов** и других систем сбора контента
- **Поисковых систем**, поддерживающих JSON Feed

---

## ✨ Возможности

- ✅ Полная поддержка спецификации **JSON Feed 1.1**
- ✅ Гибкая фильтрация по **категориям** и **типам контента**
- ✅ **Красивые URL** через `.htaccess` (`/jsonfeed.json`, `/jsonfeed-blog.json`)
- ✅ Поддержка **нескольких лент** с разными настройками
- ✅ **Кэширование** для снижения нагрузки на сервер
- ✅ **Адаптивная админ-панель** для всех настроек
- ✅ Поддержка **авторов публикаций** из базы данных DLE
- ✅ **Кастомные иконки** для ленты (`favicon` и `icon`)
- ✅ Параметры фильтрации: `limit`, `category`, `type`, `order`

---

## 📥 Установка

### Способ 1: Через админ-панель DLE

1. Скачайте архив с плагином
2. В админ-панели DLE перейдите в **"Управление модулями"** → **"Установить модуль"**
3. Загрузите файл `jsonfeed.xml`
4. Нажмите **"Установить"**

### Способ 2: Вручную (через FTP)

1. Распакуйте архив плагина
2. Скопируйте содержимое папки `engine/` в корневую папку `engine/` на сервере
3. Скопируйте содержимое папки `plugins/` в корневую папку `plugins/` на сервере
4. Убедитесь, что создана папка `uploads/jsonfeed/` (для кэша)

---

## ⚙️ Настройка

После установки перейдите в админ-панель DLE:

**Админ-панель** → **"Настройки"** → **"JSON Feed"**

### Основные настройки

| Поле | Описание |
|------|----------|
| **Название ленты** | Заголовок, который будет отображаться в JSON Feed |
| **Описание ленты** | Краткое описание вашей ленты |
| **Количество записей** | Сколько записей показывать по умолчанию |
| **Максимум записей** | Максимальное количество через параметр `&limit=` |
| **Кэширование** | Время кэширования ленты (секунды) |
| **Качество изображений** | Качество JPEG-изображений (30-100) |

### Иконки ленты

| Поле | Размер | Описание |
|------|--------|----------|
| **URL favicon** | 32×32 px | Иконка для вкладок браузера |
| **URL иконки (icon)** | 512×512 px | Квадратная иконка для RSS-ридеров и AI-агентов |

### Типы контента

Настройте фильтрацию по категориям. Например:

| Тип | Категории | URL |
|-----|-----------|-----|
| `blog` | 1, 19, 20 | `/jsonfeed-blog.json` |
| `portfolio` | 2 | `/jsonfeed-portfolio.json` |
| `works` | 21, 31, 34, 37 | `/jsonfeed-works.json` |

---

## 🚀 Использование

### Базовые URL

| URL | Описание |
|-----|----------|
| `/plugins/tcse/jsonfeed/feed.json.php` | Основная лента |
| `/jsonfeed.json` | Основная лента (ЧПУ) |
| `/jsonfeed-blog.json` | Лента "blog" (если настроена) |
| `/jsonfeed-portfolio.json` | Лента "portfolio" (если настроена) |

### Параметры фильтрации

| Параметр | Пример | Описание |
|----------|--------|----------|
| `limit` | `?limit=50` | Количество записей (макс. 100) |
| `category` | `?category=20` | Фильтр по категории |
| `type` | `?type=blog` | Фильтр по типу контента |
| `order` | `?order=date_asc` | Сортировка (`date_asc`, `date_desc`) |

### Примеры для AI-агентов

```bash
# Для ChatGPT, Claude, Perplexity AI
https://ваш-сайт.ru/jsonfeed.json

# Для подписки в RSS-ридерах (Feedly, Inoreader)
https://ваш-сайт.ru/jsonfeed.json
https://ваш-сайт.ru/jsonfeed-blog.json

# Для Telegram-ботов
https://ваш-сайт.ru/jsonfeed.json?limit=20
```

---

## 🔧 Настройка .htaccess (ЧПУ)

Для работы красивых URL добавьте в файл `.htaccess` в корне сайта:

```apache
# ===== JSON Feed (AI-формат) =====
# Полная лента
RewriteRule ^jsonfeed\.json$ /plugins/tcse/jsonfeed/feed.json.php?limit=20 [L,QSA]

# Лента "blog"
RewriteRule ^jsonfeed-blog\.json$ /plugins/tcse/jsonfeed/feed.json.php?type=blog [L,QSA]

# Лента "portfolio"
RewriteRule ^jsonfeed-portfolio\.json$ /plugins/tcse/jsonfeed/feed.json.php?type=portfolio [L,QSA]

# Динамическая категория
RewriteRule ^jsonfeed/category/([0-9]+)/?$ /plugins/tcse/jsonfeed/feed.json.php?category=$1&limit=30 [L,QSA]
```

> **Важно:** Добавьте эти правила **ВЫШЕ** блока с обработкой статических файлов (jpg, css, js и т.д.)

---

## 📂 Структура плагина

```
DLE-jsonfeed/
├── engine/
│   ├── inc/
│   │   └── jsonfeed.php           # Инициализация и обработка настроек
│   └── modules/
│       └── jsonfeed/
│           └── main.php           # Админ-панель плагина
├── plugins/
│   └── tcse/
│       └── jsonfeed/
│           ├── config.php         # Конфигурация (создается автоматически)
│           └── feed.json.php      # Основной файл генерации JSON Feed
├── uploads/
│   └── jsonfeed/                  # Папка для кэша (должна быть доступна для записи)
├── jsonfeed.xml                   # Манифест для установки через админку DLE
└── README.md                      # Документация
```

---

## ❓ Часто задаваемые вопросы

### Какая версия DLE поддерживается?

Плагин поддерживает DLE 13.x и выше. Для версий 15+ поддерживаются дополнительные категории (`category2`, `category3`).

### Как часто обновляется лента?

Лента генерируется динамически при каждом запросе. Для снижения нагрузки на сервер можно включить кэширование в настройках плагина.

### Почему не отображается иконка в RSS-ридере?

Убедитесь, что:
1. В настройках указан корректный URL иконки (рекомендуемый размер 512×512)
2. Файл иконки доступен по указанному URL
3. Ридер поддерживает отображение иконок JSON Feed

### Можно ли использовать плагин на нескольких сайтах?

Да, плагин распространяется под лицензией MIT и может быть использован на любом количестве сайтов.

---

## 📝 Пример JSON Feed

```json
{
  "version": "https://jsonfeed.org/version/1.1",
  "title": "Модельный ряд AEOLUS",
  "home_page_url": "https://dongfeng-aeolus.ru",
  "feed_url": "https://dongfeng-aeolus.ru/plugins/tcse/jsonfeed/feed.json.php?type=aeolus",
  "description": "Общая информация",
  "favicon": "https://dongfeng-aeolus.ru/favicon.ico",
  "icon": "https://dongfeng-aeolus.ru/uploads/jsonfeed-logo.png",
  "authors": [
    {
      "name": "Администрация",
      "url": "https://dongfeng-aeolus.ru"
    }
  ],
  "language": "ru",
  "items": [
    {
      "id": "123",
      "url": "https://dongfeng-aeolus.ru/123-novost.html",
      "title": "Название публикации",
      "date_published": "2026-06-09T10:00:00+03:00",
      "content_html": "<p>Текст публикации...</p>"
    }
  ]
}
```

---

## 📄 Лицензия

MIT License. Подробнее в файле [LICENSE](LICENSE).

---

## 👨‍💻 Автор

**Vitaly V. Chuyakov (TCSE CMS)**

- GitHub: [@tcse](https://github.com/tcse)
- Website: [https://tcse-cms.com](https://tcse-cms.com)

---

## 🌟 Поддержка проекта

Если плагин оказался полезен, поставьте ⭐ на GitHub и поделитесь с коллегами!

---

**Актуальная версия:** 0.1.3 (10 июня 2026)
