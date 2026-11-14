<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/register.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../halpers/db.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
$fieldErrors = [];
$fio = $login = $telefon = $pochta = $group_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio        = trim($_POST['fio'] ?? '');
    $login      = trim($_POST['login'] ?? '');
    $telefon    = trim($_POST['telefon'] ?? '');
    $pochta     = trim($_POST['pochta'] ?? '');
    $group_name = trim($_POST['group_name'] ?? '');
    $parol      = $_POST['parol'] ?? '';
    $parol2     = $_POST['parol2'] ?? '';

    if ($fio === '') $fieldErrors['fio'] = 'Введите ФИО';
    elseif (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fio)) $fieldErrors['fio'] = 'Только кириллица и пробелы';

    if ($login === '') $fieldErrors['login'] = 'Введите логин';
    elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) $fieldErrors['login'] = 'Латиница и цифры, минимум 6 символов';

    if ($telefon === '') $fieldErrors['telefon'] = 'Введите телефон';
    elseif (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $telefon)) $fieldErrors['telefon'] = 'Формат: 8(999)123-45-67';

    if ($pochta === '') $fieldErrors['pochta'] = 'Введите email';
    elseif (!filter_var($pochta, FILTER_VALIDATE_EMAIL)) $fieldErrors['pochta'] = 'Некорректный email';

    if ($group_name === '') $fieldErrors['group_name'] = 'Введите группу';

    if ($parol === '') $fieldErrors['parol'] = 'Введите пароль';
    elseif (strlen($parol) < 8) $fieldErrors['parol'] = 'Минимум 8 символов';

    if ($parol2 === '') $fieldErrors['parol2'] = 'Повторите пароль';
    elseif ($parol !== $parol2) $fieldErrors['parol2'] = 'Пароли не совпадают';

    if (empty($fieldErrors)) {
        try {
            $stmt = $pdo->prepare('SELECT id_polzovateli FROM polzovateli WHERE login = ? LIMIT 1');
            $stmt->execute([$login]);
            if ($stmt->fetch()) $fieldErrors['login'] = 'Такой логин уже занят';

            $stmt = $pdo->prepare('SELECT id_polzovateli FROM polzovateli WHERE pochta = ? LIMIT 1');
            $stmt->execute([$pochta]);
            if ($stmt->fetch()) $fieldErrors['pochta'] = 'Эта почта уже используется';
        } catch (PDOException $e) {
            $error = 'Ошибка БД: ' . $e->getMessage();
        }
    }

    if (empty($fieldErrors) && !$error) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO polzovateli (login, parol, fio, telefon, pochta, roli_id, group_name)
                 VALUES (?, ?, ?, ?, ?, 1, ?)'
            );
            $stmt->execute([$login, $parol, $fio, $telefon, $pochta, $group_name]);
            $_SESSION['user_id']    = $pdo->lastInsertId();
            $_SESSION['user_name']  = $fio;
            $_SESSION['user_login'] = $login;
            $_SESSION['role_id']    = 1;
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Регистрация';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Регистрация', 'url' => ''],
];

ob_start();
?>
<section class="auth-section" style="max-width: 560px;">
    <div class="auth-card">
        <h2>Регистрация</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <details class="requirements-details">
            <summary>
                <span>Правила заполнения ❓</span>
                <span class="arrow">▼</span>
            </summary>
            <ul>
                <li><strong>Логин</strong> — латиница и цифры, не менее 6 символов</li>
                <li><strong>Пароль</strong> — минимум 8 символов</li>
                <li><strong>ФИО</strong> — только кириллица и пробелы</li>
                <li><strong>Телефон</strong> — 8(XXX)XXX-XX-XX</li>
                <li><strong>Email</strong> — формат электронной почты</li>
            </ul>
        </details>

        <!-- УБРАЛИ novalidate, теперь форма точно уйдет на сервер -->
        <form method="post" action="" class="auth-form" id="registerForm">
            
            <div class="form-group">
                <label for="fio">ФИО</label>
                <input type="text" id="fio" name="fio" value="<?= htmlspecialchars($fio) ?>" placeholder="Иванов Иван Иванович" class="<?= isset($fieldErrors['fio']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-fio"><?= $fieldErrors['fio'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>" placeholder="ivan2026" class="<?= isset($fieldErrors['login']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-login"><?= $fieldErrors['login'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="telefon">Телефон</label>
                <input type="text" id="telefon" name="telefon" value="<?= htmlspecialchars($telefon) ?>" placeholder="8(999)123-45-67" maxlength="15" inputmode="numeric" class="<?= isset($fieldErrors['telefon']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-telefon"><?= $fieldErrors['telefon'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="pochta">Email</label>
                <input type="text" id="pochta" name="pochta" value="<?= htmlspecialchars($pochta) ?>" placeholder="kamilausubalieva3@gmail.com" class="<?= isset($fieldErrors['pochta']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-pochta"><?= $fieldErrors['pochta'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="group_name">Группа</label>
                <input type="text" id="group_name" name="group_name" value="<?= htmlspecialchars($group_name) ?>" placeholder="IS-24" class="<?= isset($fieldErrors['group_name']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-group_name"><?= $fieldErrors['group_name'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="parol">Пароль</label>
                <input type="password" id="parol" name="parol" placeholder="Минимум 8 символов" class="<?= isset($fieldErrors['parol']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-parol"><?= $fieldErrors['parol'] ?? '' ?></small>
            </div>

            <div class="form-group">
                <label for="parol2">Повторите пароль</label>
                <input type="password" id="parol2" name="parol2" placeholder="Повторите пароль" class="<?= isset($fieldErrors['parol2']) ? 'input-error' : '' ?>">
                <small class="field-error" id="err-parol2"><?= $fieldErrors['parol2'] ?? '' ?></small>
            </div>

            <button type="submit" class="btn btn-action btn-block">Создать пользователя</button>
        </form>

        <p class="auth-link">Уже есть аккаунт? <a href="auth.php">Войти</a></p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // МАСКА ТЕЛЕФОНА
    const phone = document.getElementById('telefon');
    phone.addEventListener('focus', function () { if (this.value === '') this.value = '8('; });
    phone.addEventListener('input', function () {
        let d = this.value.replace(/\D/g, '');
        if (d.length > 0 && d[0] !== '8') d = '8' + d;
        d = d.substring(0, 11);
        let f = '';
        if (d.length > 0) f += d[0];
        if (d.length > 1) f += '(' + d.substring(1, 4);
        if (d.length >= 4) f += ')';
        if (d.length > 4) f += d.substring(4, 7);
        if (d.length > 7) f += '-' + d.substring(7, 9);
        if (d.length > 9) f += '-' + d.substring(9, 11);
        this.value = f;
    });
    phone.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
        if (allowed.includes(e.key)) return;
        if (!/^\d$/.test(e.key)) e.preventDefault();
    });

    // ТОЛЬКО КИРИЛЛИЦА В ФИО
    const fio = document.getElementById('fio');
    fio.addEventListener('input', function () {
        this.value = this.value.replace(/[^А-Яа-яЁё\s]/g, '');
    });

    // ТОЛЬКО ЛАТИНИЦА В ЛОГИНЕ
    const login = document.getElementById('login');
    login.addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });
});
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';