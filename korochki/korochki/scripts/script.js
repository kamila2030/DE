// scripts/script.js
document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. МАСКА ТЕЛЕФОНА (формат: 8(XXX)XXX-XX-XX) ---
    const phoneInput = document.getElementById('telefon');
    
    if (phoneInput) {
        phoneInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '8(';
            }
        });

        phoneInput.addEventListener('input', function (e) {
            let digits = this.value.replace(/\D/g, '');

            if (digits.length > 0 && digits[0] !== '8') {
                digits = '8' + digits;
            }

            digits = digits.substring(0, 11);

            let formatted = '';
            if (digits.length > 0) formatted += digits[0]; // 8
            if (digits.length > 1) formatted += '(' + digits.substring(1, 4); // (XXX
            if (digits.length >= 4) formatted += ')'; // )
            if (digits.length > 4) formatted += digits.substring(4, 7); // XXX
            if (digits.length > 7) formatted += '-' + digits.substring(7, 9); // -XX
            if (digits.length > 9) formatted += '-' + digits.substring(9, 11); // -XX

            this.value = formatted;
        });
        
        phoneInput.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
                return;
            }
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    // --- 2. СЛАЙДЕР ---
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length > 0) {
        let slideIndex = 0;

        window.showSlides = function() {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            if (slides[slideIndex]) {
                slides[slideIndex].classList.add('active');
                if (dots[slideIndex]) dots[slideIndex].classList.add('active');
            }
        }

        window.nextSlide = function() {
            slideIndex = (slideIndex + 1) % slides.length;
            showSlides();
        }

        window.prevSlide = function() {
            slideIndex = (slideIndex - 1 + slides.length) % slides.length;
            showSlides();
        }

        window.currentSlide = function(n) {
            slideIndex = n;
            showSlides();
        }

        showSlides();
        setInterval(window.nextSlide, 5000);
    }
});