<?php
session_start();
session_destroy();
header("Location: /umkm-keuangan/auth/login.php");
exit;
