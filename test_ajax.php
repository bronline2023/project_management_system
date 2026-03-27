<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'unlock_digital_service';
$_POST['service_slug'] = 'poster_studio';

require_once 'Code/Core_Logic/App/ajax_unlock_service.php';
