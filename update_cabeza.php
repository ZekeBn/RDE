<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");


$idt = intval($_POST['idt']);
$tipocompra = intval($_POST['tpc']);
$fecompra = antisqlinyeccion($_POST['fe'], 'date');
$suc = antisqlinyeccion($_POST['suc'], 'text');
$pex = antisqlinyeccion($_POST['pe'], 'text');
$fa = antisqlinyeccion($_POST['fa'], 'text');
$provee = antisqlinyeccion($_POST['prov'], 'int');
$timbrado = intval($_POST['timb']);
$vencimientofac = antisqlinyeccion($_POST['vencefc'], 'date');
$timbradovenc = antisqlinyeccion($_POST['vencetm'], 'date');
$monto_factura = antisqlinyeccion($_POST['mfac'], 'int');
$suc = str_replace("'", "", $suc);
$pex = str_replace("'", "", $pex);
$fa = str_replace("'", "", $fa);
if (($suc != '') && ($pex != '') && ($fa != '')) {
    //controlar cabeceras de nota credito
    $completo = $suc.$pex.$fa;
    $a = strlen($suc);
    if ($a < 3) {
        $a = intval($suc);
        if ($a < 10) {
            $a = '00'.$a;
        } else {
            $a = '0'.$a;
        }
        $suc = trim($a);
    }
    $a = strlen($pex);
    if ($a < 3) {
        $a = intval($pex);
        if ($a < 10) {
            $a = '00'.$a;
        } else {
            $a = '0'.$a;

        }
        $pex = trim($a);
    }
    $completo = $suc.$pex.$fa;
    $a = strlen($completo);
    if ($a < 13) {
        $b = strlen($fa);
        $diferencia = 7 - $b;
        $medio = '';
        for ($i = 1;$i <= $diferencia;$i++) {
            $medio = $medio.'0';
        }

        $fa = $medio.$fa;
        $completo = $suc.$pex.$fa;
    }
}
$moneda = 0;
$cambio = 0;
$facompra = $completo;
$facompra = antisqlinyeccion($facompra, 'text');
//Buscamos la factura
$buscar = "Select * from compras where facturacompra=$facompra and 
idproveedor=$provee and idempresa = $idempresa and estado=1";
$controla = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if ($controla->fields['facturacompra'] == '') {
    //no existe metele
    //Vemos si la cabecera ya esta registrada, sino la registramos
    $buscar = "Select * from tmpcompras where idtran=$idt  and idempresa = $idempresa";
    $rsctm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtt = intval($rsctm->fields['idtran']);
    if ($idtt == 0) {
        $insertar = "Insert into tmpcompras 
		(idtran,fechahora,idempresa,sucursal,totalcompra,facturacompra,fecha_compra,
		tipocompra,proveedor,moneda,cambio,vencimiento,timbrado,vto_timbrado,monto_factura)
		values
		($idt,current_timestamp,$idempresa,1,0,$facompra,$fecompra,$tipocompra,$provee,$moneda,$cambio
		,$vencimientofac,$timbrado,$timbradovenc,$monto_factura)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    } else {
        //Update
        $update = "Update tmpcompras set fechahora='$ahora',tipocompra=$tipocompra,proveedor=$provee,
		vencimiento=$vencimientofac,timbrado=$timbrado
		,vto_timbrado=$timbradovenc,facturacompra=$facompra,fecha_compra=$fecompra,
		monto_factura=$monto_factura
		where idempresa=$idempresa and idtran=$idt"	;
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $update = 1;
        echo 'Actualizado!';
    }



}
