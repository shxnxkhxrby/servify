<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-user-drag:none;}
#chatbot{position:fixed;bottom:20px;right:20px;z-index:9999;user-select:none;}
#chatbot-toggle{background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;font-size:28px;width:65px;height:65px;display:flex;align-items:center;justify-content:center;border-radius:50%;cursor:move;box-shadow:0 6px 20px rgba(2,138,150,0.4),0 2px 8px rgba(2,138,150,0.3);transition:transform 0.3s,box-shadow 0.3s;position:relative;overflow:hidden;touch-action:none;border:3px solid rgba(255,255,255,0.2);}
#chatbot-toggle::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(45deg,transparent,rgba(255,255,255,0.15),transparent);transform:rotate(45deg);transition:0.5s;}
#chatbot-toggle:hover{transform:scale(1.08) translateY(-2px);box-shadow:0 10px 30px rgba(2,138,150,0.5),0 4px 12px rgba(2,138,150,0.4);}
#chatbot-toggle:hover::before{left:100%;}
#chatbot-toggle:active{cursor:grabbing;transform:scale(0.95);}
.chat-icon{position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:100%;animation:float 3s ease-in-out infinite;border-radius:50%;}
.chat-icon img{width:65%;height:auto;display:block;border-radius:50%;}
@keyframes float{0%,100%{transform:translateY(0px);}50%{transform:translateY(-3px);}}
.pulse-ring{position:absolute;width:100%;height:100%;border:3px solid rgba(2,138,150,0.6);border-radius:50%;opacity:0;}
.pulse-ring.active{animation:pulse 2s infinite;}
@keyframes pulse{0%{transform:scale(1);opacity:1;}100%{transform:scale(1.4);opacity:0;}}
#chatbot-window{display:none;width:380px;max-height:550px;background:white;border-radius:15px;box-shadow:0 10px 40px rgba(2,138,150,0.25),0 4px 12px rgba(0,0,0,0.15);flex-direction:column;overflow:hidden;position:fixed;transition:all 0.3s ease;border:2px solid rgba(2,138,150,0.1);}
#chatbot-header{background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;font-weight:bold;user-select:none;position:relative;box-shadow:0 2px 8px rgba(2,138,150,0.2);}
#chatbot-header::after{content:'';position:absolute;bottom:0;left:0;width:100%;height:2px;background:rgba(255,255,255,0.2);}
.header-title{display:flex;align-items:center;gap:10px;font-size:16px;}
.status-dot{width:8px;height:8px;background:#4ade80;border-radius:50%;animation:blink 2s infinite;box-shadow:0 0 8px rgba(74,222,128,0.6);}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}
#chatbot-body{padding:15px;overflow-y:auto;max-height:380px;display:flex;flex-direction:column;background:linear-gradient(180deg,#e8eaed 0%,#f5f5f5 100%);}
#chatbot-body::-webkit-scrollbar{width:6px;}
#chatbot-body::-webkit-scrollbar-track{background:#f1f1f1;border-radius:10px;}
#chatbot-body::-webkit-scrollbar-thumb{background:linear-gradient(135deg,#028a96,#03a9b8);border-radius:10px;}
.bot-msg,.user-msg,.warning-msg{border-radius:12px;padding:12px 16px;margin-bottom:12px;max-width:85%;word-wrap:break-word;animation:fadeIn 0.3s ease;font-size:14px;line-height:1.6;position:relative;white-space:pre-line;}
.bot-msg{background:linear-gradient(135deg,#e0e3e6 0%,#f0f2f4 100%);align-self:flex-start;color:#2d3748;box-shadow:0 2px 5px rgba(0,0,0,0.08);padding-left:45px;}
.bot-msg::before{content:'💬';position:absolute;left:12px;top:12px;font-size:22px;}
.user-msg{background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;align-self:flex-end;box-shadow:0 2px 8px rgba(2,138,150,0.3);}
.warning-msg{background:linear-gradient(135deg,#dc3545 0%,#c92a3a 100%);color:white;align-self:flex-start;font-weight:600;box-shadow:0 2px 8px rgba(220,53,69,0.4);padding-left:45px;}
.warning-msg::before{content:'⚠️';position:absolute;left:12px;top:12px;font-size:22px;}
.typing-indicator{display:flex;gap:4px;padding:12px;background:linear-gradient(135deg,#e0e3e6 0%,#f0f2f4 100%);border-radius:12px;width:60px;align-self:flex-start;margin-bottom:12px;box-shadow:0 2px 5px rgba(0,0,0,0.08);}
.typing-indicator span{width:8px;height:8px;background:linear-gradient(135deg,#028a96,#03a9b8);border-radius:50%;animation:typing 1.4s infinite;}
.typing-indicator span:nth-child(2){animation-delay:0.2s;}
.typing-indicator span:nth-child(3){animation-delay:0.4s;}
@keyframes typing{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-10px);}}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
#chatbot-input-area{display:flex;gap:10px;padding:15px;border-top:1px solid #e9ecef;background:white;position:relative;}
#chatbot-input-area::before{content:'';position:absolute;top:0;left:0;width:100%;height:1px;background:linear-gradient(90deg,transparent,rgba(2,138,150,0.3),transparent);}
#chatbot-input{flex:1;padding:12px 18px;border:2px solid #e9ecef;border-radius:25px;outline:none;font-size:14px;transition:all 0.3s;font-family:inherit;}
#chatbot-input:focus{border-color:#028a96;box-shadow:0 0 0 3px rgba(2,138,150,0.1);}
#chatbot-send{background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;border:none;border-radius:50%;width:45px;height:45px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.3s;box-shadow:0 2px 8px rgba(2,138,150,0.3);flex-shrink:0;}
#chatbot-send:hover{transform:scale(1.1);box-shadow:0 4px 12px rgba(2,138,150,0.5);}
#chatbot-send:active{transform:scale(0.95);}
#chatbot-send:disabled{background:#ccc;cursor:not-allowed;box-shadow:none;transform:scale(1);}
#chatbot-close{cursor:pointer;font-size:20px;transition:all 0.3s;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;color:white;opacity:0.9;}
#chatbot-close:hover{transform:scale(1.15);opacity:1;background:rgba(255,255,255,0.1);}
#chatbot-suggestions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;margin-bottom:15px;}
#chatbot-suggestions button{background:linear-gradient(135deg,#d0f0f3 0%,#b8e5ea 100%);border:1px solid #028a96;color:#028a96;padding:10px 16px;border-radius:20px;cursor:pointer;font-size:13px;transition:all 0.3s;font-weight:600;font-family:inherit;}
#chatbot-suggestions button:hover{background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;transform:translateY(-2px);box-shadow:0 4px 8px rgba(2,138,150,0.3);}
.greeting-bubble{position:absolute;bottom:50%;right:75px;transform:translateY(20%);background:linear-gradient(135deg,#028a96 0%,#03a9b8 100%);color:white;padding:14px 24px 14px 20px;border-radius:20px;box-shadow:0 8px 24px rgba(2,138,150,0.35),0 4px 12px rgba(0,0,0,0.15);font-size:14px;font-weight:500;min-width:240px;max-width:380px;line-height:1.5;z-index:9998;pointer-events:none;white-space:normal;word-wrap:break-word;border:2px solid rgba(255,255,255,0.2);opacity:0;display:none;visibility:hidden;}
.greeting-bubble.show{display:block;visibility:visible;animation:bubbleSlideIn 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards;}
.greeting-bubble.hide{display:block;visibility:visible;animation:bubbleFadeOut 0.5s ease forwards;}
@keyframes bubbleSlideIn{0%{opacity:0;transform:translateY(20%) translateX(30px) scale(0.9);}100%{opacity:1;transform:translateY(20%) translateX(0) scale(1);}}
@keyframes bubbleFadeOut{0%{opacity:1;transform:translateY(20%) translateX(0) scale(1);}100%{opacity:0;transform:translateY(20%) translateX(30px) scale(0.9);}}
body.dragging-active{user-select:none !important;}
body.dragging-active *{pointer-events:none !important;}
#chatbot.dragging-active,#chatbot.dragging-active *{pointer-events:auto !important;}
@media(max-width:768px){#chatbot-window{width:340px;max-height:480px;}#chatbot-body{max-height:310px;}}
@media(max-width:480px){#chatbot{bottom:15px;right:15px;}#chatbot-toggle{width:58px;height:58px;font-size:24px;}#chatbot-window{width:300px;max-height:450px;border-radius:12px;}#chatbot-body{max-height:280px;padding:12px;}#chatbot-header{padding:12px 15px;font-size:14px;}.header-title{gap:8px;font-size:14px;}.greeting-bubble{max-width:200px;min-width:180px;font-size:13px;padding:12px 18px;right:68px;border-radius:18px;}.bot-msg,.user-msg,.warning-msg{font-size:13px;padding:10px 14px;max-width:88%;}.bot-msg{padding-left:38px;}.bot-msg::before,.warning-msg::before{font-size:18px;left:10px;top:10px;}#chatbot-suggestions button{padding:8px 12px;font-size:12px;}#chatbot-input{padding:10px 14px;font-size:13px;}#chatbot-send{width:40px;height:40px;font-size:14px;}#chatbot-input-area{padding:12px;}}
@media(max-width:360px){#chatbot-window{width:280px;max-height:420px;}#chatbot-body{max-height:260px;}.greeting-bubble{right:63px;max-width:170px;font-size:12px;padding:10px 14px;}}
</style>

<!-- ✅ Font Awesome 6.5.0 (CDN) -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  integrity="sha512-TKNsoXYM9c+e8G1FmqV4GAVZhQxqqH8wvD+MxoOQ0Dqgdo5WEmZf7MejMEvQKX1WgUB8cVTX0+x5V0eLk5Zo2g=="
  crossorigin="anonymous"
  referrerpolicy="no-referrer"
/>

<div id="chatbot">
    <div class="greeting-bubble" id="greeting-bubble"></div>
    <div id="chatbot-toggle" title="Chat with Servify Assistant">
        <span class="pulse-ring" id="pulse-ring"></span>
        <div class="chat-icon"><i class="fa-solid fa-robot"></i></div>
    </div>
    <div id="chatbot-window">
        <div id="chatbot-header">
            <div class="header-title">
                <span class="status-dot"></span>
                <span>Servify Assistant</span>
            </div>
            <span id="chatbot-close"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div id="chatbot-body">
            <div class="bot-msg">👋 Hi! I'm your Servify assistant. How can I help you today?</div>
            <div id="chatbot-suggestions">
                <button onclick="sendQuickMessage('Paano mag-hire ng laborer?')">Mag-hire</button>
                <button onclick="sendQuickMessage('Paano mag-verify?')">I-verify</button>
                <button onclick="sendQuickMessage('Paano mag-rate?')">Mag-rate</button>
                <button onclick="sendQuickMessage('Ano ang terms of use?')">Terms</button>
                <button onclick="sendQuickMessage('Privacy policy')">Privacy</button>
                <button onclick="sendQuickMessage('Contact support')">Support</button>
            </div>
        </div>
        <div id="chatbot-input-area">
            <input type="text" id="chatbot-input" placeholder="Type your message..." autocomplete="off">
            <button id="chatbot-send"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
const toggle = document.getElementById('chatbot-toggle');
const windowDiv = document.getElementById('chatbot-window');
const closeBtn = document.getElementById('chatbot-close');
const chatBody = document.getElementById('chatbot-body');
const inputField = document.getElementById('chatbot-input');
const sendBtn = document.getElementById('chatbot-send');

// Greeting bubble messages
const greetingMessages = [
    "Hi! I'm Servify's chat assistant. Need help? 👋",
    "Hello! Have questions about hiring laborers? Ask me! 😊",
    "Welcome to Servify! I'm here to assist you! 💡",
    "Hi there! Looking for help? I'm here for you! 🙋‍♂️",
    "Need assistance? Click me to chat! 💬",
    "Kumusta! Kailangan ba ng tulong? Tanong lang! 👋"
];

// Show random greeting on page load (3 second delay, shows for 3 seconds)
function showGreetingBubble() {
    const greetingBubble = document.getElementById('greeting-bubble');
    if (greetingBubble) {
        const randomMessage = greetingMessages[Math.floor(Math.random() * greetingMessages.length)];
        greetingBubble.textContent = randomMessage;
        greetingBubble.classList.add('show');
        
        // Store timeout ID so we can clear it if user clicks
        window.greetingTimeout = setTimeout(() => {
            hideGreetingBubble();
        }, 3000);
    }
}

// Hide greeting bubble with animation
function hideGreetingBubble() {
    const greetingBubble = document.getElementById('greeting-bubble');
    if (greetingBubble && !greetingBubble.classList.contains('hide')) {
        greetingBubble.classList.remove('show');
        greetingBubble.classList.add('hide');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            if (greetingBubble && greetingBubble.parentNode) {
                greetingBubble.remove();
            }
        }, 500);
    }
}

// Show greeting when page loads (3 second delay)
window.addEventListener('load', () => {
    setTimeout(() => {
        // Show greeting bubble
        showGreetingBubble();
        
        // Activate pulse ring after greeting appears
        const pulseRing = document.getElementById('pulse-ring');
        if (pulseRing) {
            pulseRing.classList.add('active');
        }
    }, 3000);
});

closeBtn.onclick = () => { windowDiv.style.display = 'none'; };

// Dragging functionality for CHATBOT WINDOW - DISABLED
let isDragging = false, offsetX, offsetY;
const header = document.getElementById('chatbot-header');

// Dragging functionality for FLOATING ICON
let isDraggingIcon = false, iconOffsetX, iconOffsetY, hasMoved = false, dragStartTime = 0;

toggle.addEventListener('mousedown', e => {
    isDraggingIcon = true;
    hasMoved = false;
    dragStartTime = Date.now();
    document.body.classList.add('dragging-active');
    const chatbotContainer = document.getElementById('chatbot');
    const rect = chatbotContainer.getBoundingClientRect();
    iconOffsetX = e.clientX - rect.left;
    iconOffsetY = e.clientY - rect.top;
    
    // Store initial position for movement detection
    window.mouseDownX = e.clientX;
    window.mouseDownY = e.clientY;
    
    e.preventDefault();
});

document.addEventListener('mousemove', e => {
    if (!isDraggingIcon) return;
    
    // Only start moving if dragged more than 5px from initial position
    const dx = Math.abs(e.clientX - window.mouseDownX);
    const dy = Math.abs(e.clientY - window.mouseDownY);
    
    if (dx > 5 || dy > 5) {
        hasMoved = true;
    }
    
    if (!hasMoved) return;
    
    e.preventDefault();
    
    const chatbotContainer = document.getElementById('chatbot');
    const newX = e.clientX - iconOffsetX;
    const newY = e.clientY - iconOffsetY;
    
    const maxX = window.innerWidth - toggle.offsetWidth;
    const maxY = window.innerHeight - toggle.offsetHeight;
    
    chatbotContainer.style.left = Math.max(0, Math.min(newX, maxX)) + 'px';
    chatbotContainer.style.top = Math.max(0, Math.min(newY, maxY)) + 'px';
    chatbotContainer.style.right = 'auto';
    chatbotContainer.style.bottom = 'auto';
});

document.addEventListener('mouseup', () => {
    if (isDraggingIcon) {
        isDraggingIcon = false;
        document.body.classList.remove('dragging-active');
        
        // Only treat as click if not moved and was quick (less than 300ms)
        const clickDuration = Date.now() - dragStartTime;
        if (!hasMoved && clickDuration < 300) {
            // Clear greeting timeout and hide bubble immediately
            if (window.greetingTimeout) {
                clearTimeout(window.greetingTimeout);
            }
            hideGreetingBubble();
            
            const isOpen = windowDiv.style.display === 'flex';
            windowDiv.style.display = isOpen ? 'none' : 'flex';
            if(!isOpen) {
                positionWindow();
                inputField.focus();
            }
        } else if (hasMoved) {
            // If we moved and window is open, reposition it
            if (windowDiv.style.display === 'flex') {
                positionWindow();
            }
        }
    }
});

// Touch support for mobile (icon) - IMPROVED
let touchStartX = 0, touchStartY = 0, touchMoveThreshold = 10;

toggle.addEventListener('touchstart', e => {
    isDraggingIcon = true;
    hasMoved = false;
    dragStartTime = Date.now();
    
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    
    document.body.classList.add('dragging-active');
    const chatbotContainer = document.getElementById('chatbot');
    const rect = chatbotContainer.getBoundingClientRect();
    iconOffsetX = touch.clientX - rect.left;
    iconOffsetY = touch.clientY - rect.top;
}, { passive: true });

document.addEventListener('touchmove', e => {
    if (!isDraggingIcon) return;
    
    const touch = e.touches[0];
    const dx = Math.abs(touch.clientX - touchStartX);
    const dy = Math.abs(touch.clientY - touchStartY);
    
    // Only start dragging if moved more than threshold
    if (dx > touchMoveThreshold || dy > touchMoveThreshold) {
        hasMoved = true;
        e.preventDefault();
        
        const chatbotContainer = document.getElementById('chatbot');
        const newX = touch.clientX - iconOffsetX;
        const newY = touch.clientY - iconOffsetY;
        
        const maxX = window.innerWidth - toggle.offsetWidth;
        const maxY = window.innerHeight - toggle.offsetHeight;
        
        chatbotContainer.style.left = Math.max(0, Math.min(newX, maxX)) + 'px';
        chatbotContainer.style.top = Math.max(0, Math.min(newY, maxY)) + 'px';
        chatbotContainer.style.right = 'auto';
        chatbotContainer.style.bottom = 'auto';
    }
}, { passive: false });

document.addEventListener('touchend', e => {
    if (isDraggingIcon) {
        isDraggingIcon = false;
        document.body.classList.remove('dragging-active');
        
        // Treat as tap if not moved and was quick
        const tapDuration = Date.now() - dragStartTime;
        if (!hasMoved && tapDuration < 300) {
            e.preventDefault();
            
            // Clear greeting timeout and hide bubble immediately
            if (window.greetingTimeout) {
                clearTimeout(window.greetingTimeout);
            }
            hideGreetingBubble();
            
            const isOpen = windowDiv.style.display === 'flex';
            windowDiv.style.display = isOpen ? 'none' : 'flex';
            if(!isOpen) {
                positionWindow();
                // Don't auto-focus on mobile to prevent keyboard popup
                if (window.innerWidth > 768) {
                    inputField.focus();
                }
            }
        } else if (hasMoved) {
            // If we moved and window is open, reposition it
            if (windowDiv.style.display === 'flex') {
                positionWindow();
            }
        }
    }
}, { passive: false });

// Function to dynamically position the window based on icon location
function positionWindow() {
    const chatbotContainer = document.getElementById('chatbot');
    const iconRect = toggle.getBoundingClientRect();
    const windowWidth = 350;
    const windowHeight = 500;
    const margin = 10;
    
    // Reset all positions
    windowDiv.style.left = 'auto';
    windowDiv.style.right = 'auto';
    windowDiv.style.top = 'auto';
    windowDiv.style.bottom = 'auto';
    
    // Determine horizontal position
    const iconCenterX = iconRect.left + (iconRect.width / 2);
    const screenMidX = window.innerWidth / 2;
    
    if (iconCenterX < screenMidX) {
        windowDiv.style.left = (iconRect.right + margin) + 'px';
    } else {
        windowDiv.style.right = (window.innerWidth - iconRect.left + margin) + 'px';
    }
    
    // Determine vertical position
    const iconCenterY = iconRect.top + (iconRect.height / 2);
    const screenMidY = window.innerHeight / 2;
    
    if (iconCenterY < screenMidY) {
        windowDiv.style.top = iconRect.top + 'px';
    } else {
        windowDiv.style.bottom = (window.innerHeight - iconRect.bottom) + 'px';
    }
    
    // Ensure window stays within viewport
    setTimeout(() => {
        const windowRect = windowDiv.getBoundingClientRect();
        
        if (windowRect.right > window.innerWidth) {
            windowDiv.style.left = 'auto';
            windowDiv.style.right = margin + 'px';
        }
        if (windowRect.left < 0) {
            windowDiv.style.right = 'auto';
            windowDiv.style.left = margin + 'px';
        }
        
        if (windowRect.bottom > window.innerHeight) {
            windowDiv.style.top = 'auto';
            windowDiv.style.bottom = margin + 'px';
        }
        if (windowRect.top < 0) {
            windowDiv.style.bottom = 'auto';
            windowDiv.style.top = margin + 'px';
        }
    }, 10);
}

// Strict profanity filter
const foulWords = [
    // English profanity
    'fuck', 'fck', 'fuk', 'f u c k', 'shit', 'sht', 'bitch', 'btch', 'ass', 'arse', 'damn', 'hell', 
    'bastard', 'crap', 'piss', 'cock', 'dick', 'pussy', 'cunt', 'whore', 'slut', 'nigger', 'nigga',
    // Filipino profanity
    'gago', 'gaga', 'tangina', 'tanginamo', 'putang', 'putangina', 'puta', 'punyeta', 'punyemas',
    'hayop', 'hayupka', 'ulol', 'tarantado', 'tarantada', 'leche', 'peste', 'pokpok', 'tanga', 
    'bobo', 'inutil', 'kupal', 'buwisit', 'walanghiya', 'walangya', 'hinayupak',
    'kingina', 'taena', 'tae', 'kantot', 'jakol', 'chupa', 'supsop',
    // Hook-up/sexual content
    'hookup', 'hook up', 'hook-up', 'fubu', 'one night', 'sexting', 'nudes', 'horny', 'libog', 
    'kantutan', 'sex', 'seggs', 'salsal', 'blow', 'oral',
    'gangbang', 'threesome', 'swinger', 'affair', 'kabit', 'kerida', 'querida'
];

// Normalize text to catch variations
function normalizeText(text) {
    return text.toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .replace(/(.)\1{2,}/g, '$1$1')
        .trim();
}

// Check for leetspeak and variations
function checkVariations(text) {
    return text
        .replace(/0/g, 'o')
        .replace(/1/g, 'i')
        .replace(/3/g, 'e')
        .replace(/4/g, 'a')
        .replace(/5/g, 's')
        .replace(/7/g, 't')
        .replace(/8/g, 'b')
        .replace(/@/g, 'a')
        .replace(/\$/g, 's');
}

// Safe word patterns that contain profanity substrings
const safeWordPatterns = [
    'assist', 'assistance', 'assistant', 'class', 'classic', 'classification',
    'assumption', 'assure', 'assessment', 'asset', 'assign', 'assignment',
    'bass', 'pass', 'glass', 'mass', 'brass', 'grass', 'compass',
    'hell', 'hello', 'shell', 'shelling', 'dwell', 'shellfish'
];

function containsFoulWord(text) {
    const normalized = normalizeText(text);
    const withVariations = checkVariations(normalized);
    const original = text.toLowerCase();
    
    let foulWordCount = 0;
    const detectedWords = [];
    
    for (let word of foulWords) {
        // Check word boundaries (standalone word)
        const wordPattern = new RegExp(`\\b${word}\\b`, 'i');
        if (wordPattern.test(normalized) || wordPattern.test(withVariations) || wordPattern.test(original)) {
            foulWordCount++;
            detectedWords.push(word);
            continue;
        }
        
        // Check for intentional spacing (e.g., "ass ignment", "f u c k")
        const spacedPattern = new RegExp(`\\b${word.split('').join('\\s+')}\\b`, 'i');
        if (spacedPattern.test(original)) {
            foulWordCount++;
            detectedWords.push(word);
            continue;
        }
    }
    
    // If 2+ foul words detected as standalone, likely intentional profanity
    if (foulWordCount >= 2) {
        return true;
    }
    
    // Check for compound profanity without spaces (e.g., "fuckasswhore")
    // Count how many foul words appear in the text
    let compoundCount = 0;
    for (let word of foulWords) {
        if (word.length >= 4) { // Only check meaningful words
            if (normalized.includes(word) || withVariations.includes(word)) {
                // Check if it's NOT part of a safe word
                let isSafe = false;
                for (let safe of safeWordPatterns) {
                    if (safe.includes(word) && (normalized.includes(safe) || original.includes(safe))) {
                        isSafe = true;
                        break;
                    }
                }
                if (!isSafe) {
                    compoundCount++;
                }
            }
        }
    }
    
    // If 2+ foul words found in compound form, it's intentional
    if (compoundCount >= 2) {
        return true;
    }
    
    // Single foul word detected - return true
    if (foulWordCount >= 1) {
        return true;
    }
    
    return false;
}

// Enhanced keywords and responses
const keywords = {
    // Terms of Use / Disclaimer - English
    'terms|terms of use|disclaimer|agreement|terms and conditions':
        "📋 Servify Terms of Use\n\n" +
        "By using Servify, you agree that:\n\n" +
        "• Servify is only a connecting platform - we don't employ or control workers\n\n" +
        "• Barangay Staff monitors activities, but agreements are between users and workers\n\n" +
        "• We don't guarantee service quality\n\n" +
        "• You must verify worker credibility before hiring\n\n" +
        "• Servify isn't liable for disputes or damages",
    
    // Terms of Use / Disclaimer - Tagalog
    'ano ang terms|mga patakaran|kasunduan':
        "📋 Mga Patakaran ng Servify\n\n" +
        "Sa paggamit ng Servify, sumasang-ayon ka na:\n\n" +
        "• Servify ay platform lamang - hindi kami employer ng mga workers\n\n" +
        "• Ang Barangay Staff ay nag-monitor, pero ang agreement ay sa pagitan ng user at worker\n\n" +
        "• Hindi kami guarantor ng kalidad ng serbisyo\n\n" +
        "• Kailangan mong i-verify ang worker bago mag-hire\n\n" +
        "• Ang Servify ay hindi responsible sa disputes o damages",
    
    // Privacy Policy - English
    'privacy|privacy policy|data|personal information|data collection':
        "🔒 Privacy Policy\n\n" +
        "Servify protects your privacy:\n\n" +
        "• We collect only necessary account and contact info\n\n" +
        "• Your data is NOT shared without consent\n\n" +
        "• Exception: Required by law or for community safety\n\n" +
        "• We use data only to operate the platform\n\n" +
        "• You can request to update or delete your data",
    
    // Privacy Policy - Tagalog
    'pribado|datos|personal na impormasyon':
        "🔒 Patakaran sa Privacy\n\n" +
        "Pinoprotektahan ng Servify ang iyong privacy:\n\n" +
        "• Kinukuha lang namin ang kinakailangang account at contact info\n\n" +
        "• HINDI namin ibabahagi ang datos mo nang walang consent\n\n" +
        "• Exception: Kung required ng batas o para sa safety ng community\n\n" +
        "• Ginagamit lang namin ang data para sa platform\n\n" +
        "• Pwede mong i-request na i-update o i-delete ang datos mo",
    
    // Liability / Responsibility - English
    'liable|liability|responsible|responsibility|who is responsible':
        "⚖️ Platform Responsibility\n\n" +
        "Important to know:\n\n" +
        "Servify is NOT liable for:\n\n" +
        "• Service quality issues\n" +
        "• Worker misconduct\n" +
        "• Payment disputes\n" +
        "• Damages or losses\n\n" +
        "Final agreements are between YOU and the WORKER.\n\n" +
        "Always verify workers before hiring and report any issues to Barangay Staff.",
    
    // Liability / Responsibility - Tagalog
    'responsable|pananagutan|sino ang may pananagutan':
        "⚖️ Responsibilidad ng Platform\n\n" +
        "Mahalagang malaman:\n\n" +
        "Ang Servify ay HINDI responsible sa:\n\n" +
        "• Kalidad ng serbisyo\n" +
        "• Maling gawa ng worker\n" +
        "• Dispute sa bayad\n" +
        "• Pinsala o pagkalugi\n\n" +
        "Ang final agreement ay sa pagitan MO at ng WORKER.\n\n" +
        "I-verify lagi ang workers bago mag-hire at i-report ang anumang issue sa Barangay Staff.",

    // Platform Role / What is Servify - English
    'what is servify|about servify|platform role|what does servify do':
        "🏢 About Servify\n\n" +
        "Servify is a community-based connecting platform:\n\n" +
        "• We connect residents with local workers\n\n" +
        "• We provide the digital space for connections\n\n" +
        "• Barangay Staff helps oversee operations\n\n" +
        "• We DON'T employ or control workers\n\n" +
        "• We facilitate connections, not employment",
    
    // Platform Role / What is Servify - Tagalog
    'ano ang servify|tungkol sa servify|ano ginagawa ng servify':
        "🏢 Tungkol sa Servify\n\n" +
        "Ang Servify ay community-based connecting platform:\n\n" +
        "• Nag-connect kami ng residents sa local workers\n\n" +
        "• Nagbibigay kami ng digital space para sa connections\n\n" +
        "• Ang Barangay Staff ay tumutulong sa operations\n\n" +
        "• HINDI kami employer ng workers\n\n" +
        "• Nag-facilitate lang kami ng connections, hindi employment",

    // Disputes / Problems - English
    'dispute|problem with worker|conflict|issue with laborer|complaint':
        "⚠️ Resolving Disputes\n\n" +
        "If you have issues with a worker:\n\n" +
        "1. Try to resolve directly first\n\n" +
        "2. Document everything (chat, photos, receipts)\n\n" +
        "3. Contact Barangay Staff for mediation\n\n" +
        "4. Report through Servify support\n\n" +
        "5. Leave an honest review to warn others\n\n" +
        "📧 servify.support@gmail.com\n" +
        "📱 +63 912 345 6789",
    
    // Disputes / Problems - Tagalog
    'problema sa worker|alitan|reklamo|may issue':
        "⚠️ Pag-ayos ng Alitan\n\n" +
        "Kung may problema ka sa worker:\n\n" +
        "1. Subukang ayusin nang direkta muna\n\n" +
        "2. I-document lahat (chat, photos, receipts)\n\n" +
        "3. Makipag-ugnayan sa Barangay Staff para sa mediation\n\n" +
        "4. I-report sa Servify support\n\n" +
        "5. Mag-iwan ng honest review para warning sa iba\n\n" +
        "📧 servify.support@gmail.com\n" +
        "📱 +63 912 345 6789",

    // Hiring/Booking - English
    'hire|book|get|find': 
        "💼 To hire a laborer:\n\n" +
        "1. Browse categories or search for workers\n\n" +
        "2. Visit their profile\n\n" +
        "3. Click 'Hire Now' or 'Message'\n\n" +
        "4. Provide job details, location, and schedule\n\n" +
        "5. Wait for the laborer to accept",
    
    // Hiring/Booking - Tagalog
    'mag-?hire|kumuha|hanap|mag-?book|humanap': 
        "💼 Para mag-hire ng laborer:\n\n" +
        "1. Mag-browse ng categories o maghanap ng workers\n\n" +
        "2. Bisitahin ang kanilang profile\n\n" +
        "3. I-click ang 'Hire Now' o 'Message'\n\n" +
        "4. Ibigay ang job details, lokasyon, at schedule\n\n" +
        "5. Maghintay ng acceptance ng laborer",
    
    // Verification - English
    'verify|verification|verified': 
        "✅ To verify your account:\n\n" +
        "1. Go to your Profile\n\n" +
        "2. Click on 'Verification'\n\n" +
        "3. Upload a valid Barangay ID\n\n" +
        "4. Submit supporting documents\n\n" +
        "5. Wait for approval (24-48 hours)",
    
    // Verification - Tagalog
    'mag-?verify|pag-?verify|i-?verify|pa-?verify': 
        "✅ Para i-verify ang account:\n\n" +
        "1. Pumunta sa iyong Profile\n\n" +
        "2. I-click ang 'Verification'\n\n" +
        "3. Mag-upload ng valid Barangay ID\n\n" +
        "4. Isumite ang supporting documents\n\n" +
        "5. Maghintay ng approval (24-48 hours)",
    
    // Rating/Review - English
    'rate|rating|review|feedback': 
        "⭐ To rate a laborer:\n\n" +
        "1. Complete a job with them first\n\n" +
        "2. Visit their profile\n\n" +
        "3. Click 'Leave a Review'\n\n" +
        "4. Give a star rating (1-5 stars)\n\n" +
        "5. Write your experience\n\n" +
        "Note: You must be a verified user to leave ratings.",
    
    // Rating/Review - Tagalog
    'mag-?rate|i-?rate|mag-?review|rebyu': 
        "⭐ Para mag-rate ng laborer:\n\n" +
        "1. Dapat natapos mo muna ang trabaho\n\n" +
        "2. Bisitahin ang kanilang profile\n\n" +
        "3. I-click ang 'Leave a Review'\n\n" +
        "4. Magbigay ng star rating (1-5 stars)\n\n" +
        "5. Isulat ang iyong experience\n\n" +
        "Note: Kailangan verified user ka para mag-rate.",
    
    // Payment - English
    'pay|payment|price|cost|fee': 
        "💰 Servify is FREE!\n\n" +
        "You negotiate payment directly with the laborer based on:\n\n" +
        "• Type of service\n\n" +
        "• Duration\n\n" +
        "• Complexity\n\n" +
        "• Materials needed\n\n" +
        "⚠️ Always agree on price before starting work!",
    
    // Payment - Tagalog
    'bayad|bayaran|presyo|halaga|magkano|singil': 
        "💰 Ang Servify ay LIBRE!\n\n" +
        "Direkta kayong mag-usap ng laborer tungkol sa bayad depende sa:\n\n" +
        "• Uri ng serbisyo\n\n" +
        "• Tagal\n\n" +
        "• Kahirapan\n\n" +
        "• Materyales na kailangan\n\n" +
        "⚠️ Magkasundo muna sa presyo bago magsimula!",
    
    // Contact/Support - English
    'contact|support|help|problem|issue': 
        "📞 Servify Support\n\n" +
        "📧 servify.support@gmail.com\n\n" +
        "📱 +63 912 345 6789\n\n" +
        "⏰ Mon-Fri, 9AM-5PM\n\n" +
        "You can also message us through our Facebook page!",
    
    // Contact/Support - Tagalog
    'tulong|suporta|tanong': 
        "📞 Servify Support\n\n" +
        "📧 servify.support@gmail.com\n\n" +
        "📱 +63 912 345 6789\n\n" +
        "⏰ Lun-Biye, 9AM-5PM\n\n" +
        "Pwede ka ring mag-message sa aming Facebook page!",
    
    // Account/Profile - English
    'account|profile|edit|update|change|settings': 
        "👤 To manage your account:\n\n" +
        "1. Click your profile picture (top right)\n\n" +
        "2. Select 'Profile'\n\n" +
        "3. You can edit:\n\n" +
        "   • Personal information\n" +
        "   • Profile picture\n" +
        "   • Contact details\n" +
        "   • Services (for laborers)",
    
    // Account/Profile - Tagalog
    'pag-?edit|i-?edit|baguhin|ayusin': 
        "👤 Para i-manage ang account:\n\n" +
        "1. I-click ang profile picture (taas kanan)\n\n" +
        "2. Piliin ang 'Profile'\n\n" +
        "3. Pwede mong i-edit:\n\n" +
        "   • Personal information\n" +
        "   • Profile picture\n" +
        "   • Contact details\n" +
        "   • Services (para sa laborers)",
    
    // Services/Categories - English
    'service|category|labor|work|job': 
        "🔧 Servify offers various services:\n\n" +
        "⚡ Electrician\n" +
        "🔧 Plumber\n" +
        "🔨 Carpenter\n" +
        "🍳 Cook/Catering\n" +
        "🧹 Cleaning\n" +
        "👶 Babysitter\n" +
        "💻 IT Support\n" +
        "...and many more!\n\n" +
        "Browse categories on the homepage.",
    
    // Services/Categories - Tagalog
    'serbisyo|kategorya|trabaho|gawa|ano.*meron': 
        "🔧 Available na serbisyo sa Servify:\n\n" +
        "⚡ Electrician / Elektrisyan\n" +
        "🔧 Plumber / Tubero\n" +
        "🔨 Carpenter / Karpintero\n" +
        "🍳 Cook/Catering / Kusinero\n" +
        "🧹 Cleaning / Kalinisan\n" +
        "👶 Babysitter / Yaya\n" +
        "💻 IT Support\n" +
        "...at marami pang iba!\n\n" +
        "Tignan ang homepage para sa buong listahan.",
    
    // Safety - English
    'safe|safety|secure|trust|scam': 
        "🛡️ Your safety matters!\n\n" +
        "Safety Tips:\n\n" +
        "✅ Check laborer ratings & reviews\n\n" +
        "✅ Verify their account status\n\n" +
        "✅ Meet in public places first\n\n" +
        "✅ Agree on price before work starts\n\n" +
        "✅ Report suspicious behavior",
    
    // Safety - Tagalog
    'ligtas|security|manloloko|trustworthy': 
        "🛡️ Ang iyong kaligtasan ay mahalaga!\n\n" +
        "Mga Safety Tips:\n\n" +
        "✅ Tignan ang ratings & reviews\n\n" +
        "✅ I-verify ang account status\n\n" +
        "✅ Magkita sa public places muna\n\n" +
        "✅ Magkasundo sa presyo bago magtrabaho\n\n" +
        "✅ I-report ang suspicious behavior",
    
            // Greetings - English (only triggers if ONLY greeting words)
        '^(hi|hello|hey|good\\s*morning|good\\s*afternoon|good\\s*evening|sup|yo|hey\\s*there|wassup)[!?.\\s]*$': 
            "Hello! 👋\n\nHow can I assist you with Servify today?",

        // Greetings - Tagalog (only triggers if ONLY greeting words)
        '^(kumusta|musta|kamusta|magandang\\s*(umaga|tanghali|hapon|gabi)|hoy|oy)[!?.\\s]*$': 
            "Kumusta! 👋\n\nPaano kita matutulungan sa Servify ngayon?",

        // Thanks - English (only triggers if ONLY thank you words)
        '^(thank\\s*you|thanks|thank\\s*you\\s*so\\s*much|appreciate\\s*it)[!?.\\s]*$': 
            "You're welcome! 😊\n\nFeel free to ask if you need more help.",

        // Thanks - Tagalog (only triggers if ONLY thank you words)
        '^(salamat|maraming\\s*salamat|salamat\\s*po)[!?.\\s]*$': 
            "Walang anuman! 😊\n\nHuwag mag-atubiling magtanong kung may kailangan ka pa!",

        // Goodbye - English (only triggers if ONLY goodbye words)
        '^(bye|goodbye|see\\s*you|see\\s*ya|take\\s*care)[!?.\\s]*$': 
            "Goodbye! 👋\n\nCome back if you have questions. Have a great day!",

        // Goodbye - Tagalog (only triggers if ONLY goodbye words)
        '^(paalam|sige\\s*na|ingat|bye\\s*na)[!?.\\s]*$': 
            "Ingat! 👋\n\nBalik ka ulit kung may tanong ka. Have a great day!"
};

function generateResponse(userInput) {
    const lowerInput = userInput.toLowerCase().trim();
    
    // STRICT: Check for inappropriate content FIRST - immediately block
    if (containsFoulWord(lowerInput)) {
        return {
            type: 'warning',
            message: "⚠️ We do not allow profanity, inappropriate language, or sensitive topics in this chat. Please keep the conversation professional and respectful. How can I assist you with Servify services?"
        };
    }
    
    // Check for greetings at the start
    const greetingPattern = /^(hi|hello|hey|kumusta|musta|magandang\s*(umaga|tanghali|hapon|gabi))[,!\s]+/i;
    const hasGreeting = greetingPattern.test(lowerInput);
    let greetingPrefix = '';
    
    if (hasGreeting) {
        // Detect language and set appropriate greeting
        const isTagalog = /kumusta|musta|magandang/i.test(lowerInput);
        greetingPrefix = isTagalog ? "Kumusta! 😊\n\n" : "Hello! 😊\n\n";
        
        // Remove greeting from input for further processing
        // Only remove if there's more text after the greeting
        const inputWithoutGreeting = lowerInput.replace(greetingPattern, '').trim();
        if (inputWithoutGreeting.length > 0) {
            return processMainQuery(inputWithoutGreeting, greetingPrefix);
        }
    }
    
    // Process without greeting prefix
    return processMainQuery(lowerInput, '');
}

function processMainQuery(lowerInput, greetingPrefix = '') {
    // Collect ALL matching responses
    const matchedResponses = [];
    
    // Priority 1: Exact multi-word matches for terms/privacy/disputes
    const priorityPatterns = [
        'terms of use', 'terms and conditions', 'privacy policy', 
        'what is servify', 'about servify', 'dispute', 'problem with worker'
    ];
    
    for (let phrase of priorityPatterns) {
        if (lowerInput.includes(phrase)) {
            for (let [pattern, response] of Object.entries(keywords)) {
                if (pattern.includes(phrase.replace(/\s+/g, '|'))) {
                    if (!matchedResponses.includes(response)) {
                        matchedResponses.push(response);
                    }
                }
            }
        }
    }
    
    // Priority 2: Regular keyword matching (only if no priority matches found)
    if (matchedResponses.length === 0) {
        for (let [pattern, response] of Object.entries(keywords)) {
            const regex = new RegExp(pattern, 'i');
            if (regex.test(lowerInput)) {
                if (!matchedResponses.includes(response)) {
                    matchedResponses.push(response);
                }
            }
        }
    }
    
    // If multiple matches found, combine them
    if (matchedResponses.length > 1) {
        return {
            type: 'bot',
            message: greetingPrefix + matchedResponses.join('\n\n───────────\n\n')
        };
    }
    
    // If single match found, return it
    if (matchedResponses.length === 1) {
        return { type: 'bot', message: greetingPrefix + matchedResponses[0] };
    }
    
    // Default response - detect language
    const isTagalog = /kumusta|paano|ano|saan|kailan|bakit|mag|nag|pag|mga|ang|ng|sa|kay/i.test(lowerInput);
    
    if (isTagalog) {
        return {
            type: 'bot',
            message: greetingPrefix + "🤔 Hindi ko masyadong maintindihan. Subukan itong tanungin:\n\n" +
                     "• Paano mag-hire ng laborer?\n" +
                     "• Paano mag-verify?\n" +
                     "• Ano ang terms of use?\n" +
                     "• Privacy policy\n" +
                     "• Ano ang services?\n" +
                     "• Contact support\n\n" +
                     "O gamitin ang quick options sa ibaba!"
        };
    } else {
        return {
            type: 'bot',
            message: greetingPrefix + "🤔 I'm not sure I understand. Try asking:\n\n" +
                     "• How do I hire a laborer?\n" +
                     "• How to verify my account?\n" +
                     "• What are the terms of use?\n" +
                     "• Privacy policy\n" +
                     "• What services are available?\n" +
                     "• How to contact support?\n\n" +
                     "Or use the quick options below!"
        };
    }
}

function addMessage(text, type = 'user') {
    const msgDiv = document.createElement('div');
    msgDiv.className = type === 'warning' ? 'warning-msg' : type === 'user' ? 'user-msg' : 'bot-msg';
    msgDiv.textContent = text;
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showTyping() {
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'typing';
    typingDiv.innerHTML = '<span></span><span></span><span></span>';
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function hideTyping() {
    const typing = document.getElementById('typing');
    if (typing) typing.remove();
}

function sendMessage() {
    const userText = inputField.value.trim();
    if (!userText) return;
    addMessage(userText, 'user');
    inputField.value = '';
    showTyping();
    sendBtn.disabled = true;
    setTimeout(() => {
        hideTyping();
        const response = generateResponse(userText);
        addMessage(response.message, response.type);
        sendBtn.disabled = false;
        inputField.focus();
    }, 800 + Math.random() * 800);
}

function sendQuickMessage(text) {
    inputField.value = text;
    sendMessage();
}

sendBtn.onclick = sendMessage;
inputField.addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMessage();
});

inputField.addEventListener('keydown', e => {
    if (sendBtn.disabled && e.key === 'Enter') {
        e.preventDefault();
    }
});
</script>