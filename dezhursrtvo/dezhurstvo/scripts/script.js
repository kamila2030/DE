// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/scripts/script.js

// ===== 1. БУРГЕР-МЕНЮ =====
function toggleMenu() {
    const nav = document.getElementById('mainNav');
    if (nav) nav.classList.toggle('active');
}

// ===== 2. СЛАЙДЕР (АВТО 5 СЕК) =====
let slideIndex = 0;
let slideTimer = null;

function showSlide(n) {
    const slides = document.querySelectorAll('.slide');
    const dots   = document.querySelectorAll('.dot');
    if (!slides.length) return;
    
    slideIndex = (n + slides.length) % slides.length;
    slides.forEach((s, i) => s.classList.toggle('active', i === slideIndex));
    dots.forEach((d, i) => d.classList.toggle('active', i === slideIndex));
}

function nextSlide() { showSlide(slideIndex + 1); restartTimer(); }
function prevSlide() { showSlide(slideIndex - 1); restartTimer(); }
function currentSlide(n) { showSlide(n); restartTimer(); }

function restartTimer() {
    clearInterval(slideTimer);
    slideTimer = setInterval(() => showSlide(slideIndex + 1), 5000);
}

// ===== 3. ОБЩИЕ СКРИПТЫ =====
document.addEventListener('DOMContentLoaded', function () {
    
    // Запуск слайдера
    if (document.querySelector('.slide')) {
        showSlide(0);
        restartTimer();
    }

    // Изменение названия кнопки при выборе файла
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.files && this.files.length > 0 && label) {
                let fileName = this.files[0].name;
                if (fileName.length > 20) fileName = fileName.substring(0, 17) + '...';
                
                label.innerHTML = '<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="margin-right:4px;"><path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg> ' + fileName;
                label.style.background = '#dcfce7';
                label.style.borderColor = '#86efac';
                label.style.color = '#166534';
            }
        });
    });

    // Скрытие всплывающих сообщений (toast)
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    });

    // Клик по звездочкам (рейтинг)
    document.querySelectorAll('.stars-input').forEach(function(container) {
        const stars = container.querySelectorAll('.star');
        const hidden = document.getElementById(container.id.replace('stars-', 'zvezda-input-'));
        
        stars.forEach(function(s) {
            s.addEventListener('click', function() {
                const v = parseInt(this.dataset.value);
                if(hidden) hidden.value = v;
                stars.forEach(function(x) {
                    x.classList.toggle('active', parseInt(x.dataset.value) <= v);
                    x.style.color = parseInt(x.dataset.value) <= v ? '#fbbf24' : '#d1d5db';
                });
            });
            s.addEventListener('mouseover', function() {
                const v = parseInt(this.dataset.value);
                stars.forEach(function(x) {
                    x.style.color = parseInt(x.dataset.value) <= v ? '#fbbf24' : '#d1d5db';
                });
            });
        });

        container.addEventListener('mouseleave', function() {
            const currentVal = hidden ? (parseInt(hidden.value) || 0) : 0;
            stars.forEach(function(x) {
                x.style.color = parseInt(x.dataset.value) <= currentVal ? '#fbbf24' : '#d1d5db';
            });
        });
    });
});