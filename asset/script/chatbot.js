/**
 * ShoeTakels AI Chatbot
 * Persistent chat with OpenAI integration
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        storageKey: 'shoetakels_chatbot',
        apiEndpoint: 'api/chatbot.php',
        maxMessages: 50,
        welcomeMessage: "Hi there! 👋 I'm the ShoeTakels Assistant. How can I help you find the perfect shoes today?"
    };

    // DOM Elements
    const elements = {
        container: document.getElementById('ai-chatbot-container'),
        toggle: document.getElementById('chatbot-toggle'),
        window: document.getElementById('chatbot-window'),
        minimize: document.getElementById('chatbot-minimize'),
        messages: document.getElementById('chatbot-messages'),
        form: document.getElementById('chatbot-form'),
        input: document.getElementById('chatbot-input'),
        send: document.getElementById('chatbot-send'),
        typing: document.getElementById('chatbot-typing'),
        badge: document.getElementById('chatbot-badge')
    };

    // State
    let state = {
        isOpen: false,
        messages: [],
        isLoading: false
    };

    // Initialize
    function init() {
        loadState();
        renderMessages();
        restoreWindowState();
        bindEvents();
        
        // Show welcome message if no messages
        if (state. messages.length === 0) {
            addBotMessage(CONFIG.welcomeMessage);
        }
    }

    // Load state from localStorage
    function loadState() {
        try {
            const saved = localStorage.getItem(CONFIG.storageKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                state. messages = parsed.messages || [];
                state.isOpen = parsed.isOpen || false;
            }
        } catch (e) {
            console.warn('Failed to load chatbot state:', e);
        }
    }

    // Save state to localStorage
    function saveState() {
        try {
            // Limit stored messages
            const messagesToSave = state.messages.slice(-CONFIG.maxMessages);
            localStorage.setItem(CONFIG.storageKey, JSON. stringify({
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
        elements.toggle.addEventListener('click', toggleChat);
        elements. minimize.addEventListener('click', closeChat);
        elements.form.addEventListener('submit', handleSubmit);
        
        // Close on escape key
        document. addEventListener('keydown', (e) => {
            if (e. key === 'Escape' && state.isOpen) {
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
    function openChat(animate = true) {
        state.isOpen = true;
        elements.window.classList.add('open');
        elements.toggle.classList.add('active');
        elements.badge.style.display = 'none';
        
        if (animate) {
            elements. input.focus();
        }
        
        scrollToBottom();
        saveState();
    }

    // Close chat
    function closeChat() {
        state.isOpen = false;
        elements. window.classList.remove('open');
        elements.toggle.classList.remove('active');
        saveState();
    }

    // Handle form submission
    async function handleSubmit(e) {
        e.preventDefault();
        
        const message = elements.input. value.trim();
        if (! message || state.isLoading) return;

        // Add user message
        addUserMessage(message);
        elements.input.value = '';
        
        // Send to API
        await sendMessage(message);
    }

    // Add user message
    function addUserMessage(text) {
        const message = {
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
        const message = {
            type: 'bot',
            text: text,
            timestamp: new Date(). toISOString()
        };
        state.messages.push(message);
        renderMessage(message);
        scrollToBottom();
        saveState();
    }

    // Send message to API
    async function sendMessage(userMessage) {
        state.isLoading = true;
        showTyping();
        elements.send.disabled = true;
        elements.send.classList.add('loading');

        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: userMessage,
                    history: state.messages. slice(-10) // Send last 10 messages for context
                })
            });

            const data = await response. json();
            
            hideTyping();
            
            if (data. success && data.response) {
                addBotMessage(data.response);
            } else {
                addBotMessage(data.error || "I'm sorry, I couldn't process that. Please try again.");
            }
        } catch (error) {
            console.error('Chatbot API error:', error);
            hideTyping();
            addBotMessage("I'm having trouble connecting right now. Please try again in a moment.");
        } finally {
            state. isLoading = false;
            elements. send.disabled = false;
            elements. send.classList.remove('loading');
        }
    }

    // Render all messages
    function renderMessages() {
        elements.messages.innerHTML = '';
        state.messages.forEach(message => renderMessage(message));
    }

    // Render single message
    function renderMessage(message) {
        const div = document.createElement('div');
        div.className = `chatbot-message ${message.type}`;
        
        const time = formatTime(message.timestamp);
        
        const avatarSVG = message.type === 'bot' 
            ? '<svg xmlns="http://www. w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6. 48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14. 2c-2.5 0-4. 71-1.28-6-3. 22. 03-1.99 4-3.08 6-3. 08 1.99 0 5. 97 1.09 6 3. 08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>'
            : '<svg xmlns="http://www.w3. org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1. 79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';

        div.innerHTML = `
            <div class="chatbot-message-avatar">
                ${avatarSVG}
            </div>
            <div class="chatbot-message-content">
                <div class="chatbot-message-bubble">${escapeHtml(message.text)}</div>
                <div class="chatbot-message-time">${time}</div>
            </div>
        `;
        
        elements.messages.appendChild(div);
    }

    // Show typing indicator
    function showTyping() {
        elements.typing.style.display = 'block';
        scrollToBottom();
    }

    // Hide typing indicator
    function hideTyping() {
        elements.typing.style.display = 'none';
    }

    // Scroll to bottom of messages
    function scrollToBottom() {
        requestAnimationFrame(() => {
            elements.messages.scrollTop = elements.messages. scrollHeight;
        });
    }

    // Format timestamp
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays === 1) {
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else {
            return date. toLocaleDateString([], { month: 'short', day: 'numeric' });
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Clear chat history (utility function)
    window.clearChatHistory = function() {
        state. messages = [];
        saveState();
        renderMessages();
        addBotMessage(CONFIG.welcomeMessage);
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();