<?php
// pages/new_zayvka.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$pageTitle = 'Новая заявка';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Новая заявка', 'url' => '']
];

$error = '';
$success = '';

// Получаем списки для выпадающих списков
$kursi = $pdo->query("SELECT id_kursi, kurs FROM kursi")->fetchAll();
$oplati = $pdo->query("SELECT id_oplati, oplata FROM oplati")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kursi_id = $_POST['kursi_id'] ?? null;
    $data_nachalo = trim($_POST['data_nachalo'] ?? '');
    $oplati_id = $_POST['oplati_id'] ?? null;
    
    // Жестко задаем статус "Новая" (id_statusi = 1) и ID пользователя
    $statusi_id = 1; 
    $polzovateli_id = $_SESSION['user_id'];

    if ($kursi_id && $data_nachalo && $oplati_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO zayavki (polzovateli_id, kursi_id, data_nachalo, oplati_id, statusi_id) VALUES (:polzovateli_id, :kursi_id, :data_nachalo, :oplati_id, :statusi_id)");
            $stmt->execute([
                'polzovateli_id' => $polzovateli_id,
                'kursi_id' => $kursi_id,
                'data_nachalo' => $data_nachalo,
                'oplati_id' => $oplati_id,
                'statusi_id' => $statusi_id
            ]);
            $success = "Заявка успешно создана!";
        } catch (PDOException $e) {
            $error = "Ошибка базы данных: " . $e->getMessage();
        }
    } else {
        $error = "Пожалуйста, заполните все обязательные поля.";
    }
}

ob_start();
?>

<section class="auth-section" style="max-width: 600px;">
    <h2>Подача заявки</h2>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message">
            <?= $success ?> <br><br>
            <a href="zayvki.php" class="btn btn-primary" style="text-decoration: none; padding: 8px 16px;">Посмотреть мои заявки</a>
        </div>
    <?php else: ?>
        <form method="POST" action="new_zayvka.php" class="auth-form">
            <div class="form-group">
                <label for="kursi_id">Наименование курса:</label>
                <select name="kursi_id" id="kursi_id" required class="form-select">
                    <option value="">-- Выберите курс --</option>
                    <?php foreach ($kursi as $kurs): ?>
                        <option value="<?= $kurs['id_kursi'] ?>"><?= htmlspecialchars($kurs['kurs']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="oplati_id">Способ оплаты:</label>
                <select name="oplati_id" id="oplati_id" required class="form-select">
                    <option value="">-- Выберите способ --</option>
                    <?php foreach ($oplati as $oplata): ?>
                        <option value="<?= $oplata['id_oplati'] ?>"><?= htmlspecialchars($oplata['oplata']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_nachalo">Желаемая дата начала обучения:</label>
                <input type="date" id="data_nachalo" name="data_nachalo" required>
            </div>
            
            <button type="submit" class="btn btn-action btn-block">Подать заявку</button>
        </form>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>