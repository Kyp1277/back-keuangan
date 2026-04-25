<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /umkm-keuangan/dashboard/index.php");
} else {
    header("Location: /umkm-keuangan/auth/login.php");
}
exit;
