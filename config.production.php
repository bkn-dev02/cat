<?php
require_once __DIR__ . '/env.php';

return [
    // Database Hostinger
    'db_host' => project_env_value('PROD_DB_HOST', 'localhost'),
    'db_name' => project_env_value('PROD_DB_DATABASE'),
    'db_user' => project_env_value('PROD_DB_USERNAME'),
    'db_pass' => project_env_value('PROD_DB_PASSWORD'),

    // E-Learning Database Connection
    'elearning_db_host' => project_env_value('PROD_ELEARNING_DB_HOST', 'localhost'),
    'elearning_db_name' => project_env_value('PROD_ELEARNING_DB_DATABASE'),
    'elearning_db_user' => project_env_value('PROD_ELEARNING_DB_USERNAME'),
    'elearning_db_pass' => project_env_value('PROD_ELEARNING_DB_PASSWORD'),

    // PPDB Database Connection
    'ppdb_db_host' => project_env_value('PROD_PPDB_DB_HOST', 'localhost'),
    'ppdb_db_name' => project_env_value('PROD_PPDB_DB_DATABASE'),
    'ppdb_db_user' => project_env_value('PROD_PPDB_DB_USERNAME'),
    'ppdb_db_pass' => project_env_value('PROD_PPDB_DB_PASSWORD'),

    // App
    'app_name' => 'CAT Enterprise',
    'app_url' => project_env_value('PROD_APP_URL', 'https://cat-enterprise.binakasihnusantara.sch.id'),
];
