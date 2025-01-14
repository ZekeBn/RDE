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
$faltante = intval($_POST["faltante"]);
if ($idt > 0) {
    //Generamos la compra
    $buscar = "Select * from tmpcompras where idtran=$idt  ";
    $rscabecera = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    //exit;

    // //Generamos los detalles
    // $buscar="Select * from tmpcompradeta where idt=$idt  and idemp = $idempresa";
    // $rscuerpo=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

    // generamos los dias de pago
    $buscar = "select * from tmpcompravenc where idtran=$idt";
    $rscompravenc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // sumar dias de pago
    $buscar = "select sum(monto_cuota) as monto_cuota, min(vencimiento) as vencimiento from tmpcompravenc where idtran=$idt";
    $rscompravencsum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $monto_cuota_venc = floatval($rscompravencsum->fields['monto_cuota']);
    $vencimientomin = $rscompravencsum->fields['vencimiento'];
    // validacioens
    $valido = "S";
    $errores = "";


    $factura = antisqlinyeccion($rscabecera->fields['facturacompra'], 'text');
    $descuento = antisqlinyeccion($rscabecera->fields['descuento'], 'float');
    $fechacompra = ($rscabecera->fields['fecha_compra']);
    $tipocompra = intval($rscabecera->fields['tipocompra']);

    $totalcompra = floatval($rscabecera->fields['totalcompra']);
    $monto_factura = floatval($rscabecera->fields['monto_factura']);
    $idprov = intval($rscabecera->fields['proveedor']);
    $vencimientofac = antisqlinyeccion($rscabecera->fields['vencimiento'], 'date');

    $timbrado = intval($rscabecera->fields['timbrado']);
    $timbradovenc = antisqlinyeccion($rscabecera->fields['vto_timbrado'], 'date');
    $facturacompra_incrementatmp = antisqlinyeccion($rscabecera->fields['facturacompra_incrementa'], 'int');
    $ocnum = antisqlinyeccion($rscabecera->fields['ocnum'], 'int');
    $idsucursal = intval($rscabecera->fields['sucursal']);
    $idtipocomprobante = antisqlinyeccion($rscabecera->fields['idtipocomprobante'], "int");
    $cdc = antisqlinyeccion(trim($rscabecera->fields['cdc']), 'text');
    $moneda = intval($rscabecera->fields['moneda']);
    $cambio = floatval($rscabecera->fields['cambio']);
    $descripcion = antisqlinyeccion($rscabecera->fields['descripcion'], "text");
    $idcot = antisqlinyeccion($rscabecera->fields['idcot'], "int");
    $idtipo_origen = antisqlinyeccion($rscabecera->fields['idtipo_origen'], "int");
    $idcompra_ref = antisqlinyeccion($rscabecera->fields['idcompra_ref'], "int");
    // validar compras
    $parametros_array = [
        'idt' => $idt,
        'idprov' => $idprov,
        'idsucursal' => $idsucursal,
        'idempresa' => $idempresa,
        'fechacompra' => $fechacompra,
        'factura' => $factura,
        'idusu' => $idusu,
        'totalcompra' => $totalcompra,
        'tipocompra' => $tipocompra,
        'timbrado' => $timbrado,
        'timbradovenc' => $timbradovenc,
        'facturacompra_incrementatmp' => $facturacompra_incrementatmp,
        'ocnum' => $ocnum,
        'idtipocomprobante' => $idtipocomprobante,
        'cdc' => $cdc,
        'monto_factura' => $monto_factura,
        'monto_cuota_venc' => $monto_cuota_venc,
        'vencimientomin' => $vencimientomin,
        'vencimientofac' => $vencimientofac,
        'moneda' => $moneda,
        'cambio' => $cambio,
        'faltante' => $faltante,
        "idcot" => $idcot,
        "idtipo_origen" => $idtipo_origen,
        'descripcion' => $descripcion,
        "idcompra_ref" => $idcompra_ref,
        "descuento" => $descuento
    ];
    ///
    $respuesta = validar_compra($parametros_array);
    if ($respuesta['valido'] == 'N') {
        $valido = $respuesta['valido'];
        $errores .= nl2br($respuesta['errores']);
        $respuesta = [
            "errores" => $errores,
            "success" => false
        ];
        echo json_encode($respuesta);
    }



    if ($respuesta['valido'] == 'S' && $valido == 'S') {

        $respuesta = registrar_compra($parametros_array);// regresa idcompra como array
        // $respuesta = relacionar_gastos($parametros_array);// regresa idcompra como array
        // echo $respuesta;exit;
        $respuesta = [
            "success" => true
        ];
        echo json_encode($respuesta);
        //header("location: gest_reg_compras_resto_det.php?id=".$idtransaccion);
        // header("location: gest_adm_depositos_compras_det.php?idcompra=".$idcompra);
        // exit;


    } // if($valido == 'S'){

}//idt > 0
