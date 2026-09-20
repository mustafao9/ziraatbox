<?php
session_start();
session_destroy();
header("Location: https://ziraatbox.com/index.php");
exit;
?>