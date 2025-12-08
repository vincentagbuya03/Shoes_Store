<?php
// Chatbot partial - include this in all your pages before </body>
?>
<div id="ai-chatbot-container" class="chatbot-container">
    <!-- Floating Chat Button -->
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Open chat assistant">
        <span class="chatbot-icon-open">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L2 22l5.71-.97C9.02 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm-2 13.5c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm4 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2-4.5H8c-.55 0-1-.45-1-1s.45-1 1-1h8c.55 0 1 .45 1 1s-.45 1-1 1z"/>
            </svg>
        </span>
        <span class="chatbot-icon-close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </span>
        <span class="chatbot-badge" id="chatbot-badge" style="display:none;">1</span>
    </button>

    <!-- Chat Window -->
    <div id="chatbot-window" class="chatbot-window">
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L2 22l5.71-.97C9.02 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm-2 13.5c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm4 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2-4.5H8c-.55 0-1-.45-1-1s.45-1 1-1h8c.55 0 1 .45 1 1s-.45 1-1 1z"/>
                    </svg>
                </div>
                <div class="chatbot-header-text">
                    <h4>ShoeTakels Assistant</h4>
                    <span class="chatbot-status"><span class="status-dot"></span>Online</span>
                </div>
            </div>
            <button id="chatbot-minimize" class="chatbot-minimize" aria-label="Minimize chat">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                    <path d="M19 13H5v-2h14v2z"/>
                </svg>
            </button>
        </div>
        
        <div id="chatbot-messages" class="chatbot-messages">
            <!-- Messages will be dynamically inserted here -->
        </div>

        <div class="chatbot-typing" id="chatbot-typing" style="display: none;">
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <form id="chatbot-form" class="chatbot-form">
            <div class="chatbot-input-wrapper">
                <input type="text" id="chatbot-input" placeholder="Ask me anything about shoes..." autocomplete="off" required>
                <button type="submit" id="chatbot-send" aria-label="Send message">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </form>

        <div class="chatbot-footer">
            <span>Powered by AI</span>
        </div>
    </div>
</div>

<?php
/**
 * Get base path for assets - works on both localhost and production hosting
 * Uses multiple detection methods for reliability
 */
function getChatbotBasePath() {
    // Get the current request path to determine if we're in a subdirectory
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $php_self = $_SERVER['PHP_SELF'] ?? '';
    
    // Determine current path
    $current_path = $request_uri ?: ($script_name ?: $php_self);
    
    // Check if we're in /Shoes_Store/ subdirectory
    if (strpos($current_path, '/Shoes_Store/') !== false || strpos($current_path, '/Shoes_Store') === 0) {
        return '/Shoes_Store/';
    }
    
    // Check SCRIPT_FILENAME for subdirectory detection
    $script_filename = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if (strpos($script_filename, '/Shoes_Store/') !== false || strpos($script_filename, '\Shoes_Store\\') !== false) {
        return '/Shoes_Store/';
    }
    
    // Default: assume we're at root (for production hosting where app is at root)
    return '/';
}

$chatbot_base = getChatbotBasePath();

// Optional: Log for debugging (can be enabled if needed)
// error_log('Chatbot base path: ' . $chatbot_base . ' (method: multiple fallback detection)');
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($chatbot_base, ENT_QUOTES, 'UTF-8'); ?>asset/style/chatbot.css?v=<?php echo time(); ?>">
<script>
    // Provide global base path for JavaScript to use
    window.CHATBOT_BASE_PATH = <?php echo json_encode($chatbot_base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="<?php echo htmlspecialchars($chatbot_base, ENT_QUOTES, 'UTF-8'); ?>asset/script/chatbot.js?v=<?php echo time(); ?>" defer></script>