<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$type = $_GET['type'] ?? 'single';
$barcode = $_GET['barcode'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$items = [];

if ($type === 'single' && $barcode) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE barcode = ?");
    $stmt->execute([$barcode]);
    $item = $stmt->fetch();
    if ($item) $items[] = $item;

} elseif ($type === 'category' && $category_id) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $items = $stmt->fetchAll();

} elseif ($type === 'all') {
    $stmt = $pdo->query("SELECT * FROM items");
    $items = $stmt->fetchAll();
}

$categories = getAllCategories();
?>
<head>
<style> 
    body {
        font-family: 'Kanit', sans-serif;
        background: #f8f9fa;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #printArea, #printArea * {
            visibility: visible;
        }
        #printArea {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 0;
            background: white;
        }
        .no-print {
            display: none;
        }
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
    }

    .search-card {
        background: white;
        padding: 20px 25px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgb(0 0 0 / 0.1);
        margin-bottom: 30px;
        transition: box-shadow 0.3s ease;
    }

    .search-card:hover {
        box-shadow: 0 6px 20px rgb(0 0 0 / 0.15);
    }

    .form-label {
        font-weight: 600;
    }

    button.btn {
        transition: transform 0.15s ease;
    }

    button.btn:hover {
        transform: scale(1.05);
    }

    /* ปรับให้บาร์โค้ดต่อแถว 4 ชิ้นพอดี */
    .barcode-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        justify-content: center;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .barcode-card {
        background: white;
        padding: 4mm 2mm;
        text-align: center;
        font-size: 10px;
        width: 48mm;
        height: 36mm;
        border: 1px dashed #999;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .barcode-card strong {
        font-size: 10px;
        margin-bottom: 1mm;
        font-weight: bold;
        color: #000;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-card small {
        font-size: 8px;
        margin-bottom: 1mm;
        color: #333;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-card svg {
        width: 100%;
        height: 18mm;
    }

    .barcode-card:hover {
        box-shadow: none;
        transform: none;
        border-color: #000;
    }
</style>

</head>

<div class="container py-4">
    <h4 class="mb-4 text-primary"><i class="fa fa-barcode me-2"></i>พิมพ์บาร์โค้ด</h4>

    <div class="search-card no-print">
        <form method="get" class="row gy-3 gx-3 align-items-end">
            <div class="col-auto flex-grow-1">
                <label class="form-label">เลือกประเภท:</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="single" <?= $type === 'single' ? 'selected' : '' ?>>พิมพ์รายการเดียว</option>
                    <option value="category" <?= $type === 'category' ? 'selected' : '' ?>>ตามหมวดหมู่</option>
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                </select>
            </div>

            <?php if ($type === 'single'): ?>
                <div class="col-auto flex-grow-1">
                    <label class="form-label">บาร์โค้ด:</label>
                    <input type="text" name="barcode" id="barcodeInput" class="form-control" value="<?= htmlspecialchars($barcode) ?>" placeholder="ยิงบาร์โค้ดแล้ว" autofocus>

                </div>
            <?php elseif ($type === 'category'): ?>
                <div class="col-auto flex-grow-1">
                    <label class="form-label">หมวดหมู่:</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-auto">
                <button type="submit" class="btn btn-success px-4">
                    <i class="fa fa-search me-1"></i> แสดงรายการ
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-warning">ไม่พบรายการสำหรับพิมพ์</div>
    <?php else: ?>
        <div class="mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary px-4">
                <i class="fa fa-print me-1"></i> พิมพ์บาร์โค้ด
            </button>
        </div>

        <div id="printArea" class="barcode-grid">
            <?php foreach ($items as $item): ?>
                <div class="barcode-card" title="<?= htmlspecialchars($item['name']) ?>">
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    <small>หมวด: <?= htmlspecialchars(getCategoryName($item['category_id'])) ?></small>
                    <svg class="barcode-svg"
                         jsbarcode-format="CODE128"
                         jsbarcode-value="<?= htmlspecialchars($item['barcode']) ?>"
                         jsbarcode-textmargin="1"
                         jsbarcode-fontoptions="">
                    </svg>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    JsBarcode(".barcode-svg").init();
</script>
<script>
    // ตรวจจับ Enter ในช่องกรอกบาร์โค้ด
    document.getElementById("barcodeInput")?.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            this.form.submit(); // ส่งฟอร์มทันที
        }
    });

    // Auto-focus ใหม่หลังโหลดหน้า (เช่น ยิงเสร็จ reload แล้วกลับมาพร้อมพิมพ์ต่อได้เลย)
    window.addEventListener("DOMContentLoaded", () => {
        const input = document.getElementById("barcodeInput");
        if (input) input.focus();
    });
</script>

<?php include 'includes/footer.php'; ?>
