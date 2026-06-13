<?php
// ak/activate_session.php
session_start();
if (isset($_GET['phone']) && isset($_GET['name'])) {
    $_SESSION['user_phone'] = $_GET['phone'];
    $_SESSION['user_name'] = $_GET['name'];
}
header('Location: index.php');
exit;
?>

