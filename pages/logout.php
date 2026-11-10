<?php
session_start();
session_destroy();
header('Location: /pkk/pages/login.php');
exit;
