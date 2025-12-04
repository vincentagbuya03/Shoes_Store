<?php
// Gemini / Google Generative API configuration
// API keys are loaded from gemini.local.php (not committed to git)

// Load local config if exists
$localConfig = [];
$localConfigPath = __DIR__ . '/gemini.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = require $localConfigPath;
}

return [
    // Get from local config, environment variable, or leave empty
    'api_key' => $localConfig['api_key'] ?? (getenv('GEMINI_API_KEY') ?: ''),
    'api_url' => 'https://generativelanguage.googleapis.com/v1/models/text-bison-001:generate',

    'provider' => 'google',

    'debug' => false,

    // Get from local config, environment variable, or leave empty
    'openai_api_key' => $localConfig['openai_api_key'] ?? (getenv('OPENAI_API_KEY') ?: ''),

    'service_account_file' => __DIR__ . '/service-account.json',
    'service_account_json' => null,
    'sa_token_cache' => __DIR__ . '/../storage/gemini_sa_token.json',
];