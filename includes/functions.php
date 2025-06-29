<?php
require_once 'db.php';

/**
 * สร้างรหัสบาร์โค้ดสุ่มแบบไม่ซ้ำ (ถ้ายังไม่มี)
 */
function generateBarcode($length = 12) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $barcode = '';
    for ($i = 0; $i < $length; $i++) {
        $barcode .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $barcode;
}

/**
 * ตรวจสอบว่าบาร์โค้ดนี้มีอยู่แล้วหรือยัง
 */
function isBarcodeExists($barcode) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE barcode = ?");
    $stmt->execute([$barcode]);
    return $stmt->fetchColumn() > 0;
}

/**
 * สร้างบาร์โค้ดที่ไม่ซ้ำ
 */
function generateUniqueBarcode($length = 12) {
    do {
        $barcode = generateBarcode($length);
    } while (isBarcodeExists($barcode));
    return $barcode;
}

/**
 * ดึงชื่อหมวดหมู่จาก ID
 */
function getCategoryName($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $row = $stmt->fetch();
    return $row ? $row['name'] : 'ไม่ทราบหมวดหมู่';
}

/**
 * ดึงรายการหมวดหมู่ทั้งหมด (สำหรับแสดงใน <select>)
 */
function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * เพิ่มสิ่งของเข้า queue สำหรับพิมพ์บาร์โค้ด
 */
function addToPrintQueue($item_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO print_queue (item_id) VALUES (?)");
    return $stmt->execute([$item_id]);
}
