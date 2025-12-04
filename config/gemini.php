<?php
// Gemini / Google Generative API configuration
// Set your API keys as environment variables or in a .env file (not committed to git)

return [
    // Get from environment variable or leave empty
    'api_key' => getenv('GEMINI_API_KEY') ?: '',
    'api_url' => 'https://generativelanguage.googleapis.com/v1/models/text-bison-001:generate',

    'provider' => 'google',

    'debug' => false,

    // Get from environment variable or leave empty
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',

    'service_account_file' => __DIR__ . '/service-account.json',
    'service_account_json' => null,
    'sa_token_cache' => __DIR__ . '/../storage/gemini_sa_token.json',
];