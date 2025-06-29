<?php
require_once 'includes/header.php'; // โหลด Bootstrap 5, FontAwesome, SweetAlert2
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['item_ids'] ?? [];
    if (!empty($ids)) {
        foreach ($ids as $item_id) {
            $stmt = $pdo->prepare("INSERT INTO logs (item_id, action, note) VALUES (?, 'out', ?)");
            $stmt->execute([$item_id, 'นำออกสิ่งของ']);
    
            $update = $pdo->prepare("UPDATE items SET status = 'out' WHERE id = ?");
            $update->execute([$item_id]);
        }
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'นำจ่ายสิ่งของแล้ว',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => window.location.href='export_item.php');
        </script>";
    } else {
        echo "<script>
            Swal.fire('ข้อผิดพลาด!', 'กรุณาเลือกสิ่งของอย่างน้อย 1 รายการ', 'warning');
        </script>";
    }
}

// ดึงรายการสิ่งของที่สถานะ 'in' (สามารถนำออกได้)
$items = $pdo->query("SELECT * FROM items WHERE status = 'in' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$itemsByBarcode = [];
foreach ($items as $item) {
    $itemsByBarcode[$item['barcode']] = $item;
}
?>

<style>
    .item-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        cursor: pointer;
        border-radius: 0.5rem;
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1);
    }
    .item-card.selected {
        border: 2px solid var(--bs-danger);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-danger-rgb), 0.5);
    }
    .item-card.highlighted {
        animation: highlightFlash 1.2s ease;
        border: 2px solid var(--bs-danger) !important;
        background-color: #ffe6e6 !important;
    }
    @keyframes highlightFlash {
        0%, 100% { box-shadow: 0 0 10px 3px var(--bs-danger); }
        50% { box-shadow: none; }
    }
    .card-img-top-container {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        overflow: hidden;
        border-top-left-radius: calc(0.5rem - 1px);
        border-top-right-radius: calc(0.5rem - 1px);
    }
    .card-img-top-container img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
    .card-img-top-container .placeholder-icon {
        font-size: 4rem;
        color: #adb5bd;
    }
    .select-button-icon {
        transition: transform 0.2s ease;
    }
    .item-card.selected .select-button-icon {
        transform: rotate(360deg);
    }
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
        <h2 class="fw-bold text-danger">
            <i class="fas fa-arrow-alt-circle-up me-2"></i>นำจ่ายสิ่งของ
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

    <form method="post" id="export-form">
        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <p class="lead text-muted">ไม่มีรายการสิ่งของที่สามารถนำออกได้ในขณะนี้</p>
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
                                    <i class="fas fa-image placeholder-icon"></i>
                                <?php endif; ?>
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
                                <button type="button" class="btn btn-outline-danger w-100 select-item-btn">
                                    <i class="far fa-square me-2 select-button-icon"></i>เลือกรายการนี้
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-danger btn-lg px-5 py-3">
                    <i class="fas fa-check-circle me-2"></i>ยืนยันนำออก
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const barcodeInput = document.getElementById('scan-barcode');
    const exportForm = document.getElementById('export-form');
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
            selectButton.classList.remove('btn-outline-danger');
            selectButton.classList.add('btn-danger');
            buttonIcon.classList.remove('fa-square', 'far');
            buttonIcon.classList.add('fa-check-square', 'fas');
            selectButton.childNodes[2].nodeValue = ' เลือกแล้ว';
        } else {
            cardElement.classList.remove('selected');
            selectButton.classList.remove('btn-danger');
            selectButton.classList.add('btn-outline-danger');
            buttonIcon.classList.remove('fa-check-square', 'fas');
            buttonIcon.classList.add('fa-square', 'far');
            selectButton.childNodes[2].nodeValue = ' เลือกรายการนี้';
        }
    }

    // Click on card or button toggles selection
    itemListCards?.addEventListener('click', e => {
        const btn = e.target.closest('.select-item-btn');
        const card = e.target.closest('.item-card');
        if (!card) return;
        if (btn) {
            const currentlySelected = card.classList.contains('selected');
            updateCardSelection(card, !currentlySelected);
        } else if (e.target.closest('.item-checkbox')) {
            // Do nothing - checkbox handled by input itself
        } else {
            // Clicking card toggles checkbox
            const currentlySelected = card.classList.contains('selected');
            updateCardSelection(card, !currentlySelected);
        }
    });

    // Barcode scanner input handler
    barcodeInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = barcodeInput.value.trim();
            if (code.length === 0) return;
            if (itemsByBarcode.hasOwnProperty(code)) {
                // Select this item
                const card = itemListCards.querySelector(`.item-card[data-barcode="${code}"]`);
                if (card) {
                    updateCardSelection(card, true);
                    // Highlight briefly
                    card.classList.add('highlighted');
                    setTimeout(() => card.classList.remove('highlighted'), 1200);
                }
                barcodeInput.value = '';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่พบสิ่งของ',
                    text: `ไม่พบสิ่งของที่มีบาร์โค้ด "${code}" ในรายการ`,
                });
                barcodeInput.value = '';
            }
        }
    });

    // Optional: Confirm before submitting form if no items selected
    exportForm.addEventListener('submit', function (e) {
        const checkedBoxes = exportForm.querySelectorAll('input.item-checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'โปรดเลือกสิ่งของ',
                text: 'กรุณาเลือกสิ่งของอย่างน้อย 1 รายการก่อนยืนยัน',
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
