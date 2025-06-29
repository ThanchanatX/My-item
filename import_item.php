<?php
require_once 'includes/header.php'; // ตรวจสอบว่า header.php มี Bootstrap 5 และ Font Awesome 5+
require_once 'includes/functions.php';

// --- PHP Logic (เหมือนเดิม) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['item_ids'] ?? [];
    if (!empty($ids)) {
        foreach ($ids as $item_id) {
            $stmt = $pdo->prepare("INSERT INTO logs (item_id, action, note) VALUES (?, 'in', ?)");
            $stmt->execute([$item_id, 'นำเข้าสิ่งของ']);
            
            $update = $pdo->prepare("UPDATE items SET status = 'in' WHERE id = ?");
            $update->execute([$item_id]);
        }
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'นำเข้าสิ่งของเรียบร้อยแล้ว',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => window.location.href='import_item.php');
        </script>";
    } else {
        echo "<script>
            Swal.fire('ข้อผิดพลาด!', 'กรุณาเลือกสิ่งของอย่างน้อย 1 รายการ', 'warning');
        </script>";
    }
}

$items = $pdo->query("SELECT * FROM items WHERE status = 'out' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$itemsByBarcode = [];
foreach ($items as $item) {
    $itemsByBarcode[$item['barcode']] = $item;
}
?>

<style>
    .item-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        cursor: pointer;
        border-radius: 0.5rem; /* เพิ่มความโค้งมน */
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1);
    }
    .item-card.selected {
        border: 2px solid var(--bs-primary); /* ใช้สี primary ของ Bootstrap */
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.5);
    }
    .item-card.highlighted {
        animation: highlightFlash 1.2s ease;
        border: 2px solid var(--bs-success) !important; /* ใช้สี success ของ Bootstrap */
        background-color: #e6ffed !important;
    }
    @keyframes highlightFlash {
        0%, 100% { box-shadow: 0 0 10px 3px var(--bs-success); }
        50% { box-shadow: none; }
    }
    .card-img-top-container {
        height: 180px; /* กำหนดความสูงคงที่สำหรับส่วนรูปภาพ */
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa; /* สีพื้นหลังอ่อนๆ */
        overflow: hidden;
        border-top-left-radius: calc(0.5rem - 1px); /* ปรับให้เข้ากับ card border-radius */
        border-top-right-radius: calc(0.5rem - 1px);
    }
    .card-img-top-container img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain; /* ให้รูปภาพแสดงเต็มแต่ไม่ล้น */
    }
    .card-img-top-container .placeholder-icon {
        font-size: 4rem;
        color: #adb5bd; /* สีเทาสำหรับ placeholder */
    }
    .select-button-icon {
        transition: transform 0.2s ease;
    }
    .item-card.selected .select-button-icon {
        transform: rotate(360deg);
    }
    /* ทำให้ checkbox ซ่อนอยู่ แต่ยังทำงานได้ */
    .visually-hidden-checkbox {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
</style>

<div class="container py-4">
    <header class="pb-3 mb-4 border-bottom">
        <h2 class="fw-bold">
            <i class="fas fa-arrow-alt-circle-down me-2 text-primary"></i>นำเข้าสิ่งของ
        </h2>
    </header>

    <div class="row mb-4 justify-content-center">
        <div class="col-md-8 col-lg-6">
            <label for="scan-barcode" class="form-label fs-5">สแกนบาร์โค้ดเพื่อเลือกอัตโนมัติ:</label>
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                <input type="text" class="form-control" id="scan-barcode" placeholder="ยิงบาร์โค้ดที่นี่..." autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
            </div>
        </div>
    </div>

    <form method="post" id="import-form">
        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <p class="lead text-muted">ไม่มีรายการสิ่งของที่สามารถนำเข้าได้ในขณะนี้</p>
            </div>
        <?php else: ?>
            <div id="item-list-cards" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
                <?php foreach ($items as $item): ?>
                    <div class="col">
                        <div class="card h-100 item-card" data-barcode="<?= htmlspecialchars($item['barcode']) ?>" data-item-id="<?= $item['id'] ?>">
                            <input class="visually-hidden-checkbox item-checkbox" type="checkbox" name="item_ids[]" value="<?= $item['id'] ?>" id="item_checkbox_<?= $item['id'] ?>">
                            <div class="card-img-top-container">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">

                                <?php else: ?>
                                    <i class="fas fa-image placeholder-icon"></i> <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title flex-grow-1"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="card-text small text-muted mb-2">
                                    <i class="fas fa-barcode me-1"></i><?= htmlspecialchars($item['barcode']) ?>
                                </p>
                                <?php if (!empty($item['detail'])): ?>
                                <p class="card-text small text-muted fst-italic" style="font-size: 0.8em;">
                                    <?= htmlspecialchars(mb_strimwidth($item['detail'], 0, 70, "...")) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                                <button type="button" class="btn btn-outline-primary w-100 select-item-btn">
                                    <i class="far fa-square me-2 select-button-icon"></i>เลือกรายการนี้
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 py-3">
                    <i class="fas fa-check-circle me-2"></i>ยืนยันการนำเข้า
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const barcodeInput = document.getElementById('scan-barcode');
    const importForm = document.getElementById('import-form');
    const itemsByBarcode = <?= json_encode($itemsByBarcode, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
    const itemListCards = document.getElementById('item-list-cards');

    // Auto focus on barcode input and re-focus if user clicks away
    barcodeInput.focus();
    document.body.addEventListener('click', (event) => {
        if (!event.target.closest('input, textarea, button, a, .swal2-container')) {
             barcodeInput.focus();
        }
    });

    function updateCardSelection(cardElement, isSelected) {
        const checkbox = cardElement.querySelector('.item-checkbox');
        const selectButton = cardElement.querySelector('.select-item-btn');
        const buttonIcon = selectButton.querySelector('.select-button-icon');

        checkbox.checked = isSelected;
        if (isSelected) {
            cardElement.classList.add('selected');
            selectButton.classList.remove('btn-outline-primary');
            selectButton.classList.add('btn-primary');
            buttonIcon.classList.remove('fa-square', 'far');
            buttonIcon.classList.add('fa-check-square', 'fas');
            selectButton.childNodes[2].nodeValue = ' เลือกแล้ว'; // Text node after icon
        } else {
            cardElement.classList.remove('selected');
            selectButton.classList.remove('btn-primary');
            selectButton.classList.add('btn-outline-primary');
            buttonIcon.classList.remove('fa-check-square', 'fas');
            buttonIcon.classList.add('fa-square', 'far');
            selectButton.childNodes[2].nodeValue = ' เลือกรายการนี้';
        }
    }
    
    if (itemListCards) { // Ensure itemListCards exists
        itemListCards.addEventListener('click', function(e) {
            const card = e.target.closest('.item-card');
            if (!card) return;

            const selectButton = e.target.closest('.select-item-btn');
            
            // If click is on card (not specifically the button) or on the button
            if (card && (e.target === card || card.contains(e.target) || selectButton)) {
                const checkbox = card.querySelector('.item-checkbox');
                if (checkbox) {
                    updateCardSelection(card, !checkbox.checked);
                }
            }
        });
    }


    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            const code = barcodeInput.value.trim();
            if (!code) return;

            const itemData = itemsByBarcode[code];
            const cardElement = document.querySelector(`.item-card[data-barcode="${code}"]`);

            if (itemData && cardElement) {
                updateCardSelection(cardElement, true);
                
                cardElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                cardElement.classList.add('highlighted');
                setTimeout(() => cardElement.classList.remove('highlighted'), 1500);

                Swal.fire({
                    title: `<i class="fas fa-check-circle text-success me-2"></i> ${itemData.name}`,
                    html: `
                        <div class="text-start">
                            <p class="mb-1"><strong><i class="fas fa-barcode me-1"></i> บาร์โค้ด:</strong> ${itemData.barcode}</p>
                            ${itemData.detail ? `<p class="mb-1"><strong><i class="fas fa-info-circle me-1"></i> รายละเอียด:</strong> ${itemData.detail}</p>` : ''}
                        </div>
                        ${itemData.image ? `<img src="${itemData.image}" alt="${itemData.name}" style="max-width:80%; max-height: 200px; object-fit:contain; border-radius:8px; margin-top:15px;">` : '<i class="fas fa-image fa-3x text-muted my-3"></i>'}
                    `,
                    confirmButtonText: '<i class="fas fa-thumbs-up me-1"></i> ตกลง',
                    focusConfirm: true // Focus the confirm button
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบข้อมูล',
                    text: `ไม่พบสิ่งของที่มีบาร์โค้ด "${code}" ในรายการที่สามารถนำเข้าได้`,
                    confirmButtonText: 'รับทราบ'
                });
            }
            barcodeInput.value = '';
            barcodeInput.focus(); // Re-focus after processing
        }
    });

    importForm.addEventListener('submit', function(e) {
        const selectedItems = importForm.querySelectorAll('.item-checkbox:checked');
        if (selectedItems.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'ยังไม่ได้เลือกรายการ',
                text: 'กรุณาเลือกสิ่งของอย่างน้อย 1 รายการเพื่อนำเข้า',
                confirmButtonText: 'ตกลง'
            });
        }
        // Form will submit normally if items are selected
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>