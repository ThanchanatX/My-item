<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$error = '';
$success = false;
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = 'กรุณากรอกชื่อหมวดหมู่';
    } else {
        // เช็คซ้ำในฐานข้อมูล
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'หมวดหมู่นี้มีอยู่แล้ว';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                $success = true;
                $name = '';
            } else {
                $error = 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่';
            }
        }
    }
}
?>

<h3><i class="fa-solid fa-folder-plus me-2"></i>เพิ่มหมวดหมู่</h3>

<?php if ($success): ?>
    <div class="alert alert-success">เพิ่มหมวดหมู่เรียบร้อยแล้ว!</div>
<?php elseif (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="card p-4 shadow-sm mt-3">
    <div class="mb-3">
        <label for="name" class="form-label">ชื่อหมวดหมู่</label>
        <input type="text" class="form-control" name="name" id="name" required value="<?= htmlspecialchars($name) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> เพิ่มหมวดหมู่</button>
</form>

<?php include 'includes/footer.php'; ?>

