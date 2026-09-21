<?php
session_start();
unset($_SESSION['admin_id']);
unset($_SESSION['admin_ad']);
header("Location: giris.php");
exit;
?>