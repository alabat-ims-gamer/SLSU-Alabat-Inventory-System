<?php
namespace App\Helpers;

/**
 * PSR-4 Autoloader
 * 
 * Automatically loads classes based on their namespace.
 * E.g., App\Models\User will map to app/Models/User.php
 */
class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            // The project namespace prefix
            $prefix = 'App\\';
            
            // Base directory for the namespace prefix
            $base_dir = __DIR__ . '/../../app/';
            
            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // No, move to the next registered autoloader
                return;
            }
            
            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If the file exists, require it
            if (file_exists($file)) {
                require $file;
            }
        });

        // Preload global URL helpers so url(), asset(), and redirect() are immediately available
        if (file_exists(__DIR__ . '/Url.php')) {
            require_once __DIR__ . '/Url.php';
        }

        // Load Composer dependencies (PhpSpreadsheet, Dompdf, etc.) if available
        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }
    }
}

