<?php
/**
 * Universal URL and Asset Engine
 * Handles seamless URL resolution across local XAMPP and cloud hosting (Railway).
 */

declare(strict_types=1);

namespace App\Helpers {

    class Url {
        private static ?string $basePath = null;

        /**
         * Resets internal memoized state (used primarily in automated tests).
         */
        public static function reset(): void {
            self::$basePath = null;
        }

        /**
         * Resolves the relative base subfolder path (e.g., '/slsu-inventory/public' or '').
         */
        public static function basePath(): string {
            if (self::$basePath !== null) {
                return self::$basePath;
            }

            // 1. Check for manual override in environment
            $envUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
            if (!empty($envUrl)) {
                $parsed = parse_url($envUrl, PHP_URL_PATH);
                if ($parsed && $parsed !== '/') {
                    return self::$basePath = rtrim($parsed, '/');
                }
            }

            // 2. Auto-detect from SCRIPT_NAME (XAMPP subfolder detection)
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($scriptName, '/slsu-inventory/public') !== false) {
                return self::$basePath = '/slsu-inventory/public';
            }

            // 3. Fallback to empty string for root domain deployments (Railway)
            return self::$basePath = '';
        }

        /**
         * Generates a normalized application route URL.
         *
         * @param string $path Target route (e.g., '/admin/users' or '')
         * @return string Normalized path with appropriate base folder prefix
         */
        public static function to(string $path = ''): string {
            $base = self::basePath();
            $cleanPath = '/' . ltrim($path, '/');
            
            // If requesting root path ('/' or '')
            if ($cleanPath === '/') {
                return !empty($base) ? $base : '/';
            }

            return $base . $cleanPath;
        }

        /**
         * Generates an asset path with cache-busting query parameter.
         *
         * @param string $path Asset path (e.g., '/css/custom.css' or '/uploads/avatars/...')
         * @return string Full asset URL with timestamp query string
         */
        public static function asset(string $path): string {
            $url = self::to($path);
            
            // Append cache-buster timestamp if file exists on disk
            $filePath = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
            if (file_exists($filePath)) {
                $mtime = filemtime($filePath);
                $separator = (strpos($url, '?') === false) ? '?' : '&';
                $url .= $separator . 'v=' . $mtime;
            }
            
            return $url;
        }
    }

}

namespace {

    // Global convenience functions
    if (!function_exists('url')) {
        function url(string $path = ''): string {
            return \App\Helpers\Url::to($path);
        }
    }

    if (!function_exists('asset')) {
        function asset(string $path): string {
            return \App\Helpers\Url::asset($path);
        }
    }

    if (!function_exists('redirect')) {
        function redirect(string $path): void {
            header("Location: " . \App\Helpers\Url::to($path));
            exit;
        }
    }

    if (!function_exists('component')) {
        /**
         * Reusable UI Component Renderer
         * Loads and renders a component from views/components/{$name}.php with isolated or local props.
         *
         * @param string $name Component name (e.g. 'stat-card', 'data-table')
         * @param array $props Component input properties/slots
         */
        function component(string $name, array $props = []): void {
            $baseDir = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $componentFile = $baseDir . '/views/components/' . ltrim($name, '/') . '.php';
            if (file_exists($componentFile)) {
                require $componentFile;
            } else {
                error_log("[component] Component file not found: {$componentFile}");
            }
        }
    }

}
