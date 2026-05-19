<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php?return=messages.php'); exit; }
define('PAGE_TITLE', 'الرسائل | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<style>
.chat-wrapper{display:grid;grid-template-columns:320px 1fr;height:calc(100vh - var(--header-h) - 100px);min-height:540px;background:var(--surface);border:1px solid var(--line);border-radius:var(--r-xl);overflow:hidden;box-shadow:var(--sh-sm);}
.chat-sidebar{border-inline-end:1px solid var(--line-soft);display:flex;flex-direction:column;background:var(--surface-2);}
.chat-search{padding:14px 14px 10px;}
.chat-search input{width:100%;height:38px;border-radius:var(--r-full);border:1px solid var(--line);background:var(--surface);padding:0 14px;font-size:13px;outline:none;}
.chat-threads{flex:1;overflow-y:auto;}
.chat-thread{display:flex;gap:10px;padding:12px 14px;cursor:pointer;border-inline-start:3px solid transparent;transition:background .15s;}
.chat-thread:hover{background:var(--bg-soft);}
.chat-thread.active{background:var(--bg-soft);border-inline-start-color:var(--brand-500);}
.chat-thread .avatar-circle{width:44px;height:44px;font-size:16px;}
.chat-thread-info{flex:1;min-width:0;}
.chat-thread-name{font-weight:700;font-size:14px;display:flex;justify-content:space-between;align-items:center;}
.chat-thread-name small{font-size:11px;font-weight:500;color:var(--muted);}
.chat-thread-last{font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;}
.unread-dot{width:18px;height:18px;border-radius:50%;background:var(--brand-500);color:#fff;font-size:11px;font-weight:700;display:grid;place-items:center;}
.chat-main{display:flex;flex-direction:column;}
.chat-header{padding:14px 18px;border-bottom:1px solid var(--line-soft);display:flex;align-items:center;gap:12px;}
.chat-header .avatar-circle{width:40px;height:40px;font-size:14px;}
.chat-body{flex:1;overflow-y:auto;padding:18px;background:var(--bg-soft);}
.chat-message{display:flex;gap:8px;margin-bottom:12px;}
.chat-message.mine{justify-content:flex-end;}
.chat-bubble{max-width:70%;padding:10px 14px;border-radius:var(--r-lg);font-size:14px;line-height:1.5;word-break:break-word;}
.chat-message:not(.mine) .chat-bubble{background:var(--surface);border:1px solid var(--line-soft);}
.chat-message.mine .chat-bubble{background:linear-gradient(135deg,var(--brand-500),var(--brand-700));color:#fff;}
.chat-meta{font-size:11px;opacity:.7;margin-top:4px;}
.chat-input-area{padding:14px 18px;border-top:1px solid var(--line-soft);display:flex;gap:8px;align-items:center;}
.chat-input{flex:1;height:42px;border-radius:var(--r-full);border:1px solid var(--line);padding:0 16px;font-size:14px;outline:none;background:var(--bg-soft);}
.chat-input:focus{border-color:var(--brand-400);background:var(--surface);}
@media(max-width:768px){.chat-wrapper{grid-template-columns:1fr;height:calc(100vh - var(--header-h) - 70px);}.chat-sidebar.hide-mobile{display:none;}.chat-main.hide-mobile{display:none;}}
</style>

<div class="chat-wrapper">
    <aside class="chat-sidebar" id="chatSidebar">
        <div class="chat-search"><input type="search" placeholder="ابحث في المحادثات..." id="threadSearch"></div>
        <div class="chat-threads" id="threadsList"><div style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">جارٍ التحميل...</div></div>
    </aside>
    <section class="chat-main hide-mobile" id="chatMain" style="display:none;">
        <div class="chat-header">
            <button class="icon-btn" onclick="closeChat()" style="display:none;" id="backBtn"><?= icon('chevron-right', ['size'=>20]) ?></button>
            <div class="avatar-circle" id="chatAvatar">م</div>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:15px;" id="chatName">—</div>
                <div style="font-size:12px;color:var(--muted);" id="chatStatus">—</div>
            </div>
        </div>
        <div class="chat-body" id="chatBody"></div>
        <div class="chat-input-area">
            <label class="icon-btn" style="cursor:pointer;" title="إرفاق صورة">
                <?= icon('image', ['size'=>20]) ?>
                <input type="file" id="chatFile" accept="image/*" style="display:none;">
            </label>
            <input type="text" class="chat-input" id="messageInput" placeholder="اكتب رسالة..." onkeypress="if(event.key==='Enter')sendMessage()">
            <button class="icon-btn" style="background:var(--brand-500);color:#fff;" onclick="sendMessage()" title="إرسال"><?= icon('send', ['size'=>18]) ?></button>
        </div>
    </section>
    <section class="chat-main" id="chatEmpty">
        <div class="empty-state" style="margin:auto;">
            <div style="font-size:60px;opacity:.3;"><?= icon('message', ['size'=>80]) ?></div>
            <h3>اختر محادثة لبدء الدردشة</h3>
            <p>اضغط على أي محادثة من القائمة</p>
        </div>
    </section>
</div>

<script>
let threads = [], currentThread = null, pollInterval = null;
const initialThreadId = parseInt(new URLSearchParams(location.search).get('thread') || 0);

async function loadThreads() {
    const res = await api('chat&action=threads');
    if (!res.success) return;
    threads = res.threads || res.data?.threads || res.data || [];
    renderThreads();
    if (initialThreadId && !currentThread) {
        const t = threads.find(x => x.id == initialThreadId);
        if (t) openThread(t);
    }
}
function renderThreads(filter = '') {
    const list = document.getElementById('threadsList');
    const filtered = threads.filter(t => !filter || (t.other_name || t.otherName || '').includes(filter) || (t.last_message || t.lastMessage || '').includes(filter));
    if (!filtered.length) { list.innerHTML = `<div style="padding:30px 14px;text-align:center;color:var(--muted);"><div style="font-size:40px;opacity:.3;">💬</div><p style="margin-top:10px;font-size:13px;">لا توجد محادثات</p></div>`; return; }
    list.innerHTML = filtered.map(t => {
        const name = t.other_name || t.otherName || 'مستخدم';
        const lastMsg = t.last_message || t.lastMessage || '';
        const lastAt = t.last_at || t.lastMessageAt || t.lastAt;
        const unread = t.unread_count || t.unreadCount || 0;
        return `<div class="chat-thread ${currentThread?.id==t.id?'active':''}" onclick='openThread(${JSON.stringify(t).replace(/"/g,"&quot;")})'>
            <div class="avatar-circle">${escapeHtml(name.substring(0,1))}</div>
            <div class="chat-thread-info">
                <div class="chat-thread-name">
                    <span>${escapeHtml(name)}</span>
                    <small>${fmtDate(lastAt)}</small>
                </div>
                <div class="chat-thread-last"><span>${escapeHtml(lastMsg.substring(0,50))}</span></div>
                ${unread > 0 ? `<span class="unread-dot" style="float:left;margin-top:-18px;">${unread}</span>` : ''}
            </div>
        </div>`;
    }).join('');
}
document.getElementById('threadSearch').oninput = (e) => renderThreads(e.target.value);

async function openThread(t) {
    currentThread = t;
    document.getElementById('chatSidebar').classList.add('hide-mobile');
    document.getElementById('chatMain').style.display = 'flex';
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('backBtn').style.display = window.innerWidth < 768 ? '' : 'none';
    const name = t.other_name || t.otherName || 'مستخدم';
    document.getElementById('chatAvatar').textContent = name.substring(0,1);
    document.getElementById('chatName').textContent = name;
    document.getElementById('chatStatus').textContent = t.ad_title || t.adTitle || '';
    renderThreads();
    await loadMessages();
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 3000);
}
function closeChat() {
    document.getElementById('chatSidebar').classList.remove('hide-mobile');
    document.getElementById('chatMain').style.display = 'none';
    if (pollInterval) clearInterval(pollInterval);
}
async function loadMessages() {
    if (!currentThread) return;
    const res = await api('chat&action=messages&thread_id=' + currentThread.id);
    if (!res.success) return;
    const body = document.getElementById('chatBody');
    const myId = window.CURRENT_USER?.id;
    const msgs = res.messages || res.data?.messages || res.data || [];
    body.innerHTML = msgs.map(m => {
        const senderId = m.sender_id || m.senderId;
        const isRead = m.is_read || m.isRead;
        const attachment = m.attachment || m.image;
        const text = m.body || m.content || m.message || '';
        return `<div class="chat-message ${senderId == myId ? 'mine' : ''}">
            <div class="chat-bubble">
                ${attachment ? `<img src="${escapeHtml(attachment)}" style="max-width:200px;border-radius:8px;margin-bottom:6px;display:block;">` : ''}
                ${escapeHtml(text)}
                <div class="chat-meta">${fmtDate(m.created_at || m.createdAt)} ${senderId == myId && isRead ? '✓✓' : (senderId == myId ? '✓' : '')}</div>
            </div>
        </div>`;
    }).join('');
    body.scrollTop = body.scrollHeight;
}
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const text = input.value.trim();
    if (!text || !currentThread) return;
    input.value = '';
    const res = await api('chat&action=send', { method: 'POST', data: { thread_id: currentThread.id, threadId: currentThread.id, message: text, body: text, content: text } });
    if (res.success) { loadMessages(); loadThreads(); }
    else toast(res.message || 'تعذّر الإرسال', 'error');
}
document.getElementById('chatFile').onchange = async (e) => {
    const file = e.target.files[0];
    if (!file || !currentThread) return;
    if (file.size > 5 * 1024 * 1024) return toast('الصورة كبيرة جداً', 'error');
    const fd = new FormData();
    fd.append('thread_id', currentThread.id);
    fd.append('threadId', currentThread.id);
    fd.append('image', file);
    const res = await api('chat&action=send_image', { method: 'POST', body: fd });
    if (res.success) loadMessages();
    else toast(res.message || 'تعذّر الإرسال', 'error');
    e.target.value = '';
};
loadThreads();
setInterval(loadThreads, 8000);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
