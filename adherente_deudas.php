<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "168";
require_once("includes/rsusuario.php");


set_time_limit(450); // 450 15 minutos

$consulta = "
select * from adherentes_estadocuenta_actu order by fechahora desc limit 1
";
$rsultactu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if ($_GET['actu'] == 's') {


    // HAY QUE TRUNCAR LA TABLA adherente_estadocuenta antes de ejecutar las 2 consultotas
    $consulta = "truncate table adherente_estadocuenta";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualiza fecha para los indices
    $consulta = "
	update adherente_estadocuenta set fechasola = fechahora WHERE fechasola is null;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // llenar estado de cuenta
    $consulta = "
	INSERT INTO adherente_estadocuenta 
	( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim, idsucursal)
	SELECT registrado_el as fechahora, 'D' as tipomov, idcliente, idadherente, idserviciocom, deuda_global as monto, idventa, 
	NULL as idpago, idcta, idempresa , NULL as idpagodiscrim, sucursal
	from cuentas_clientes 
	where 
	cuentas_clientes.idcta not in (
					select idcta 
					from adherente_estadocuenta 
					where 
					idcta is not null
					and tipomov = 'D'
				 )		 
	and cuentas_clientes.estado <> 6
	order by idcta asc;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	INSERT INTO adherente_estadocuenta 
	( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim, idsucursal)
	SELECT registrado_el as fechahora, 'C' as tipomov, COALESCE(idcliente,0), idadherente, idservicio as idserviciocom, monto_asignado as monto, NULL as idventa, idpago, NULL, idempresa, unicorrffg as idpagodiscrim, (select sucursal from gest_pagos where idpago = adherentes_pagos_reg.idpago limit 1) as sucursal
	FROM adherentes_pagos_reg
	where
	adherentes_pagos_reg.monto_asignado > 0
	and unicorrffg not in (
							select idpagodiscrim 
							from adherente_estadocuenta 
							where 
							idpagodiscrim is not null
							and tipomov = 'C'
						  )		  
	and adherentes_pagos_reg.estado <> 6
	ORDER BY unicorrffg asc;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    //exit;


    // borra todo
    $consulta = "
	truncate table vta_estadocuenta;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // vuelve a rellenar
    $consulta = "
	INSERT INTO vta_estadocuenta (idcliente, idadherente, idserviciocom, tipomov, monto, idsucursal) 
	select adherente_estadocuenta.idcliente, adherente_estadocuenta.idadherente, adherente_estadocuenta.idserviciocom, 
	adherente_estadocuenta.tipomov, adherente_estadocuenta.monto, adherente_estadocuenta.idsucursal
	from adherente_estadocuenta	
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update vta_estadocuenta set monto_credito=monto, monto_debito = 0 where tipomov = 'C';
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	update vta_estadocuenta set monto_credito=0, monto_debito=monto*-1 where tipomov = 'D';
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // registra la ultima actualizacion
    $consulta = "
	INSERT INTO adherentes_estadocuenta_actu
	(fechahora, idempresa) 
	VALUES 
	('$ahora',$idempresa)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: adherente_deudas.php?listo=s");
    exit;

} // if($_GET['actu'] == 'S'){

if ($_GET['gen'] == 's') {




    // filtros
    //?agr=1&sald=T&idtipoad=&idserviciocom=&ord=SD
    //agrupacion
    if ($_GET['agr'] == 1 or intval($_GET['agr']) == 0) { // detallado
        $select_add = "idcliente, idadherente, idserviciocom,
		(select nomape from adherentes where adherentes.idadherente = adsub.idadherente) as nomape_adherente,
		(select nombre_servicio from servicio_comida where servicio_comida.idserviciocom = adsub.idserviciocom) as servicio_comida,
		(
			select descripcion 
			from adherentes 
			inner join adherentes_tipos_opcionales on adherentes_tipos_opcionales.idtipoad = adherentes.idtipoad
			where 
			adherentes.idadherente = adsub.idadherente
		 ) as seccion,
		";
        $group = "idcliente, idadherente, idserviciocom";
        $whereadd_saldo = "
	   and vta_estadocuenta.idadherente = adsub.idadherente
	   and vta_estadocuenta.idserviciocom = adsub.idserviciocom
		";
    }
    if ($_GET['agr'] == 2) { // por adherente
        $select_add = "idcliente, idadherente,
		(select nomape from adherentes where adherentes.idadherente = adsub.idadherente) as nomape_adherente,
		(
			select descripcion 
			from adherentes 
			inner join adherentes_tipos_opcionales on adherentes_tipos_opcionales.idtipoad = adherentes.idtipoad
			where 
			adherentes.idadherente = adsub.idadherente
		 ) as seccion,
		";
        $group = "idcliente, idadherente";
        $whereadd_saldo = "
		and vta_estadocuenta.idadherente = adsub.idadherente
		";
    }
    if ($_GET['agr'] == 3) { // por cliente
        $select_add = "idcliente,";
        $group = "idcliente";
    }


    // filtros de saldo
    if ($_GET['sald'] == 'T' or trim($_GET['sald']) == '') { // todo
        $filtro_saldo = "";
    }
    if ($_GET['sald'] == 'N') { // negativo
        $filtro_saldo = "
		and vta_estadocuenta_maestro.saldo < 0
		";
    }
    if ($_GET['sald'] == 'P') {  /// positivo
        $filtro_saldo = "
		and vta_estadocuenta_maestro.saldo > 0
		";
    }
    if ($_GET['sald'] == 'C') {  /// cero
        $filtro_saldo = "
		and vta_estadocuenta_maestro.saldo = 0
		";
    }
    // seccion
    if (intval($_GET['idtipoad']) > 0) {
        $idtipoadg = intval($_GET['idtipoad']);
        $whereaddsec = "
		and (
			select adherentes.idtipoad 
			from adherentes 
			inner join adherentes_tipos_opcionales on adherentes_tipos_opcionales.idtipoad = adherentes.idtipoad
			where 
			adherentes.idadherente = adsub.idadherente
		 ) = $idtipoadg
		 ";

    }
    // servicio comida
    if (intval($_GET['idserviciocom']) > 0) {
        $idserviciocomg = intval($_GET['idserviciocom']);
        $whereaddserv = "
		and (select idserviciocom from servicio_comida where servicio_comida.idserviciocom = adsub.idserviciocom) = $idserviciocomg
		 ";
    }
    // tipo cliente
    if (intval($_GET['idclientecat']) > 0) {
        $idclientecatg = intval($_GET['idclientecat']);
        $whereaddtipocli = "
		and (select cliente.idclientecat from cliente where cliente.idcliente = adsub.idcliente) = $idclientecatg
		 ";
    }
    // sucursal
    if (intval($_GET['idsucu']) > 0) {
        $idsucu = intval($_GET['idsucu']);

        // actualiza por sucursal
        // borra todo
        $consulta = "
		truncate table vta_estadocuenta;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // vuelve a rellenar
        $consulta = "
		INSERT INTO vta_estadocuenta (idcliente, idadherente, idserviciocom, tipomov, monto, idsucursal) 
		select adherente_estadocuenta.idcliente, adherente_estadocuenta.idadherente, adherente_estadocuenta.idserviciocom, 
		adherente_estadocuenta.tipomov, adherente_estadocuenta.monto, idsucursal 
		from adherente_estadocuenta	
		where
		idsucursal = $idsucu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update vta_estadocuenta set monto_credito=monto, monto_debito = 0 where tipomov = 'C';
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		update vta_estadocuenta set monto_credito=0, monto_debito=monto*-1 where tipomov = 'D';
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $whereaddsuc = "
		and adsub.idsucursal = $idsucu
		 ";
    } else {
        // borra todo
        $consulta = "
		truncate table vta_estadocuenta;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // vuelve a rellenar
        $consulta = "
		INSERT INTO vta_estadocuenta (idcliente, idadherente, idserviciocom, tipomov, monto, idsucursal) 
		select adherente_estadocuenta.idcliente, adherente_estadocuenta.idadherente, adherente_estadocuenta.idserviciocom, 
		adherente_estadocuenta.tipomov, adherente_estadocuenta.monto, idsucursal 
		from adherente_estadocuenta	
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update vta_estadocuenta set monto_credito=monto, monto_debito = 0 where tipomov = 'C';
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		update vta_estadocuenta set monto_credito=0, monto_debito=monto*-1 where tipomov = 'D';
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // orden
    if (trim($_GET['ord']) == 'SD') {
        $orderadd = "
		(
				coalesce(SUM(adsub.monto_credito),0)
				-
				coalesce(SUM(adsub.monto_debito),0)
		) desc
		";

    }
    if (trim($_GET['ord']) == 'SA') {
        $orderadd = "
		(
				coalesce(SUM(adsub.monto_credito),0)
				-
				coalesce(SUM(adsub.monto_debito),0)
		) asc
		";

    }
    if (trim($_GET['ord']) == 'TI') {
        $orderadd = "(select razon_social from cliente where cliente.idcliente = adsub.idcliente) ASC";
    }




    $consulta = "
		select * 
		from 
		(
			select 
				$select_add
				(select razon_social from cliente where cliente.idcliente = adsub.idcliente) as razon_social_cliente,
				(select nombre from cliente where cliente.idcliente = adsub.idcliente) as nombre_cliente,
				(select nombre from cliente where cliente.idcliente = adsub.idcliente) as apellido_cliente,
				(select telefono from cliente where cliente.idcliente = adsub.idcliente) as telefono_cliente,
				(select celular from cliente where cliente.idcliente = adsub.idcliente) as celular_cliente,
				(select nombre from sucursales where idsucu=adsub.idsucursal) as sucursalc,
				coalesce(SUM(adsub.monto_debito),0)  as monto_debito,
				coalesce(SUM(adsub.monto_credito),0)  as monto_credito,
				(
					coalesce(SUM(adsub.monto_credito),0)
					+
					coalesce(SUM(adsub.monto_debito),0)
				) as saldo
			
			
			from vta_estadocuenta as adsub
			
			where
			adsub.idcliente is not null
			
			$whereaddsec
			$whereaddserv
			$whereaddtipocli
			$whereaddsuc
			
			GROUP by $group
			order by 
			$orderadd
		) vta_estadocuenta_maestro
		where
		idcliente is not null
		$filtro_saldo
		";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
<script>
function generar_reporte(){
	$("#generar").hide();
	$("#reporte").html('Generando Reporte, favor aguarde....');	
	$("#form1").submit();	
}
function actualizar_reporte(){
	$("#generar").hide();
	$("#actualizar").hide();
	$("#reporte").html('Actualizando Datos, favor aguarde....');	
	document.location.href='adherente_deudas.php?actu=s';
}	
</script>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
	 	  <div class="colcompleto" id="contenedor">

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Deudas por Adherentes<br />
    		</span>
 		</div>
      <p>&nbsp;</p>
			  <input type="button" name="actualizar" id="actualizar" value="Actualizar Datos" onmouseup="actualizar_reporte();" /><br />
			  Ultima Actualizacion: <?php echo date("d/m/Y H:i:s", strtotime($rsultactu->fields['fechahora'])); ?>
			  
		 <p>&nbsp;</p>	  
      <form id="form1" name="form1" method="get" action="">
      
      <table width="900" border="1">
        <tbody>
          <tr>
            <td>Agrupacion:</td>
            <td>
              <select name="agr" id="agr">
                <option value="1" <?php if ($_GET['agr'] == 1) { ?> selected="selected"<?php } ?>>Detallado (Sin Agrupar)</option>
                <option value="2" <?php if ($_GET['agr'] == 2) { ?> selected="selected"<?php } ?>>Agrupado por Adherente</option>
                <option value="3" <?php if ($_GET['agr'] == 3) { ?> selected="selected"<?php } ?>>Agrupado por Cliente</option>
            </select></td>
          </tr>
          <tr>
            <td>Saldo:</td>
            <td><select name="sald" id="sald">
              <option value="N" <?php if ($_GET['sald'] == 'N') { ?> selected="selected"<?php } ?>>Saldos Negativos</option>
              <option value="P" <?php if ($_GET['sald'] == 'P') { ?> selected="selected"<?php } ?>>Saldos Positivos</option>
              <option value="C" <?php if ($_GET['sald'] == 'C') { ?> selected="selected"<?php } ?>>Saldo Cero</option>
              <option value="T" <?php if ($_GET['sald'] == 'T') { ?> selected="selected"<?php } ?>>Todo</option>
            </select></td>
          </tr>
          <tr>
            <td>Tipo Cliente:</td>
            <td><?php
        // consulta
        $consulta = "select * from cliente_categoria where estado = 1";

// valor seleccionado
if (isset($_GET['idclientecat'])) {
    $value_selected = htmlentities($_GET['idclientecat']);
} else {
    $value_selected = htmlentities($rs->fields['idclientecat']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'idclientecat',
'id_campo' => 'idclientecat',

'nombre_campo_bd' => 'cliente_categoria',
'id_campo_bd' => 'idclientecat', // idclientetipo

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => ''
];

// construye campo
echo campo_select($consulta, $parametros_array);

?></td>
          </tr>
          <tr>
            <td>Seccion:</td>
            <td><?php
// consulta
$consulta = "select * from adherentes_tipos_opcionales where estado = 1";

// valor seleccionado
if (isset($_GET['idtipoad'])) {
    $value_selected = htmlentities($_GET['idtipoad']);
} else {
    $value_selected = htmlentities($rs->fields['idtipoad']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'idtipoad',
'id_campo' => 'idtipoad',

'nombre_campo_bd' => 'descripcion',
'id_campo_bd' => 'idtipoad',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => ''
];

// construye campo
echo campo_select($consulta, $parametros_array);

?></td>
          </tr>
          <tr>
            <td>Servicio:</td>
            <td><?php
// consulta
$consulta = "select * from servicio_comida  where estado = 'A'";

// valor seleccionado
if (isset($_GET['idserviciocom'])) {
    $value_selected = htmlentities($_GET['idserviciocom']);
} else {
    $value_selected = htmlentities($rs->fields['idserviciocom']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'idserviciocom',
'id_campo' => 'idserviciocom',

'nombre_campo_bd' => 'nombre_servicio',
'id_campo_bd' => 'idserviciocom',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => ''
];

// construye campo
echo campo_select($consulta, $parametros_array);

?></td>
          </tr>
          <tr>
            <td>Orden:</td>
            <td><select name="ord" id="ord">
           	  <option value="SA" <?php if ($_GET['ord'] == 'SA') { ?>selected="selected"<?php } ?>>Saldo Ascendente</option>
              <option value="SD" <?php if ($_GET['ord'] == 'SD') { ?>selected="selected"<?php } ?>>Saldo Descendente</option>
              <option value="TI" <?php if ($_GET['ord'] == 'TI') { ?>selected="selected"<?php } ?>>Titular</option>
            </select></td>
          </tr>
          <tr>
            <td>Sucursal:</td>
            <td>
<?php

// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</td>
          </tr>
        </tbody>
      </table>
      <p>&nbsp;</p>
      <p align="center">
        <input type="button" name="generar" id="generar" value="Generar Reporte" onmouseup="generar_reporte();" />
        <input type="hidden" name="gen" id="gen" value="s" />
      </p>
      </form>
      <p>&nbsp;</p>
<div id="reporte">
<?php if ($_GET['gen'] == 's') {?>
      <p><br />
      </p>
      <hr /><br />

      			<table width="980" border="1">
      			    <tr align="center" bgcolor="#F8FFCC">
      			      <td><strong>Titular</strong></td>
      			      <td><strong>Adherente</strong></td>
      			      <td><strong>Servicio</strong></td>
      			      <td><strong>Seccion</strong></td>
      			      <td><strong>Contacto</strong></td>
      			      <td><strong>Debitos</strong></td>
      			      <td><strong>Creditos</strong></td>
      			      <td><strong>Saldo</strong></td>
					    <td><strong>Sucursal</strong></td>
   			        </tr>
				<tbody>
<?php while (!$rs->EOF) {
    if ($rs->fields['saldo'] < 0) {
        $color = "#FF0000";
    } else {
        $color = "#000";
    }
    $debacum += $rs->fields['monto_debito'];
    $credacum += $rs->fields['monto_credito'];
    $saldacum += $rs->fields['saldo'];

    if ($saldacum < 0) {
        $colorac = "#FF0000";
    } else {
        $colorac = "#000";
    }
    ?>
      			    <tr>
      			      <td><?php echo $rs->fields['idcliente']; ?>-<?php echo $rs->fields['razon_social_cliente']; ?></td>
      			      <td><?php if ($rs->fields['idadherente'] > 0) { ?><?php echo $rs->fields['idadherente']; ?>-<?php echo $rs->fields['nomape_adherente']; ?><?php } ?></td>
      			      <td><?php echo $rs->fields['servicio_comida']; ?></td>
      			      <td><?php echo $rs->fields['seccion']; ?></td>
      			      <td><?php echo $rs->fields['telefono_cliente']; ?> <?php echo $rs->fields['celular_cliente']; ?></td>
      			      <td align="right" style="color:#FF0000;"><?php echo formatomoneda($rs->fields['monto_debito'], 4, 'N'); ?></td>
      			      <td align="right"  style="color:#000;"><?php echo formatomoneda($rs->fields['monto_credito'], 4, 'N'); ?></td>
      			      <td align="right" style="color:<?php echo $color; ?>;"><?php echo formatomoneda($rs->fields['saldo'], 4, 'N'); ?></td>
					   <td><?php echo $rs->fields['sucursalc']; ?></td>
   			        </tr>
<?php $rs->MoveNext();
} ?>
      			    <tr>
      			      <td colspan="5" bgcolor="#EEEEEE"><strong>Total</strong></td>
      			      <td align="right" bgcolor="#EEEEEE" style="color:#FF0000;">-<?php echo formatomoneda($debacum, 4, 'N'); ?></td>
      			      <td align="right" bgcolor="#EEEEEE"  style="color:#000;"><?php echo formatomoneda($credacum, 4, 'N'); ?></td>
      			      <td align="right" bgcolor="#EEEEEE" style="color:<?php echo $colorac; ?>;"><?php echo formatomoneda($saldacum, 4, 'N'); ?></td>
					    <td></td>
   			      </tr>
   			      </tbody>
		    </table>

      			<br /><br /><br /><br /><br />
	<?php } ?>		
</div>


		  </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>