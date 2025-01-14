<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


if (isset($_SESSION['idwebpedido'])) {
    $_SESSION['idwebpedido'] = null;
    unset($_SESSION['idwebpedido']);
}

header("location: gest_ventas_resto_caja.php");
exit;
