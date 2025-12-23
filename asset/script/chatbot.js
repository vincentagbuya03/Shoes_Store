/**
 * ShoeTakels AI Chatbot
 * Persistent chat with AI integration
 * Fixed for hosting compatibility
 */

(function() {
    'use strict';

    // Get base URL dynamically for hosting compatibility
    function getBaseUrl() {
        // Method 0: Check if PHP provided a base path globally (PREFERRED - most reliable)
        if (typeof window.CHATBOT_BASE_PATH !== 'undefined' && window.CHATBOT_BASE_PATH) {
            var baseUrl = window.location.origin + window.CHATBOT_BASE_PATH;
            console.log('Chatbot: Base URL from PHP global:', baseUrl);
            return baseUrl;
        }
        
        // Method 1: Try to find chatbot.js script source
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src;
            if (src && src.indexOf('chatbot.js') !== -1) {
                var baseUrl = src.replace(/asset\/script\/chatbot\.js.*$/, '');
                if (baseUrl && baseUrl.length > 0) {
                    console.log('Chatbot: Base URL from script src:', baseUrl);
                    return baseUrl;
                }
            }
        }
        
        // Method 2: Use current location and analyze path structure
        var loc = window.location;
        var pathname = loc.pathname;
        
        // Try to extract base path from current pathname
        // Look for common patterns: /subdirectory/page.php or /page.php
        var pathParts = pathname.split('/').filter(function(part) { return part.length > 0; });
        
        // Common file extensions that indicate we're looking at a file, not a directory
        var fileExtensions = /\.(php|html|htm|asp|aspx|jsp)$/i;
        
        // If we have path parts and the first one doesn't look like a file
        // it's likely a subdirectory
        if (pathParts.length > 1 && !fileExtensions.test(pathParts[0])) {
            var potentialBase = '/' + pathParts[0] + '/';
            console.log('Chatbot: Detected potential subdirectory:', potentialBase);
            return loc.origin + potentialBase;
        }
        
        // Default to root directory
        console.log('Chatbot: Using root directory');
        return loc.protocol + '//' + loc.host + '/';
    }

    // Configuration
    var CONFIG = {
        storageKey: 'shoetakels_chatbot',
        apiEndpoint: getBaseUrl() + 'api/chatbot.php',
        maxMessages: 50,
        welcomeMessage: "Hi there! 👋 I'm the ShoeTakels Assistant. How can I help you find the perfect shoes today?"
    };

    // DOM Elements - wait for them to be available
    console.log('Chatbot CONFIG:', CONFIG);
    var elements = {};

    // State
    var state = {
        isOpen: false,
        messages: [],
        isLoading: false,
        initialized: false
    };

    // Initialize DOM elements
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

        // Check if all required elements exist
        return elements.container && elements.toggle && elements.window && 
               elements.messages && elements.form && elements.input;
    }

    // Initialize
    function init() {
        if (state.initialized) return;
        
        if (!initElements()) {
            console.warn('Chatbot: Some elements not found, retrying...');
            setTimeout(init, 100);
            return;
        }

        console.log('Chatbot: Initializing with base URL:', CONFIG.apiEndpoint);
        
        loadState();
        renderMessages();
        restoreWindowState();
        bindEvents();
        
        // Show welcome message if no messages
        if (state.messages.length === 0) {
            addBotMessage(CONFIG.welcomeMessage);
        }

        state.initialized = true;
        console.log('Chatbot initialized successfully');
    }

    // Load state from localStorage
    function loadState() {
        try {
            var saved = localStorage.getItem(CONFIG.storageKey);
            if (saved) {
                var parsed = JSON.parse(saved);
                state.messages = parsed.messages || [];
                state.isOpen = parsed.isOpen || false;
            }
        } catch (e) {
            console.warn('Failed to load chatbot state:', e);
            state.messages = [];
            state.isOpen = false;
        }
    }

    // Save state to localStorage
    function saveState() {
        try {
            // Limit stored messages
            var messagesToSave = state.messages.slice(-CONFIG.maxMessages);
            localStorage.setItem(CONFIG.storageKey, JSON.stringify({
                messages: messagesToSave,
                isOpen: state.isOpen,
                timestamp: Date.now()
            }));
        } catch (e) {
            console.warn('Failed to save chatbot state:', e);
        }
    }

    // Restore window open/closed state
    function restoreWindowState() {
        if (state.isOpen) {
            openChat(false);
        }
    }

    // Bind event listeners
    function bindEvents() {
        if (elements.toggle) {
            elements.toggle.addEventListener('click', toggleChat);
        }
        
        if (elements.minimize) {
            elements.minimize.addEventListener('click', closeChat);
        }
        
        if (elements.form) {
            elements.form.addEventListener('submit', handleSubmit);
        }
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && state.isOpen) {
                closeChat();
            }
        });
    }

    // Toggle chat window
    function toggleChat() {
        if (state.isOpen) {
            closeChat();
        } else {
            openChat(true);
        }
    }

    // Open chat
    function openChat(animate) {
        state.isOpen = true;
        if (elements.window) {
            elements.window.classList.add('open');
        }
        if (elements.toggle) {
            elements.toggle.classList.add('active');
        }
        if (elements.badge) {
            elements.badge.style.display = 'none';
        }
        
        if (animate && elements.input) {
            setTimeout(function() {
                elements.input.focus();
            }, 300);
        }
        
        scrollToBottom();
        saveState();
    }

    // Close chat
    function closeChat() {
        state.isOpen = false;
        if (elements.window) {
            elements.window.classList.remove('open');
        }
        if (elements.toggle) {
            elements.toggle.classList.remove('active');
        }
        saveState();
    }

    // Handle form submission
    function handleSubmit(e) {
        e.preventDefault();
        
        if (!elements.input) return;
        
        var message = elements.input.value.trim();
        if (!message || state.isLoading) return;

        // Add user message
        addUserMessage(message);
        elements.input.value = '';
        
        // Send to API
        sendMessage(message);
    }

    // Add user message
    function addUserMessage(text) {
        var message = {
            type: 'user',
            text: text,
            timestamp: new Date().toISOString()
        };
        state.messages.push(message);
        renderMessage(message);
        scrollToBottom();
        saveState();
    }

    // Add bot message
    function addBotMessage(text) {
        var message = {
            type: 'bot',
            text: text,
            timestamp: new Date().toISOString()
        };
        state.messages.push(message);
        renderMessage(message);
        scrollToBottom();
        saveState();
    }

    // Send message to API
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
                        var data = JSON.parse(xhr.responseText);
                        if (data.success && data.response) {
                            addBotMessage(data.response);
                        } else {
                            addBotMessage(data.error || "I'm sorry, I couldn't process that. Please try again.");
                        }
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        addBotMessage("I'm having trouble understanding the response. Please try again.");
                    }
                } else {
                    console.error('Chatbot API error:', xhr.status, xhr.statusText);
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
        
        // Send last 10 messages for context
        var historyToSend = state.messages.slice(-10);
        xhr.send(JSON.stringify({
            message: userMessage,
            history: historyToSend
        }));
    }

    // Render all messages
    function renderMessages() {
        if (!elements.messages) return;
        elements.messages.innerHTML = '';
        state.messages.forEach(function(message) {
            renderMessage(message);
        });
    }

    // Render single message
    function renderMessage(message) {
        if (!elements.messages) return;
        
        var div = document.createElement('div');
        div.className = 'chatbot-message ' + message.type;
        
        var time = formatTime(message.timestamp);
        
        var botAvatarSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
        var userAvatarSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
        
        var avatarSVG = message.type === 'bot' ? botAvatarSVG : userAvatarSVG;

        div.innerHTML = '<div class="chatbot-message-avatar">' + avatarSVG + '</div>' +
            '<div class="chatbot-message-content">' +
            '<div class="chatbot-message-bubble">' + escapeHtml(message.text) + '</div>' +
            '<div class="chatbot-message-time">' + time + '</div>' +
            '</div>';
        
        elements.messages.appendChild(div);
    }

    // Show typing indicator
    function showTyping() {
        if (elements.typing) {
            elements.typing.style.display = 'block';
            scrollToBottom();
        }
    }

    // Hide typing indicator
    function hideTyping() {
        if (elements.typing) {
            elements.typing.style.display = 'none';
        }
    }

    // Scroll to bottom of messages
    function scrollToBottom() {
        if (elements.messages) {
            setTimeout(function() {
                elements.messages.scrollTop = elements.messages.scrollHeight;
            }, 10);
        }
    }

    // Format timestamp
    function formatTime(timestamp) {
        try {
            var date = new Date(timestamp);
            var now = new Date();
            var diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
            
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            var timeStr = hours + ':' + (minutes < 10 ? '0' : '') + minutes + ' ' + ampm;
            
            if (diffDays === 0) {
                return timeStr;
            } else if (diffDays === 1) {
                return 'Yesterday ' + timeStr;
            } else {
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return months[date.getMonth()] + ' ' + date.getDate();
            }
        } catch (e) {
            return '';
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Clear chat history (utility function)
    window.clearChatHistory = function() {
        state.messages = [];
        saveState();
        renderMessages();
        addBotMessage(CONFIG.welcomeMessage);
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded, but wait a bit for elements to be parsed
        setTimeout(init, 50);
    }

    // Also try to init when window loads (backup)
    window.addEventListener('load', function() {
        if (!state.initialized) {
            init();
        }
    });
})();
