</div> <!-- /container -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome JS (optional if you use dynamic icons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

</body>
</html>
<?php
// ดึงข้อมูลจาก logs
$logCounts = $pdo->query("
    SELECT action, COUNT(*) as count 
    FROM logs 
    GROUP BY action
")->fetchAll(PDO::FETCH_KEY_PAIR);

// ดึงจำนวนสิ่งของต่อหมวดหมู่
$categoryStats = $pdo->query("
    SELECT c.name, COUNT(*) as total 
    FROM items i 
    JOIN categories c ON i.category_id = c.id 
    GROUP BY c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>
