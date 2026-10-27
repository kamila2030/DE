<?php
// pages/register.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$fio     = '';
$login   = '';
$telefon = '';
$pochta  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio     = trim($_POST['fio'] ?? '');
    $login   = trim($_POST['login'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $pochta  = trim($_POST['pochta'] ?? '');
    $parol   = $_POST['parol'] ?? '';
    $parol2  = $_POST['parol2'] ?? '';

    // Валидация
    if ($fio === '' || $login === '' || $telefon === '' || $pochta === '' || $parol === '' || $parol2 === '') {
        $error = 'Заполните все поля';
    } 
    // Проверка ФИО (Только кириллица, пробелы и дефисы)
    elseif (!preg_match('/^[а-яА-ЯёЁ\s\-]+$/u', $fio)) {
        $error = 'ФИО должно содержать только русские буквы, пробелы и дефисы';
    } 
    // Проверка Логина (Только латиница и цифры, минимум 3 символа)
    elseif (!preg_match('/^[a-zA-Z0-9]{3,}$/', $login)) {
        $error = 'Логин должен состоять только из английских букв и цифр (минимум 3 символа)';
    } 
    elseif (!filter_var($pochta, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный адрес электронной почты';
    } 
    elseif (strlen($parol) < 8) {
        $error = 'Пароль должен содержать минимум 8 символов';
    } 
    elseif ($parol !== $parol2) {
        $error = 'Пароли не совпадают';
    } 
    elseif (strlen($telefon) !== 15) { 
        $error = 'Введите корректный номер телефона (формат 8(XXX)XXX-XX-XX)';
    } 
    else {
        try {
            $stmt = $pdo->prepare('SELECT id_polzovateli FROM polzovateli WHERE login = ? LIMIT 1');
            $stmt->execute([$login]);
            
            if ($stmt->fetch()) {
                $error = 'Такой логин уже занят';
            } else {
                $stmt = $pdo->prepare('SELECT id_polzovateli FROM polzovateli WHERE pochta = ? LIMIT 1');
                $stmt->execute([$pochta]);
                
                if ($stmt->fetch()) {
                    $error = 'Пользователь с такой почтой уже существует';
                } else {
                    $hash    = md5($parol); 
                    $roli_id = 1;

                    $stmt = $pdo->prepare(
                        'INSERT INTO polzovateli (login, parol, fio, telefon, pochta, roli_id)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    
                    if ($stmt->execute([$login, $hash, $fio, $telefon, $pochta, $roli_id])) {
                        $_SESSION['user_id']    = $pdo->lastInsertId();
                        $_SESSION['user_name']  = $fio;
                        $_SESSION['user_login'] = $login;
                        $_SESSION['role_id']    = $roli_id;

                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Ошибка при регистрации. Попробуйте позже.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Регистрация';
$breadcrumbs = [
    ['title' => 'Главная',     'url' => 'index.php'],
    ['title' => 'Регистрация', 'url' => '']
];

ob_start();
?>

<section class="auth-section">
    <h2>Регистрация</h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="" class="auth-form">
        <div class="form-group">
            <label for="fio">ФИО</label>
            <input type="text" id="fio" name="fio" required
                   value="<?= htmlspecialchars($fio) ?>"
                   placeholder="Иванов Иван Иванович">
        </div>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" required minlength="3"
                   value="<?= htmlspecialchars($login) ?>"
                   placeholder="Придумайте логин (латиница)">
        </div>

        <div class="form-group">
            <label for="telefon">Телефон</label>
            <input type="text" id="telefon" name="telefon" required
                   value="<?= htmlspecialchars($telefon) ?>"
                   placeholder="8(999)123-45-67"
                   pattern="8\(\d{3}\)\d{3}-\d{2}-\d{2}"
                   title="Формат: 8(999)123-45-67"
                   maxlength="15">
        </div>

        <div class="form-group">
            <label for="pochta">Email</label>
            <input type="email" id="pochta" name="pochta" required
                   value="<?= htmlspecialchars($pochta) ?>"
                   placeholder="user@example.com">
        </div>

        <div class="form-group">
            <label for="parol">Пароль</label>
            <input type="password" id="parol" name="parol" required minlength="8"
                   placeholder="Минимум 8 символов">
        </div>

        <div class="form-group">
            <label for="parol2">Повторите пароль</label>
            <input type="password" id="parol2" name="parol2" required minlength="8"
                   placeholder="Повторите пароль">
        </div>

        <button type="submit" class="btn btn-action btn-block">Зарегистрироваться</button>
    </form>

    <p class="auth-link">
        Уже есть аккаунт? <a href="auth.php">Войти</a>
    </p>
</section>

<!-- СКРИПТЫ ВАЛИДАЦИИ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. МАСКА ТЕЛЕФОНА
    const phoneInput = document.getElementById('telefon');
    if (phoneInput) {
        phoneInput.addEventListener('focus', function() {
            if (this.value === '') this.value = '8(';
        });
        phoneInput.addEventListener('input', function (e) {
            let digits = this.value.replace(/\D/g, '');
            if (digits.length > 0 && digits[0] !== '8') digits = '8' + digits;
            digits = digits.substring(0, 11);
            let formatted = '';
            if (digits.length > 0) formatted += digits[0];
            if (digits.length > 1) formatted += '(' + digits.substring(1, 4);
            if (digits.length >= 4) formatted += ')';
            if (digits.length > 4) formatted += digits.substring(4, 7);
            if (digits.length > 7) formatted += '-' + digits.substring(7, 9);
            if (digits.length > 9) formatted += '-' + digits.substring(9, 11);
            this.value = formatted;
        });
        phoneInput.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
    }

    // 2. МАСКА ДЛЯ ФИО (только кириллица, пробел и дефис)
    const fioInput = document.getElementById('fio');
    if (fioInput) {
        fioInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^а-яА-ЯёЁ\s\-]/g, '');
        });
    }

    // 3. МАСКА ДЛЯ ЛОГИНА (только латиница и цифры)
    const loginInput = document.getElementById('login');
    if (loginInput) {
        loginInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>