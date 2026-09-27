<?php

use App\Database;

function resolve(string $alias): string {
    static $map = null;

    if ($map === null) {
        $map = [
            '@/Views'      => VIEW_PATH,
            '@/Components' => VIEW_PATH . '/components',
            '@/Layout'     => VIEW_PATH . '/layout',
            '@/Pages'      => VIEW_PATH . '/pages',
            '@/Src'        => BASE_PATH . '/src',
            '@/Config'     => BASE_PATH . '/config',
            '@/Public'     => BASE_PATH . '/public',
            ];
    }

    foreach ($map as $prefix => $dir) {
        if (str_starts_with($alias, $prefix)) {
            return $dir . substr($alias, strlen($prefix));
        }
    }

    throw new RuntimeException("Unknown alias: {$alias}");
}

function url(string $path = ''): string {
    // Prefer the explicit constant
    if (defined('BASE_URL')) {
        $base = rtrim(BASE_URL, '/');
    } else {
        // Fallback: guess from SCRIPT_NAME
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = in_array($scriptDir, ['/', '.', ''], true) ? '' : rtrim($scriptDir, '/');
    }

    $cleanPath = ltrim($path, '/');

    if ($cleanPath === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . '/' . $cleanPath;
}

// function url(string $path = ''): string {
//     $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
//     $base = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : rtrim($scriptDir, '/');
//     $cleanPath = ltrim($path, '/');
//     if ($cleanPath === '') {
//         return $base ?: '/';
//     }
//     return $base . '/' . $cleanPath;
// }


function asset(string $path): string {
    return url(ltrim($path, '/'));
}


function view(string $path, array $data = []): void {
    if (str_starts_with($path, '@')) {
        $full = resolve($path);
    } else {
        $cleanPath = ltrim($path, '/');
        $full = VIEW_PATH . '/' . $cleanPath;
    }

    if (!str_ends_with($full, '.php')) {
        $full .= '.php';
    }

    if (!is_file($full)) {
        $publicFallback = BASE_PATH . '/public/' . ltrim($path, '/');
        if (!str_ends_with($publicFallback, '.php')) {
            $publicFallback .= '.php';
        }

        if (is_file($publicFallback)) {
            $full = $publicFallback;
        } else {
            throw new RuntimeException("View not found: {$full}");
        }
    }

    extract($data, EXTR_SKIP);
    require $full;
}

function test_connection(bool $verbose = true): bool {
    try {
        $pdo = Database::connection();

        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $dbName  = $pdo->query('SELECT DATABASE()')->fetchColumn();

        if ($verbose) {
            echo "✅ Connected to '{$dbName}' (MySQL {$version})\n";
        }
        return true;

    } catch (\Throwable $e) {
        if ($verbose) {
            echo "❌ Connection failed: " . $e->getMessage() . "\n";
        }
        return false;
    }
}

function css(string $file): string {
    $file = ltrim($file, '/');
    if (!str_ends_with($file, '.css')) {
        $file .= '.css';
    }
    return url('css/' . $file);
}

function js(string $file): string {
    $file = ltrim($file, '/');
    if (!str_ends_with($file, '.js')) {
        $file .= '.js';
    }
    return url('js/' . $file);
}

function img(string $file): string {
    return url('assets/img/' . ltrim($file, '/'));
}

function font(string $file): string {
    return url('assets/fonts/' . ltrim($file, '/'));
}
function styles(string|array $files): string {
    $files = (array) $files;
    $html  = '';

    foreach ($files as $file) {
        $file = ltrim($file, '/');
        if (!str_ends_with($file, '.css')) {
            $file .= '.css';
        }

        $url    = css($file);
        $fsPath = BASE_PATH . '/public/css/' . $file;

        if (is_file($fsPath)) {
            $url .= '?v=' . filemtime($fsPath);
        }

        $html   .= '<link rel="stylesheet" href="'
                . htmlspecialchars($url, ENT_QUOTES) . '">' . "\n";
    }

    return $html;
}

function scripts(string|array $files): string {
    $files = (array) $files;
    $html  = '';

    foreach ($files as $file) {
        $file = ltrim($file, '/');
        if (!str_ends_with($file, '.js')) {
            $file .= '.js';
        }

        $url    = js($file);
        $fsPath = BASE_PATH . '/public/js/' . $file;

        if (is_file($fsPath)) {
            $url .= '?v=' . filemtime($fsPath);
        }

        $html .= '<script src="'
                . htmlspecialchars($url, ENT_QUOTES)
                . '" defer></script>' . "\n";
    }

    return $html;
}