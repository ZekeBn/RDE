 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$idmesa = intval($_POST['idmesa']);
if ($idmesa == 0) {
    echo "No se recibio el idmesa.";
    exit;
}
$consulta = "
SELECT
    ventas.idventa,
    ventas.idcaja,
    mesas.numero_mesa,
    mesas.idmesa,
    mesas_atc.idatc,
    ventas.factura,
    usuarios_ven.usuario AS usuario_venta,
    usuarios_caj.usuario AS usuario_caja
FROM ventas
INNER JOIN mesas_atc ON mesas_atc.idatc = ventas.idatc
INNER JOIN mesas ON mesas.idmesa = mesas_atc.idmesa
INNER JOIN caja_super ON caja_super.idcaja = ventas.idcaja
INNER JOIN usuarios AS usuarios_ven ON usuarios_ven.idusu = caja_super.cajero
INNER JOIN usuarios AS usuarios_caj ON usuarios_caj.idusu = ventas.registrado_por
LEFT JOIN ventas_rendido ON ventas_rendido.idventa = ventas.idventa AND ventas_rendido.estado = 1
WHERE ventas.estado <> 6
    AND mesas_atc.estado = 3
    AND ventas_rendido.idventa IS NULL
    AND mesas.estado_mesa = 8
    AND mesas.idmesa = $idmesa
ORDER BY ventas.idventa DESC
LIMIT 1;
";
$rspendrend = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
/*
$mensaje_modal="'";
$mensaje_modal.='MESA #'.$rsmesas->fields['numero_mesa'].' BLOQUEADA\',\';
$mensaje_modal.='';
$mensaje_modal.='<STRONG>Estado:</STRONG> PENDIENTE DE RENDICION<hr />';

$mensaje_modal.='<STRONG>Idmesa:</STRONG> '.$rsmesas->fields['idmesa'].'<br />';
$mensaje_modal.='<STRONG>Idatc:</STRONG> '.$rsmesas->fields['idatc'].'<br />';
$mensaje_modal.='<STRONG>Idventa (pendiente rendicion):</STRONG> '.$rspendrend->fields['idventa'].'<br />';
if(trim($rspendrend->fields['factura']) != ''){
    $mensaje_modal.='<STRONG>Factura (pendiente rendicion):</STRONG> '.$rspendrend->fields['factura'].'<br />';
}
$mensaje_modal.='<STRONG>Idcaja:</STRONG> '.$rspendrend->fields['idcaja'].'<br />';
$mensaje_modal.='<STRONG>Cajero:</STRONG> '.$rspendrend->fields['usuario_caja'].'<br />';
$mensaje_modal.='<STRONG>Usuario Venta:</STRONG> '.$rspendrend->fields['usuario_venta'].'<br />';
$mensaje_modal.="'";

echo $mensaje_modal;

/*$mensaje_modal="'";
$mensaje_modal.='MESA #'.$rsmesas->fields['numero_mesa'].' BLOQUEADA\',\'LA <STRONG>MESA #'.$rsmesas->fields['numero_mesa'].'</STRONG> SE ENCUENTRA EN ESTADO <STRONG>PENDIENTE DE RENDICION</STRONG><hr />';
$mensaje_modal.='<STRONG>Mesa Numero:</STRONG> '.$rsmesas->fields['numero_mesa'].'<br />';
$mensaje_modal.='<STRONG>Estado:</STRONG> PENDIENTE DE RENDICION<hr />';
//$mensaje_modal.='<a href="javascript:mas_info_mesa('.$idmesa.');void(0);" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Mas Informacion</a><br /><div id="masinfo_bloq"></div>';
$mensaje_modal.="'";
$mesa_accion='onClick="alerta_modal('.$mensaje_modal.');"';*/

?>
LA <STRONG>MESA # <?php echo $rspendrend->fields['numero_mesa']; ?> </STRONG> SE ENCUENTRA EN ESTADO <STRONG>PENDIENTE DE RENDICION</STRONG><hr />
<STRONG>Mesa Numero:</STRONG> <?php echo $rspendrend->fields['numero_mesa']; ?><br />
<STRONG>Estado:</STRONG> PENDIENTE DE RENDICION<hr />
<STRONG>Idmesa:</STRONG> <?php echo $rspendrend->fields['idmesa']; ?><br />
<STRONG>Idatc:</STRONG> <?php echo $rspendrend->fields['idatc']; ?><br />
<STRONG>Idventa (pendiente rendicion):</STRONG> <?php echo $rspendrend->fields['idventa']; ?><br />
<?php if (trim($rspendrend->fields['factura']) != '') { ?>
<STRONG>Factura (pendiente rendicion):</STRONG> <?php echo $rspendrend->fields['factura']; ?><br />
<?php } ?>
<STRONG>Idcaja:</STRONG> <?php echo $rspendrend->fields['idcaja']; ?><br />
<STRONG>Cajero:</STRONG> <?php echo $rspendrend->fields['usuario_caja']; ?><br />
<STRONG>Usuario Venta:</STRONG> <?php echo $rspendrend->fields['usuario_venta']; ?><br />
