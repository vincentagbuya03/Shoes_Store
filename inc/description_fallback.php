<?php
// inc/description_fallback.php
// Provides a simple fallback description function if not present elsewhere.

if (!function_exists('generate_fallback_description')) {
    function generate_fallback_description(array $product, string $lang = 'en'): string {
        $name = $product['product_name'] ?? $product['name'] ?? 'This product';
        $price = isset($product['price']) ? '₱' . number_format((float)$product['price'], 2) : '';
        if ($lang === 'tl' || $lang === 'fil') {
            return "{$name} — isang komportableng sapatos na may mahusay na cushioning. Presyo: {$price}.";
        }
        return "{$name} — a comfortable shoe with reliable cushioning and a durable sole.";
    }
}
