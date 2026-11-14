<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/dezhurstva.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../halpers/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }

$user_id = $_SESSION['user_id'];
$success = ''; 
$error = '';

// --- 1. ЗАГРУЗКА ФОТО ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    $dez_id = (int)($_POST['dezurstva_id'] ?? 0);
    $type   = $_POST['foto_type'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM dezurstva WHERE id_dezurstva = ? AND polzovateli_id = ?');
    $stmt->execute([$dez_id, $user_id]);
    $d = $stmt->fetch();

    if (!$d) {
        $error = 'Дежурство не найдено';
    } elseif (!in_array($type, ['do','posle'], true)) {
        $error = 'Неверный тип фото';
    } elseif (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка загрузки файла (возможно, он слишком большой)';
    } else {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            $error = 'Разрешены только форматы jpg, jpeg, png, webp';
        } else {
            $dir = __DIR__ . '/../uploads';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $fname = 'd' . $dez_id . '_' . $type . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $fname)) {
                $db_path = '/DE/dezhursrtvo/dezhurstvo/uploads/' . $fname;
                $col = $type === 'do' ? 'foto_do' : 'foto_posle';
                
                $pdo->prepare("UPDATE dezurstva SET $col = ? WHERE id_dezurstva = ?")
                    ->execute([$db_path, $dez_id]);
                $success = 'Фото успешно загружено!';
            } else {
                $error = 'Не удалось сохранить файл на сервере. Проверьте права на папку uploads.';
            }
        }
    }
}

// --- 2. ОСТАВИТЬ ОТЗЫВ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $dez_id = (int)($_POST['dezurstva_id'] ?? 0);
    $koment = trim($_POST['koment'] ?? '');
    $zvezda = (int)($_POST['zvezda'] ?? 0);

    $stmt = $pdo->prepare('
        SELECT d.id_dezurstva, d.otziv_id, s.status
        FROM dezurstva d 
        JOIN statusi s ON d.status_id = s.id_statusi
        WHERE d.id_dezurstva = ? AND d.polzovateli_id = ?');
    $stmt->execute([$dez_id, $user_id]);
    $d = $stmt->fetch();

    if (!$d) {
        $error = 'Дежурство не найдено';
    } elseif ($d['status'] !== 'Выполнено') {
        $error = 'Отзыв доступен только после выполнения дежурства';
    } elseif ($d['otziv_id']) {
        $error = 'Вы уже оставили отзыв';
    } elseif ($koment === '' || $zvezda < 1 || $zvezda > 5) {
        $error = 'Напишите текст отзыва и выберите оценку от 1 до 5';
    } else {
        $pdo->prepare('INSERT INTO otziv (koment, zvezda) VALUES (?, ?)')->execute([$koment, $zvezda]);
        $new_id = $pdo->lastInsertId();
        $pdo->prepare('UPDATE dezurstva SET otziv_id = ? WHERE id_dezurstva = ?')->execute([$new_id, $dez_id]);
        $success = 'Спасибо! Ваш отзыв успешно добавлен.';
    }
}

// --- 3. ПОЛУЧЕНИЕ СПИСКА ---
$stmt = $pdo->prepare('
    SELECT d.*, s.status, s.id_statusi, o.koment AS otziv_text, o.zvezda
    FROM dezurstva d
    JOIN statusi s ON d.status_id = s.id_statusi
    LEFT JOIN otziv o ON d.otziv_id = o.id_otziv
    WHERE d.polzovateli_id = ?
    ORDER BY d.data DESC');
$stmt->execute([$user_id]);
$list = $stmt->fetchAll();

$pageTitle = 'Мои дежурства';
$breadcrumbs = [['title' => 'Главная', 'url' => 'index.php'], ['title' => 'Мои дежурства', 'url' => '']];

ob_start();
?>
<section class="zayvki-section">
    <h2 style="margin-bottom: 20px; color: #1d4ed8;">Мой график дежурств</h2>
    
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (empty($list)): ?>
        <p class="empty-message" style="background:#fff; padding:20px; border-radius:12px;">У вас пока нет назначенных дежурств.</p>
    <?php else: ?>
        <div class="cards-grid">
        <?php foreach ($list as $d): ?>
            <?php
                $cls = 'status-default';
                if ($d['status'] === 'Назначено') $cls = 'status-new';
                elseif ($d['status'] === 'Выполнено') $cls = 'status-completed';
                elseif ($d['status'] === 'Не выполнено') $cls = 'status-progress';
                elseif ($d['status'] === 'Замена одобрена') $cls = 'status-progress';
            ?>
            <div class="zayvka-card">
                <div class="zayvka-header">
                    <div class="card-id">Дежурство №<?= $d['id_dezurstva'] ?></div>
                    <div class="zayvka-status <?= $cls ?>"><?= htmlspecialchars($d['status']) ?></div>
                </div>
                
                <div class="zayvka-body">
                    <div class="info-grid">
                        <div class="zayvka-info-group">
                            <span class="info-label">Дата</span>
                            <span class="info-value"><?= date('d.m.Y', strtotime($d['data'])) ?></span>
                        </div>
                        <div class="zayvka-info-group">
                            <span class="info-label">Зона уборки</span>
                            <span class="info-value"><?= htmlspecialchars($d['zona']) ?></span>
                        </div>
                    </div>
                    <div class="zayvka-info-group">
                        <span class="info-label">Обязанности</span>
                        <span class="info-value"><?= htmlspecialchars($d['obyazannosti']) ?></span>
                    </div>

                    <!-- Блок с фото -->
                    <div class="foto-container">
                        <!-- Фото ДО -->
                        <div class="foto-item">
                            <span class="foto-title">Фото «до»</span>
                            <div class="attached-file-box">
                                <?php if (!empty($d['foto_do']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $d['foto_do'])): ?>
                                    <a href="<?= htmlspecialchars($d['foto_do']) ?>" target="_blank" class="attached-file-link" title="Открыть фото">
                                        <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l-4 4m0 0l-4-4m4 4V4M4 20h16"/></svg>
                                        <span class="file-name"><?= basename($d['foto_do']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <div class="file-empty">
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M4 0h5.5v1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h1V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2z"/><path d="M9.5 3V0L14 4.5h-3.5A1.5 1.5 0 0 1 9.5 3z"/></svg>
                                        Файл не прикреплен
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" enctype="multipart/form-data" class="foto-form">
                                <input type="hidden" name="upload_foto" value="1">
                                <input type="hidden" name="dezurstva_id" value="<?= $d['id_dezurstva'] ?>">
                                <input type="hidden" name="foto_type" value="do">
                                <div class="file-input-wrapper">
                                    <input type="file" name="foto" id="file-do-<?= $d['id_dezurstva'] ?>" accept="image/*" required>
                                    <label for="file-do-<?= $d['id_dezurstva'] ?>" class="custom-file-upload">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                                        Выбрать файл
                                    </label>
                                </div>
                                <button class="btn btn-action btn-sm" type="submit">Загрузить</button>
                            </form>
                        </div>

                        <!-- Фото ПОСЛЕ -->
                        <div class="foto-item">
                            <span class="foto-title">Фото «после»</span>
                            <div class="attached-file-box">
                                <?php if (!empty($d['foto_posle']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $d['foto_posle'])): ?>
                                    <a href="<?= htmlspecialchars($d['foto_posle']) ?>" target="_blank" class="attached-file-link" title="Открыть фото">
                                        <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l-4 4m0 0l-4-4m4 4V4M4 20h16"/></svg>
                                        <span class="file-name"><?= basename($d['foto_posle']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <div class="file-empty">
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M4 0h5.5v1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h1V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2z"/><path d="M9.5 3V0L14 4.5h-3.5A1.5 1.5 0 0 1 9.5 3z"/></svg>
                                        Файл не прикреплен
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" enctype="multipart/form-data" class="foto-form">
                                <input type="hidden" name="upload_foto" value="1">
                                <input type="hidden" name="dezurstva_id" value="<?= $d['id_dezurstva'] ?>">
                                <input type="hidden" name="foto_type" value="posle">
                                <div class="file-input-wrapper">
                                    <input type="file" name="foto" id="file-posle-<?= $d['id_dezurstva'] ?>" accept="image/*" required>
                                    <label for="file-posle-<?= $d['id_dezurstva'] ?>" class="custom-file-upload">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                                        Выбрать файл
                                    </label>
                                </div>
                                <button class="btn btn-action btn-sm" type="submit">Загрузить</button>
                            </form>
                        </div>
                    </div>

                    <div class="review-section">
                        <?php if (!empty($d['otziv_text'])): ?>
                            <div class="existing-review">
                                <span class="info-label">Ваш отзыв:</span>
                                <p class="review-text"><?= htmlspecialchars($d['otziv_text']) ?></p>
                                <div class="stars-display"><?= str_repeat('★', (int)$d['zvezda']) ?><?= str_repeat('☆', 5 - (int)$d['zvezda']) ?></div>
                            </div>
                        <?php elseif ($d['status'] === 'Выполнено'): ?>
                            <form method="POST" class="review-form">
                                <input type="hidden" name="dezurstva_id" value="<?= $d['id_dezurstva'] ?>">
                                <span class="info-label">Оцените качество уборки:</span>
                                <div class="stars-input" id="stars-<?= $d['id_dezurstva'] ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?><span class="star" data-value="<?= $i ?>">★</span><?php endfor; ?>
                                </div>
                                <input type="hidden" name="zvezda" id="zvezda-input-<?= $d['id_dezurstva'] ?>" value="0">
                                <textarea name="koment" placeholder="Напишите пару слов о качестве уборки..." rows="2" required></textarea>
                                <button type="submit" name="submit_review" class="btn btn-action btn-block">Оставить отзыв</button>
                            </form>
                        <?php else: ?>
                            <div class="review-hint">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
                                Отзыв можно будет оставить после подтверждения администратором.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';