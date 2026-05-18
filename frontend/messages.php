<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }

define('PAGE_TITLE', 'الرسائل - ' . SITE_NAME);
define('HIDE_SEARCH', true);
define('EXTRA_CSS', '<link rel="stylesheet" href="assets/css/chat.css">');
include __DIR__ . '/includes/header.php';
?>

<div class="chat-app" id="chat-app">
    <!-- Sidebar -->
    <aside class="chat-sidebar" id="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2>💬 الرسائل</h2>
        </div>
        <div class="chat-search">
            <input type="text" id="thread-search" placeholder="ابحث في المحادثات...">
        </div>
        <div class="threads-list" id="threads-list">
            <div style="text-align:center; padding:2rem; color:var(--text-muted);">جاري التحميل...</div>
        </div>
    </aside>

    <!-- Main Chat Area -->
    <main class="chat-main" id="chat-main">
        <div class="chat-empty" id="chat-empty">
            <div class="chat-empty-icon">💬</div>
            <h3>اختر محادثة للبدء</h3>
            <p style="color: var(--text-muted);">اختر محادثة من القائمة أو ابدأ محادثة جديدة من صفحة إعلان</p>
        </div>

        <div id="chat-active" style="display:none; flex-direction:column; flex:1;">
            <header class="chat-header-bar">
                <button class="chat-header-back" onclick="backToList()">→</button>
                <div class="chat-header-info">
                    <div class="chat-header-avatar"><img id="other-avatar" src="" alt=""></div>
                    <div class="chat-header-text">
                        <h3 class="chat-header-name" id="other-name"></h3>
                        <div class="chat-header-status" id="other-status">
                            <span class="online-dot offline"></span> <span id="status-text">غير متصل</span>
                        </div>
                    </div>
                </div>
                <button onclick="confirmDeleteThread()" class="header-icon-btn" style="background:var(--bg-color); color:var(--danger); border:1px solid var(--border-color);" title="حذف المحادثة">🗑️</button>
            </header>

            <a id="chat-ad-info" class="chat-ad-info" href="#">
                <img id="ad-thumb" class="chat-ad-thumb" src="" alt="">
                <div class="chat-ad-text">
                    <div class="chat-ad-title" id="ad-title-chat"></div>
                    <div class="chat-ad-price" id="ad-price-chat"></div>
                </div>
                <span style="color: var(--text-muted); font-size: 0.8rem;">عرض ›</span>
            </a>

            <div class="messages-area" id="messages-area"></div>

            <div class="attachment-preview" id="attachment-preview">
                <img id="attachment-img" src="">
                <span id="attachment-name" style="font-weight: 700; font-size: 0.85rem;"></span>
                <button class="remove-attachment" onclick="clearAttachment()">×</button>
            </div>

            <div class="chat-input-area">
                <label class="chat-input-icon-btn" title="إرفاق صورة">
                    📷
                    <input type="file" id="file-input" accept="image/*" onchange="handleAttachment(event)">
                </label>
                <input type="text" class="chat-input-text" id="message-input" placeholder="اكتب رسالة..." onkeydown="handleKey(event)" oninput="sendTyping()">
                <button class="chat-send-btn" id="send-btn" onclick="sendMessage()" title="إرسال">➤</button>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/app.js"></script>
<script>
let currentThread = null;
let lastMessageId = 0;
let eventSource = null;
let typingTimer = null;
let pendingAttachment = null;
let threadsData = [];
const ME_ID = <?= (int)$_SESSION['user_id'] ?>;

async function loadThreads() {
    try {
        const r = await apiRequest('chat&action=threads');
        threadsData = r.data;
        renderThreads(threadsData);

        // إذا في URL ?thread=X افتحها
        const urlParams = new URLSearchParams(window.location.search);
        const tid = parseInt(urlParams.get('thread'));
        if (tid) openThread(tid);
    } catch (e) {}
}

function renderThreads(list) {
    const container = document.getElementById('threads-list');
    if (!list.length) {
        container.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد محادثات بعد.<br><a href="index.php" style="color:var(--primary);">تصفح الإعلانات</a></div>';
        return;
    }
    container.innerHTML = list.map(t => `
        <a class="thread-item ${currentThread === t.id ? 'active' : ''}" onclick="openThread(${t.id})">
            <div class="thread-avatar">
                <img src="${t.otherAvatar}" alt="${escapeHtml(t.otherName)}">
                <span class="thread-status-dot ${t.otherStatus}"></span>
            </div>
            <div class="thread-content">
                <div class="thread-top-line">
                    <span class="thread-name">${escapeHtml(t.otherName)}</span>
                    <span class="thread-time">${escapeHtml(t.date)}</span>
                </div>
                <div class="thread-bottom-line">
                    <span class="thread-preview">${escapeHtml(t.lastMessage)}</span>
                    ${t.unread > 0 ? `<span class="thread-unread-badge">${t.unread}</span>` : ''}
                </div>
            </div>
        </a>
    `).join('');
}

async function openThread(id) {
    currentThread = id;
    lastMessageId = 0;
    document.getElementById('chat-empty').style.display = 'none';
    document.getElementById('chat-active').style.display = 'flex';

    // Mobile: hide sidebar, show main
    if (window.innerWidth <= 768) {
        document.getElementById('chat-sidebar').classList.add('hidden-mobile');
        document.getElementById('chat-main').classList.add('show-mobile');
    }

    // Highlight active
    document.querySelectorAll('.thread-item').forEach(t => t.classList.remove('active'));
    const activeEl = Array.from(document.querySelectorAll('.thread-item')).find(t => t.getAttribute('onclick')?.includes(`openThread(${id})`));
    if (activeEl) activeEl.classList.add('active');

    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('thread', id);
    window.history.replaceState({}, '', url);

    try {
        const r = await apiRequest(`chat&action=messages&thread_id=${id}`);
        const data = r.data;

        // Other user info
        document.getElementById('other-avatar').src = data.other.avatar_url;
        document.getElementById('other-name').textContent = data.other.name;
        updateStatusUI(data.other.status, data.other.lastSeenAt);

        // Ad info
        if (data.ad) {
            document.getElementById('ad-thumb').src = data.ad.image;
            document.getElementById('ad-title-chat').textContent = data.ad.title;
            document.getElementById('ad-price-chat').textContent = data.ad.priceFormatted;
            document.getElementById('chat-ad-info').href = `ad.php?id=${data.ad.id}${data.ad.slug ? '&slug='+encodeURIComponent(data.ad.slug) : ''}`;
        }

        // Messages
        renderMessages(data.messages);
        if (data.messages.length) lastMessageId = data.messages[data.messages.length - 1].id;

        // Start SSE
        startEventSource(id);

        // Update unread badge
        updateBadgeCounts();
    } catch (e) {}
}

function renderMessages(messages) {
    const area = document.getElementById('messages-area');
    area.innerHTML = '';
    messages.forEach(addMessageToUI);
    scrollToBottom();
}

function addMessageToUI(m) {
    const area = document.getElementById('messages-area');
    const group = document.createElement('div');
    group.className = 'message-group ' + (m.isMe ? 'message-mine' : 'message-theirs');
    group.style.alignSelf = m.isMe ? 'flex-end' : 'flex-start';
    group.dataset.id = m.id;

    const msg = document.createElement('div');
    msg.className = 'message ' + (m.isMe ? 'message-mine' : 'message-theirs');

    let content = '';
    if (m.attachment) {
        content = `<img src="${m.attachment}" class="message-image" onclick="openImageView('${m.attachment}')" alt="مرفق">`;
    }
    if (m.text && m.text !== '[رسالة محذوفة]') {
        content += `<div>${escapeHtml(m.text).replace(/\n/g, '<br>')}</div>`;
    } else if (m.text === '[رسالة محذوفة]') {
        content = `<div style="font-style:italic; opacity:0.7;">🗑️ ${m.text}</div>`;
    }

    const readCheck = m.isMe ? `<span class="message-read-check ${m.isRead ? 'read' : ''}">${m.isRead ? '✓✓' : '✓'}</span>` : '';

    msg.innerHTML = content + `
        <div class="message-meta">
            <span>${escapeHtml(m.date)}</span>
            ${readCheck}
        </div>
        ${m.isMe ? `<div class="message-actions">
            <button class="message-action-btn" onclick="deleteMessage(${m.id})" title="حذف">🗑️</button>
        </div>` : ''}
    `;
    group.appendChild(msg);
    area.appendChild(group);
}

function scrollToBottom() {
    const area = document.getElementById('messages-area');
    area.scrollTop = area.scrollHeight;
}

function updateStatusUI(status, lastSeen) {
    const dot = document.querySelector('#other-status .online-dot');
    const text = document.getElementById('status-text');
    dot.className = 'online-dot ' + status;
    if (status === 'online') text.textContent = 'متصل الآن';
    else if (status === 'away') text.textContent = 'بعيد';
    else text.textContent = 'غير متصل' + (lastSeen ? ` · آخر ظهور ${lastSeen}` : '');
}

function startEventSource(threadId) {
    if (eventSource) eventSource.close();
    try {
        eventSource = new EventSource(`../backend/router.php?route=chat&action=sse&thread_id=${threadId}&last_id=${lastMessageId}`);

        eventSource.addEventListener('message', e => {
            try {
                const m = JSON.parse(e.data);
                addMessageToUI(m);
                lastMessageId = Math.max(lastMessageId, m.id);
                scrollToBottom();
                // Refresh threads sidebar
                loadThreads();
            } catch (err) {}
        });

        eventSource.addEventListener('typing', e => {
            try {
                const d = JSON.parse(e.data);
                toggleTypingIndicator(d.typing);
            } catch (err) {}
        });

        eventSource.addEventListener('presence', e => {
            try {
                const d = JSON.parse(e.data);
                updateStatusUI(d.status);
            } catch (err) {}
        });

        eventSource.onerror = () => {
            // إعادة الاتصال
            eventSource.close();
            setTimeout(() => { if (currentThread) startEventSource(currentThread); }, 3000);
        };
    } catch (e) {
        // Fallback to polling
        startPolling(threadId);
    }
}

let pollingInterval = null;
function startPolling(threadId) {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(async () => {
        if (currentThread !== threadId) return;
        try {
            const r = await apiRequest(`chat&action=messages&thread_id=${threadId}&last_id=${lastMessageId}`, 'GET', null, { silent: true });
            r.data.messages.forEach(m => {
                addMessageToUI(m);
                lastMessageId = Math.max(lastMessageId, m.id);
            });
            if (r.data.messages.length) scrollToBottom();
            if (r.data.other) updateStatusUI(r.data.other.status);
        } catch (e) {}
    }, 3000);
}

function toggleTypingIndicator(typing) {
    const area = document.getElementById('messages-area');
    let ti = document.getElementById('typing-indicator');
    if (typing && !ti) {
        ti = document.createElement('div');
        ti.id = 'typing-indicator';
        ti.className = 'typing-indicator';
        ti.innerHTML = '<span></span><span></span><span></span>';
        area.appendChild(ti);
        scrollToBottom();
    } else if (!typing && ti) {
        ti.remove();
    }
}

let typingDebounce = null;
function sendTyping() {
    if (!currentThread) return;
    if (typingDebounce) clearTimeout(typingDebounce);
    typingDebounce = setTimeout(() => {
        apiRequest('chat&action=typing', 'POST', { thread_id: currentThread }, { silent: true, skipCsrf: true }).catch(() => {});
    }, 300);
}

async function sendMessage() {
    const input = document.getElementById('message-input');
    const text = input.value.trim();
    if (!text && !pendingAttachment) return;
    if (!currentThread) {
        showToast('اختر محادثة أولاً', 'warning');
        return;
    }

    const btn = document.getElementById('send-btn');
    btn.disabled = true;

    try {
        const data = { thread_id: currentThread, text };
        if (pendingAttachment) {
            data.attachment = pendingAttachment;
            data.type = 'image';
        }
        await apiRequest('chat&action=send', 'POST', data);
        input.value = '';
        clearAttachment();
    } catch (e) {} finally {
        btn.disabled = false;
        input.focus();
    }
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

async function handleAttachment(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { showToast('فقط الصور مسموحة', 'warning'); return; }
    if (file.size > 5 * 1024 * 1024) { showToast('حجم الصورة كبير', 'warning'); return; }

    try {
        const dataUrl = await resizeImage(file, 1400, 0.8);
        pendingAttachment = dataUrl;
        document.getElementById('attachment-img').src = dataUrl;
        document.getElementById('attachment-name').textContent = file.name;
        document.getElementById('attachment-preview').classList.add('show');
    } catch (err) {
        showToast('فشل قراءة الصورة', 'error');
    }
    e.target.value = '';
}

function clearAttachment() {
    pendingAttachment = null;
    document.getElementById('attachment-preview').classList.remove('show');
}

async function deleteMessage(id) {
    if (!await confirmModal('سيتم حذف الرسالة. هل أنت متأكد؟', 'حذف رسالة')) return;
    try {
        await apiRequest('chat&action=delete_message', 'POST', { message_id: id });
        showToast('تم الحذف', 'success');
        // إعادة تحميل
        openThread(currentThread);
    } catch (e) {}
}

async function confirmDeleteThread() {
    if (!currentThread) return;
    if (!await confirmModal('سيتم حذف المحادثة من قائمتك. (لن تُحذف للطرف الآخر)', 'حذف محادثة')) return;
    try {
        await apiRequest('chat&action=delete_thread', 'POST', { thread_id: currentThread });
        showToast('تم الحذف', 'success');
        currentThread = null;
        if (eventSource) eventSource.close();
        document.getElementById('chat-active').style.display = 'none';
        document.getElementById('chat-empty').style.display = 'flex';
        loadThreads();
    } catch (e) {}
}

function openImageView(src) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:10000; display:flex; align-items:center; justify-content:center; cursor:zoom-out;';
    overlay.innerHTML = `<img src="${src}" style="max-width:95vw; max-height:95vh; object-fit:contain;"><button style="position:absolute; top:20px; left:20px; background:rgba(255,255,255,0.1); color:white; border:1px solid rgba(255,255,255,0.3); width:44px; height:44px; border-radius:50%; cursor:pointer; font-size:1.2rem;">×</button>`;
    overlay.onclick = () => overlay.remove();
    document.body.appendChild(overlay);
}

function backToList() {
    document.getElementById('chat-sidebar').classList.remove('hidden-mobile');
    document.getElementById('chat-main').classList.remove('show-mobile');
    if (eventSource) eventSource.close();
}

// Search threads
document.getElementById('thread-search').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    const filtered = threadsData.filter(t =>
        t.otherName.toLowerCase().includes(q) ||
        t.lastMessage.toLowerCase().includes(q) ||
        t.adTitle.toLowerCase().includes(q)
    );
    renderThreads(filtered);
});

// Close SSE on unload
window.addEventListener('beforeunload', () => { if (eventSource) eventSource.close(); });

document.addEventListener('DOMContentLoaded', loadThreads);
</script>
</body>
</html>
