<?php
require_once __DIR__ . '/env.php';

return [
    // Database CAT (utama)
    'db_host' => project_env_value('DB_HOST', 'localhost'),
    'db_name' => project_env_value('DB_DATABASE', 'cat_enterprise'),
    'db_user' => project_env_value('DB_USERNAME', 'root'),
    'db_pass' => project_env_value('DB_PASSWORD'),

    // E-Learning Database Connection
    'elearning_db_host' => project_env_value('ELEARNING_DB_HOST', 'localhost'),
    'elearning_db_name' => project_env_value('ELEARNING_DB_DATABASE', 'db_elearning'),
    'elearning_db_user' => project_env_value('ELEARNING_DB_USERNAME', 'root'),
    'elearning_db_pass' => project_env_value('ELEARNING_DB_PASSWORD'),

    // PPDB Database Connection
    'ppdb_db_host' => project_env_value('PPDB_DB_HOST', 'localhost'),
    'ppdb_db_name' => project_env_value('PPDB_DB_DATABASE', 'ppdb_bkn_hostinger'),
    'ppdb_db_user' => project_env_value('PPDB_DB_USERNAME', 'root'),
    'ppdb_db_pass' => project_env_value('PPDB_DB_PASSWORD'),

    // App
    'app_name' => 'CAT Enterprise',
    'app_url' => project_env_value('APP_URL', 'http://localhost/cat'),
];
