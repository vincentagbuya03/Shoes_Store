<?php
// -----------------------------------------------------------------------------
// PRODUCT DESCRIPTION GENERATOR (Gemini API Backed with Details Section)
// Fixed version with proper API call and fallback only on failure
// -----------------------------------------------------------------------------

// Load API key from config
function get_gemini_api_key(): ?string {
    // First try the config file
    $config_path = __DIR__ . '/config/gemini.local.php';
    if (file_exists($config_path)) {
        $config = require $config_path;
        if (!empty($config['api_key'])) {
            return $config['api_key'];
        }
    }
    
    // Fallback to environment variable
    $env_key = getenv('GEMINI_API_KEY');
    if ($env_key && $env_key !== 'YOUR_API_KEY_HERE') {
        return $env_key;
    }
    
    return null;
}

// -------------------------
// Gemini API Call
// -------------------------
function gemini_generate_content(string $prompt): ?string {
    $api_key = get_gemini_api_key();
    if (!$api_key) {
        error_log('[gemini] GEMINI_API_KEY not set; skipping LLM call');
        return null;
    }

    // Use a valid Gemini model
    $model_name = 'gemini-2.0-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key={$api_key}";

    $payload = [
        'contents' => [
            [ 'parts' => [ [ 'text' => $prompt ] ] ]
        ]
    ];

    $max_retries = 2;
    $attempt = 0;
    $lastErr = null;

    while ($attempt <= $max_retries) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15
        ]);

        $resp = curl_exec($ch);
        $err  = curl_errno($ch) ? curl_error($ch) : null;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);

        $attempt++;
        $snippet = is_string($resp) ? substr($resp, 0, 1200) : '';
        error_log(sprintf('[gemini] attempt=%d HTTP=%d resp_snippet=%s curl_err=%s', $attempt, $http_code, $snippet, $err ?: 'none'));

        if ($resp === false || $err) {
            $lastErr = $err ?: 'curl failed';
        } elseif ($http_code >= 500) {
            $lastErr = 'server error ' . $http_code;
        } else {
            $decoded = json_decode($resp, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Shape: candidates[].content.parts[].text
                if (!empty($decoded['candidates']) && is_array($decoded['candidates'])) {
                    foreach ($decoded['candidates'] as $cand) {
                        if (!empty($cand['content']['parts'])) {
                            $texts = [];
                            foreach ($cand['content']['parts'] as $p) {
                                if (is_array($p) && !empty($p['text'])) $texts[] = $p['text'];
                                elseif (is_string($p)) $texts[] = $p;
                            }
                            if (!empty($texts)) return trim(implode("\n\n", array_map('trim', $texts)));
                        }
                    }
                }

                // Fallback: walk entire decoded tree for first non-empty string
                $possible = [];
                array_walk_recursive($decoded, function($v) use (&$possible) { if (is_string($v) && strlen(trim($v))>0) $possible[] = $v; });
                if (!empty($possible)) return trim($possible[0]);
            } else {
                $lastErr = 'json decode error: ' . json_last_error_msg();
            }
        }

        if ($attempt <= $max_retries) {
            $wait = 200 * (2 ** ($attempt - 1));
            usleep($wait * 1000);
            continue;
        }
        break;
    }

    if ($lastErr) error_log('[gemini] final error: ' . $lastErr);
    return null;
}

// -------------------------
// Generate Product Description
// -------------------------
function generate_product_description(array $product, string $lang = 'en'): string {
    $product_id = $product['product_id'] ?? mt_rand(1000,9999);
    $cache_dir = __DIR__ . '/upload/product-descriptions/';
    if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
    $cache_file = $cache_dir . "{$product_id}-long-{$lang}.txt";

    if (file_exists($cache_file)) {
        $text = trim(file_get_contents($cache_file));
        if ($text !== '') return $text;
    }

    $prompt = build_prompt_with_details($product, $lang);

    $response = gemini_generate_content($prompt);

    $used = 'gemini';
    if (!$response || trim($response) === '') {
        error_log('[gemini_desc] Gemini response empty, using fallback');
        $response = generate_fallback_description($product, $lang);
        $used = 'fallback';
    }

    $response = trim(strip_tags($response));
    if ($response !== '') @file_put_contents($cache_file, $response);

    $snippet = is_string($response) ? substr($response, 0, 200) : '';
    error_log(sprintf('[gemini_desc] product=%s used=%s len=%d snippet=%s', $product_id, $used, strlen($response), $snippet));

    return $response;
}

// -------------------------
// Build Prompt with Details Section
// -------------------------
function build_prompt_with_details(array $product, string $lang): string {
    $name   = $product['product_name'] ?? $product['name'] ?? 'This product';
    $brand  = $product['brand_name'] ?? $product['brand'] ?? '';
    $price  = isset($product['price']) ? '₱' . number_format((float)$product['price'], 2) : '';
    $color  = $product['color_name'] ?? '';
    $badge  = $product['product_badge'] ?? '';

    $features = [];
    if ($badge) $features[] = $badge;
    if ($color) $features[] = "{$color} color";
    $features_text = $features ? implode(', ', $features) : 'stylish, comfortable design';

    $language_prefix = match($lang) {
        'en' => "Write the description in English.",
        'tl','fil' => "Isulat ang buong paglalarawan sa Filipino.",
        default => "Write the entire description in {$lang}."
    };

    $instruction = "Write a **long, unique product description (5-7 sentences)** suitable for an e-commerce product page. Include a **Details** section at the end listing fit, closure, materials, midsole, outsole, and other features. Make it informative, consumer-friendly, and persuasive without exaggeration.";

    return <<<PROMPT
{$language_prefix}

{$instruction}

Product: "{$name}" by {$brand}
Price: {$price}
Features: {$features_text}

Make the description unique for this product. Provide a Details section like an e-commerce listing.
PROMPT;
}

// -------------------------
// Fallback Description
// -------------------------
function generate_fallback_description(array $product, string $lang): string {
    $name   = $product['product_name'] ?? $product['name'] ?? 'This product';
    $price  = isset($product['price']) ? '₱' . number_format((float)$product['price'], 2) : '';
    $core   = trim("{$name}");

    return match($lang) {
        'tl','fil' => "{$core} — isang komportableng sapatos na may mahusay na cushioning at matibay na outsole. Presyo: {$price}.",
        default    => "{$core} — a comfortable shoe with reliable cushioning and a durable sole. Price: {$price}."
    };
}
?>
