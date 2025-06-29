<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Search & fetch items
$items = [];
$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE name LIKE ? OR barcode LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC");
}
$items = $stmt->fetchAll();

// --- Dashboard Stats Section ---
// 1. จำนวนสิ่งของทั้งหมด
$stmt = $pdo->query("SELECT COUNT(*) FROM items");
$totalItems = $stmt->fetchColumn();

// 2. สถิติการนำเข้า/นำออก (จากตาราง logs หรือ items ถ้าไม่มีตาราง logs)
// สมมติว่ามีตาราง logs (item_logs) ที่เก็บ type = 'in'/'out' และจำนวน
$logCounts = ['in' => 0, 'out' => 0];
if ($pdo->query("SHOW TABLES LIKE 'item_logs'")->rowCount()) {
    $stmt = $pdo->query("SELECT type, SUM(amount) as total FROM item_logs GROUP BY type");
    foreach ($stmt as $row) {
        $logCounts[$row['type']] = (int)$row['total'];
    }
} else {
    // ถ้าไม่มีตาราง logs, นับจาก items
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM items GROUP BY status");
    foreach ($stmt as $row) {
        if ($row['status'] === 'in') $logCounts['in'] = (int)$row['total'];
        if ($row['status'] === 'out') $logCounts['out'] = (int)$row['total'];
    }
}

// 3. หมวดหมู่ & จำนวนในแต่ละหมวด
$categoryStats = [];
$stmt = $pdo->query("SELECT category_id, COUNT(*) as total FROM items GROUP BY category_id");
$categoryTotals = [];
foreach ($stmt as $row) {
    $categoryTotals[$row['category_id']] = $row['total'];
}
// ดึงชื่อหมวดหมู่ทั้งหมด
$stmt = $pdo->query("SELECT id, name FROM categories");
foreach ($stmt as $row) {
    $categoryStats[] = [
        'name' => $row['name'],
        'total' => $categoryTotals[$row['id']] ?? 0
    ];
}

// 4. จำนวน "อยู่ในกล่อง" / "นำออก"
$inBoxCount = $logCounts['in'];
$outBoxCount = $logCounts['out'];

// 5. จำนวนหมวดหมู่
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$totalCategories = $stmt->fetchColumn();
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard สินค้านำเข้า</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style> 
    /* custom-dashboard.css */

/* --- Global Styles & Variables --- */
:root {
    --primary-color: #005c9e; /* สีน้ำเงินเข้มแบบมืออาชีพ */
    --secondary-color: #6c757d; /* Bootstrap Secondary Gray */
    --success-color: #198754; /* Bootstrap Success Green */
    --danger-color: #dc3545; /* Bootstrap Danger Red */
    --warning-color: #ffc107; /* Bootstrap Warning Yellow */
    --light-gray: #f8f9fa;
    --medium-gray: #e9ecef;
    --dark-gray: #343a40;
    --border-color: #dee2e6;
    --card-bg: #ffffff;
    --text-muted-light: #86909c;
    --font-family-sans-serif: 'Kanit', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    --box-shadow-sm: 0 .125rem .25rem rgba(0,0,0,.075);
    --box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    --box-shadow-lg: 0 1rem 3rem rgba(0,0,0,.175);
    --border-radius: 0.375rem; /* 6px */
    --border-radius-lg: 0.5rem; /* 8px */
}

body {
    background-color: #f4f7fc; /* พื้นหลังสีเทาอ่อนๆ สบายตา */
    font-family: var(--font-family-sans-serif);
    color: var(--dark-gray);
    line-height: 1.6;
}

/* --- Typography Enhancements --- */
h3 {
    color: var(--dark-gray);
    font-weight: 600; /* เพิ่มความหนาของตัวอักษร */
    font-size: 1.75rem; /* ขนาดใหญ่ขึ้นเล็กน้อย */
}

h5.card-title {
    color: var(--dark-gray);
    font-weight: 500;
    margin-bottom: 1.25rem; /* เพิ่มระยะห่างด้านล่าง */
    font-size: 1.1rem;
}

/* --- Header Section (Title and Add Button) --- */
.dashboard-header { /* คุณจะต้องเพิ่ม class นี้ให้กับ div ที่ครอบ h3 และปุ่ม "เพิ่มสิ่งของใหม่" */
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 2rem !important;
}

.dashboard-header h3 .fa-boxes-stacked {
    color: var(--primary-color);
    font-size: 1.6em; /* ไอคอนใหญ่ขึ้น */
    margin-right: 0.8rem !important; /* ระยะห่างไอคอนกับข้อความ */
}

.dashboard-header .btn-success {
    font-weight: 500;
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem; /* ปรับ padding ปุ่ม */
    box-shadow: var(--box-shadow-sm);
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
}
.dashboard-header .btn-success:hover {
    box-shadow: var(--box-shadow);
}
.dashboard-header .btn-success .fa-plus {
    font-size: 0.9em;
}

/* --- Search Bar Card --- */
.search-card { /* เพิ่ม class นี้ให้กับ card ที่ครอบ form ค้นหา */
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
}

.search-card .card-body {
    padding: 1.5rem; /* เพิ่ม padding ภายใน card */
}

.search-card .form-control {
    border-radius: var(--border-radius);
    border-color: var(--border-color);
    padding: 0.5rem 1rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.search-card .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(0, 92, 158, 0.25); /* สี shadow ตอน focus */
}

.search-card .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    font-weight: 500;
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem;
    box-shadow: var(--box-shadow-sm);
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
}
.search-card .btn-primary:hover {
    background-color: #004a80; /* สีเข้มขึ้นเมื่อ hover */
    border-color: #004a80;
    box-shadow: var(--box-shadow);
}

.search-card .btn-primary .fa-search {
    font-size: 0.9em;
}

.search-card .btn-outline-secondary {
    border-color: var(--border-color);
    color: var(--secondary-color);
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem;
}
.search-card .btn-outline-secondary:hover {
    background-color: var(--secondary-color);
    color: #fff;
}

/* --- Chart Cards --- */
.chart-card { /* เพิ่ม class นี้ให้กับ card ของ chart */
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.chart-card:hover {
    transform: translateY(-4px); /* เอฟเฟกต์ลอยขึ้นเล็กน้อย */
    box-shadow: var(--box-shadow-lg);
}

.chart-card .card-body {
    padding: 1.75rem; /* เพิ่ม padding */
}
#importExportPie, #itemBarChart {
    max-height: 300px; /* จำกัดความสูงของ chart */
}


/* --- Item Count Text --- */
.item-count-text { /* เพิ่ม class นี้ให้กับ div ที่แสดงจำนวนรายการ */
    font-size: 0.95rem;
    color: var(--text-muted-light);
    margin-bottom: 0.75rem !important;
    padding-left: 0.25rem;
}

/* --- Main Table Card --- */
.table-card { /* เพิ่ม class นี้ให้กับ card ที่ครอบตาราง */
    border: none;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    overflow: hidden; /* สำคัญสำหรับ table ที่มีมุมโค้ง */
}

.table-responsive {
    /* border: 1px solid var(--border-color); */ /* อาจจะไม่จำเป็นถ้า card มี shadow แล้ว */
    /* border-radius: var(--border-radius-lg); */ /* ถ้า table-responsive อยู่ด้านนอก card โดยตรง */
}

.table {
    margin-bottom: 0 !important; /* Bootstrap table margin ถ้าอยู่ข้างใน card */
    border-collapse: separate; /* เพื่อให้ border-spacing ทำงานได้ */
    border-spacing: 0; /* ลบช่องว่างระหว่าง cell */
}

.table thead th {
    background-color: var(--light-gray);
    color: var(--dark-gray);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    border-bottom: 2px solid var(--primary-color); /* เส้นใต้หัวตารางเป็นสีหลัก */
    border-top: none;
    padding: 0.9rem 0.75rem;
    vertical-align: middle;
}
.table thead th:first-child {
    border-top-left-radius: var(--border-radius-lg); /* สำหรับมุมโค้งของ card */
}
.table thead th:last-child {
    border-top-right-radius: var(--border-radius-lg); /* สำหรับมุมโค้งของ card */
}


.table tbody tr {
    transition: background-color 0.15s ease-in-out;
}

.table tbody tr:nth-of-type(odd) {
    background-color: var(--card-bg);
}
.table tbody tr:nth-of-type(even) {
    background-color: #fbfcfd; /* สีสลับที่อ่อนมากๆ */
}

.table tbody tr:hover {
    background-color: #eef4fb; /* สีฟ้าอ่อนเมื่อ hover */
}

.table td {
    border-top: 1px solid var(--medium-gray); /* เส้นคั่นระหว่าง row ที่อ่อนลง */
    vertical-align: middle;
    font-size: 0.9rem;
    padding: 0.9rem 0.75rem; /* ปรับ padding ให้สอดคล้องกัน */
    color: #525f7f; /* สีข้อความในตาราง */
}
.table td:first-child {
    border-left: none;
}
.table td:last-child {
    border-right: none;
}


.table .item-image { /* เพิ่ม class นี้ให้กับ img tag */
    width: 48px !important;
    height: 48px !important;
    border-radius: var(--border-radius); /* มุมโค้งของรูป */
    border: 2px solid var(--medium-gray);
    object-fit: cover;
    transition: transform 0.2s ease;
}
.table .item-image:hover {
    transform: scale(1.1);
}

.table .no-image-text { /* เพิ่ม class นี้ให้กับ span "ไม่มีรูป" */
    color: var(--text-muted-light);
    font-size: 0.85rem;
    display: inline-block;
    width: 48px;
    height: 48px;
    line-height: 48px;
    text-align: center;
    background-color: var(--light-gray);
    border-radius: var(--border-radius);
}

.table code {
    background-color: var(--medium-gray);
    padding: 0.25em 0.5em;
    border-radius: 4px;
    font-size: 0.85em;
    color: var(--primary-color); /* สีของ code ให้เด่นขึ้น */
    border: 1px solid var(--border-color);
}

/* Status Badges */
.table .badge {
    font-size: 0.8em;
    padding: 0.55em 0.8em;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    border-radius: var(--border-radius); /* มุมโค้งของ badge */
    text-transform: capitalize;
}
.table .badge .fa {
    margin-right: 0.4em;
    font-size: 0.9em;
}
.table .badge.bg-success {
    background-color: var(--success-color) !important;
    color: #fff;
}
.table .badge.bg-danger {
    background-color: var(--danger-color) !important;
    color: #fff;
}

/* Action Buttons in Table */
.table .btn-sm {
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    border-radius: var(--border-radius);
    margin: 0 0.15rem; /* ระยะห่างระหว่างปุ่ม */
    transition: all 0.2s ease;
}
.table .btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: var(--box-shadow-sm);
}

.table .btn-sm .fa {
    font-size: 0.95em;
}

.table .btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
    color: #fff;
}
.table .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}

.table .btn-warning {
    background-color: var(--warning-color);
    border-color: var(--warning-color);
    color: var(--dark-gray);
}
.table .btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: var(--dark-gray);
}
.table .btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
    color: #fff;
}
.table .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}


/* --- SweetAlert2 Customization --- */
.swal2-popup {
    border-radius: var(--border-radius-lg) !important;
    font-family: var(--font-family-sans-serif);
    box-shadow: var(--box-shadow-lg) !important;
}
.swal2-title {
    font-weight: 500;
    color: var(--dark-gray);
}
.swal2-html-container {
    color: #525f7f;
}
.swal2-confirmButton,
.swal2-cancelButton,
.swal2-denyButton {
    border-radius: var(--border-radius) !important;
    font-weight: 500;
    padding: 0.6em 1.2em !important;
    box-shadow: var(--box-shadow-sm) !important;
    transition: background-color 0.2s ease, box-shadow 0.2s ease !important;
}
.swal2-confirmButton:hover,
.swal2-cancelButton:hover,
.swal2-denyButton:hover {
    box-shadow: var(--box-shadow) !important;
}

/* --- Responsive Adjustments --- */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .dashboard-header h3 {
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }
    .dashboard-header div:last-child { /* div containing the "Add New" button */
        width: 100%;
    }
    .dashboard-header .btn-success {
        width: 100%;
        padding: 0.75rem 1rem; /* ปุ่มใหญ่ขึ้นบนมือถือ */
    }

    .search-card .d-flex {
        flex-direction: column;
    }
    .search-card .form-control {
        margin-bottom: 0.75rem;
    }
    .search-card .btn {
        width: 100%;
    }
    .search-card .btn-outline-secondary {
        margin-top: 0.5rem;
    }

    .table thead {
        display: none; /* ซ่อน aตารางบนมือถือ */
    }
    .table tbody, .table tr, .table td {
        display: block;
        width: 100% !important; /* กำหนด width ให้เต็ม */
    }
    .table tr {
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background-color: var(--card-bg) !important; /* ให้แต่ละ row เป็น card */
        padding: 0.5rem;
    }
    .table td {
        text-align: right !important; /* จัดข้อความชิดขวา */
        padding-left: 45% !important; /* เว้นที่สำหรับ label */
        position: relative;
        border: none;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }
     .table td:last-child {
        padding-bottom: 1rem; /* เพิ่ม padding ด้านล่างของ td สุดท้ายใน card */
    }

    .table td::before {
        content: attr(data-label); /* ใช้ data-label ที่คุณต้องเพิ่มใน HTML */
        position: absolute;
        left: 0.75rem;
        width: calc(45% - 1.5rem);
        padding-right: 0.75rem;
        font-weight: 600;
        text-align: left !important;
        white-space: nowrap;
        color: var(--dark-gray);
        font-size: 0.85rem;
    }
    .table td.text-center, .table td.align-middle.text-center { /* Override Bootstrap's text-center for mobile */
        text-align: right !important;
    }

    /* จัดการ cell รูปภาพเป็นพิเศษ */
    .table td[data-label="รูป"] {
        display: flex;
        justify-content: flex-end; /* จัดรูปไปทางขวา */
        align-items: center;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .table td[data-label="รูป"]::before {
        align-self: center; /* ให้ label อยู่กลางแนวตั้ง */
    }
     .table .item-image {
        margin-left: auto; /* ให้รูปชิดขวา */
    }

    /* จัดการ cell action buttons */
    .table td[data-label="การจัดการ"] .btn {
        margin: 0.2rem 0.1rem; /* ปรับ margin ปุ่ม */
    }
}

/* เพิ่ม Font Kanit (ถ้าต้องการ) */
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap');
</style>
</head>
<div class="dashboard-header d-flex justify-content-between align-items-center mb-3">
    <h3><i class="fa-solid fa-boxes-stacked me-2"></i>รายการสิ่งของ</h3>
    <div>
        <a href="add_item.php" class="btn btn-success">
            <i class="fa fa-plus me-1"></i> เพิ่มข้อมูล
        </a>
    </div>
</div>

<!-- DASHBOARD STAT CARDS -->
<div class="row mb-4 g-3">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm chart-card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fa-solid fa-box fa-2x text-primary"></i>
                </div>
                <div class="fs-4 fw-bold"><?= $totalItems ?></div>
                <div class="text-muted small mt-1">สิ่งของทั้งหมด</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm chart-card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fa-solid fa-arrow-down fa-2x text-success"></i>
                </div>
                <div class="fs-4 fw-bold"><?= $logCounts['in'] ?></div>
                <div class="text-muted small mt-1">นำเข้า (ครั้ง/จำนวน)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm chart-card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fa-solid fa-arrow-up fa-2x text-danger"></i>
                </div>
                <div class="fs-4 fw-bold"><?= $logCounts['out'] ?></div>
                <div class="text-muted small mt-1">นำออก (ครั้ง/จำนวน)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm chart-card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fa-solid fa-layer-group fa-2x text-warning"></i>
                </div>
                <div class="fs-4 fw-bold"><?= $totalCategories ?></div>
                <div class="text-muted small mt-1">หมวดหมู่ทั้งหมด</div>
            </div>
        </div>
    </div>
</div>
<!-- END DASHBOARD STAT CARDS -->

<div class="card shadow-sm mb-4 search-card">
    <div class="card-body">
        <form method="get" class="d-flex flex-wrap gap-2">
            <input type="search" id="searchInput" class="form-control flex-grow-1" name="search" placeholder="ค้นหาด้วยชื่อหรือบาร์โค้ด" value="<?= htmlspecialchars($search) ?>" aria-label="ค้นหา" autofocus>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-search me-1"></i> ค้นหา
            </button>
            <?php if ($search): ?>
                <a href="items.php" class="btn btn-outline-secondary" title="ล้างการค้นหา">
                    <i class="fa fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>
<div class="row mb-4 g-3">
    <div class="col-md-6">
        <div class="card shadow-sm chart-card">
            <div class="card-body">
                <h5 class="card-title"><i class="fa-solid fa-circle-half-stroke me-2 text-success"></i>สัดส่วนการนำเข้า / นำออก</h5>
                <canvas id="importExportPie"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm chart-card">
            <div class="card-body">
                <h5 class="card-title"><i class="fa-solid fa-chart-column me-2 text-primary"></i>จำนวนสิ่งของในแต่ละหมวดหมู่</h5>
                <canvas id="itemBarChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="mb-2 text-muted">
    <i class="fa fa-list me-1"></i><?= count($items) ?> รายการ (ที่แสดง)
</div>

<div class="card shadow-sm table-card">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-nowrap mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th scope="col" style="width:60px;">รูป</th>
                    <th scope="col" style="width:50px;">ID</th>
                    <th scope="col" style="min-width:140px;">ชื่อ</th>
                    <th scope="col" style="min-width:180px;">รายละเอียด</th>
                    <th scope="col" style="width:120px;">หมวดหมู่</th>
                    <th scope="col" style="min-width:130px;">บาร์โค้ด</th>
                    <th scope="col" style="width:100px;">กล่อง</th>
                    <th scope="col" style="width:100px;">สถานะ</th>
                    <th scope="col" style="width:110px;">เพิ่มเมื่อ</th>
                    <th scope="col" style="width:110px;">อัปเดตเมื่อ</th>
                    <th scope="col" style="width:130px;">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted fst-italic">ไม่มีข้อมูล</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
<tr>
    <td data-label="รูป" class="text-center align-middle">
        <?php if (!empty($item['image']) && file_exists(__DIR__ . "/uploads/{$item['image']}")): ?>
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="rounded item-image" style="width:50px; height:50px; object-fit:cover;">
        <?php else: ?>
            <span class="no-image-text">ไม่มีรูป</span>
        <?php endif; ?>
    </td>
    <td data-label="ID" class="align-middle text-center"><?= $item['id'] ?></td>
    <td data-label="ชื่อ" class="align-middle" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars(mb_strimwidth($item['name'], 0, 25, '...')) ?></td>
    <td data-label="รายละเอียด" class="align-middle" title="<?= htmlspecialchars($item['details']) ?>"><?= nl2br(htmlspecialchars(mb_strimwidth($item['details'], 0, 50, '...'))) ?></td>
    <td data-label="หมวดหมู่" class="align-middle text-center"><?= htmlspecialchars(getCategoryName($item['category_id'])) ?></td>
    <td data-label="บาร์โค้ด" class="align-middle text-center"><code><?= htmlspecialchars($item['barcode']) ?></code></td>
    <td data-label="กล่อง" class="align-middle text-center"><?= htmlspecialchars($item['box_number']) ?></td>
    <td data-label="สถานะ" class="align-middle text-center">
        <?php if ($item['status'] === 'in'): ?>
            <span class="badge bg-success" data-bs-toggle="tooltip" title="อยู่ในกล่อง"><i class="fa fa-check-circle me-1"></i>อยู่ในกล่อง</span>
        <?php else: ?>
            <span class="badge bg-danger" data-bs-toggle="tooltip" title="นำออก"><i class="fa fa-times-circle me-1"></i>นำออก</span>
        <?php endif; ?>
    </td>
    <td data-label="เพิ่มเมื่อ" class="align-middle text-center" title="<?= date('d M Y H:i:s', strtotime($item['created_at'])) ?>"><?= date('Y-m-d', strtotime($item['created_at'])) ?></td>
    <td data-label="อัปเดตเมื่อ" class="align-middle text-center" title="<?= date('d M Y H:i:s', strtotime($item['updated_at'])) ?>"><?= date('Y-m-d', strtotime($item['updated_at'])) ?></td>
    <td data-label="การจัดการ" class="align-middle text-center">
        <a href="print_barcode.php?barcode=<?= urlencode($item['barcode']) ?>" class="btn btn-sm btn-secondary me-1" title="พิมพ์บาร์โค้ด" target="_blank" data-bs-toggle="tooltip">
            <i class="fa fa-barcode"></i>
        </a>
        <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning me-1" title="แก้ไข" data-bs-toggle="tooltip">
            <i class="fa fa-edit"></i>
        </a>
        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?= $item['id'] ?>" title="ลบ" data-bs-toggle="tooltip">
            <i class="fa fa-trash-alt"></i>
        </button>
    </td>
</tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- SweetAlert2 Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Bootstrap tooltip enable
    document.addEventListener('DOMContentLoaded', () => {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
    });

    // Delete button handler
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', () => {
            const itemId = button.getAttribute('data-id');

            Swal.fire({
                title: 'คุณแน่ใจไหม?',
                text: "หากลบแล้วจะไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = `delete_item.php?id=${itemId}`;
                }
            });
        });
    });

    // Show success/error alert after delete
    <?php if (isset($_GET['deleted'])): ?>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($_GET['deleted'] === 'success'): ?>
        Swal.fire({
            icon: 'success',
            title: 'ลบข้อมูลสำเร็จ',
            timer: 1800,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
        <?php elseif ($_GET['deleted'] === 'notfound'): ?>
        Swal.fire({
            icon: 'error',
            title: 'ไม่พบรายการที่ต้องการลบ',
            timer: 1800,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
        <?php endif; ?>
    });
    <?php endif; ?>
</script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ข้อมูล Pie Chart (นำเข้า / นำออก)
    const pieCtx = document.getElementById('importExportPie').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: ['นำเข้า', 'นำออก'],
            datasets: [{
                data: [<?= $logCounts['in'] ?? 0 ?>, <?= $logCounts['out'] ?? 0 ?>],
                backgroundColor: ['#198754', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
            }
        }
    });

    // ข้อมูล Bar Chart (สิ่งของในแต่ละหมวดหมู่)
    const barCtx = document.getElementById('itemBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($categoryStats, 'name')) ?>,
            datasets: [{
                label: 'จำนวนสิ่งของ',
                data: <?= json_encode(array_column($categoryStats, 'total')) ?>,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
<script>
    // กด Enter ในช่อง search แล้วค้นหาเลย
    document.getElementById("searchInput")?.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            this.form.submit();
        }
    });
</script>