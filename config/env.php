<?php
/**
 * Lightweight Environment (.env) Loader
 * MITS Connect
 */

if (!function_exists('load_env')) {
    /**
     * Load an .env file into PHP environment variables ($_ENV, $_SERVER, getenv)
     *
     * @param string $path Path to the .env file
     * @return bool True if loaded successfully, false otherwise
     */
    function load_env(string $path): bool {
        if (!file_exists($path)) {
            return false;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return false;
        }

        // Split by any newline format (\r\n, \r, \n)
        $lines = preg_split('/\r\n|\r|\n/', $content);

        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // Must contain key=value delimiter
            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip inline comments if value is not quoted
            if (substr($value, 0, 1) !== '"' && substr($value, 0, 1) !== "'") {
                $parts = preg_split('/\s+#/', $value, 2);
                $value = trim($parts[0]);
            }

            // Strip surrounding single or double quotes
            if (
                (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
            ) {
                $value = substr($value, 1, -1);
            }

            // Populate $_ENV, $_SERVER and putenv
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv("{$name}={$value}");
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable value with fallback and type casting
     *
     * @param string $key Variable name
     * @param mixed $default Fallback value if not set
     * @return mixed
     */
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            switch ($lower) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'empty':
                case '(empty)':
                    return '';
                case 'null':
                case '(null)':
                    return null;
            }
        }

        return $value;
    }
}

// Automatically load root .env file
load_env(realpath(__DIR__ . '/..') . '/.env');
