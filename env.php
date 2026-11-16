<?php

function project_env_value(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function project_load_env(string $path): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Unable to read environment file: ' . $path);
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            throw new RuntimeException('Invalid environment entry in: ' . $path);
        }

        $name = trim(substr($line, 0, $separator));
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new RuntimeException('Invalid environment variable name in: ' . $path);
        }

        if (getenv($name) !== false) {
            continue;
        }

        $value = trim(substr($line, $separator + 1));
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' || $first === "'") && $last === $first) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($name . '=' . $value);
    }
}

project_load_env(__DIR__ . '/.env');
