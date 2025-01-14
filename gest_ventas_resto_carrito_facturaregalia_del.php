<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idfacturaregalia = intval($_GET['id']);
if ($idfacturaregalia > 0) {
    // destruye la sesion
    $_SESSION['idfacturaregalia'] = 0;
    $_SESSION['idfacturaregalia'] = null;
    unset($_SESSION['idfacturaregalia']);

    header("location: carrito_borra.php?todo=s&redir=ven");
    exit;
}
