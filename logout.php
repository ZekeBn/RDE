<?php

if (!isset($_SESSION)) {
    session_start();
}
// *** Logout the current user.
$novalido = intval($_GET['ef']);
$logoutGoTo = "login.php";
require_once("includes/borrasesiones.php");
if ($novalido == 1) {
    echo "<div align='center' style='color:#000; font-weight:bold'>Ning&uacute;n m&oacute;dulo ha sido asignado al usuario.<br />Solicite acceso al administrador del sistema<br /><a href='login.php'><img src='img/logoem01.png' width='128' height='128' title='Regresar' /></a></div>";
    exit;
} else {
    if ($novalido == 2) {
        echo "<div align='center' style='color:#000; font-weight:bold'>Su licencia de uso para el sistema es inv&aacute;lida.<br />Solicite verificaci&oacute;n al administrador del sistema<br /><a href='login.php'><img src='img/logo2017e-karucslogan.png' width='128' height='128' title='Regresar' /></a></div>";
        exit;
    }
}
if ($logoutGoTo != "") {

    $_SESSION['idusuario'] = null;
    unset($_SESSION['idusuario']);
    $_SESSION['idempresa'] = null;
    unset($_SESSION['idempresa']);
    $_SESSION['idempresa'] = null;
    unset($_SESSION['usuariologin']);
    header("Location: ".$logoutGoTo);
    exit;
}
