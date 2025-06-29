<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// ดึงข้อมูล logs พร้อม join กับ items
$stmt = $pdo->prepare("
    SELECT logs.*, items.name, items.barcode 
    FROM logs 
    JOIN items ON logs.item_id = items.id 
    ORDER BY logs.scanned_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h4 class="card-title text-primary mb-3">
                <i class="fa fa-history me-2"></i>ประวัติการเคลื่อนไหวของสิ่งของ
            </h4>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 180px;"><i class="fa fa-clock me-1"></i>วันที่และเวลา</th>
                            <th><i class="fa fa-box me-1"></i>ชื่อสิ่งของ</th>
                            <th><i class="fa fa-barcode me-1"></i>บาร์โค้ด</th>
                            <th><i class="fa fa-exchange-alt me-1"></i>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-center">
                                    <span title="<?= htmlspecialchars($log['scanned_at']) ?>">
                                        <?= date('d/m/Y H:i', strtotime($log['scanned_at'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($log['barcode']) ?></td>
                                <td class="text-center">
                                    <?php if ($log['action'] === 'in'): ?>
                                        <span class="badge bg-success px-3 py-2 rounded-pill">
                                            <i class="fa fa-arrow-down me-1"></i>นำเข้า
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2 rounded-pill">
                                            <i class="fa fa-arrow-up me-1"></i>นำออก
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa fa-info-circle me-1"></i>ยังไม่มีประวัติการเคลื่อนไหว
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
