/* ============================================================================
   حراج اليمن الفاخر — تحسينات JavaScript v4.1
   تحسينات الأداء والوظائف والاستجابة
   ============================================================================ */

/* ===== Mobile Menu Toggle ===== */
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('open');
    }
}

function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.remove('open');
    }
}

window.toggleMobileMenu = toggleMobileMenu;
window.closeMobileMenu = closeMobileMenu;

/* ===== Mobile Filter Toggle ===== */
function toggleMobileFilters() {
    const filters = document.querySelector('.sidebar-filters');
    if (filters) {
        filters.classList.toggle('mobile-open');
    }
}

function closeMobileFilters() {
    const filters = document.querySelector('.sidebar-filters');
    if (filters) {
        filters.classList.remove('mobile-open');
    }
}

window.toggleMobileFilters = toggleMobileFilters;
window.closeMobileFilters = closeMobileFilters;

/* ===== Responsive Image Loading ===== */
function setupResponsiveImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

/* ===== Smooth Scroll ===== */
function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

window.smoothScroll = smoothScroll;

/* ===== Debounce Function ===== */
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

/* ===== Throttle Function ===== */
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

/* ===== Intersection Observer for Animations ===== */
function setupScrollAnimations() {
    const elements = document.querySelectorAll('[data-animate]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        elements.forEach(el => observer.observe(el));
    } else {
        elements.forEach(el => el.classList.add('animated'));
    }
}

/* ===== Keyboard Navigation ===== */
document.addEventListener('keydown', (e) => {
    // ESC to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.open, .dialog.open');
        modals.forEach(modal => {
            if (modal.querySelector('.close-btn')) {
                modal.querySelector('.close-btn').click();
            }
        });
        closeMobileMenu();
        closeMobileFilters();
    }
    
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-bar input');
        if (searchInput) searchInput.focus();
    }
});

/* ===== Form Validation ===== */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

window.validateForm = validateForm;

/* ===== Input Character Counter ===== */
function setupCharCounters() {
    const textareas = document.querySelectorAll('textarea[data-max-length]');
    
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxLength);
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.style.display = 'block';
        counter.style.marginTop = 'var(--sp-2)';
        counter.style.color = 'var(--muted)';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length} / ${maxLength}`;
            if (remaining < 50) {
                counter.style.color = remaining < 20 ? 'var(--danger)' : 'var(--warning)';
            } else {
                counter.style.color = 'var(--muted)';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
}

/* ===== Confirm Dialog ===== */
function confirmAction(message, onConfirm, onCancel) {
    const confirmed = confirm(message);
    if (confirmed && onConfirm) {
        onConfirm();
    } else if (!confirmed && onCancel) {
        onCancel();
    }
    return confirmed;
}

window.confirmAction = confirmAction;

/* ===== Copy to Clipboard ===== */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            toast('تم النسخ بنجاح', 'success', 2000);
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    toast('تم النسخ بنجاح', 'success', 2000);
}

window.copyToClipboard = copyToClipboard;

/* ===== Format Currency ===== */
function formatCurrency(amount, currency = 'ر.ي') {
    return new Intl.NumberFormat('ar-YE', {
        style: 'currency',
        currency: 'YER',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

window.formatCurrency = formatCurrency;

/* ===== Format Date ===== */
function formatDate(date, format = 'short') {
    const d = new Date(date);
    const options = format === 'short' 
        ? { year: 'numeric', month: 'short', day: 'numeric' }
        : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    
    return d.toLocaleDateString('ar-YE', options);
}

window.formatDate = formatDate;

/* ===== Format Time Ago ===== */
function timeAgo(date) {
    const now = new Date();
    const d = new Date(date);
    const seconds = Math.floor((now - d) / 1000);
    
    const intervals = {
        'سنة': 31536000,
        'شهر': 2592000,
        'أسبوع': 604800,
        'يوم': 86400,
        'ساعة': 3600,
        'دقيقة': 60
    };
    
    for (const [key, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value);
        if (interval >= 1) {
            return `منذ ${interval} ${key}${interval > 1 ? '' : ''}`;
        }
    }
    
    return 'الآن';
}

window.timeAgo = timeAgo;

/* ===== Pagination Helper ===== */
function setupPagination(totalPages, currentPage, onPageChange) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;
    
    let html = '';
    
    // Previous button
    if (currentPage > 1) {
        html += `<button class="btn btn-secondary" onclick="changePage(${currentPage - 1})">السابق</button>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        if (i === currentPage) {
            html += `<button class="btn btn-primary" disabled>${i}</button>`;
        } else {
            html += `<button class="btn btn-secondary" onclick="changePage(${i})">${i}</button>`;
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<button class="btn btn-secondary" onclick="changePage(${currentPage + 1})">التالي</button>`;
    }
    
    container.innerHTML = html;
}

window.setupPagination = setupPagination;

/* ===== Lazy Load Images ===== */
function lazyLoadImages() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('loading' in HTMLImageElement.prototype) {
        // Native lazy loading support
        return;
    }
    
    setupResponsiveImages();
}

/* ===== Performance Monitoring ===== */
function monitorPerformance() {
    if ('PerformanceObserver' in window) {
        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    console.log(`${entry.name}: ${entry.duration.toFixed(2)}ms`);
                }
            });
            
            observer.observe({ entryTypes: ['measure', 'navigation', 'resource'] });
        } catch (e) {
            console.log('Performance monitoring not available');
        }
    }
}

/* ===== Initialize All Improvements ===== */
document.addEventListener('DOMContentLoaded', () => {
    setupResponsiveImages();
    setupScrollAnimations();
    setupCharCounters();
    lazyLoadImages();
    
    // Monitor performance in development
    if (document.body.dataset.debug === 'true') {
        monitorPerformance();
    }
});

/* ===== Handle Visibility Change ===== */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page is hidden
        console.log('Page hidden');
    } else {
        // Page is visible
        console.log('Page visible');
    }
});

/* ===== Handle Online/Offline ===== */
window.addEventListener('online', () => {
    toast('الاتصال بالإنترنت استعاد', 'success');
});

window.addEventListener('offline', () => {
    toast('فقدت الاتصال بالإنترنت', 'error');
});

/* ===== Prevent Double Submit ===== */
function preventDoubleSubmit(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', (e) => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = 'جاري الإرسال...';
            
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText;
            }, 3000);
        }
    });
}

window.preventDoubleSubmit = preventDoubleSubmit;

/* ===== Auto-save Form Data ===== */
function setupAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Load saved data
    const saved = localStorage.getItem(storageKey);
    if (saved) {
        const data = JSON.parse(saved);
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    }
    
    // Save on input
    form.addEventListener('input', debounce(() => {
        const data = new FormData(form);
        const obj = {};
        data.forEach((value, key) => {
            obj[key] = value;
        });
        localStorage.setItem(storageKey, JSON.stringify(obj));
    }, 1000));
    
    // Clear on submit
    form.addEventListener('submit', () => {
        localStorage.removeItem(storageKey);
    });
}

window.setupAutoSave = setupAutoSave;

/* ===== Export to CSV ===== */
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', filename);
    link.click();
}

window.exportToCSV = exportToCSV;

/* ===== Print Page ===== */
function printPage(elementId = null) {
    if (elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const printWindow = window.open('', '', 'height=400,width=800');
        printWindow.document.write('<html><head><title>طباعة</title></head><body>');
        printWindow.document.write(element.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    } else {
        window.print();
    }
}

window.printPage = printPage;
