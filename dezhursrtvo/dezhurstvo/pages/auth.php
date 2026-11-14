<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/auth.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['role_id'] ?? 0) == 2 ? 'admin.php' : 'index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $parol = trim($_POST['parol'] ?? '');

    if ($login === '' || $parol === '') {
        $error = 'Заполните пустые поля';
    } else {
        $stmt = $pdo->prepare(
            'SELECT p.id_polzovateli, p.login, p.parol, p.fio, p.roli_id, r.rol
             FROM polzovateli p JOIN roli r ON r.id_roli = p.roli_id
             WHERE p.login = ? LIMIT 1'
        );
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Пользователь с такими данными не найден';
        } elseif ($parol !== $user['parol']) {
            $error = 'Неверный пароль';
        } else {
            $_SESSION['user_id']    = $user['id_polzovateli'];
            $_SESSION['user_name']  = $user['fio'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['role_id']    = $user['roli_id'];
            $_SESSION['role']       = $user['rol'];

            header('Location: ' . ($user['roli_id'] == 2 ? 'admin.php' : 'index.php'));
            exit;
        }
    }
}

$pageTitle = 'Вход';
$breadcrumbs = [['title' => 'Главная', 'url' => 'index.php'], ['title' => 'Вход', 'url' => '']];

ob_start();
?>
<section class="auth-section">
    <div class="auth-card">
        <h2>Вход</h2>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" action="" class="auth-form">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="parol">Пароль</label>
                <input type="password" id="parol" name="parol" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>
        <p class="auth-link">Еще не зарегистрированы? <a href="register.php">Регистрация</a></p>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';