<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المراسلات الخاصة - حراج اليمن</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: calc(100vh - 65px);
            max-width: 1400px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }
        @media (max-width: 768px) {
            .chat-layout {
                grid-template-columns: 1fr;
            }
            .threads-sidebar {
                display: block;
            }
            .chat-area.active {
                display: flex !important;
                position: fixed;
                top: 65px;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 200;
                background-color: var(--card-bg);
            }
            .threads-sidebar.hidden-mobile {
                display: none;
            }
        }
        
        .threads-sidebar {
            border-left: 1px solid var(--border-color);
            overflow-y: auto;
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
        }

        .threads-sidebar-header {
            padding: 1rem;
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--primary);
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-color);
        }
        
        .thread-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .thread-item:hover, .thread-item.active {
            background-color: rgba(0, 77, 122, 0.04);
        }
        .thread-item.unread {
            border-right: 4px solid var(--secondary);
            background-color: rgba(0, 153, 102, 0.05);
        }

                .chat-area {
            display: flex;
            flex-direction: column;
            background-color: #E5DDD5; /* WhatsApp like bg */
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            height: 100%;
        }
        .chat-header {
            padding: 0.9rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--card-bg);
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .messages-list {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .msg-bubble {
            max-width: 65%;
            padding: 0.5rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.5;
            font-weight: 600;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .msg-bubble.me {
            background-color: #DCF8C6; /* WhatsApp light green */
            color: #111;
            align-self: flex-start;
            border-top-right-radius: 0;
        }
        .msg-bubble.other {
            background-color: #FFFFFF;
            color: #111;
            align-self: flex-end;
            border-top-left-radius: 0;
        }
        .chat-input-area {
            padding: 1rem;
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
    </style>
</head>
<body>

    <!-- Consistent Premium Header -->
    <header class="glass-header">
        <div class="header-container">
            <a href="index.php" class="header-logo">
                <span class="header-logo-badge">الرسائل</span>
                <span>حراج</span>
            </a>
            
            <div class="header-search" style="visibility:hidden;">
                <input type="text" placeholder="البحث...">
            </div>
            
            <div class="header-actions">
                <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:white;">🌓</button>
                <a href="index.php" style="color:white; font-weight:bold; text-decoration:none; font-size:0.85rem;">الرئيسية</a>
            </div>
        </div>
    </header>

    <!-- Chat Area Layout -->
    <div class="chat-layout animate-fade-in">
        
        <!-- Sidebar Conversation List -->
        <div class="threads-sidebar" id="threads-sidebar-container">
            <div class="threads-sidebar-header">📥 علبة الوارد (الرسائل)</div>
            <div id="threads-list" style="flex:1; overflow-y:auto;">
                <div style="padding:2rem; text-align:center; color:var(--text-muted); font-weight:bold;">جاري التحميل...</div>
            </div>
        </div>
        
        <!-- Active Chat Window -->
        <div class="chat-area" id="chat-area" style="display:none;">
            <div class="chat-header">
                <div>
                    <button class="btn-gold" id="btn-back-sidebar" style="padding:0.25rem 0.75rem; font-size:0.75rem; display:none; margin-left:8px;">⬅️ رجوع</button>
                    <span id="chat-ad-title" style="color:var(--primary); font-weight:900;"></span> - مع <span id="chat-other-name" style="color:var(--secondary);"></span>
                </div>
                <a href="#" id="view-ad-link" class="btn-gold" style="font-size:0.75rem; padding:0.3rem 0.8rem;">📦 عرض السلعة</a>
            </div>
            
            <!-- Messages Bubble List -->
            <div class="messages-list" id="messages-list"></div>
            
            <!-- Text message input -->
            <form class="chat-input-area" onsubmit="sendMessage(event)">
                <input type="text" id="msg-text" class="input-premium" style="margin:0;" placeholder="اكتب رسالتك الخاصة هنا بوضوح..." required autocomplete="off">
                <button type="submit" class="btn-gold" style="margin:0; padding:0.6rem 1.5rem;">إرسال ✈️</button>
            </form>
        </div>
        
        <!-- No Chat Selected Placeholder -->
        <div id="no-chat-selected" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--text-muted); font-weight:bold; background-color:var(--bg-color);">
            <div style="font-size:3rem; margin-bottom:1rem;">✉️</div>
            <div>يرجى اختيار محادثة من القائمة الجانبية للبدء بالدردشة الخاصة.</div>
        </div>
    </div>

    <!-- Core App JS Utilities -->
    <script src="assets/js/app.js"></script>
    <script>
        let currentThreadId = null;
        let pollInterval = null;

        const urlParams = new URLSearchParams(window.location.search);
        const initThreadId = urlParams.get('thread');

        async function loadThreads() {
            try {
                const res = await apiRequest('chat&action=threads');
                const list = document.getElementById('threads-list');
                
                if (res.data.length === 0) {
                    list.innerHTML = '<div style="padding:3rem; text-align:center; color:var(--text-muted); font-size:0.85rem; font-weight:700;">لا توجد محادثات نشطة بعد.</div>';
                    return;
                }
                
                list.innerHTML = res.data.map(t => `
                    <div class="thread-item ${t.isUnread ? 'unread' : ''} ${t.id == currentThreadId ? 'active' : ''}" onclick="selectThread(${t.id}, '${t.adTitle}', '${t.otherName}', ${t.adId})">
                        <div style="font-weight:900; color:var(--text-main); font-size:0.85rem; margin-bottom:2px;">${t.otherName}</div>
                        <div style="font-size:0.75rem; color:var(--primary); font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">📦 سلعة: ${t.adTitle}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:600;">${t.lastMessage}</div>
                        <span style="position:absolute; left:10px; top:10px; font-size:0.65rem; color:var(--text-muted); font-weight:700;">${t.date}</span>
                    </div>
                `).join('');
                
            } catch(e) {}
        }

        let lastMsgId = 0;

        function selectThread(threadId, adTitle, otherName, adId) {
            currentThreadId = threadId;
            lastMsgId = 0; // Reset message tracker for new thread
            
            // Adjust mobile display
            if (window.innerWidth <= 768) {
                document.getElementById('threads-sidebar-container').classList.add('hidden-mobile');
                document.getElementById('chat-area').classList.add('active');
                const backBtn = document.getElementById('btn-back-sidebar');
                if (backBtn) {
                    backBtn.style.display = 'inline-flex';
                    backBtn.onclick = () => {
                        document.getElementById('threads-sidebar-container').classList.remove('hidden-mobile');
                        document.getElementById('chat-area').classList.remove('active');
                        currentThreadId = null;
                        if (pollInterval) {
                            clearInterval(pollInterval);
                            pollInterval = null;
                        }
                    };
                }
            }

            // Sync active states on sidebar items immediately
            document.querySelectorAll('.thread-item').forEach(i => i.classList.remove('active'));

            loadChat(threadId, adTitle, otherName, adId, false);
        }

        async function loadChat(threadId, adTitle, otherName, adId, isPolling = false) {
            if (currentThreadId !== threadId) return;
            
            document.getElementById('no-chat-selected').style.display = 'none';
            document.getElementById('chat-area').style.display = 'flex';
            
            if (adTitle) document.getElementById('chat-ad-title').innerText = adTitle;
            if (otherName) document.getElementById('chat-other-name').innerText = otherName;
            if (adId) document.getElementById('view-ad-link').href = `ad.php?id=${adId}`;
            
            try {
                const res = await apiRequest(`chat&action=messages&thread_id=${threadId}&last_msg_id=${lastMsgId}`);
                const list = document.getElementById('messages-list');
                
                if (!isPolling && lastMsgId === 0) {
                    list.innerHTML = ''; // Clear container only on initial load
                }
                
                if (res.data.messages && res.data.messages.length > 0) {
                    res.data.messages.forEach(m => {
                        const msgDiv = document.createElement('div');
                        msgDiv.className = `msg-bubble ${m.isMe ? 'me' : 'other'}`;
                        msgDiv.innerHTML = `
                            <div>${m.text}</div>
                            <div style="font-size:0.6rem; opacity:0.75; margin-top:0.25rem; text-align:${m.isMe ? 'left' : 'right'}; font-weight:700;">${m.date}</div>
                        `;
                        list.appendChild(msgDiv);
                        
                        // Keep track of the highest message ID
                        if (m.id > lastMsgId) {
                            lastMsgId = m.id;
                        }
                    });
                    
                    // Smoothly scroll to the bottom when new messages arrive
                    list.scrollTo({ top: list.scrollHeight, behavior: isPolling ? 'smooth' : 'auto' });
                }
                
                // Set up polling interval if not active
                if (!pollInterval) {
                    pollInterval = setInterval(() => {
                        if (currentThreadId) {
                            loadChat(currentThreadId, null, null, null, true);
                        }
                    }, 3000); // Poll every 3 seconds for instant feels
                }
            } catch(e) {}
        }

        async function sendMessage(e) {
            e.preventDefault();
            const textInput = document.getElementById('msg-text');
            const text = textInput.value.trim();
            
            if (!text || !currentThreadId) return;
            
            try {
                const res = await apiRequest('chat', 'POST', { action: 'send', thread_id: currentThreadId, text: text });
                textInput.value = '';
                // Instantly poll for new messages
                loadChat(currentThreadId, null, null, null, true);
                loadThreads();
            } catch(e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadThreads();
            if (initThreadId) {
                // If thread parameter is passed in URL, load it directly
                selectThread(initThreadId);
            }
            setInterval(loadThreads, 10000);
        });
    </script>
</body>
</html>
