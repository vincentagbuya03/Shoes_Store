<?php
// inc/gemini_description.php
// Wrapper: prefer the project's root helper if it exists, otherwise provide minimal safe fallbacks

if (file_exists(__DIR__ . '/../gemini_description.php')) {
    include_once __DIR__ . '/../gemini_description.php';
    return;
}

// Minimal fallback implementations to avoid fatal errors when the real helper is missing
if (!function_exists('generate_fallback_description')) {
    function generate_fallback_description(array $product, string $lang = 'en'): string {
        $name = $product['product_name'] ?? $product['name'] ?? 'This product';
        $price = isset($product['price']) ? '₱' . number_format((float)$product['price'], 2) : '';
        if ($lang === 'tl' || $lang === 'fil') {
            return "{$name} — isang komportableng sapatos. Presyo: {$price}.";
        }
        return "{$name} — a comfortable shoe with reliable cushioning and a durable sole.";
    }
}

if (!function_exists('generate_product_description')) {
    function generate_product_description(array $product, string $lang = 'en'): string {
        return generate_fallback_description($product, $lang);
    }
}
