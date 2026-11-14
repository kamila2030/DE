<?php
// /opt/lampp/htdocs/DE/dezhursrtvo/dezhurstvo/pages/admin.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../halpers/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? 0) != 2) {
    header('Location: index.php'); 
    exit;
}

$success = ''; 
$error = '';

// --- 0. СОЗДАНИЕ НОВОГО ДЕЖУРСТВА АДМИНИСТРАТОРОМ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_dezhurstvo'])) {
    $student_id = (int)$_POST['student_id'];
    $zona = trim($_POST['zona']);
    $obyaz = trim($_POST['obyazannosti']);
    $data = $_POST['data'];

    if ($student_id && $zona && $obyaz && $data) {
        $pdo->prepare('INSERT INTO dezurstva (zona, obyazannosti, status_id, polzovateli_id, data) VALUES (?, ?, 1, ?, ?)')
            ->execute([$zona, $obyaz, $student_id, $data]);
        $success = "Новое дежурство успешно назначено!";
    } else {
        $error = "Заполните все поля для назначения дежурства!";
    }
}

// --- 0.1 УДАЛЕНИЕ ДЕЖУРСТВА ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dezhurstvo'])) {
    $del_id = (int)$_POST['delete_id'];
    if ($del_id) {
        try {
            $pdo->prepare('DELETE FROM zayavki WHERE dezurstva_id = ?')->execute([$del_id]);
            $pdo->prepare('DELETE FROM dezurstva WHERE id_dezurstva = ?')->execute([$del_id]);
            $success = "Дежурство №{$del_id} успешно удалено!";
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении: ' . $e->getMessage();
        }
    }
}

// --- 1. СМЕНА СТАТУСА ЗАЯВКИ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $zid = (int)($_POST['zayavka_id'] ?? 0);
    $sid = (int)($_POST['status_id'] ?? 0);
    
    if ($zid && $sid) {
        try {
            $pdo->prepare('UPDATE zayavki SET status_id = ? WHERE id_zayavki = ?')->execute([$sid, $zid]);
            
            // Если статус "Замена одобрена" (id=4), меняем дежурного
            if ($sid == 4) {
                $stmt = $pdo->prepare('SELECT dezurstva_id, zamena_id FROM zayavki WHERE id_zayavki = ?');
                $stmt->execute([$zid]);
                $z = $stmt->fetch();
                if ($z && $z['zamena_id']) {
                    $pdo->prepare('UPDATE dezurstva SET polzovateli_id = ? WHERE id_dezurstva = ?')
                        ->execute([$z['zamena_id'], $z['dezurstva_id']]);
                }
            }
            $success = "Статус заявки №{$zid} успешно обновлён!";
        } catch (PDOException $e) {
            $error = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}

// --- 2. ФИЛЬТРЫ ---
$f_status = $_GET['f_status'] ?? '';
$f_zona   = trim($_GET['f_zona'] ?? '');
$f_date   = $_GET['f_date'] ?? '';

$where = ' WHERE 1=1 ';
$params = [];
if ($f_status !== '') { $where .= ' AND z.status_id = :f_status '; $params['f_status'] = $f_status; }
if ($f_zona !== '')   { $where .= ' AND d.zona LIKE :f_zona ';    $params['f_zona'] = '%' . $f_zona . '%'; }
if ($f_date !== '')   { $where .= ' AND d.data = :f_date ';       $params['f_date'] = $f_date; }

// --- 3. ПАГИНАЦИЯ ---
$limit = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$cnt = $pdo->prepare("SELECT COUNT(*) FROM zayavki z JOIN dezurstva d ON z.dezurstva_id = d.id_dezurstva $where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

// --- 4. ПОЛУЧЕНИЕ ЗАЯВОК ---
$sql = "SELECT z.id_zayavki, z.prichina, z.status_id AS z_status,
               d.id_dezurstva, d.zona, d.data,
               s.status AS status_name,
               u1.fio AS user_fio, u2.fio AS zamena_fio
        FROM zayavki z
        JOIN dezurstva d ON z.dezurstva_id = d.id_dezurstva
        JOIN polzovateli u1 ON d.polzovateli_id = u1.id_polzovateli
        LEFT JOIN polzovateli u2 ON z.zamena_id = u2.id_polzovateli
        JOIN statusi s ON z.status_id = s.id_statusi
        $where
        ORDER BY z.id_zayavki DESC
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statuses = $pdo->query('SELECT id_statusi, status FROM statusi')->fetchAll();

$pageTitle = 'Панель администратора';
$breadcrumbs = [['title' => 'Главная', 'url' => 'index.php'], ['title' => 'Админ-панель', 'url' => '']];

ob_start();
?>
<section class="admin-panel-wrapper">
    <h2 style="margin-bottom: 20px; color: #1d4ed8;">Панель администратора</h2>
    
    <?php if ($error): ?><div class="toast toast-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="toast toast-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Блок назначения дежурства -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px; color: #1d4ed8; font-size: 18px;">Назначить новое дежурство</h3>
        <form method="POST" action="" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="create_dezhurstvo" value="1">
            
            <select name="student_id" required style="padding: 8px; border-radius: 6px; border: 1px solid #ccc; flex: 1; min-width: 200px;">
                <option value="">Выберите студента</option>
                <?php 
                $students = $pdo->query("SELECT id_polzovateli, fio, group_name FROM polzovateli WHERE roli_id = 1")->fetchAll();
                foreach ($students as $st): 
                ?>
                    <option value="<?= $st['id_polzovateli'] ?>"><?= htmlspecialchars($st['fio']) ?> (<?= htmlspecialchars($st['group_name']) ?>)</option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="zona" placeholder="Зона (Аудитория 301)" required style="padding: 8px; border-radius: 6px; border: 1px solid #ccc; flex: 1;">
            <input type="text" name="obyazannosti" placeholder="Обязанности (Мытье полов)" required style="padding: 8px; border-radius: 6px; border: 1px solid #ccc; flex: 1;">
            <input type="date" name="data" required style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
            
            <button type="submit" class="btn btn-action">Добавить</button>
        </form>
    </div>

    <!-- Блок со всеми назначенными дежурствами -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px; color: #1d4ed8; font-size: 18px;">Все назначенные дежурства</h3>
        <?php
        $all_dezh = $pdo->query('
            SELECT d.*, p.fio, p.group_name, s.status 
            FROM dezurstva d
            JOIN polzovateli p ON d.polzovateli_id = p.id_polzovateli
            JOIN statusi s ON d.status_id = s.id_statusi
            ORDER BY d.data DESC
        ')->fetchAll();
        ?>
        <?php if (empty($all_dezh)): ?>
            <p style="color: #6b7280;">Пока нет назначенных дежурств.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: #f3f4f6; color: #374151;">
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">№</th>
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Студент (Группа)</th>
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Дата</th>
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Зона</th>
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Статус</th>
                            <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_dezh as $ad): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px;"><?= $ad['id_dezurstva'] ?></td>
                            <td style="padding: 10px;"><strong><?= htmlspecialchars($ad['fio']) ?></strong><br><span style="font-size:12px; color:#6b7280;"><?= htmlspecialchars($ad['group_name']) ?></span></td>
                            <td style="padding: 10px;"><?= date('d.m.Y', strtotime($ad['data'])) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($ad['zona']) ?></td>
                            <td style="padding: 10px;">
                                <span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    <?= htmlspecialchars($ad['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px;">
                                <form method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить это дежурство?');" style="margin:0;">
                                    <input type="hidden" name="delete_dezhurstvo" value="1">
                                    <input type="hidden" name="delete_id" value="<?= $ad['id_dezurstva'] ?>">
                                    <button type="submit" style="background: #ef4444; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <h3 style="margin-bottom: 15px; color: #1d4ed8; font-size: 18px;">Заявки на замену</h3>
    
    <!-- Фильтры -->
    <form method="GET" class="filter-form">
        <select name="f_status">
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s['id_statusi'] ?>" <?= $f_status == $s['id_statusi'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['status']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="f_zona" placeholder="Поиск по зоне..." value="<?= htmlspecialchars($f_zona) ?>">
        <input type="date" name="f_date" value="<?= htmlspecialchars($f_date) ?>">
        <button type="submit" class="btn btn-primary">Применить</button>
        <a href="admin.php" class="btn btn-secondary">Сбросить</a>
    </form>

    <!-- Список заявок -->
    <?php if (empty($rows)): ?>
        <p style="background:#fff; padding:20px; border-radius:12px; text-align:center;">Заявок по вашему фильтру не найдено.</p>
    <?php else: ?>
        <div class="cards-grid">
        <?php foreach ($rows as $r): ?>
            <div class="zayvka-card">
                <div class="zayvka-header">
                    <div>Заявка №<?= $r['id_zayavki'] ?></div>
                    <div class="zayvka-status"><?= htmlspecialchars($r['status_name']) ?></div>
                </div>
                <div class="zayvka-body">
                    <p><strong>Дежурит:</strong> <?= htmlspecialchars($r['user_fio']) ?></p>
                    <p><strong>Заменяющий:</strong> <span style="color:#1d4ed8; font-weight:600;"><?= htmlspecialchars($r['zamena_fio'] ?? '—') ?></span></p>
                    <p><strong>Дата:</strong> <?= date('d.m.Y', strtotime($r['data'])) ?></p>
                    <p><strong>Зона:</strong> <?= htmlspecialchars($r['zona']) ?></p>
                    <p><strong>Причина:</strong> <em><?= htmlspecialchars($r['prichina']) ?></em></p>

                    <form method="POST" class="card-status-form">
                        <input type="hidden" name="zayavka_id" value="<?= $r['id_zayavki'] ?>">
                        <input type="hidden" name="update_status" value="1">
                        <select name="status_id" class="status-dropdown">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s['id_statusi'] ?>" <?= $s['id_statusi'] == $r['z_status'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-action">Сохранить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&f_status=<?= urlencode($f_status) ?>&f_zona=<?= urlencode($f_zona) ?>&f_date=<?= urlencode($f_date) ?>"
                       class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';