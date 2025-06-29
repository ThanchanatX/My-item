<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสิ่งของ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f2f4f8;
        }

        .navbar-glass {
            background: linear-gradient(to right,rgb(30, 86, 207),rgb(91, 166, 228));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s ease-in-out;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            animation: float 2s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .navbar-nav .nav-link {
            color: #ffffff !important;
            font-weight: 500;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .navbar-nav .nav-link i {
            margin-right: 6px;
            transition: transform 0.2s ease-in-out; /* เพิ่ม transition ให้ไอคอน */
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.3); /* เปลี่ยนสีให้เข้มขึ้นเล็กน้อย */
            transform: translateY(-2px) scale(1.02); /* เลื่อนขึ้นเล็กน้อยและขยาย */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15); /* เพิ่มเงาเล็กน้อย */
        }

        .navbar-nav .nav-link:hover i {
            transform: rotate(15deg); /* ลองหมุนไอคอนเล็กน้อยเมื่อ hover */
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.4);
            font-weight: bold;
            /* เพิ่มสไตล์อื่นๆ ที่ต้องการสำหรับหน้าปัจจุบัน เช่น border-bottom: 2px solid white; */
        }

        .navbar-toggler {
            border: none;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass sticky-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fa-solid fa-boxes-packing me-2"></i>ThanchantX
        </a>
        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto text-center text-lg-start">
                <li class="nav-item">
                    <a class="nav-link" href="items.php"><i class="fa-solid fa-house"></i> หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_item.php"><i class="fa-solid fa-plus"></i> เพิ่มข้อมูล</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="import_item.php"><i class="fa-solid fa-truck-ramp-box"></i> นำเข้า</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="export_item.php"><i class="fa-solid fa-dolly"></i> นำออก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="print_barcode.php"><i class="fa-solid fa-barcode"></i> พิมพ์บาร์โค้ด</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const currentPath = window.location.pathname;

        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    });
</script>