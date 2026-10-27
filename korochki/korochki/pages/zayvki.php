<?php
// pages/zayvki.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) {
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$polzovateli_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- ОБРАБОТКА ОТПРАВКИ ОТЗЫВА ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $zayavka_id = $_POST['zayavka_id'] ?? null;
    $koment = trim($_POST['koment'] ?? '');
    $zvezda = (int)($_POST['zvezda'] ?? 0);

    if ($zayavka_id && !empty($koment) && $zvezda >= 1 && $zvezda <= 5) {
        try {
            $checkStmt = $pdo->prepare("SELECT otziv_id FROM zayavki WHERE id_zayavki = ? AND polzovateli_id = ?");
            $checkStmt->execute([$zayavka_id, $polzovateli_id]);
            $existing = $checkStmt->fetch();

            if ($existing && $existing['otziv_id']) {
                $error = "Вы уже оставили отзыв на эту заявку.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO orziv (koment, zvezda) VALUES (?, ?)");
                $stmt->execute([$koment, $zvezda]);
                $new_otziv_id = $pdo->lastInsertId();

                $updateStmt = $pdo->prepare("UPDATE zayavki SET otziv_id = ? WHERE id_zayavki = ? AND polzovateli_id = ?");
                $updateStmt->execute([$new_otziv_id, $zayavka_id, $polzovateli_id]);

                $success = "Спасибо! Ваш отзыв успешно добавлен.";
            }
        } catch (PDOException $e) {
            $error = "Ошибка базы данных: " . $e->getMessage();
        }
    } else {
        $error = "Пожалуйста, напишите текст отзыва и выберите оценку.";
    }
}

$pageTitle = 'Мои заявки';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Просмотр заявок', 'url' => '']
];

// Пагинация
$limit = 6; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM zayavki WHERE polzovateli_id = :id");
$countStmt->execute(['id' => $polzovateli_id]);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Получаем заявки с отзывами
$stmt = $pdo->prepare("
    SELECT z.id_zayavki, z.data_nachalo, k.kurs, o.oplata, s.status, s.id_statusi, 
           ot.koment AS otziv_text, ot.zvezda 
    FROM zayavki z
    LEFT JOIN kursi k ON z.kursi_id = k.id_kursi
    LEFT JOIN oplati o ON z.oplati_id = o.id_oplati
    LEFT JOIN statusi s ON z.statusi_id = s.id_statusi
    LEFT JOIN orziv ot ON z.otziv_id = ot.id_otziv
    WHERE z.polzovateli_id = :polzovateli_id
    ORDER BY z.id_zayavki DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute(['polzovateli_id' => $polzovateli_id]);
$zayavki = $stmt->fetchAll();

ob_start();
?>

<section class="zayvki-section">
    <div class="zayvki-top-actions">
        <p>Нет вашей заявки? <a href="new_zayvka.php">Создать</a></p>
    </div>

    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (empty($zayavki)): ?>
        <p class="empty-message">У вас пока нет активных заявок.</p>
    <?php else: ?>
        
        <div class="cards-grid">
            <?php foreach ($zayavki as $z): ?>
                <?php 
                    // Определяем класс для статуса
                    $status_class = 'status-default';
                    if ($z['status'] === 'Новая') $status_class = 'status-new';
                    elseif ($z['status'] === 'Идет обучение') $status_class = 'status-progress';
                    elseif ($z['status'] === 'Обучение завершено') $status_class = 'status-completed';
                ?>
                <div class="zayvka-card">
                    <!-- Заголовок -->
                    <div class="zayvka-header">
                        <div class="zayvka-id">Заявка №<?= htmlspecialchars($z['id_zayavki']) ?></div>
                        <div class="zayvka-status <?= $status_class ?>">
                            <?= htmlspecialchars($z['status']) ?>
                        </div>
                    </div>
                    
                    <!-- Тело -->
                    <div class="zayvka-body">
                        <div class="zayvka-info-group">
                            <span class="info-label">Наименование курса</span>
                            <span class="info-value"><?= htmlspecialchars($z['kurs']) ?></span>
                        </div>
                        
                        <div class="zayvka-info-group">
                            <span class="info-label">Дата начала обучения</span>
                            <span class="info-value"><?= htmlspecialchars($z['data_nachalo']) ?></span>
                        </div>
                        
                        <div class="zayvka-info-group">
                            <span class="info-label">Способ оплаты</span>
                            <span class="info-value"><?= htmlspecialchars($z['oplata']) ?></span>
                        </div>

                        <!-- Блок отзыва -->
                        <div class="review-section">
                            <?php if (!empty($z['otziv_text'])): ?>
                                <div class="existing-review">
                                    <span class="info-label">Ваш отзыв:</span>
                                    <p class="review-text"><?= htmlspecialchars($z['otziv_text']) ?></p>
                                    <div class="stars-display">
                                        <?= str_repeat('★', $z['zvezda']) ?><?= str_repeat('☆', 5 - $z['zvezda']) ?>
                                    </div>
                                </div>
                            <?php elseif ($z['status'] === 'Обучение завершено'): ?>
                                <form method="POST" action="" class="review-form">
                                    <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                                    
                                    <div class="stars-input" id="stars-<?= $z['id_zayavki'] ?>">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                    <input type="hidden" name="zvezda" id="zvezda-input-<?= $z['id_zayavki'] ?>" value="0" required>
                                    
                                    <textarea name="koment" placeholder="Напишите ваш отзыв..." required rows="3"></textarea>
                                    
                                    <button type="submit" name="submit_review" class="btn btn-action btn-block" style="margin-top: 10px;">Оставить отзыв</button>
                                </form>
                            <?php else: ?>
                                <p class="review-hint">Отзыв можно будет оставить после завершения обучения.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" class="page-link">&lt;</a><?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>" class="page-link">&gt;</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- СКРИПТ ДЛЯ ЗВЕЗД -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const starContainers = document.querySelectorAll('.stars-input');
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const inputId = container.id.replace('stars-', 'zvezda-input-');
        const hiddenInput = document.getElementById(inputId);

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                hiddenInput.value = value;
                
                stars.forEach(s => {
                    if (parseInt(s.getAttribute('data-value')) <= parseInt(value)) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const value = this.getAttribute('data-value');
                stars.forEach(s => {
                    if (parseInt(s.getAttribute('data-value')) <= parseInt(value)) {
                        s.style.color = '#fbbf24';
                    } else {
                        s.style.color = '#d1d5db';
                    }
                });
            });
        });

        container.addEventListener('mouseleave', function() {
            const currentValue = hiddenInput.value;
            stars.forEach(s => {
                if (parseInt(s.getAttribute('data-value')) <= parseInt(currentValue)) {
                    s.style.color = '#fbbf24';
                } else {
                    s.style.color = '#d1d5db';
                }
            });
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>