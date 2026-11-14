<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/layout.php
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle   = $pageTitle   ?? 'Дежурство';
$breadcrumbs = $breadcrumbs ?? [];
$base = '/DE/dezhursrtvo/dezhurstvo';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>/styles/style.css">
</head>
<body class="<?= (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2) ? 'admin-body' : 'user-body' ?>">

<header class="site-header">
    <div class="header-container">
        <!-- ИСПРАВЛЕН ПУТЬ К ЛОГОТИПУ -->
        <img src="<?= $base ?>/media/logo.jpg" alt="Логотип" class="logo">
        <h1 class="site-title">Дежурство</h1>
    </div>
    <button class="burger-menu" type="button" onclick="toggleMenu()">☰ Меню</button>

    <nav class="main-nav" id="mainNav">
        <?php if (!empty($breadcrumbs)): ?>
            <ul class="breadcrumbs">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li>
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= $base ?>/pages/<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($crumb['title']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="nav-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['role_id'] ?? 0) == 2): ?>
                    <a href="<?= $base ?>/pages/admin.php" class="btn btn-primary">Панель администратора</a>
                    <a href="<?= $base ?>/pages/logout.php" class="btn btn-secondary">Выход</a>
                <?php else: ?>
                    <a href="<?= $base ?>/pages/index.php" class="btn btn-action-outline">Главная</a>
                    <a href="<?= $base ?>/pages/dezhurstva.php" class="btn btn-action-outline">Мои дежурства</a>
                    <a href="<?= $base ?>/pages/zayavka_na_zamenu.php" class="btn btn-action-outline">Заявка на замену</a>
                    <a href="<?= $base ?>/pages/logout.php" class="btn btn-secondary">Выход</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= $base ?>/pages/index.php" class="btn btn-action-outline">Главная</a>
                <a href="<?= $base ?>/pages/auth.php" class="btn btn-primary">Вход</a>
                <a href="<?= $base ?>/pages/register.php" class="btn btn-action">Регистрация</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="site-content">
    <?= $content ?? '' ?>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>Портал «Дежурство» — информационная система для ведения графика дежурств студентов по уборке учебных помещений техникума.</p>
        <p class="footer-copy">© <?= date('Y') ?> Дежурство</p>
    </div>
</footer>

<script src="<?= $base ?>/scripts/script.js"></script>
</body>
</html>