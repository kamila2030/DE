<?php
// pages/layout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Корочки есть';
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body class="<?= (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) ? 'admin-body' : 'user-body' ?>">

    <!-- ШАПКА -->
    <header class="site-header">
        <div class="header-container">
            <img src="../media/image01.webp" alt="Логотип" class="logo">
            <h1 class="site-title">"Корочки есть"</h1>
        </div>
        
        <!-- Кнопка Бургер (видна только на мобильных) -->
        <button class="burger-menu" onclick="toggleMenu()">☰ Меню</button>

        <nav class="main-nav" id="mainNav">
            <?php if (!empty($breadcrumbs)): ?>
                <ul class="breadcrumbs">
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <li>
                            <?php if (!empty($crumb['url'])): ?>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($crumb['title']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
                        <!-- МЕНЮ ДЛЯ АДМИНА -->
                        <a href="admin.php" class="btn btn-primary">Панель администратора</a>
                        <a href="logout.php" class="btn btn-secondary">Выход</a>
                    <?php else: ?>
                        <!-- МЕНЮ ДЛЯ ПОЛЬЗОВАТЕЛЯ -->
                        <a href="index.php" class="btn btn-action-outline">Главная</a>
                        <a href="zayvki.php" class="btn btn-action-outline">Мои заявки</a>
                        <a href="logout.php" class="btn btn-secondary">Выход</a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- МЕНЮ ДЛЯ ГОСТЕЙ -->
                    <a href="auth.php" class="btn btn-primary">Вход</a>
                    <a href="register.php" class="btn btn-action">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- ОСНОВНОЙ КОНТЕНТ -->
    <main class="site-content">
        <?= $content ?? '' ?>
    </main>

    <!-- ПОДВАЛ -->
    <footer class="site-footer">
        <div class="footer-container">
            <p>Портал «Корочки есть» представляет собой информационную систему для записи на онлайн курсы дополнительного профессионального образования.</p>
        </div>
    </footer>

    <script src="../scripts/script.js"></script>
    <script>
        // Скрипт для бургер-меню
        function toggleMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        }
    </script>
</body>
</html>