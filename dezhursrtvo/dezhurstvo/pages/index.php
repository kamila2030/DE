<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/index.php
session_start();

$pageTitle = 'Главная';
$breadcrumbs = [['title' => 'Главная', 'url' => '']];

ob_start();
$base = '/DE/dezhursrtvo/dezhurstvo';
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

    <div class="description-box">
        <p>Портал «Дежурство» представляет собой информационную систему для ведения графика дежурств студентов по уборке учебных помещений техникума, фиксации выполнения работ и подачи заявок на замену дежурного.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';