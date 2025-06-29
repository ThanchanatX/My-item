<?php 
require_once 'includes/header.php';
require_once 'includes/functions.php';

$success = false;
$error = '';
$name = '';
$details = '';
$category_id = '';
$barcode = '';
$box_number = '';
$status = 'in';
$image = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $category_id = $_POST['category_id'] ?? '';
    $barcode = trim($_POST['barcode']);
    $box_number = trim($_POST['box_number']);
    $status = $_POST['status'] ?? 'in';

    if (empty($name)) {
        $error = 'กรุณาใส่ชื่อสิ่งของ';
    } elseif (empty($category_id)) {
        $error = 'กรุณาเลือกหมวดหมู่';
    }

    if (empty($barcode)) {
        $barcode = generateUniqueBarcode();
    } else {
        if (isBarcodeExists($barcode)) {
            $error = 'บาร์โค้ดนี้มีอยู่แล้วในระบบ!';
        }
    }
if (isset($_POST['pasted_image']) && !empty($_POST['pasted_image'])) {
    $base64 = $_POST['pasted_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);
        $ext = strtolower($type[1]);
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg'])) {
            $newFileName = uniqid('img_') . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $newFileName;
            file_put_contents($destPath, $data);
            $image = $newFileName;
        } else {
            $error = 'ประเภทไฟล์ที่วางไม่รองรับ';
        }
    } else {
        $error = 'ข้อมูลภาพไม่ถูกต้อง';
    }
}

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp','bmp','svg'];   
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = 'รองรับเฉพาะไฟล์รูปภาพ jpg, jpeg, png, gif เท่านั้น';
        } else {
            $newFileName = uniqid('img_') . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $destPath)) {
                $image = $newFileName;
            } else {
                $error = 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ';
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO items (name, details, category_id, barcode, box_number, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $res = $stmt->execute([$name, $details, $category_id, $barcode, $box_number, $status, $image]);

        if ($res) {
            $success = true;
            addToPrintQueue($pdo->lastInsertId());
            $name = $details = $category_id = $barcode = $box_number = $image = '';
            $status = 'in';
        } else {
            $errorInfo = $stmt->errorInfo();
            $error = 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $errorInfo[2];
        }
    }
}

$categories = getAllCategories();
?>

<style>
    body {
        font-family: 'Kanit', sans-serif;
    }
    h3 {
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2c3e50;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    form.card {
        max-width: 600px;
        margin: auto;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease-in-out;
    }
    form.card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .form-label {
        font-weight: 600;
        color: #34495e;
    }
    input.form-control,
    select.form-select,
    textarea.form-control {
        border-radius: 6px;
        border: 1.5px solid #ced4da;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    input.form-control:focus,
    select.form-select:focus,
    textarea.form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 6px rgba(13, 110, 253, 0.3);
        outline: none;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .btn-primary {
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 30px;
        box-shadow: 0 5px 15px rgba(13,110,253,.3);
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #0b5ed7;
    }

    .alert {
        max-width: 600px;
        margin: 1rem auto;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: 0.02em;
        animation: fadeInDown 0.5s ease forwards;
    }
    @keyframes fadeInDown {
        0% {
            opacity: 0;
            transform: translateY(-20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .upload-box {
    background: #f8f9fa;
    border: 2px dashed #ced4da;
    cursor: pointer;
    transition: border-color 0.3s ease;
}
.upload-box:hover {
    border-color: #0d6efd;
    background: #eef5ff;
}
.upload-box input[type="file"] {
    margin-top: 10px;
}
@media (min-width: 768px) {
  form.card {
    max-width: 900px;
  }
}
#preview.d-none + span {
  display: block;
}

</style>

<h3><i class="fa-solid fa-plus me-2 text-primary"></i>เพิ่มสิ่งของใหม่</h3>

<?php if ($success): ?>
    <div class="alert alert-success text-center shadow-sm">เพิ่มสิ่งของเรียบร้อยแล้ว!</div>
<?php elseif (!empty($error)): ?>
    <div class="alert alert-danger text-center shadow-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm mt-3">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="name" class="form-label">ชื่อสิ่งของ <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="name" id="name" required value="<?= htmlspecialchars($name) ?>">
      </div>

      <div class="mb-3">
        <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
        <select class="form-select" name="category_id" id="category_id" required>
          <option value="">-- เลือกหมวดหมู่ --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label for="barcode" class="form-label">บาร์โค้ด</label>
        <input type="text" class="form-control" name="barcode" id="barcode" value="<?= htmlspecialchars($barcode) ?>">
        <div class="form-text">ถ้าว่าง ระบบจะสร้างให้เองอัตโนมัติ</div>
      </div>

      <div class="mb-3">
        <label for="box_number" class="form-label">หมายเลขกล่อง/ที่เก็บ</label>
        <input type="text" class="form-control" name="box_number" id="box_number" value="<?= htmlspecialchars($box_number) ?>">
      </div>

      <div class="mb-3">
        <label for="status" class="form-label">สถานะ</label>
        <select class="form-select" name="status" id="status">
          <option value="in" <?= $status == 'in' ? 'selected' : '' ?>>อยู่ในกล่อง</option>
          <option value="out" <?= $status == 'out' ? 'selected' : '' ?>>นำออก</option>
        </select>
      </div>
    </div>

    <div class="col-md-6">
      <div class="mb-3">
        <label for="details" class="form-label">รายละเอียด</label>
        <textarea class="form-control" name="details" id="details"><?= htmlspecialchars($details) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">รูปภาพสิ่งของ</label>
        <div class="upload-box border rounded d-flex flex-column align-items-center justify-content-center p-3" id="drop-area">
          <i class="fa-solid fa-image fa-3x text-muted mb-2"></i>
          <span class="text-muted text-center">ลากรูปมาวาง / เลือกไฟล์ / Ctrl+V</span>
          <input type="file" class="form-control mt-2" name="image" id="image" accept="image/*">
          <input type="hidden" name="pasted_image" id="pasted_image">
          <img id="preview" src="" alt="" class="img-fluid rounded mt-2 d-none" style="max-height: 200px;">
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-4">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-check me-2"></i>บันทึกสิ่งของ
    </button>
  </div>
</form>

<script>
document.getElementById('barcode').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const nextInput = document.getElementById('box_number');
        if (nextInput) nextInput.focus();
    }
});
</script>
<script>
document.addEventListener('paste', function (event) {
    const items = (event.clipboardData || event.originalEvent.clipboardData).items;
    for (const item of items) {
        if (item.type.indexOf('image') !== -1) {
            const file = item.getAsFile();
            const reader = new FileReader();
            reader.onload = function (event) {
                const base64 = event.target.result;
                document.getElementById('pasted_image').value = base64;
                const preview = document.getElementById('preview');
                preview.src = base64;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    }
});
</script>


<?php include 'includes/footer.php'; ?>
