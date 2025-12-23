<?php
// Chatbot partial - include this in all your pages before </body>
// Load store settings to get admin-configured colors
if (!isset($store_settings)) {
    require_once __DIR__ . '/../inc/store_settings.php';
}

// Get colors from admin settings, with fallbacks
$chatbot_primary = $store_settings['customer_primary_color'] ?? '#6366f1';
$chatbot_secondary = $store_settings['customer_secondary_color'] ?? '#8b5cf6';
$store_name = $store_settings['store_name'] ?? 'ShoeTakels';
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
                    <h4><?php echo htmlspecialchars($store_name); ?> Assistant</h4>
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
?>

<!-- Chatbot Styles - Embedded Inline with Dynamic Colors -->
<style>
:root {
    --chatbot-primary: <?php echo htmlspecialchars($chatbot_primary); ?>;
    --chatbot-secondary: <?php echo htmlspecialchars($chatbot_secondary); ?>;
    --chatbot-primary-dark: <?php 
        // Calculate darker shade by reducing brightness
        $primary = $chatbot_primary;
        $primary = ltrim($primary, '#');
        $r = hexdec(substr($primary, 0, 2)) * 0.8;
        $g = hexdec(substr($primary, 2, 2)) * 0.8;
        $b = hexdec(substr($primary, 4, 2)) * 0.8;
        echo '#' . str_pad(dechex((int)$r), 2, '0', STR_PAD_LEFT) . 
             str_pad(dechex((int)$g), 2, '0', STR_PAD_LEFT) . 
             str_pad(dechex((int)$b), 2, '0', STR_PAD_LEFT);
    ?>;
    --chatbot-bg: #ffffff;
    --chatbot-text: #1e293b;
    --chatbot-text-light: #64748b;
    --chatbot-border: #e2e8f0;
    --chatbot-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --chatbot-shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --chatbot-radius: 20px;
    --chatbot-radius-sm: 12px;
    --chatbot-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Container */
.chatbot-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Toggle Button */
.chatbot-toggle {
    position: fixed !important;
    bottom: 24px !important;
    right: 24px !important;
    width: 60px !important;
    height: 60px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, var(--chatbot-primary) 0%, var(--chatbot-secondary) 100%) !important;
    border: none !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
    transition: var(--chatbot-transition) !important;
    z-index: 9999 !important;
    color: white !important;
    font-weight: 600 !important;
}

.chatbot-toggle:hover {
    transform: scale(1.15) translateY(-3px) !important;
    box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.2) !important;
}

.chatbot-toggle:active {
    transform: scale(0.95) !important;
}

.chatbot-toggle svg {
    width: 28px !important;
    height: 28px !important;
}

.chatbot-icon-close {
    display: none !important;
}

.chatbot-toggle.active .chatbot-icon-open {
    display: none !important;
}

.chatbot-toggle.active .chatbot-icon-close {
    display: block !important;
}

/* Chat Window */
.chatbot-window {
    position: fixed !important;
    bottom: 100px !important;
    right: 24px !important;
    width: 420px !important;
    height: 650px !important;
    background: var(--chatbot-bg) !important;
    border-radius: var(--chatbot-radius) !important;
    box-shadow: 0 20px 60px -15px rgba(0, 0, 0, 0.3) !important;
    z-index: 9998 !important;
    display: flex !important;
    flex-direction: column !important;
    opacity: 0 !important;
    transform: translateY(20px) scale(0.95) !important;
    pointer-events: none !important;
    transition: var(--chatbot-transition) !important;
    border: 1px solid var(--chatbot-border) !important;
}

.chatbot-window.open {
    opacity: 1 !important;
    transform: translateY(0) scale(1) !important;
    pointer-events: auto !important;
}

/* Header */
.chatbot-header {
    background: linear-gradient(135deg, var(--chatbot-primary) 0%, var(--chatbot-secondary) 100%) !important;
    color: white !important;
    padding: 20px !important;
    border-radius: var(--chatbot-radius) var(--chatbot-radius) 0 0 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    flex-shrink: 0 !important;
}

.chatbot-header-info {
    display: flex !important;
    gap: 12px !important;
    align-items: center !important;
    flex: 1 !important;
}

.chatbot-avatar {
    width: 44px !important;
    height: 44px !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.chatbot-avatar svg {
    width: 24px !important;
    height: 24px !important;
    color: white !important;
}

.chatbot-header-text h4 {
    margin: 0 !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px !important;
}

.chatbot-status {
    font-size: 12px !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    opacity: 0.9 !important;
}

.status-dot {
    width: 8px !important;
    height: 8px !important;
    background: #10b981 !important;
    border-radius: 50% !important;
    display: inline-block !important;
    animation: pulse 2s infinite !important;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5) !important;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Minimize Button */
.chatbot-minimize {
    background: rgba(255, 255, 255, 0.15) !important;
    border: none !important;
    color: white !important;
    padding: 8px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: var(--chatbot-transition) !important;
    flex-shrink: 0 !important;
}

.chatbot-minimize:hover {
    background: rgba(255, 255, 255, 0.25) !important;
}

/* Messages Container */
.chatbot-messages {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 16px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    background: #f8fafc !important;
}

.chatbot-messages::-webkit-scrollbar {
    width: 6px !important;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: transparent !important;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: var(--chatbot-border) !important;
    border-radius: 3px !important;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1 !important;
}

/* Message */
.chatbot-message {
    display: flex !important;
    gap: 8px !important;
    animation: slideUp 0.3s ease !important;
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(10px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.chatbot-message.bot {
    justify-content: flex-start !important;
}

.chatbot-message.user {
    justify-content: flex-end !important;
}

/* Avatar */
.chatbot-message-avatar {
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    background: var(--chatbot-primary) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.chatbot-message-avatar svg {
    width: 16px !important;
    height: 16px !important;
    color: white !important;
}

.chatbot-message.user .chatbot-message-avatar {
    background: var(--chatbot-secondary) !important;
    order: 2 !important;
}

/* Message Content */
.chatbot-message-content {
    max-width: 280px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
}

.chatbot-message.user .chatbot-message-content {
    align-items: flex-end !important;
}

/* Bubble */
.chatbot-message-bubble {
    background: var(--chatbot-bg) !important;
    padding: 12px 16px !important;
    border-radius: 18px !important;
    border: 1px solid var(--chatbot-border) !important;
    word-wrap: break-word !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    color: var(--chatbot-text) !important;
}

.chatbot-message.user .chatbot-message-bubble {
    background: var(--chatbot-primary) !important;
    color: white !important;
    border: none !important;
    border-radius: 18px 18px 4px 18px !important;
}

.chatbot-message.bot .chatbot-message-bubble {
    border-radius: 18px 18px 18px 4px !important;
}

/* Time */
.chatbot-message-time {
    font-size: 11px !important;
    color: var(--chatbot-text-light) !important;
    margin: 0 8px !important;
}

/* Typing Indicator */
.chatbot-typing {
    display: none !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 12px 16px !important;
    background: #f8fafc !important;
}

.typing-indicator {
    display: flex !important;
    gap: 4px !important;
}

.typing-indicator span {
    width: 8px !important;
    height: 8px !important;
    background: var(--chatbot-primary) !important;
    border-radius: 50% !important;
    animation: typingAnimation 1.4s infinite !important;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s !important;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s !important;
}

@keyframes typingAnimation {
    0%, 60%, 100% { opacity: 0.4; transform: translateY(0); }
    30% { opacity: 1; transform: translateY(-8px); }
}

/* Form */
.chatbot-form {
    padding: 12px !important;
    border-top: 1px solid var(--chatbot-border) !important;
    display: flex !important;
    gap: 8px !important;
    background: var(--chatbot-bg) !important;
    flex-shrink: 0 !important;
}

.chatbot-input-wrapper {
    display: flex !important;
    gap: 8px !important;
    width: 100% !important;
}

/* Input */
#chatbot-input {
    flex: 1 !important;
    padding: 10px 14px !important;
    border: 1px solid var(--chatbot-border) !important;
    border-radius: 24px !important;
    font-size: 14px !important;
    font-family: inherit !important;
    outline: none !important;
    transition: var(--chatbot-transition) !important;
    background: var(--chatbot-bg) !important;
    color: var(--chatbot-text) !important;
}

#chatbot-input::placeholder {
    color: var(--chatbot-text-light) !important;
}

#chatbot-input:focus {
    border-color: var(--chatbot-primary) !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
}

/* Send Button */
#chatbot-send {
    padding: 10px 14px !important;
    background: var(--chatbot-primary) !important;
    color: white !important;
    border: none !important;
    border-radius: 50% !important;
    width: 40px !important;
    height: 40px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: var(--chatbot-transition) !important;
    flex-shrink: 0 !important;
}

#chatbot-send:hover {
    background: var(--chatbot-secondary) !important;
    transform: scale(1.05) !important;
}

#chatbot-send:active {
    transform: scale(0.95) !important;
}

#chatbot-send:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

#chatbot-send.loading {
    animation: spin 1s linear infinite !important;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#chatbot-send svg {
    width: 18px !important;
    height: 18px !important;
}

/* Footer */
.chatbot-footer {
    padding: 8px 16px !important;
    border-top: 1px solid var(--chatbot-border) !important;
    text-align: center !important;
    font-size: 11px !important;
    color: var(--chatbot-text-light) !important;
    background: var(--chatbot-bg) !important;
    border-radius: 0 0 var(--chatbot-radius) var(--chatbot-radius) !important;
    flex-shrink: 0 !important;
}

/* Badge */
.chatbot-badge {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    background: #ef4444 !important;
    color: white !important;
    border-radius: 50% !important;
    width: 24px !important;
    height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .chatbot-window {
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
        bottom: 0 !important;
        right: 0 !important;
        border-radius: 0 !important;
    }
    
    .chatbot-window.open {
        bottom: 0 !important;
    }
    
    .chatbot-toggle {
        bottom: 16px !important;
        right: 16px !important;
    }
}
</style>

<script>
    // Provide global base path for JavaScript to use
    window.CHATBOT_BASE_PATH = <?php echo json_encode($chatbot_base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<!-- Load chatbot JavaScript with fallback -->
<script src="<?php echo htmlspecialchars($chatbot_base, ENT_QUOTES, 'UTF-8'); ?>asset/script/chatbot.js?v=<?php echo time(); ?>" defer></script>

<!-- Inline Chatbot Script - Embedded for reliability -->
<script>
(function() {
    'use strict';

    function getBaseUrl() {
        if (typeof window.CHATBOT_BASE_PATH !== 'undefined' && window.CHATBOT_BASE_PATH) {
            return window.location.origin + window.CHATBOT_BASE_PATH;
        }
        return window.location.protocol + '//' + window.location.host + '/';
    }

    var CONFIG = {
        storageKey: 'shoetakels_chatbot',
        apiEndpoint: getBaseUrl() + 'api/chatbot.php',
        maxMessages: 50,
        welcomeMessage: "Hi there! 👋 I'm the ShoeTakels Assistant. How can I help you find the perfect shoes today?"
    };

    var elements = {};
    var state = {
        isOpen: false,
        messages: [],
        isLoading: false,
        initialized: false
    };

    function initElements() {
        elements.container = document.getElementById('ai-chatbot-container');
        elements.toggle = document.getElementById('chatbot-toggle');
        elements.window = document.getElementById('chatbot-window');
        elements.minimize = document.getElementById('chatbot-minimize');
        elements.messages = document.getElementById('chatbot-messages');
        elements.form = document.getElementById('chatbot-form');
        elements.input = document.getElementById('chatbot-input');
        elements.send = document.getElementById('chatbot-send');
        elements.typing = document.getElementById('chatbot-typing');
        elements.badge = document.getElementById('chatbot-badge');
        return elements.container && elements.toggle && elements.window && elements.messages && elements.form && elements.input;
    }

    function init() {
        if (state.initialized) return;
        if (!initElements()) {
            setTimeout(init, 100);
            return;
        }
        loadState();
        renderMessages();
        restoreWindowState();
        bindEvents();
        if (state.messages.length === 0) {
            addBotMessage(CONFIG.welcomeMessage);
        }
        state.initialized = true;
    }

    function loadState() {
        try {
            var saved = localStorage.getItem(CONFIG.storageKey);
            if (saved) {
                var parsed = JSON.parse(saved);
                state.messages = parsed.messages || [];
                state.isOpen = parsed.isOpen || false;
            }
        } catch (e) {
            state.messages = [];
            state.isOpen = false;
        }
    }

    function saveState() {
        try {
            var messagesToSave = state.messages.slice(-CONFIG.maxMessages);
            localStorage.setItem(CONFIG.storageKey, JSON.stringify({
                messages: messagesToSave,
                isOpen: state.isOpen,
                timestamp: Date.now()
            }));
        } catch (e) {}
    }

    function restoreWindowState() {
        if (state.isOpen) openChat(false);
    }

    function bindEvents() {
        if (elements.toggle) elements.toggle.addEventListener('click', toggleChat);
        if (elements.minimize) elements.minimize.addEventListener('click', closeChat);
        if (elements.form) elements.form.addEventListener('submit', handleSubmit);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && state.isOpen) closeChat();
        });
    }

    function toggleChat() {
        state.isOpen ? closeChat() : openChat(true);
    }

    function openChat(animate) {
        state.isOpen = true;
        if (elements.window) elements.window.classList.add('open');
        if (elements.toggle) elements.toggle.classList.add('active');
        if (elements.badge) elements.badge.style.display = 'none';
        if (animate && elements.input) setTimeout(function() { elements.input.focus(); }, 300);
        scrollToBottom();
        saveState();
    }

    function closeChat() {
        state.isOpen = false;
        if (elements.window) elements.window.classList.remove('open');
        if (elements.toggle) elements.toggle.classList.remove('active');
        saveState();
    }

    function handleSubmit(e) {
        e.preventDefault();
        if (!elements.input) return;
        var message = elements.input.value.trim();
        if (!message || state.isLoading) return;
        addUserMessage(message);
        elements.input.value = '';
        sendMessage(message);
    }

    function addUserMessage(text) {
        var message = { type: 'user', text: text, timestamp: new Date().toISOString() };
        state.messages.push(message);
        renderMessage(message);
        scrollToBottom();
        saveState();
    }

    function addBotMessage(text) {
        var message = { type: 'bot', text: text, timestamp: new Date().toISOString() };
        state.messages.push(message);
        renderMessage(message);
        scrollToBottom();
        saveState();
    }

    function sendMessage(userMessage) {
        state.isLoading = true;
        showTyping();
        if (elements.send) {
            elements.send.disabled = true;
            elements.send.classList.add('loading');
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', CONFIG.apiEndpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                hideTyping();
                state.isLoading = false;
                if (elements.send) {
                    elements.send.disabled = false;
                    elements.send.classList.remove('loading');
                }
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        addBotMessage(response.reply || getFallbackResponse(userMessage));
                    } catch (e) {
                        // JSON parse error - use fallback
                        addBotMessage(getFallbackResponse(userMessage));
                    }
                } else {
                    // API error - use fallback
                    addBotMessage(getFallbackResponse(userMessage));
                }
            }
        };
        xhr.onerror = function() {
            hideTyping();
            state.isLoading = false;
            if (elements.send) {
                elements.send.disabled = false;
                elements.send.classList.remove('loading');
            }
            // Network error - use fallback
            addBotMessage(getFallbackResponse(userMessage));
        };
        var historyToSend = state.messages.slice(-10);
        xhr.send(JSON.stringify({ message: userMessage, history: historyToSend }));
    }

    function getFallbackResponse(userMessage) {
        var message = userMessage.toLowerCase();
        
        // Greeting responses
        if (/hello|hi|hey|greetings|what's up|yo/.test(message)) {
            return "Hey there! 👋 Welcome to ShoeTakels! How can I help you find the perfect shoes today?";
        }
        
        // Help/Support
        if (/help|support|assist|can you help/.test(message)) {
            return "Of course! I'm here to help! 😊 I can assist you with:\n• Finding shoes by size, brand, or style\n• Information about our products\n• Shipping and delivery questions\n• Returns and refunds\n• Payment options\n\nWhat would you like to know?";
        }
        
        // Shipping & Delivery
        if (/ship|deliver|delivery|how long|when will|track|tracking/.test(message)) {
            return "Great question! 📦 We offer fast shipping options:\n• Standard Delivery: 5-7 business days\n• Express Delivery: 2-3 business days\n• You'll receive a tracking number once your order ships\n\nNeed help tracking your order?";
        }
        
        // Returns & Refunds
        if (/return|refund|exchange|send back|money back/.test(message)) {
            return "No problem! 🔄 Here's our return policy:\n• You can return items within 30 days\n• Items must be unworn and in original condition\n• Free return shipping on most items\n• Full refund or exchange available\n\nWould you like to start a return?";
        }
        
        // Sizes & Fit
        if (/size|fit|how to|measure|what size|fitting/.test(message)) {
            return "Let me help you find the right fit! 👟\n• We have sizes from 4 to 14\n• Check our size guide for accurate measurements\n• Men's, Women's, and Kids' sizes available\n• Not sure about your size? I can help!\n\nWhat size range are you looking for?";
        }
        
        // Brands
        if (/brand|nike|adidas|puma|converse|timberland|vans|other brands/.test(message)) {
            return "Awesome! 🌟 We carry a great selection of brands:\n• Nike\n• Adidas\n• Puma\n• Converse\n• Timberland\n• Vans\n• And many more!\n\nWhich brand interests you?";
        }
        
        // Sales & Discounts
        if (/sale|discount|coupon|promo|deal|offer|price/.test(message)) {
            return "Great timing! 🎉 We always have amazing deals:\n• Check our SALE section for up to 50% off\n• Subscribe to our newsletter for exclusive discounts\n• Seasonal promotions throughout the year\n• Flash sales on weekends\n\nWant to see what's on sale right now?";
        }
        
        // Payment
        if (/payment|pay|credit card|debit|method|gcash|paypal|how to pay/.test(message)) {
            return "We accept multiple payment methods! 💳\n• Credit Cards (Visa, Mastercard)\n• Debit Cards\n• GCash & other mobile wallets\n• Bank transfers\n• Payment plans available\n\nWhich payment method do you prefer?";
        }
        
        // Products/Categories
        if (/shoe|sneaker|boot|casual|sport|athletic|formal|women|men|kids|categories/.test(message)) {
            return "Perfect! 👟 We have a wide variety of shoes:\n• Sneakers & Athletic shoes\n• Casual shoes\n• Formal shoes\n• Boots\n• Men's, Women's & Kids' styles\n• All the latest trends!\n\nWhat type of shoe are you looking for?";
        }
        
        // New/Latest
        if (/new|latest|trending|popular|bestseller|top rated|best seller/.test(message)) {
            return "Excellent choice! ✨ Check out our hottest items:\n• New arrivals this week\n• Best sellers\n• Customer favorites\n• Trending styles\n• Limited edition items\n\nWould you like to see our newest collection?";
        }
        
        // Delivery locations
        if (/where|ship to|deliver to|country|location|available/.test(message)) {
            return "We ship to many locations! 🌍 We deliver to:\n• Local areas\n• Nationwide\n• Select international addresses\n• Metro Manila with express option\n\nWhere are you located?";
        }
        
        // Generic responses
        if (message.length < 5) {
            return "I'm not quite sure what you mean. Could you tell me more about what you're looking for? 😊";
        }
        
        // Default fallback
        return "Thanks for your question! 😊 That's interesting. I'm still learning, but our team can definitely help you better. \n\nIn the meantime, you can:\n• Browse our products\n• Check out our size guide\n• Contact our support team\n\nIs there anything else I can help with?";
    }

    function renderMessages() {
        if (!elements.messages) return;
        elements.messages.innerHTML = '';
        state.messages.forEach(function(message) { renderMessage(message); });
    }

    function renderMessage(message) {
        if (!elements.messages) return;
        var div = document.createElement('div');
        div.className = 'chatbot-message ' + message.type;
        var time = formatTime(message.timestamp);
        var botAvatarSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
        var userAvatarSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
        var avatarSVG = message.type === 'bot' ? botAvatarSVG : userAvatarSVG;
        div.innerHTML = '<div class="chatbot-message-avatar">' + avatarSVG + '</div><div class="chatbot-message-content"><div class="chatbot-message-bubble">' + escapeHtml(message.text) + '</div><div class="chatbot-message-time">' + time + '</div></div>';
        elements.messages.appendChild(div);
    }

    function showTyping() {
        if (elements.typing) {
            elements.typing.style.display = 'flex';
            scrollToBottom();
        }
    }

    function hideTyping() {
        if (elements.typing) elements.typing.style.display = 'none';
    }

    function scrollToBottom() {
        if (elements.messages) {
            setTimeout(function() { elements.messages.scrollTop = elements.messages.scrollHeight; }, 10);
        }
    }

    function formatTime(timestamp) {
        try {
            var date = new Date(timestamp);
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ' ' + ampm;
        } catch (e) {
            return '';
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.clearChatHistory = function() {
        state.messages = [];
        saveState();
        renderMessages();
        addBotMessage(CONFIG.welcomeMessage);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 50);
    }

    window.addEventListener('load', function() {
        if (!state.initialized) init();
    });
})();
</script>