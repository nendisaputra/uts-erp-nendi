// assets/js/main.js - Interaktivitas Aplikasi Simulasi Praktikum ERP TRPL

document.addEventListener('DOMContentLoaded', () => {
    // 1. Tab Pills Switching dengan Smooth Animation & Memory State
    const tabButtons = document.querySelectorAll('.tab-pill-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-tab');
            const parentCard = btn.closest('.card') || document;
            
            // Deactivate all buttons in group
            parentCard.querySelectorAll('.tab-pill-btn').forEach(b => b.classList.remove('active'));
            // Deactivate all contents
            parentCard.querySelectorAll('.tab-content-panel').forEach(c => c.style.display = 'none');
            
            // Activate current
            btn.classList.add('active');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.style.display = 'block';
                targetPanel.style.animation = 'none';
                void targetPanel.offsetWidth;
                targetPanel.style.animation = '';
            }

            // Save active tab state
            try {
                sessionStorage.setItem('active_tab_' + window.location.pathname, targetId);
            } catch(e) {}
        });
    });

    // Restore active tab dari URL hash atau sessionStorage
    const currentPath = window.location.pathname;
    let savedTab = null;
    if (window.location.hash) {
        savedTab = window.location.hash.replace('#', '');
    } else {
        try {
            savedTab = sessionStorage.getItem('active_tab_' + currentPath);
        } catch(e) {}
    }
    if (savedTab) {
        const matchingBtn = document.querySelector(`.tab-pill-btn[data-tab="${savedTab}"]`);
        if (matchingBtn) {
            matchingBtn.click();
        }
    }

    // 2. Check for URL flash message / notification
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msg = urlParams.get('msg');
        const type = urlParams.get('type') || 'info';
        showToast(msg, type);
    }

    // 3. Button Click Tactile Feedback
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.97)';
            setTimeout(() => {
                this.style.transform = '';
            }, 120);
        });
    });
});

// Toast notification helper
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    
    let borderStyle = '1px solid rgba(255, 255, 255, 0.15)';
    let dotColor = '#6366f1';
    let iconSymbol = 'ℹ️';

    if (type === 'success') {
        dotColor = '#10b981';
        borderStyle = '1.5px solid rgba(16, 185, 129, 0.4)';
        iconSymbol = '✅';
    } else if (type === 'danger') {
        dotColor = '#ef4444';
        borderStyle = '1.5px solid rgba(239, 68, 68, 0.4)';
        iconSymbol = '⚠️';
    } else if (type === 'warning') {
        dotColor = '#f59e0b';
        borderStyle = '1.5px solid rgba(245, 158, 11, 0.4)';
        iconSymbol = '🔔';
    }

    toast.style.border = borderStyle;
    toast.innerHTML = `<span style="font-size: 1.1rem;">${iconSymbol}</span><div class="toast-msg" style="font-size: 0.84rem; font-weight: 600; color: #ffffff; line-height: 1.4;"></div>`;
    toast.querySelector('.toast-msg').textContent = message;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(12px)';
        setTimeout(() => toast.remove(), 400);
    }, 4500);
}

// Reset Database Confirmation
function confirmResetDatabase() {
    if (confirm('PERINGATAN: Apakah Anda yakin ingin mereset seluruh database ke kondisi awal Soal UTS ERP?\n\nSemua perubahan simulasi (jurnal duplikat, penerimaan PO, dll) akan dikembalikan ke kondisi default.')) {
        window.location.href = 'api/reset_db.php?redirect=' + encodeURIComponent(window.location.pathname);
    }
}
