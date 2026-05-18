// frontend/assets/js/app.js - Core Javascript Utilities

const API_BASE = '../backend/router.php?route=';

// Wrapper for API fetch requests
async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(API_BASE + endpoint, options);
        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'حدث خطأ غير معروف');
        }
        
        return result;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    }
}

// UI Toast Notification System
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span>${type === 'success' ? '✅' : '⚠️'}</span>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Dark Mode Toggle
function toggleTheme() {
    const html = document.documentElement;
    if (html.getAttribute('data-theme') === 'dark') {
        html.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
}

// Initialize theme and badges
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    
    // Check if user is logged in by looking for a specific element or just trying the API
    if (document.getElementById('user-menu-area')) {
        updateBadgeCounts();
        // Poll every 30 seconds
        setInterval(updateBadgeCounts, 30000);
    }
});

async function updateBadgeCounts() {
    try {
        const chatRes = await apiRequest('chat&action=unread_count');
        const notifRes = await apiRequest('notifications&action=unread_count');
        
        const chatBadge = document.getElementById('chat-badge');
        const notifBadge = document.getElementById('notif-badge');
        
        if (chatBadge) {
            chatBadge.innerText = chatRes.data.count > 0 ? chatRes.data.count : '';
            chatBadge.style.display = chatRes.data.count > 0 ? 'flex' : 'none';
        }
        
        if (notifBadge) {
            notifBadge.innerText = notifRes.data.count > 0 ? notifRes.data.count : '';
            notifBadge.style.display = notifRes.data.count > 0 ? 'flex' : 'none';
        }
    } catch(e) {}
}
