<?php
// Chatbot partial - include this in all your pages before </body>
?>
<div id="ai-chatbot-container" class="chatbot-container">
    <!-- Floating Chat Button -->
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Open chat assistant">
        <span class="chatbot-icon-open">
            <svg xmlns="http://www. w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85. 5 3.58 1.36 5.07L2 22l4.93-1.36C8.42 21.5 10.15 22 12 22c5.52 0 10-4.48 10-10S17. 52 2 12 2zm-1 15h-2v-2h2v2zm2. 07-7.75l-. 9. 92C11.45 10.9 11 11.5 11 13h-2v-. 5c0-1.1.45-2. 1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1. 41 0-1. 1-.9-2-2-2s-2 .9-2 2H6c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
            </svg>
        </span>
        <span class="chatbot-icon-close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6. 41 19 12 13.41 17.59 19 19 17. 59 13.41 12z"/>
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
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14. 2c-2. 5 0-4.71-1. 28-6-3. 22. 03-1.99 4-3.08 6-3. 08 1.99 0 5. 97 1.09 6 3. 08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div class="chatbot-header-text">
                    <h4>ShoeTakels Assistant</h4>
                    <span class="chatbot-status"><span class="status-dot"></span>Online</span>
                </div>
            </div>
            <button id="chatbot-minimize" class="chatbot-minimize" aria-label="Minimize chat">
                <svg xmlns="http://www. w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
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
                    <svg xmlns="http://www.w3. org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
                        <path d="M2. 01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </form>

        <div class="chatbot-footer">
            <span>Powered by AI</span>
        </div>
    </div>
</div>

<link rel="stylesheet" href="asset/style/chatbot.css">
<script src="asset/script/chatbot.js"></script>