<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$errores = "";
if (isset($_POST['idconteo'])) {
    $idconteo = $_POST['idconteo'];
}
$agregar = intval($_POST['agregar']);
if ($agregar == 1) {
    $idalm = intval($_POST['idalm']);
    $idregseriedptostk = intval($_POST['idregseriedptostk']);
    $fila = intval($_POST['fila']);
    $columna = intval($_POST['columna']);
    $cantidad = floatval($_POST['cantidad']);
    $idmedida = intval($_POST['idmedida']);
    $idpasillo = intval($_POST['idpasillo']);
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = antisqlinyeccion('1', "int");





    // $tipo_almacenamiento=intval($_POST['tipo_almacenamiento']); // no se usa
    // $iddeposito=intval($_POST['iddeposito']); // no se usa
    // $lote=antisqlinyeccion($_POST['lote'],"text"); // no se usa
    // $vencimiento=antisqlinyeccion($_POST['vencimiento'],'date'); // no se usa
    // $idinsumo=intval($_POST['idinsumo']); // no se usa
    // $idalamcto=intval($_POST['idalamcto']); // no se usa


    $valido = "S";

    $buscar = "SELECT 
        gest_depositos_stock.disponible 
    from 
        gest_depositos_stock 
    WHERE 
        gest_depositos_stock.idregseriedptostk = $idregseriedptostk
        and gest_depositos_stock.disponible > 0
    ";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $disponible = floatval($rs->fields['disponible']);
    if ($cantidad > $disponible) {
        $valido = "N";
        $errores .= "La cantidad sobrepasa lo disponible.<br>";
    }
    if ($valido == "S") {
        $idregserie_almacto = select_max_id_suma_uno("gest_depositos_stock_almacto", "idregserie_almacto")["idregserie_almacto"];

        $insert = "INSERT into  gest_depositos_stock_almacto 
    (idregserie_almacto, idalm, idregseriedptostk,fila,columna,
    cantidad,disponible,idmedida,registrado_por,registrado_el,estado,idpasillo)
    values ($idregserie_almacto, $idalm, $idregseriedptostk, $fila, $columna,
    $cantidad, $cantidad, $idmedida, $registrado_por, $registrado_el, $estado, $idpasillo)
    ";
        $rsd = $conexion->Execute($insert) or die(errorpg($conexion, $insert));
    }



}
