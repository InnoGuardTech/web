/**
 * ============================================================================
 * حراج اليمن الفاخر — JavaScript عصري v5.0
 * تفاعلات حديثة وتأثيرات سلسة
 * ============================================================================
 */

// ===== Intersection Observer للتأثيرات ===== 
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// مراقبة جميع العناصر القابلة للتحريك
document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));

// ===== Ripple Effect للأزرار =====
document.querySelectorAll('.btn-modern').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
            animation: ripple-animation 0.6s ease-out;
        `;
        
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

// ===== Smooth Scroll للروابط الداخلية =====
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ===== Lazy Loading للصور =====
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.add('loaded');
                }
                imageObserver.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
}

// ===== Parallax Effect =====
window.addEventListener('scroll', () => {
    document.querySelectorAll('[data-parallax]').forEach(el => {
        const speed = el.dataset.parallax || 0.5;
        const yPos = window.scrollY * speed;
        el.style.transform = `translateY(${yPos}px)`;
    });
});

// ===== Counter Animation =====
function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toLocaleString('ar-YE');
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString('ar-YE');
        }
    }, 16);
}

// تشغيل العدادات عند الوصول إليها
if ('IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                const target = parseInt(entry.target.dataset.counter) || 0;
                animateCounter(entry.target, target);
                entry.target.dataset.animated = 'true';
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('[data-counter]').forEach(el => counterObserver.observe(el));
}

// ===== Toast Notifications =====
class Toast {
    static show(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toastContainer') || this.createContainer();
        const toast = document.createElement('div');
        
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${this.getIcon(type)}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;
        
        toast.style.cssText = `
            background: ${this.getColor(type)};
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    static createContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    static getIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }
    
    static getColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#0ea5e9'
        };
        return colors[type] || colors.info;
    }
}

// ===== Modal Dialog =====
class Modal {
    constructor(options = {}) {
        this.title = options.title || '';
        this.content = options.content || '';
        this.buttons = options.buttons || [];
        this.onClose = options.onClose || null;
    }
    
    show() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        `;
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.3s ease-out;
        `;
        
        let html = `
            <div style="padding: 24px;">
                ${this.title ? `<h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 700;">${this.title}</h2>` : ''}
                <div style="color: #64748b; margin-bottom: 24px;">${this.content}</div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
        `;
        
        this.buttons.forEach(btn => {
            html += `<button class="btn-modern btn-modern-${btn.type || 'secondary'}" onclick="${btn.onclick || ''}">${btn.text}</button>`;
        });
        
        html += '</div></div>';
        modal.innerHTML = html;
        overlay.appendChild(modal);
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.close(overlay);
        });
        
        document.body.appendChild(overlay);
        return overlay;
    }
    
    close(overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            overlay.remove();
            if (this.onClose) this.onClose();
        }, 300);
    }
}

// ===== Form Validation =====
class FormValidator {
    static validate(form) {
        let isValid = true;
        const errors = [];
        
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                errors.push(`${field.name} مطلوب`);
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });
        
        return { isValid, errors };
    }
    
    static showErrors(errors) {
        errors.forEach(error => Toast.show(error, 'error'));
    }
}

// ===== Debounce Helper =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== Throttle Helper =====
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== CSS Animations =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes ripple-animation {
        from {
            transform: scale(0);
            opacity: 1;
        }
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .animate-in {
        animation: slideUp 0.6s ease-out forwards;
    }
    
    input.error, textarea.error, select.error {
        border-color: #ef4444 !important;
        background: rgba(239, 68, 68, 0.05);
    }
`;
document.head.appendChild(style);

// تصدير الدوال العامة
window.Toast = Toast;
window.Modal = Modal;
window.FormValidator = FormValidator;
window.debounce = debounce;
window.throttle = throttle;
