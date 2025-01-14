 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");


require_once("includes/funciones_mesas.php");




$idqrtmpmonto = intval($_GET['id']);
if ($idqrtmpmonto == 0) {
    header("location: cuenta_mesas.php");
    exit;
}

$consulta = "
select * from qr_tmp_monto where idqrtmpmonto = $idqrtmpmonto and estado = 1
";
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idatc = intval($rscab->fields['idatc']);
$monto_abonar = $rscab->fields['monto_abonar'];
$monto_propina = $rscab->fields['monto_propina'];
$total_abonar = $rscab->fields['total_abonar'];
if ($idatc == 0) {
    header("location: cuenta_mesas.php");
    exit;
}


$consulta = "
select idsucursal from mesas_atc where idatc = $idatc
";
$rsatcsuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal = $rsatcsuc->fields['idsucursal'];

// saldo de la mesa
$parametros_array_saldo['idatc'] = $idatc;
$saldo_mesa_res = saldo_mesa($parametros_array_saldo);
$saldomesa = floatval($saldo_mesa_res['saldo_mesa']);

$idpersonalizacion = 1;


$idusu_pedido = $idusu;
if ($idusu_pedido == 0) {
    echo "No se asigno ningun usuario del sistema para pedidos por QR.";
    exit;
}



require_once("includes/funciones_bancard_qr.php");

$segundos_inactividad = '300'; // 300 = 5 minutos recomendado por bancard




//  elimina el qr viejo
$consulta = "
update  pos_tmp
set
json_bancard_fijo = NULL,
hook_alias_fijo = NULL
where
idatc = $idatc
and idqrtmpmonto=$idqrtmpmonto
and estado = 1
and idventa is null
and idatc is not null
and hook_alias_fijo is not null
";
//echo $consulta;exit;
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
select * 
from pos_tmp
where
idatc = $idatc
order by idpostmp desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpostmp = intval($rs->fields['idpostmp']);
$hook_alias_fijo = trim($rs->fields['hook_alias_fijo']);
$json_bancard_fijo = trim($rs->fields['json_bancard_fijo']);
// sino existe inserta
if ($idpostmp == 0) {
    $consulta = "
    INSERT INTO `pos_tmp`
    (`datos_json`, `estado`, `idsucursal`, `idventa`, idatc, idqrtmpmonto, `tipo`, `registrado_por`, `registrado_el`) 
    VALUES 
    (NULL,1,$idsucursal,NULL,$idatc,$idqrtmpmonto,'QRB',$idusu_pedido,'$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

// busca si existe pero esta vez valida que no se haya usado anteriormente
$consulta = "
select * 
from pos_tmp
where
idatc = $idatc
and idqrtmpmonto = $idqrtmpmonto
and estado = 1
and idventa is null
and idatc is not null
order by idpostmp desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpostmp = intval($rs->fields['idpostmp']);

//TRAE EL PEDIDO WEB REGISTRADO
/*$consulta="
select * from tmp_ventares_cab where idwebpedido = $idwebpedido order by idtmpventares_cab desc limit 1
";
$rsped=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idtmpventares_cab=$rsped->fields['idtmpventares_cab'];
$idcliente=$rsped->fields['idclienteped'];
if(intval($idtmpventares_cab) == 0){
    $errores.="- Hubo un problema y no se registro el pedido.";
    $valido="N";
}
if(intval($idcliente) == 0){
    $errores.="- Hubo un problema y no se registro el cliente.";
    $valido="N";
}
    */

if ($idpostmp == 0) {
    echo "No se recibio el registro o el registro ya fue procesado.";
    exit;
}
$datos_post = json_decode($rs->fields['datos_json'], true);

//$datos_get=$_GET;


//print_r($datos_post['monto_recibido']);exit;

// si no existe consulta en bancard
if ($hook_alias_fijo == '') {

    $consulta = "
    select codigo_sucursal_bancard from sucursales where idsucu = $idsucursal
    ";
    $rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $codigo_sucursal_bancard = $rssuc->fields['codigo_sucursal_bancard'];

    $idpedido = $idpostmp;
    $subtotal = floatval($saldomesa);
    $parametros_array = [
        'monto' => $subtotal,
        'descripcion' => 'COMPRA EN COMERCIO',
        'idpostmp' => $idpostmp,
        'codigo_sucursal_bancard' => $codigo_sucursal_bancard,
    ];
    $res = generar_qr_pago($parametros_array);
    //print_r($res);
    // si ya existe trae de la bd
} else {
    $res = $json_bancard_fijo;
}

$respuesta_bruta_bancard = $res;
$respuesta = json_decode($res, true);
$status = $respuesta['status'];
/*
        "amount": 500000,
        "hook_alias": "SXKBT74691",
        "description": "Coca Cola 1lt.",
        "url": "https://desa.infonet.com.py:8035/s4/public/selling_qr_images/SXKBT74691_1697829696.png",
        "created_at": "20/10/2023 16:21:36",
        "qr_data": "00020101021202035775204444453036005408500000.05802PY5918BELLINI PASTAS-SOL6008Asuncion62320510SXKBT746910814Coca Cola 1lt.630477CF"
*/
$qr_img = $respuesta['qr_express']['url'];
$qr_contenido = $respuesta['qr_express']['qr_data'];
$qr_hook_alias = $respuesta['qr_express']['hook_alias'];
$qr_description = $respuesta['qr_express']['description'];
$qr_created_at = $respuesta['qr_express']['created_at'];
$qr_amount = $respuesta['qr_express']['amount'];
$clientes_soportados = $respuesta['supported_clients'];

if (trim($qr_hook_alias) == '') {
    $error_bancard = $respuesta_bruta_bancard;
    echo 'Error de Bancard: '.antixss($error_bancard);
    exit;
} else {
    $error_bancard = ""; // ALTER TABLE `pos_tmp` ADD `json_bancard_fijo` TEXT NULL AFTER `hook_alias_fijo`

    $respuesta_bruta_bancard_sql = antisqlinyeccion($respuesta_bruta_bancard, "textbox");
    $qr_hook_alias_sql = antisqlinyeccion($qr_hook_alias, "textbox");
    $consulta = "
    update  pos_tmp
    set
    json_bancard_fijo = $respuesta_bruta_bancard_sql,
    hook_alias_fijo = $qr_hook_alias_sql
    where
    idpostmp = $idpostmp
    and estado = 1
    and idventa is null
    and idatc is not null
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    header("location: cuenta_mesas_qr_gen.php?id=".$idqrtmpmonto);
    exit;

}



?>
