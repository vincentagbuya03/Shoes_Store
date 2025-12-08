<?php
/**
 * Chatbot API Endpoint
 * Handles chat requests and communicates with Gemini AI (primary) or OpenAI (fallback)
 * Fixed for shared hosting compatibility
 */

// Error handling for shared hosting
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// Set headers early
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Handle CORS for cross-origin requests (if needed)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Start session for user context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Gemini config with error handling
$geminiConfig = [];
$configPath = __DIR__ . '/../config/gemini.php';
if (file_exists($configPath)) {
    $geminiConfig = @require $configPath;
    if (!is_array($geminiConfig)) {
        $geminiConfig = [];
    }
}

// Configuration
define('GEMINI_API_KEY', $geminiConfig['api_key'] ?? '');
define('OPENAI_API_KEY', $geminiConfig['openai_api_key'] ?? '');
define('OPENAI_MODEL', 'gpt-3.5-turbo');
define('MAX_TOKENS', 500);
define('USE_GEMINI', false); // Changed to false - using OpenAI fallback due to Gemini quota exceeded

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || ! isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$userMessage = trim($input['message']);
$history = isset($input['history']) ? $input['history'] : [];

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

// Rate limiting (simple implementation)
$rateLimitKey = 'chatbot_rate_' . ($_SESSION['customer_id'] ?? session_id());
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = [];
}

$now = time();
$_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], fn($t) => $now - $t < 60);

if (count($_SESSION[$rateLimitKey]) >= 20) { // 20 requests per minute
    echo json_encode(['success' => false, 'error' => 'Too many requests.  Please wait a moment.']);
    exit;
}
$_SESSION[$rateLimitKey][] = $now;

// System prompt for the chatbot
$systemPrompt = <<<PROMPT
You are the friendly and helpful AI assistant for ShoeTakels, an online shoe store. Your role is to:

1. Help customers find the perfect shoes based on their needs, preferences, and occasions
2. Answer questions about products, sizing, shipping, returns, and store policies
3.  Provide style recommendations and outfit suggestions
4.  Assist with order tracking and account issues
5. Share information about current sales and promotions (like the 12. 12 Sale)

Brand personality: Friendly, knowledgeable, and enthusiastic about footwear.  Use casual but professional language.

Important policies to know:
- Free shipping on orders over ₱50
- 30-day hassle-free returns
- Quality guarantee with lifetime warranty on defects
- Categories: Men's, Women's, and Kids' shoes
- 24/7 customer support available

Keep responses concise but helpful. If you don't know specific product details, suggest the customer browse the catalog or contact support. 
PROMPT;

// Build messages array for OpenAI
$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

// Add conversation history (limited to last 10 for context)
foreach (array_slice($history, -10) as $msg) {
    if (isset($msg['type']) && isset($msg['text'])) {
        $role = $msg['type'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => $msg['text']];
    }
}

// Add current user message
$messages[] = ['role' => 'user', 'content' => $userMessage];

// Call AI API (Gemini or OpenAI)
try {
    if (USE_GEMINI && !empty(GEMINI_API_KEY)) {
        $response = callGemini($messages, $systemPrompt);
    } else if (!empty(OPENAI_API_KEY)) {
        $response = callOpenAI($messages);
    } else {
        throw new Exception('No API keys configured');
    }
    echo json_encode(['success' => true, 'response' => $response]);
} catch (Exception $e) {
    error_log('AI API Error: ' . $e->getMessage());
    
    // Try fallback to static responses if API fails
    $userMsg = $messages[count($messages) - 1]['content'] ?? '';
    $fallbackResponse = getFallbackResponse($userMsg);
    echo json_encode(['success' => true, 'response' => $fallbackResponse, 'usingFallback' => true]);
}

/**
 * Call Google Gemini API
 */
function callGemini($messages, $systemPrompt) {
    $apiKey = GEMINI_API_KEY;
    
    if (empty($apiKey)) {
        throw new Exception('Gemini API key not configured');
    }
    
    // Build the prompt for Gemini
    $conversationText = $systemPrompt . "\n\n";
    
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') continue;
        $role = $msg['role'] === 'user' ? 'Customer' : 'Assistant';
        $conversationText .= $role . ": " . $msg['content'] . "\n";
    }
    
    $conversationText .= "Assistant:";
    
    // Use Gemini 2.0 Flash (fast and capable)
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $conversationText]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => MAX_TOKENS,
            'topP' => 0.9,
            'topK' => 40
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE']
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Curl error: ' . $error);
    }

    if ($httpCode !== 200) {
        error_log('Gemini API Error Response: ' . $result);
        throw new Exception('Gemini API returned status ' . $httpCode);
    }

    $response = json_decode($result, true);
    
    if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        error_log('Gemini Invalid Response: ' . $result);
        throw new Exception('Invalid Gemini API response structure');
    }

    return trim($response['candidates'][0]['content']['parts'][0]['text']);
}

/**
 * Call OpenAI Chat Completion API
 */
function callOpenAI($messages) {
    $apiKey = OPENAI_API_KEY;
    
    // Check if API key is configured
    if ($apiKey === 'your-openai-api-key-here') {
        // Return a fallback response for demo/testing
        return getFallbackResponse($messages[count($messages) - 1]['content']);
    }
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => MAX_TOKENS,
        'temperature' => 0.7,
        'presence_penalty' => 0.1,
        'frequency_penalty' => 0.1
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Curl error: ' .  $error);
    }

    if ($httpCode !== 200) {
        throw new Exception('API returned status ' . $httpCode);
    }

    $response = json_decode($result, true);
    
    if (! isset($response['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response structure');
    }

    return trim($response['choices'][0]['message']['content']);
}

/**
 * Fallback responses when API is unavailable
 * This allows the chatbot to work even if APIs are down or quota exceeded
 */
function getFallbackResponse($message) {
    $message = strtolower(trim($message));
    
    // Exact keyword matching
    $responses = [
        'hello' => "Hello! 👋 Welcome to ShoeTakels!  I'm here to help you find the perfect pair of shoes. What are you looking for today?",
        'hi' => "Hi there! 👋 How can I help you find your perfect shoes today?",
        'help' => "I'd be happy to help!  I can assist you with:\n• Finding the right shoes for any occasion\n• Size recommendations\n• Shipping and returns information\n• Current sales and promotions\n\nWhat would you like to know?",
        'shipping' => "Great question! 📦 We offer FREE shipping on all orders over ₱50. Standard shipping takes 3-5 business days. Express shipping is available for an additional fee.",
        'return' => "We have a hassle-free 30-day return policy!  If you're not completely satisfied, simply return your unworn shoes in original packaging for a full refund.  No questions asked!  😊",
        'size' => "Finding the right size is important! Here are some tips:\n• Measure your feet in the evening when they're largest\n• Check our size guide on each product page\n• When in doubt, size up!\n\nNeed help with a specific shoe?",
        'sale' => "🎉 Great timing! Check out our 12.12 Sale for amazing discounts! We also have new arrivals and featured collections with special prices. Browse our Best Seller section for top picks!",
        'men' => "We have a fantastic collection of men's shoes! From athletic performance shoes to casual everyday styles. Browse our Men's category to see all options. Would you like recommendations for a specific occasion?",
        'women' => "Our women's collection features everything from elegant heels to comfortable sneakers!  Check out our Women's category for all styles. Are you looking for something specific?",
        'kids' => "We have adorable and durable shoes for kids! Growing feet need good support.  Check out our Kids' category for all sizes and styles. 👟",
        'discount' => "We offer fantastic discounts! Check out our 12.12 Sale, Best Sellers, and Featured Collections. Visit our promotions page to see all current deals!",
        'payment' => "We accept various payment methods including GCash and other local payment options. GCash proof upload is supported during checkout. Any other questions about payment?",
        'order' => "You can track your order anytime! Visit our 'My Orders' section to see your order status, estimated delivery, and more. Need specific order details?",
        'delivery' => "We work with professional delivery riders to ensure your shoes arrive safely and on time. You can track your delivery in real-time. Want to know more about our delivery process?",
        'refund' => "If you need to request a refund, you can do so through your account. We process refund requests within 5-7 business days. Need help with a specific refund?",
        'brand' => "We stock shoes from many popular brands! Browse our Brand section to see all available brands and their exclusive collections.",
        'new' => "We have exciting new arrivals! Check out our New Arrivals section to see the latest shoe styles we just added to our store.",
        'best' => "Our Best Sellers are the most popular shoes our customers love! Browse the Best Seller section to find top-rated, highly-reviewed shoes.",
    ];

    // Check for exact matches first
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }

    // If no keyword match, return a generic helpful response
    $defaults = [
        "Thanks for your message! I'm here to help you find the perfect shoes. Could you tell me more about what you're looking for? For example, you can ask about:\n• Specific shoe types or styles\n• Sizing or fit\n• Shipping and returns\n• Our sales and promotions",
        "I'd love to help! Are you shopping for:\n• Men's shoes 👟\n• Women's shoes 👠\n• Kids' shoes 👧\n\nOr would you like to know about shipping, returns, or current promotions?",
        "Great question! I'm here to assist. You can ask me about:\n• Our shoe collections\n• Sizing help\n• Shipping and delivery\n• Payment methods\n• Order tracking\n• Returns and refunds\n\nWhat interests you most?",
        "Thanks for reaching out! 😊 To better assist you, could you tell me:\n• What type of shoes are you interested in?\n• Any specific occasion or style?\n• Or do you have questions about our policies?",
    ];
    
    return $defaults[array_rand($defaults)];
}