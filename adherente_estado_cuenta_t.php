<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "163";
require_once("includes/rsusuario.php");




if (isset($_POST['busqueda'])) {
    //hay una busqueda
    //Busqueda x titular
    //filtros
    $codigo = intval($_POST['codigo']);
    $busqueda = antisqlinyeccion($_POST['busqueda'], 'text');
    $medio = trim($_POST['medio']);
    $bustrim = str_replace("'", "", $busqueda);
    if ($medio == 'S') {
        //titular

        if ($codigo > 0) {
            //Vemos segun el tipo de busqueda seleccionado
            $add = " and cliente.codclie=$codigo";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
        if ($busqueda != 'NULL') {
            $add = " and cliente.razon_social like ('%$bustrim%')";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
    } else {
        //adherente
        if ($codigo > 0) {
            //Vemos segun el tipo de busqueda seleccionado
            $add = " and adherentes.codadhe=$codigo";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
        if ($busqueda != 'NULL') {
            $add = " and idcliente in(select idcliente from adherentes where nomape like '%$bustrim%' )";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
    }

    if ($hayfiltro == "S") {
        $consulta = "
		select * 
		from cliente 
		where 
		idcliente is not null
		$add
		order by 
		$orderby
		cliente.idcliente asc
		";
        $rsb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tfound = $rsb->RecordCount();
    }




}
if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


$idcliente = intval($_GET['id']);
if (isset($_GET['id']) && intval($_GET['id']) > 0) {


    // llenar estado de cuenta
    $consulta = "
INSERT INTO adherente_estadocuenta 
( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
SELECT registrado_el as fechahora, 'D' as tipomov, idcliente, idadherente, idserviciocom, deuda_global as monto, idventa, 
NULL as idpago, idcta, idempresa , NULL as idpagodiscrim
from cuentas_clientes 
where 
idcta not in (
				select idcta 
				from adherente_estadocuenta 
				where 
				idempresa = cuentas_clientes.idempresa 
				and adherente_estadocuenta.idcliente = $idcliente
				and idcta is not null
			 )
and cuentas_clientes.estado <> 6
and cuentas_clientes.idcliente = $idcliente
order by registrado_el asc, idcta asc;
;
";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // pagos viejos
    $consulta = "
INSERT INTO adherente_estadocuenta 
( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
SELECT registrado_el as fechahora, 'C' as tipomov, idcliente, idadherente, idservicio as idserviciocom, monto_asignado as monto, NULL as idventa, idpago, NULL, idempresa, unicorrffg as idpagodiscrim
FROM adherentes_pagos_reg
where
monto_asignado > 0 
and unicorrffg not in (
						select idpagodiscrim 
						from adherente_estadocuenta 
						where 
						adherente_estadocuenta.idcliente = $idcliente
						and idpagodiscrim is not null
					  )
and adherentes_pagos_reg.estado <> 6
and adherentes_pagos_reg.idcliente = $idcliente
ORDER BY fecha ASC, unicorrffg asc;
";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // pagos nuevos
    $consulta = "
INSERT INTO adherente_estadocuenta 
( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim, idpago_afavor)
SELECT fechahora, 'C' as tipomov, idcliente, idadherente, idserviciocom, monto, NULL as idventa, idpago, NULL, 1, NULL as idpagodiscrim, idpago_afavor
FROM pagos_afavor_adh
where
monto > 0
and pagos_afavor_adh.idpago_afavor not in (
						select idpago_afavor 
						from adherente_estadocuenta 
						where 
						adherente_estadocuenta.idcliente = $idcliente
						and idpago_afavor is not null
					  )
and pagos_afavor_adh.idpago not in (
						select idpago 
						from adherente_estadocuenta 
						where 
						adherente_estadocuenta.idcliente = $idcliente
						and idpago_afavor is not null
					  )
and pagos_afavor_adh.estado = 1 
and pagos_afavor_adh.idcliente = $idcliente
ORDER BY fechahora ASC, idpago_afavor asc;
";
    //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


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
        $whereadd .= " and adherente_estadocuenta.idadherente = $idadherente ".$saltolinea;
        $buscar = "Select nomape from adherentes where idempresa=$idempresa and idadherente=$idadherente";
        $rsadcl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $adherente = $rsadcl->fields['nomape'];
    } elseif (trim($_GET['adh']) == 'st') {
        $whereadd .= " and adherente_estadocuenta.idadherente = 0 ".$saltolinea;
        $adherente = 'SOLO TITULAR';
    } else {
        $adherente = 'TODOS';

    }
    if (intval($_GET['sc']) > 0) {
        $idserviciocom = antisqlinyeccion(trim($_GET['sc']), "int");
        $whereadd .= " and adherente_estadocuenta.idserviciocom = $idserviciocom ".$saltolinea;
        $buscar = "Select nombre_servicio from servicio_comida where idserviciocom=$idserviciocom";
        $rsff = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $servicios = $rsff->fields['nombre_servicio'];
    } else {
        $servicios = 'TODOS';
    }


    $buscar = "
			 Select (
    				select sum(monto) from adherente_estadocuenta where tipomov = 'D' and fechahora < '$desde' and idcliente=$idcliente 
					$whereadd
						) as montodeb,
					(   
   					select sum(monto) from adherente_estadocuenta where tipomov = 'C' and fechahora < '$desde' and idcliente=$idcliente
					$whereadd
    				) as montocred  
    
   					from adherente_estadocuenta where idcliente=$idcliente 
					$whereadd
					limit 1 
	";
    $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $saldocredito = intval($rssal->fields['montocred']);
    $saldodebito = intval($rssal->fields['montodeb']);
    $saldoinicial = $saldocredito - $saldodebito;
    $saldoacum = $saldoinicial;

    $consulta = "
	select *
	from adherente_estadocuenta
	where 
	idcliente=$idcliente 
	and date(fechahora) between '$desde' and '$hasta'
	$whereadd
	order by fechahora asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;
    $tr = $rs->RecordCount();

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
			<div align="center">
    				<div align="center">
					  <form id="fd" action='adherente_estado_cuenta_t.php' method="post">
							<table width="633" border="1">
								<tr>
									<td width="177"><input type="text" name="codigo" id="codigo" style="height: 40px;width:100% " placeholder="Ingrese codigo a buscar" disabled="disabled" />

									 </td>
									<td width="242" >
										<input type="text" name="busqueda" id="busqueda" style="height: 40px;width:100% " placeholder="Ingrese texto a buscar" required="required" /></td>
									<td width="122">
										<input type="radio" name="medio" value="S" checked="checked" /><strong>TITULAR <br />
										<input type="radio" name="medio" value="N" />ADHERENTE</strong>
									</td>
									<td width="64" align="center"><input type="submit" value="Buscar" /></td>
								</tr>
			 				 </table>
						</form>
					</div>
					<br />
	  				 <?php if (isset($_POST['busqueda']) && $tfound > 0) { ?>
			  <div align="center">
							<table width="526" border="1">
							  <tbody>
								  <tr>
								  <td width="70" height="30" align="center" bgcolor="#F8FFCC"><strong>Nombre</strong></td>
								  <td width="71" align="center" bgcolor="#F8FFCC"><strong>Apellido</strong></td>
								  <td width="157" align="center" bgcolor="#F8FFCC"><strong>Razon Social Titular</strong></td>
								  <td width="112" bgcolor="#F8FFCC"><strong>Adherentes</strong></td>
								  <td bgcolor="#F8FFCC">&nbsp;</td>
								</tr>
									<?php while (!$rsb->EOF) {
									    $idc = intval($rsb->fields['idcliente']);?>
									<tr>
									  <td><?php echo $rsb->fields['nombre'];?></td>
									  <td><?php echo $rsb->fields['apellido'];?></td>
									  <td><strong><?php echo $rsb->fields['razon_social'];?></strong></td>
									  <td><strong>
										<?php
									      $buscar = "select nomape from adherentes where idcliente=$idc order by nomape asc";
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
										<?php }?>
									  </strong></td>
									 <td width="82"><input type="button" name="button" id="button" value="Seleccionar" onclick="document.location.href='adherente_estado_cuenta_t.php?id=<?php echo $rsb->fields['idcliente']; ?>'" /></td>
								 </tr>
									<?php $rsb->MoveNext();
									} ?>
							  </tbody>
				</table>
			  </div>
	  				 <?php } else {?>
	   	  					<?php if (isset($_POST['busqueda']) && $tfound == 0) { ?>
	  						 <span class="resaltarojomini">No se encontraron resultados de la busqueda</span>
	   					<?php }?>
	   				<?php }?>
		    </div>
  			 <br />
			<hr />
<?php if (($idcliente > 0)) {

    $buscar = "Select * from cliente where idempresa=$idempresa and idcliente=$idcliente"				;
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    ?>	

	
			  <form id="form2" name="form2" method="get" action="adherente_estado_cuenta_t.php">
<table width="400" border="1">
  <tbody>
    <tr>
      <td align="center"><strong>Cliente</strong></td>
      <td align="center"><?php echo $rscli->fields['razon_social'];?>&nbsp;</td>
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
          <option value="0" <?php if ($_GET['adh'] == 0) { ?> selected="selected"<?php } ?>>Todos</option>
          <option value="st" <?php if ($_GET['adh'] == 'st') { ?> selected="selected"<?php } ?>>Solo Titular (excluir adherentes)</option>
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
</p><br />
</form>	
<hr />
<?php

    if ($tr > 0) {?>			  
			  <p align="center"><a href="adherente_estado_cuenta_t_pdf.php?adh=<?php echo htmlentities($_GET['adh']);?>&sc=<?php echo intval($_GET['sc']);?>&desde=<?php echo htmlentities($_GET['desde']);?>&hasta=<?php echo htmlentities($_GET['hasta']);?>&submit=Filtrar&id=<?php echo intval($_GET['id']);?>" target="_blank"><img src="img/pdf.png" width="40" height="40" title="Descargar"/> </a></p>
		    <br />
<table width="600" border="1">
  <tbody>
    <tr>
      <td colspan="2" align="center" bgcolor="#FFFEC4"><strong>Titular: <?php echo $rscli->fields['nombre'];?> <?php echo $rscli->fields['apellido'];?>&nbsp;</strong></td>
      </tr>
    <tr>
      <td><strong>Desde: <?php echo date("d/m/Y", strtotime($desde)) ?></strong></td>
      <td width="50%"><strong>Hasta:  <?php echo date("d/m/Y", strtotime($hasta)) ?></strong></td>
      </tr>
    <tr>
      <td><strong>Adherente:<?php echo $adherente?></strong></td>
      <td><strong>Servicio: <?php echo $servicios?></strong></td>
      </tr>
  </tbody>
</table>
<p>&nbsp;</p>
<table width="800" class="tablalinda2">
		<tr>
				<td width="182" height="28" align="left" bgcolor="#FFFEC4"><strong>Fecha/Hora</strong></td>
				
				<td width="83" align="right" bgcolor="#FFFEC4"><strong>D&eacute;bito</strong></td>
				<td width="116" align="right" bgcolor="#FFFEC4"><strong>Cr&eacute;dito</strong></td>
				<td width="116" align="right" bgcolor="#FFFEC4"><strong>Saldo Acumulado</strong></td>
		  </tr>
			<?php

        ?>
			<tr>
			  <td height="32"><strong>Saldo Anterior</strong></td>
				<td></td>
			  <td></td>
				<td align="right" <?php if ($saldoinicial < 0) {?>style="color:#FF0000;"<?php } ?>><strong><?php if ($saldoinicial < 0) {?>-<?php } ?><?php echo formatomoneda($saldoinicial, 4, 'N')?></strong></td>
		  </tr>
			<?php while (!$rs->EOF) {
			    $idventa = intval($rs->fields['idventa']);
			    $idpago = intval($rs->fields['idpago']);
			    $tipo = $rs->fields['tipomov'];

			    if ($tipo == 'C') {
			        $credito = $rs->fields['monto'];
			        $debito = '';
			        $tven = 0;
			    }
			    if ($tipo == 'D') {
			        $credito = '';
			        $debito = $rs->fields['monto'];
			        $buscar = "Select  sum(cantidad) as cantidad,descripcion,idprod_serial,sum(subtotal) as subtotal
				from ventas_detalles
				inner join productos on productos.idprod_serial=ventas_detalles.idprod
				where ventas_detalles.idventa=$idventa group by idprod_serial order by descripcion asc";
			        $rsven = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
			        $tven = $rsven->RecordCount();

			    }
			    $saldoacum = $saldoacum + (intval($credito) - intval($debito));
			    ?>
			
			<tr style="border-top: 1px solid #000;">
			  <td align="left" ><strong><?php echo date("d/m/Y H:i", strtotime($rs->fields['fechahora']));?></strong><?php if ($debito != '') { ?> - Ticket: <?php echo $idventa?><?php } else {  ?> - Pago: <?php echo $idpago ?><?php } ?></td>
		
				<td align="right"><span style="color:#ff0000;"><?php if ($debito != '') {
				    echo '-'.formatomoneda($debito);
				}?></span></td>
			  <td align="right"><?php if ($credito != '') {
			      echo formatomoneda($credito);
			  }?></td>
				<td align="right" <?php if ($saldoacum < 0) {?>style="color:#FF0000;"<?php } ?>><?php echo formatomoneda($saldoacum)?></td>
			</tr>
			<?php if ($tven > 0) {?>
			<tr>
				
			  <td height="30" align="left">
					<div style="float: left; margin-left: 20px; border: 1px solid #EDEDED">
					<table width="350">
					<?php while (!$rsven->EOF) {?>
						<tr>
							<td width="180" align="left"><?php echo $rsven->fields['descripcion']?></td>
							<td width="18" align="left">-></td>
							<td width="32" align="center"><?php echo formatomoneda($rsven->fields['cantidad'], 'f');?></td>
							<td width="37" align="right"><?php echo formatomoneda($rsven->fields['subtotal'], 'f');?></td>
						</tr>	
					<?php $rsven->MoveNext();
					}?>
					</table>
					</div>
			  </td>
			  <td height="30" colspan="3" align="left">&nbsp;</td>
		  </tr>
			
			<?php }?>
			<?php $rs->MoveNext();
			}?>
			<tr>
			  <td height="30" align="left" bgcolor="#DCDCDC"><strong>Total:</strong></td>
			  <td height="30" align="left" bgcolor="#DCDCDC">&nbsp;</td>
			  <td height="30" align="left" bgcolor="#DCDCDC">&nbsp;</td>
			  <td height="30" align="right" bgcolor="#DCDCDC"><strong><?php echo formatomoneda($saldoacum)?></strong></td>
	      </tr>
		</table>
	<?php } else { ?>	<br />			
    <p align="center">* Sin registros para los filtros seleccionados.</p><br />
    <?php }?>
	<?php }?>			
				
	<br />



		  </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>