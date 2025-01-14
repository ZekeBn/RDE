<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

/*
solucionar problema de vice que no traia registros
update `cuentas_clientes` set prox_vencimiento=registrado_el where prox_vencimiento is null;

INSERT INTO cuentas_clientes_det
(idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado)
select cuentas_clientes.idcta, 1, (select ventas.fecha from ventas where idventa = cuentas_clientes.idventa), cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
from cuentas_clientes
where
idcta not in (select idcta from cuentas_clientes_det);


$consulta="
INSERT INTO cuentas_clientes_det
(idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado)
select cuentas_clientes.idcta, 1, (select ventas.fecha from ventas where idventa = cuentas_clientes.idventa), cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
from cuentas_clientes
where
idcta not in (select idcta from cuentas_clientes_det);
";
$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));

$consulta="
update `cuentas_clientes` set prox_vencimiento=registrado_el where prox_vencimiento is null;
";
$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));
*/
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
	SELECT cuentas_clientes.idcta, date(ventas.fecha) as fecha, 
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
	inner join ventas on ventas.idventa = cuentas_clientes.idventa
	where 
	cuentas_clientes.idcliente = $idcliente
	and cuentas_clientes.estado <> 6
	and cuentas_clientes.idempresa = $idempresa
	and date(ventas.fecha) >= '$desde'
	and date(ventas.fecha) <= '$hasta'
	$whereadd
	order by date(ventas.fecha) asc
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
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Estado de Cuenta por Adherente<br />
    		</span>
 		</div>
<br /><hr /><br />
<?php if (!isset($_GET['id'])) { ?>
<form id="form1" name="form1" method="get" action="">

<table width="400" border="1">
  <tbody>
    <tr>
      <td><strong>Nombre:</strong></td>
      <td>
        <input type="text" name="nombre" id="nombre" value="<?php echo antixss($_GET['nombre']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Apellido:</strong></td>
      <td><input type="text" name="apellido" id="apellido" value="<?php echo antixss($_GET['apellido']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Razon Social Titular:</strong></td>
      <td><input type="text" name="razon_social" id="razon_social" value="<?php echo antixss($_GET['razon_social']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>RUC:</strong></td>
      <td><input type="text" name="ruc" id="ruc" value="<?php echo antixss($_GET['ruc']); ?>" /></td>
    </tr>
	   <tr>
      <td><strong>Adherente:</strong></td>
      <td><input type="text" name="adherente" id="adherente" value="<?php echo antixss($_GET['adherente']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Documento:</strong></td>
      <td><input type="text" name="documento" id="documento" value="<?php echo antixss($_GET['documento']); ?>" /></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="Buscar" id="Buscar" value="Buscar" />
        <input type="hidden" name="s" id="s" value="form1" /></td>
      </tr>
  </tbody>
</table>
</form>
<br /><hr /><br />
<?php } ?>
<?php if (isset($_GET['s']) && trim($_GET['s']) == 'form1' && intval($_GET['id']) == 0) { ?>
<?php if ($rs->fields['idcliente'] > 0) {?>


<table width="697" border="1">
  <tbody>
<?php while (!$rs->EOF) {

    $idcliente = intval($rs->fields['idcliente']);?>
    <tr>
    
		  <td width="280" height="33" align="center" bgcolor="#F8FFCC"><strong>Razon Social Titular</strong></td>
      		<td width="316" align="center" bgcolor="#F8FFCC"><strong>Adherentes</strong></td>
      <td bgcolor="#F8FFCC">&nbsp;</td>
    </tr>
    <tr>
      <td><?php echo $rs->fields['nombre'].' '.$rs->fields['apellido'];?></td>
      <td> <?php
        $buscar = "select nomape from adherentes where idcliente=$idcliente order by nomape asc";
    $rsadh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tsd = $rsadh->RecordCount();
    if ($tsd > 0) {
        $son = '';
        $paso = 0;
        while (!$rsadh->EOF) {
            $paso = $paso + 1;
            if ($paso == 1) {
                $son = '';
            } else {
                $son = $son.' | ';
            }
            $son = $son.$rsadh->fields['nomape'];
            $rsadh->MoveNext();
        }

        ?>
        <?php echo $son;?>
		  <?php }?></td>
      <td width="233"><input type="button" name="button" id="button" value="Seleccionar" onclick="document.location.href='adherentes_estadocuenta.php?id=<?php echo $rs->fields['idcliente']; ?>'" /></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<?php } else {?>
<p align="center">* No se encontraron registros con los datos de busqueda.</p>
<?php } ?>

<?php } ?>
<?php if (isset($_GET['id']) && intval($_GET['id']) > 0 && (!isset($_GET['s']))) {



    ?>
<form id="form2" name="form2" method="get" action="">
<table width="400" border="1">
  <tbody>
    <tr>
      <td align="center"><strong>Cliente</strong></td>
      <td colspan="3" align="center"><?php echo $rscli->fields['nombre'];?> <?php echo $rscli->fields['apellido'];?>&nbsp;</td>
      <td width="10" align="center"><input type="button" name="button2" id="button2" value="Cambiar" onmouseup="document.location.href='adherentes_estadocuenta.php'" /></td>
    </tr>
  </tbody>
</table>
 <br />
<table width="900" border="1">
  <tbody>
    <tr>
      <td><strong>Adherente</strong></td>
      <td><?php

    $consulta = "
	select * from adherentes where idcliente = $idcliente order by nomape asc
	";
    $rsad = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
        <select name="adh" id="adh">
          <option value="0">Todos</option>
          <?php while (!$rsad->EOF) { ?>
          <option value="<?php echo $rsad->fields['idadherente']; ?>" <?php if ($rsad->fields['idadherente'] == $_GET['adh']) { ?>selected="selected"<?php } ?>><?php echo $rsad->fields['nomape']; ?></option>
          <?php $rsad->MoveNext();
          }?>
          </select></td>
      <td><strong>Servicio</strong></td>
      <td><?php

    $consulta = "
	select * from servicio_comida order by nombre_servicio asc  
	";
    $rssc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?><select name="sc" id="sc">
        <option value="0">Todos</option>
        <?php while (!$rssc->EOF) { ?>
        <option value="<?php echo $rssc->fields['idserviciocom']; ?>" <?php if ($rssc->fields['idserviciocom'] == $_GET['sc']) { ?>selected="selected"<?php } ?>><?php echo $rssc->fields['nombre_servicio']; ?></option>
        <?php $rssc->MoveNext();
        }?>
        </select></td>
    </tr>
    <tr>
      <td><strong>Desde</strong></td>
      <td>
        <input type="date" name="desde" id="desde" value="<?php echo $desde; ?>" /></td>
      <td><strong>Hasta</strong></td>
      <td><input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" /></td>
    </tr>
  </tbody>
</table>

<p>&nbsp;</p>
<p align="center">
  <input type="submit" name="submit" id="submit" value="Filtrar" />
  <input type="hidden" name="id" id="id" value="<?php echo intval($_GET['id']); ?>" />
</p>
</form>
  <br /><hr /><br />
<p align="center"><a href="adherentes_estadocuenta_csv.php?adh=<?php echo htmlentities($_GET['adh']); ?>&sc=<?php echo htmlentities($_GET['sc']); ?>&desde=<?php echo htmlentities($_GET['desde']); ?>&hasta=<?php echo htmlentities($_GET['hasta']); ?>&id=<?php echo htmlentities($_GET['id']); ?>">[Descargar]</a></p>
<br /><br />
<table width="980" border="1">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Venta N�</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Adherente</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Servicio</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Monto Ticket</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Monto Abonado</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Fec. Pago</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Saldo</strong></td>
      <td width="50" align="center" bgcolor="#F8FFCC"><strong>[Detalle]</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $saldoacum += $rs->fields['saldo_activo'];
    ?>
    <tr>
      <td align="center"><?php echo $rs->fields['idventa']; ?></td>
      <td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
      <td align="center"><?php echo $rs->fields['adherente']; ?></td>
      <td align="center"><?php echo $rs->fields['servicio']; ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['deuda_global']); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['monto_abonado']); ?></td>
      <td align="center"><?php if ($rs->fields['fecha_pago'] != '') {
          echo date("d/m/Y", strtotime($rs->fields['fecha_pago']));
      } ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['saldo_activo']); ?></td>
      <td align="center"><a href="adherentes_estadocuenta_tkdet.php?v=<?php echo $rs->fields['idventa']; ?>" target="_blank">[Detalle]</a></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table><br /><br />
<p align="center" style="font-size:16px;">
Saldo Acumulado (Filtros): <?php echo formatomoneda($saldoacum); ?><br />
<?php
    $consulta = "
	SELECT sum(cuentas_clientes.saldo_activo) as saldo_activo
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
Saldo Total Titular: <?php echo formatomoneda($rssact->fields['saldo_activo']); ?>
</p><br /><br />
<?php } ?>


          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>