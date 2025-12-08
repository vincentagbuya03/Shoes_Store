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
?>

<!-- Chatbot Styles - Embedded Inline -->
<style>
:root {
    --chatbot-primary: #6366f1;
    --chatbot-primary-dark: #4f46e5;
    --chatbot-secondary: #f1f5f9;
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

.chatbot-toggle {
    position: fixed !important;
    bottom: 24px !important;
    right: 24px !important;
    width: 60px !important;
    height: 60px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, var(--chatbot-primary) 0%, var(--chatbot-primary-dark) 100%) !important;
    border: none !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: var(--chatbot-shadow), 0 0 0 0 rgba(99, 102, 241, 0.4) !important;
    transition: var(--chatbot-transition) !important;
    z-index: 9999 !important;
    color: white !important;
}

.chatbot-toggle:hover {
    transform: scale(1.1) !important;
    box-shadow: var(--chatbot-shadow), 0 0 0 8px rgba(99, 102, 241, 0.2) !important;
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

.chatbot-window {
    position: fixed !important;
    bottom: 100px !important;
    right: 24px !important;
    width: 400px !important;
    height: 600px !important;
    background: var(--chatbot-bg) !important;
    border-radius: var(--chatbot-radius) !important;
    box-shadow: var(--chatbot-shadow) !important;
    z-index: 9998 !important;
    display: flex !important;
    flex-direction: column !important;
    opacity: 0 !important;
    transform: translateY(20px) !important;
    pointer-events: none !important;
    transition: var(--chatbot-transition) !important;
}

.chatbot-window.open {
    opacity: 1 !important;
    transform: translateY(0) !important;
    pointer-events: auto !important;
}

.chatbot-header {
    background: linear-gradient(135deg, var(--chatbot-primary) 0%, var(--chatbot-primary-dark) 100%) !important;
    color: white !important;
    padding: 20px !important;
    border-radius: var(--chatbot-radius) var(--chatbot-radius) 0 0 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.chatbot-header-info {
    display: flex !important;
    gap: 12px !important;
    align-items: center !important;
}

.chatbot-avatar svg {
    width: 40px !important;
    height: 40px !important;
    color: white !important;
}

.chatbot-header-text h4 {
    margin: 0 !important;
    font-size: 16px !important;
    font-weight: 600 !important;
}

.chatbot-status {
    font-size: 12px !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.status-dot {
    width: 8px !important;
    height: 8px !important;
    background: #10b981 !important;
    border-radius: 50% !important;
    display: inline-block !important;
    animation: pulse 2s infinite !important;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.chatbot-minimize {
    background: rgba(255, 255, 255, 0.2) !important;
    border: none !important;
    color: white !important;
    padding: 8px !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: var(--chatbot-transition) !important;
}

.chatbot-minimize:hover {
    background: rgba(255, 255, 255, 0.3) !important;
}

.chatbot-messages {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 16px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
}

.chatbot-message {
    display: flex !important;
    gap: 8px !important;
    animation: slideUp 0.3s ease !important;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chatbot-message.bot {
    justify-content: flex-start !important;
}

.chatbot-message.user {
    justify-content: flex-end !important;
}

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
    width: 18px !important;
    height: 18px !important;
    color: white !important;
}

.chatbot-message.user .chatbot-message-avatar {
    background: var(--chatbot-secondary) !important;
    order: 2 !important;
}

.chatbot-message-content {
    max-width: 280px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
}

.chatbot-message.user .chatbot-message-content {
    align-items: flex-end !important;
}

.chatbot-message-bubble {
    background: var(--chatbot-secondary) !important;
    padding: 12px 16px !important;
    border-radius: var(--chatbot-radius-sm) !important;
    word-wrap: break-word !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
}

.chatbot-message.user .chatbot-message-bubble {
    background: var(--chatbot-primary) !important;
    color: white !important;
}

.chatbot-message-time {
    font-size: 11px !important;
    color: var(--chatbot-text-light) !important;
}

.chatbot-typing {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 12px 16px !important;
}

.typing-indicator {
    display: flex !important;
    gap: 4px !important;
}

.typing-indicator span {
    width: 8px !important;
    height: 8px !important;
    background: var(--chatbot-text-light) !important;
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
    0%, 60%, 100% { opacity: 0.5; }
    30% { opacity: 1; }
}

.chatbot-form {
    padding: 12px !important;
    border-top: 1px solid var(--chatbot-border) !important;
    display: flex !important;
    gap: 8px !important;
}

.chatbot-input-wrapper {
    display: flex !important;
    gap: 8px !important;
    width: 100% !important;
}

#chatbot-input {
    flex: 1 !important;
    padding: 10px 14px !important;
    border: 1px solid var(--chatbot-border) !important;
    border-radius: var(--chatbot-radius-sm) !important;
    font-size: 14px !important;
    font-family: inherit !important;
    outline: none !important;
    transition: var(--chatbot-transition) !important;
}

#chatbot-input:focus {
    border-color: var(--chatbot-primary) !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
}

#chatbot-send {
    padding: 10px 14px !important;
    background: var(--chatbot-primary) !important;
    color: white !important;
    border: none !important;
    border-radius: var(--chatbot-radius-sm) !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: var(--chatbot-transition) !important;
}

#chatbot-send:hover {
    background: var(--chatbot-primary-dark) !important;
}

#chatbot-send:active {
    transform: scale(0.95) !important;
}

#chatbot-send.loading {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

#chatbot-send svg {
    width: 20px !important;
    height: 20px !important;
}

.chatbot-footer {
    padding: 8px 16px !important;
    border-top: 1px solid var(--chatbot-border) !important;
    text-align: center !important;
    font-size: 12px !important;
    color: var(--chatbot-text-light) !important;
    background: var(--chatbot-secondary) !important;
    border-radius: 0 0 var(--chatbot-radius) var(--chatbot-radius) !important;
}

.chatbot-badge {
    position: absolute !important;
    top: -5px !important;
    right: -5px !important;
    background: #ef4444 !important;
    color: white !important;
    border-radius: 50% !important;
    width: 24px !important;
    height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 12px !important;
    font-weight: bold !important;
}

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
                        addBotMessage(response.reply || "I didn't understand that. Can you rephrase?");
                    } catch (e) {
                        addBotMessage("I'm having trouble processing that. Please try again.");
                    }
                } else {
                    addBotMessage("I'm having trouble connecting right now. Please try again in a moment.");
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
            addBotMessage("I'm having trouble connecting right now. Please try again in a moment.");
        };
        var historyToSend = state.messages.slice(-10);
        xhr.send(JSON.stringify({ message: userMessage, history: historyToSend }));
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