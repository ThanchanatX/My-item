<?php
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: items.php');
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$item) {
    header('Location: items.php?deleted=notfound');
    exit;
}

// ลบบาร์โค้ดภาพ (ถ้ามี)
$barcodeImagePath = 'barcodes/' . $item['barcode'] . '.png';
if (file_exists($barcodeImagePath)) {
    unlink($barcodeImagePath);
}

// ลบรายการ
$stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
$stmt->execute([$id]);

header('Location: items.php?deleted=success');
exit;
