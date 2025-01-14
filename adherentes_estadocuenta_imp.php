<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

if (isset($_GET['s']) && trim($_GET['s']) == 'form1' && intval($_GET['id']) == 0) {

    //filtros
    $whereadd = "";
    $orderby = "";
    $hayfiltro = "N";
    if (trim($_GET['nombre']) != '') {
        $nombre = antisqlinyeccion(trim($_GET['nombre']), "like");
        $whereadd .= " and cliente.nombre like '%$nombre%' ".$saltolinea;
        $orderby .= " cliente.nombre asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['apellido']) != '') {
        $apellido = antisqlinyeccion(trim($_GET['apellido']), "like");
        $whereadd .= " and cliente.apellido like '%$apellido%' ".$saltolinea;
        $orderby .= " cliente.apellido asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['razon_social']) != '') {
        $razon_social = antisqlinyeccion(trim($_GET['razon_social']), "like");
        $whereadd .= " and cliente.razon_social like '%$razon_social%' ".$saltolinea;
        $orderby .= " cliente.razon_social asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['adherente']) != '') {
        $adherente = antisqlinyeccion(trim($_GET['adherente']), "like");
        $whereadd .= " and cliente.idcliente in (select idcliente from adherentes where nomape like '%$adherente%' )".$saltolinea;
        $orderby .= " cliente.razon_social asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['documento']) != '') {
        $documento = antisqlinyeccion(trim($_GET['documento']), "text");
        $whereadd .= " and cliente.documento = $documento ".$saltolinea;
        $orderby .= " cliente.documento asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['ruc']) != '') {
        $ruc = antisqlinyeccion(trim($_GET['ruc']), "text");
        $whereadd .= " and cliente.ruc = $ruc ".$saltolinea;
        $orderby .= " cliente.ruc asc, ".$saltolinea;
        $hayfiltro = "S";
    }





    if ($hayfiltro == "S") {
        $consulta = "
		select * 
		from cliente 
		where 
		idcliente is not null
		$whereadd
		order by 
		$orderby
		cliente.idcliente asc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
    }

}

if (isset($_GET['id']) && intval($_GET['id']) > 0 && (!isset($_GET['s']))) {

    $idcliente = intval($_GET['id']);
    $consulta = "
	select * 
	from cliente 
	where 
	idcliente = $idcliente
	and idempresa = $idempresa
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //filtros
    $whereadd = "";
    $orderby = "";
    // filtro fijo
    if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
        $desde = date("Y-m-").'01';
        $hasta = date("Y-m-d");
    } else {
        $desde = date("Y-m-d", strtotime($_GET['desde']));
        $hasta = date("Y-m-d", strtotime($_GET['hasta']));
    }

    // otros filtos
    if (intval($_GET['adh']) > 0) {
        $idadherente = antisqlinyeccion(trim($_GET['adh']), "int");
        $whereadd .= " and cuentas_clientes.idadherente = $idadherente ".$saltolinea;
    }
    if (intval($_GET['sc']) > 0) {
        $idserviciocom = antisqlinyeccion(trim($_GET['sc']), "int");
        $whereadd .= " and cuentas_clientes.idserviciocom = $idserviciocom ".$saltolinea;
    }




    $consulta = "
	SELECT cuentas_clientes.idcta, date(prox_vencimiento) as fecha, 
	(select date(cuentas_clientes_pagos.fecha_pago) as fecha_pago from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta order by cuentas_clientes_pagos.fecha_pago desc limit 1) as fecha_pago, 
	cuentas_clientes.deuda_global, 
	(select sum(cuentas_clientes_pagos.monto_abonado) from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta ) as monto_abonado, cuentas_clientes.saldo_activo, 
	cuentas_clientes.idcliente, 
	cuentas_clientes.idadherente, 
	cuentas_clientes.idserviciocom, 
	(select nombre_servicio from servicio_comida where servicio_comida.idserviciocom = cuentas_clientes.idserviciocom) as servicio,
	(select nomape from adherentes where adherentes.idadherente = cuentas_clientes.idadherente) as adherente,
	cuentas_clientes.idventa 
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	and cuentas_clientes.idempresa = $idempresa
	and date(cuentas_clientes.prox_vencimiento) >= '$desde'
	and date(cuentas_clientes.prox_vencimiento) <= '$hasta'
	$whereadd
	order by date(prox_vencimiento) asc
	";
    //echo $consulta;
    //exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
</head>
<body bgcolor="#FFFFFF">
      <br /><br />

    			<strong>Estado de Cuenta por Adherente</strong><br />

<br /><hr /><br />

<?php if (isset($_GET['id']) && intval($_GET['id']) > 0 && (!isset($_GET['s']))) {



    ?>


<table width="980" border="1" style="border-collapse:collapse;">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Venta N°</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Adherente</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Servicio</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Monto Ticket</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Monto Abonado</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Fec. Pago</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Deuda</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $saldoacum += $rs->fields['saldo_activo'];
    $idventa = $rs->fields['idventa'];
    ?>
    <tr>
      <td align="center" bgcolor="#EEEEEE"><?php echo $rs->fields['idventa']; ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php echo $rs->fields['adherente']; ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php if ($rs->fields['servicio'] != '') {
          echo $rs->fields['servicio'];
      } else { ?>N/A<?php } ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php echo formatomoneda($rs->fields['deuda_global']); ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php echo formatomoneda($rs->fields['monto_abonado']); ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php if ($rs->fields['fecha_pago'] != '') {
          echo date("d/m/Y", strtotime($rs->fields['fecha_pago']));
      } ?></td>
      <td align="center" bgcolor="#EEEEEE"><?php echo formatomoneda($rs->fields['saldo_activo']); ?></td>
    </tr>
    <tr>
      <td colspan="8" align="left">
<?php
$consulta = "
select sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal, productos.descripcion as producto, productos.idprod_serial
from ventas_detalles 
inner join productos on productos.idprod_serial = ventas_detalles.idprod 
where
idventa = $idventa
and idempresa = $idempresa
group by productos.idprod_serial
order by productos.descripcion
";
    $rsvd = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsvd->EOF) {


        ?>&rarr; <?php echo formatomoneda($rsvd->fields['cantidad'], 4, 'N'); ?> x <?php echo $rsvd->fields['producto']; ?> | Gs. <?php echo formatomoneda($rsvd->fields['subtotal']); ?><br />

<?php $rsvd->MoveNext();
    } ?>
</td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<p align="left" style="font-size:16px;">Titular:</p>
<p align="left" style="font-size:16px;">
  <?php
    $consulta = "
	SELECT sum(pagos_afavor_adh.saldo) as saldo_activo
	FROM pagos_afavor_adh
	where 
	pagos_afavor_adh.idcliente = $idcliente
	and pagos_afavor_adh.saldo > 0
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Saldo a Favor: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?><br />
<?php
        $consulta = "
	SELECT sum(cuentas_clientes.saldo_activo) as saldo_activo
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	and cuentas_clientes.estado <> 6
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Deuda Total Titular: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?> </p>
<p align="left" style="font-size:16px;">Por Adherente</p>
<p align="left" style="font-size:16px;">
  <?php
        $consulta = "
	SELECT sum(pagos_afavor_adh.saldo) as saldo_activo
	FROM pagos_afavor_adh
	where 
	pagos_afavor_adh.idcliente = $idcliente
	and pagos_afavor_adh.saldo > 0
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Saldo a Favor: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?><br />
<?php
        $consulta = "
	SELECT sum(cuentas_clientes.saldo_activo) as saldo_activo
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	and cuentas_clientes.estado <> 6
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Deuda Total Titular: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?> </p>
<p align="left" style="font-size:16px;">Por Servicio de cada adherente </p>
<p align="left" style="font-size:16px;">
  <?php
        $consulta = "
	SELECT sum(pagos_afavor_adh.saldo) as saldo_activo
	FROM pagos_afavor_adh
	where 
	pagos_afavor_adh.idcliente = $idcliente
	and pagos_afavor_adh.saldo > 0
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Saldo a Favor: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?><br />
<?php
        $consulta = "
	SELECT sum(cuentas_clientes.saldo_activo) as saldo_activo
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	and cuentas_clientes.estado <> 6
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Deuda Total Titular: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?>
</p>
<br /><br />
<?php } ?>
</body>
</html>