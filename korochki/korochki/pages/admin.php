<?php
// pages/admin.php
session_start();
require_once __DIR__ . '/../halpers/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit;
}

$pageTitle = 'Панель администратора';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Панель администратора', 'url' => '']
];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $zayavka_id = $_POST['zayavka_id'] ?? null;
    $new_status = $_POST['statusi_id'] ?? null;
    if ($zayavka_id && $new_status) {
        try {
            $stmt = $pdo->prepare("UPDATE zayavki SET statusi_id = :status WHERE id_zayavki = :id");
            $stmt->execute(['status' => $new_status, 'id' => $zayavka_id]);
            $success = "Статус заявки №{$zayavka_id} обновлен!";
        } catch (PDOException $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}

$filter_status = $_GET['filter'] ?? '';
$where = "WHERE 1=1";
$params = [];
if (!empty($filter_status)) {
    $where .= " AND s.id_statusi = :filter_status";
    $params['filter_status'] = $filter_status;
}

$limit = 3;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) FROM zayavki z LEFT JOIN statusi s ON z.statusi_id = s.id_statusi $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

$query = "
    SELECT z.id_zayavki, z.data_nachalo, p.fio AS user_fio, k.kurs, o.oplata, s.status, s.id_statusi, ot.koment AS otziv, ot.zvezda 
    FROM zayavki z
    LEFT JOIN polzovateli p ON z.polzovateli_id = p.id_polzovateli
    LEFT JOIN kursi k ON z.kursi_id = k.id_kursi
    LEFT JOIN oplati o ON z.oplati_id = o.id_oplati
    LEFT JOIN statusi s ON z.statusi_id = s.id_statusi
    LEFT JOIN orziv ot ON z.otziv_id = ot.id_otziv
    $where
    ORDER BY z.id_zayavki DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$zayavki = $stmt->fetchAll();

$statusi_list = $pdo->query("SELECT id_statusi, status FROM statusi")->fetchAll();

ob_start();
?>

<div class="admin-panel-wrapper">
    <div class="admin-title-bar">Панель администратора</div>
    
    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="admin-top-actions">
        <p>Нет вашей заявки?<br><a href="new_zayvka.php">Создать</a></p>
    </div>

    <div class="admin-cards-grid">
        <?php if (empty($zayavki)): ?>
            <p>Заявок не найдено.</p>
        <?php else: ?>
            <?php foreach ($zayavki as $z): ?>
                <?php 
                    $status_class = 'status-default';
                    if ($z['status'] === 'Новая') $status_class = 'status-new';
                    elseif ($z['status'] === 'Идет обучение') $status_class = 'status-progress';
                    elseif ($z['status'] === 'Обучение завершено') $status_class = 'status-completed';
                ?>
                <div class="admin-card-new <?= $status_class ?>">
                    <div class="card-header-new">
                        <span>≡</span> Заявка №<?= $z['id_zayavki'] ?>
                    </div>
                    <div class="card-body-new">
                        <p><strong>ФИО того кто подал заявку:</strong> <?= htmlspecialchars($z['user_fio'] ?? 'Не указано') ?></p>
                        <p><strong>желаемая дата начала обучения:</strong> <?= htmlspecialchars($z['data_nachalo']) ?></p>
                        <p><strong>способ отплаты:</strong> <?= htmlspecialchars($z['oplata']) ?></p>
                        
                        <?php if (!empty($z['otziv'])): ?>
                            <p><strong>текст отзыв:</strong> <?= htmlspecialchars($z['otziv']) ?></p>
                            <p><strong>звезды:</strong> <?= str_repeat('★', $z['zvezda']) ?></p>
                        <?php endif; ?>
                        
                        <p><strong>наименование курса:</strong> <?= htmlspecialchars($z['kurs']) ?></p>
                        
                        <form method="POST" action="?page=<?= $page ?>&filter=<?= urlencode($filter_status) ?>" class="card-status-form">
                            <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                            <input type="hidden" name="update_status" value="1">
                            
                            <div class="status-select-wrapper">
                                <select name="statusi_id" class="status-dropdown <?= $status_class ?>">
                                    <?php foreach ($statusi_list as $status): ?>
                                        <option value="<?= $status['id_statusi'] ?>" <?= $status['id_statusi'] == $z['id_statusi'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status['status']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-save-status">Сохранить</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="admin-bottom-bar">
        <div class="admin-filter">
            <form method="GET" action="admin.php" id="filterForm">
                <select name="filter" onchange="document.getElementById('filterForm').submit()">
                    <option value="">все статусы</option>
                    <?php foreach ($statusi_list as $status): ?>
                        <option value="<?= $status['id_statusi'] ?>" <?= $filter_status == $status['id_statusi'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="admin-pagination">
            <?php if ($totalPages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&filter=<?= urlencode($filter_status) ?>">&lt;</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&filter=<?= urlencode($filter_status) ?>" class="<?= $i == $page ? 'active-page' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&filter=<?= urlencode($filter_status) ?>">&gt;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>