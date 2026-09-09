![phpstan-level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen)
![pest-php](https://img.shields.io/badge/Tests-%20Passed-brightgreen)

# Session

A lightweight, predictable and fully‑optimized session manager for PHP‑FPM applications. This package provides a complete, production‑ready implementation of PHP sessions with:
- custom lifecycle
- automatic expiration
- secure regeneration
- pluggable storage handlers
- optimized SQLite backend
- strict and safe defaults
  
It builds on top of PHP’s native session_start() and $_SESSION, but adds a clean, modern and extensible architecture that avoids the pitfalls of the default PHP session system.

## ✨ Features

- **[todo]**  
  [todo]

- **Drop‑in replacement for native PHP sessions**  
Uses session_start() and $_SESSION, but with a predictable lifecycle and safer defaults.

- **Custom session lifecycle**  
Automatic expiration based on last activity, configurable regeneration interval, and strict mode enabled by default.

- **Pluggable storage handlers**  
  Choose between:
    - File‑based storage (optimized)
    - SQLite storage (high‑performance, WAL, mmap, lazy I/O, internal cache)

- **Optimized SQLite handler**  
  WAL mode, mmap, prepared statements, lazy writes, internal RAM cache, WITHOUT ROWID tables, and indexed GC.

- **Secure defaults**  
  Strict mode, cookie‑only sessions, SameSite support, secure cookies when HTTPS is detected.

- **Predictable behavior**  
  No magic. No framework lock‑in. No hidden side effects.

- **Simple integration**  
  Works with any router, middleware pipeline or DI container in PHP‑FPM environments.

---

## 📦 Installation

Install via Composer:

```bash
composer require stougeiro/session
```

## 🚀 Usage Example

### Basic usage

```php
use STDW\Session\SessionConfig;
use STDW\Session\Session;

$config = new SessionConfig([
    'name' => 'MYSESSID',
    'handler' => 'sqlite', // or "file"
    'storage' => __DIR__ .'/path/to/storage',

    'gc' => [
        'maxlifetime' => 1800, // 30 minutes
        'probability' => 1,
        'divisor' => 100,
    ],

    'cookie' => [
        'lifetime' => 0,
        'same_site' => 'Lax',
    ],

    'extra' => [
        'regeneration' => true,
        'regeneration_time' => 300, // 5 minutes
    ],
]);

$session = new Session($config);
$session->start();

// store data
$session->set('user_id', 42);

// retrieve data
$userId = $session->get('user_id');

// remove data
$session->remove('user_id');

// destroy session
$session->destroy();
```

### Middleware example

```php
$session->start();

if ( ! $session->has('user_id')) {
    return redirect('/login');
}

// authenticated area...
```

---

## 🧠 Why?

PHP’s native session system is powerful, but its default behavior is:
- unpredictable
- hard to control
- inconsistent across environments
- tied to filesystem storage
- insecure unless manually configured

This package solves these problems by providing:
- a clean session lifecycle
- automatic expiration based on last activity
- secure regeneration
- strict mode always enabled
- cookie‑only sessions
- pluggable storage drivers
- optimized SQLite backend
- zero magic and zero framework lock‑in

It keeps the simplicity of $_SESSION, but adds the structure and reliability expected in modern PHP‑FPM applications.

In short:
- You keep the native PHP session API.
- You gain a predictable, secure and extensible session system.

---

## 🤝 Contributions

Contributions are welcome.
Feel free to open issues or submit pull requests.

<br><br>

[<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" width="170"/>](https://www.buymeacoffee.com/stougeiro)