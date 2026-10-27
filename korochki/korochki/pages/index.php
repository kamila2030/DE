<?php
// pages/index.php
session_start();

$pageTitle = 'Главная';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '']
];

ob_start();
?>

<div class="index-wrapper">
    <!-- Слайдер -->
    <div class="slider-container">
        <button type="button" class="slider-arrow" onclick="prevSlide()">&larr;</button>
        
        <div class="slider-slides">
            <img src="../media/image11.jpg" alt="Слайд 1" class="slide active">
            <img src="../media/image05.jpg" alt="Слайд 2" class="slide">
            <img src="../media/image06.jpg" alt="Слайд 3" class="slide">
            <img src="../media/image07.jpg" alt="Слайд 4" class="slide">
        </div>

        <button type="button" class="slider-arrow" onclick="nextSlide()">&rarr;</button>
        
        <div class="slider-dots">
            <span class="dot active" onclick="currentSlide(0)"></span>
            <span class="dot" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
        </div>
    </div>

    <!-- Блок с текстом (кнопки убраны) -->
    <div class="description-box">
        <p>Портал «Корочки есть» представляет собой информационную систему для записи на онлайн курсы дополнительного профессионального образования.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>