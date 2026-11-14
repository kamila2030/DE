<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/zayavka_na_zamenu.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
if (($_SESSION['role_id'] ?? 0) == 2) { header('Location: admin.php'); exit; }

$user_id = $_SESSION['user_id'];
$success = ''; 
$error = '';

// --- 1. ПОЛУЧЕНИЕ СВОИХ ДЕЖУРСТВ (ТОЛЬКО «НАЗНАЧЕНО») ---
$stmt = $pdo->prepare('
    SELECT d.id_dezurstva, d.data
    FROM dezurstva d
    JOIN statusi s ON d.status_id = s.id_statusi
    WHERE d.polzovateli_id = ? AND s.status = "Назначено"
    ORDER BY d.data ASC');
$stmt->execute([$user_id]);
$my_dezh = $stmt->fetchAll();

// --- 2. ПОЛУЧЕНИЕ ОДНОГРУППНИКОВ ---
$my_group = $_SESSION['group_name'] ?? null;
if (!$my_group) {
    $stmt = $pdo->prepare('SELECT group_name FROM polzovateli WHERE id_polzovateli = ?');
    $stmt->execute([$user_id]);
    $my_group = $stmt->fetchColumn();
    $_SESSION['group_name'] = $my_group;
}

$stmt = $pdo->prepare('
    SELECT id_polzovateli, fio 
    FROM polzovateli
    WHERE group_name = ? AND id_polzovateli != ? AND roli_id = 1
    ORDER BY fio ASC');
$stmt->execute([$my_group, $user_id]);
$group_mates = $stmt->fetchAll();

// --- 3. ОБРАБОТКА ОТПРАВКИ ФОРМЫ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dez_id    = (int)($_POST['dezurstva_id'] ?? 0);
    $zamena_id = (int)($_POST['zamena_id'] ?? 0);
    $prichina  = trim($_POST['prichina'] ?? '');

    if (!$dez_id || !$zamena_id || $prichina === '') {
        $error = 'Пожалуйста, заполните все поля формы.';
    } elseif (mb_strlen($prichina) < 5) {
        $error = 'Причина замены слишком короткая (минимум 5 символов).';
    } else {
        // Проверка, что дежурство реально принадлежит пользователю и «Назначено»
        $stmt = $pdo->prepare('
            SELECT d.id_dezurstva FROM dezurstva d
            JOIN statusi s ON d.status_id = s.id_statusi
            WHERE d.id_dezurstva = ? AND d.polzovateli_id = ? AND s.status = "Назначено"');
        $stmt->execute([$dez_id, $user_id]);
        
        if (!$stmt->fetch()) {
            $error = 'Это дежурство недоступно для заявки (возможно, оно уже выполнено или не ваше).';
        } else {
            // Отправляем заявку со статусом 1 (Назначено)
            $pdo->prepare('INSERT INTO zayavki (dezurstva_id, prichina, zamena_id, status_id) VALUES (?, ?, ?, 1)')
                ->execute([$dez_id, $prichina, $zamena_id]);
            $success = 'Заявка на замену успешно отправлена коменданту!';
        }
    }
}

$pageTitle = 'Заявка на замену';
$breadcrumbs = [['title' => 'Главная', 'url' => 'index.php'], ['title' => 'Заявка на замену', 'url' => '']];

ob_start();
?>
<section class="auth-section" style="max-width:640px;">
    <div class="auth-card">
        <h2 style="margin-bottom: 20px;">Заявка на замену</h2>
        
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if (empty($my_dezh)): ?>
            <div style="text-align: center; padding: 20px; background: #f9fafb; border-radius: 8px;">
                <p style="color: #6b7280; margin-bottom: 10px;">У вас нет активных дежурств со статусом «Назначено».</p>
                <p style="font-size: 13px; color: #9ca3af;">Заявку можно подать только на предстоящее дежурство.</p>
            </div>
        <?php else: ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="dezurstva_id">Дата вашего дежурства</label>
                <select name="dezurstva_id" id="dezurstva_id" required class="form-select">
                    <option value="">— выберите дату —</option>
                    <?php foreach ($my_dezh as $d): ?>
                        <option value="<?= $d['id_dezurstva'] ?>" <?= (isset($_POST['dezurstva_id']) && $_POST['dezurstva_id'] == $d['id_dezurstva']) ? 'selected' : '' ?>>
                            <?= date('d.m.Y', strtotime($d['data'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="zamena_id">Кто вас заменит? (студенты группы <?= htmlspecialchars($my_group) ?>)</label>
                <select name="zamena_id" id="zamena_id" required class="form-select">
                    <option value="">— выберите одногруппника —</option>
                    <?php foreach ($group_mates as $m): ?>
                        <option value="<?= $m['id_polzovateli'] ?>" <?= (isset($_POST['zamena_id']) && $_POST['zamena_id'] == $m['id_polzovateli']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['fio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($group_mates)): ?>
                    <small style="color: #dc2626; font-size: 12px; margin-top: 5px; display: block;">В вашей группе нет других студентов для замены.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="prichina">Причина замены</label>
                <textarea name="prichina" id="prichina" rows="4" placeholder="Например: Болезнь, уважительная причина..." required><?= isset($_POST['prichina']) ? htmlspecialchars($_POST['prichina']) : '' ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-action btn-block" <?= empty($group_mates) ? 'disabled' : '' ?>>Отправить заявку</button>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';