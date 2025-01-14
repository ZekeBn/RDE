<?php

require_once("../includes/funciones_compras.php");
require_once("../includes/conexion.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones.php");


$idt = intval($_POST['tran']);
$idempresa = intval($_POST['idempresa']);
$idusu = intval($_POST["idusu"]);

if ($idt > 0) {
    //Generamos la compra
    $buscar = "Select * from tmpcompras where idtran=$idt  ";
    $rscabecera = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // validacioens
    $valido = "S";
    $errores = "";


    $factura = antisqlinyeccion($rscabecera->fields['facturacompra'], 'text');
    $fechacompra = ($rscabecera->fields['fecha_compra']);
    $tipocompra = intval($rscabecera->fields['tipocompra']);

    $totalcompra = intval($rscabecera->fields['totalcompra']);
    $monto_factura = intval($rscabecera->fields['monto_factura']);
    $idprov = intval($rscabecera->fields['proveedor']);
    $vencimientofac = antisqlinyeccion($rscabecera->fields['vencimiento'], 'date');

    $timbrado = intval($rscabecera->fields['timbrado']);
    $timbradovenc = antisqlinyeccion($rscabecera->fields['vto_timbrado'], 'date');
    $facturacompra_incrementatmp = antisqlinyeccion($rscabecera->fields['facturacompra_incrementa'], 'int');
    $ocnum = antisqlinyeccion($rscabecera->fields['ocnum'], 'int');


    $consulta = "SELECT cmp_dt.descripcion as item, cmp_dt.idprod, cmp_dt.cant_transito  , (cmp_dt.cant_transito - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante
    FROM compras_ordenes_detalles AS cmp_dt
    INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
    LEFT JOIN (
        SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
        FROM tmpcompradeta AS cmp_det
        INNER JOIN tmpcompras AS cmp ON cmp.idtran = cmp_det.idt AND cmp.ocnum = $ocnum
        GROUP BY cmp_det.idprod
    ) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
    WHERE cmp_dt.ocnum = $ocnum";
    $orden_items_faltantes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $valido = "N";
    while (!$orden_items_faltantes->EOF) {
        if (floatval($orden_items_faltantes->fields['cantidad_faltante']) > 0 && floatval($orden_items_faltantes ->fields['cant_transito']) != 0) {
            $valido = "S";
        }
        $orden_items_faltantes->MoveNext();
    }

    $respuesta = [
        "faltante" => $valido,
        "success" => true
    ];
    echo json_encode($respuesta);



}//idt > 0
