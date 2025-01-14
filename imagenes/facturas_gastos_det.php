<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "209";
require_once("includes/rsusuario.php");

$id_factura = intval($_GET['idf']);
if ($id_factura == 0) {
    header("location: facturas_gastos.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from facturas_proveedores 
where 
id_factura = $id_factura
and estado = 1
and estado_carga = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_factura = intval($rs->fields['id_factura']);
$total_factura = floatval($rs->fields['total_factura']);
$condicion = intval($rs->fields['tipo_factura']);
if ($id_factura == 0) {
    header("location: facturas_gastos.php");
    exit;
}

//Generamos la lista para continuar con la carga de las facturas dejadas por la mitad
$buscar = "Select id_factura,fecha_compra,fecha_carga,factura_numero,total_factura,proveedores.nombre
from facturas_proveedores
inner join proveedores on proveedores.idproveedor=facturas_proveedores.id_proveedor
where facturas_proveedores.estado=1
and facturas_proveedores.estado_carga=1
order by fecha_compra asc, fecha_carga asc
 ";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    //$id_factura=antisqlinyeccion($_POST['id_factura'],"int");
    $monto_gasto = antisqlinyeccion($_POST['monto_gasto'], "float");
    $estado = antisqlinyeccion(1, "float");
    $id_gasto = antisqlinyeccion($_POST['idgasto'], "int");
    $iva_porc = antisqlinyeccion($_POST['iva_porc'], "int");
    $monto_iva = antisqlinyeccion($_POST['monto_iva'], "float");



    $consulta = "
	select permite_ceroynegativo from gastos_lista where idgasto = $id_gasto limit 1
	";
    $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $permite_ceroynegativo = trim($rsp->fields['permite_ceroynegativo']);

    if ($permite_ceroynegativo != 'S') {
        if (floatval($_POST['monto_gasto']) <= 0) {
            $valido = "N";
            $errores .= " - El campo monto gasto no puede ser cero o negativo.<br />";
        }
    }


    if (floatval($_POST['idgasto']) <= 0) {
        $valido = "N";
        $errores .= " - El campo tipo gasto no puede estar vacio.<br />";
    }

    $consulta = "
	select * from gastos_lista where idgasto = $id_gasto
	";
    $rsg = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_gasto = intval($rsg->fields['idgasto']);
    $iva_porc = intval($rsg->fields['iva_porc']);
    $monto_iva = calcular_iva($iva_porc, floatval($_POST['monto_gasto']));
    $idconcepto = intval($rsg->fields['idconcepto']);

    /*
        if(intval($_POST['iva_porc']) == 0){
            $valido="N";
            $errores.=" - El campo iva_porc no puede ser cero o nulo.<br />";
        }
        if(floatval($_POST['monto_iva']) <= 0){
            $valido="N";
            $errores.=" - El campo monto_iva no puede ser cero o negativo.<br />";
        }
    */

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into facturas_proveedores_gastos
		(id_factura, monto_gasto, estado, id_gasto, idconcepto, iva_porc, monto_iva)
		values
		($id_factura, $monto_gasto, 1, $id_gasto, $idconcepto, $iva_porc, $monto_iva)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: facturas_gastos_det.php?idf=".$id_factura);
        exit;

    }

}


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'formfinal') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // si es a credito valida que las cuotas coincidan con el total de factura
    if ($condicion == 2) {
        $consulta = "
		select sum(monto_cuota) as totalcuotas from tmpgastovenc where idusu = $idusu 
		";
        $rscuo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rscuo->fields['totalcuotas'] <> $total_factura) {
            $totcuotxt = formatomoneda($rscuo->fields['totalcuotas']);
            $total_factura_txt = formatomoneda($total_factura);
            $errores .= "- Sumatoria de cuotas ($totcuotxt) no coincide con el total de factura ($total_factura_txt).<br />";
            $valido = "N";
        }
    }

    // valida que el detalle coincida con el total de factura
    $consulta = "
	SELECT sum(monto_gasto) as totaldetalle
	FROM facturas_proveedores_gastos
	where
	id_factura = $id_factura
	and estado <> 6
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsdet->fields['totaldetalle'] <> $total_factura) {
        $totcuotxt = formatomoneda($rsdet->fields['totaldetalle']);
        $total_factura_txt = formatomoneda($total_factura);
        $errores .= "- Sumatoria de detalles ($totcuotxt) no coincide con el total de factura ($total_factura_txt).<br />";
        $valido = "N";
    }

    // si todo es correcto genera
    if ($valido == "S") {

        // consulta a la tabla
        $consulta = "
		select id_factura, tipo_factura, id_proveedor, fecha_compra, fecha_carga, usuario_carga, factura_numero, 
		fecha_valida, validado_por, total_factura, total_iva10, total_iva5, total_exenta, anulado_por, anulado_el, 
		vencimiento_factura, estado, total_iva, estado_carga, timbrado, vtotimbrado, 
		saldo_factura, cobrado_factura, quita_factura, iddeposito, idcompra, idgasto, fact_num, cant_cuotas 
		from facturas_proveedores 
		where 
		id_factura = $id_factura
		and estado = 1
		and estado_carga = 1
		limit 1
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $fecha = $rs->fields['fecha_compra'];
        $factura = $rs->fields['factura_numero'];
        $monto = floatval($total_factura);
        $idproveedor = $rs->fields['id_proveedor'];
        $timbrado = $rs->fields['timbrado'];
        $condicion = $rs->fields['tipo_factura'];
        $cuotas = intval($rs->fields['cant_cuotas']);


        // inserta en gastos
        $consulta = "
		insert into gastos_registro
		(fecha, factura, monto, descripcion, idtipogasto, sucursal, registrado_el, registrado_por, idempresa, idproveedor, timbrado, condicion, cuotas, iva_porc)
		values
		('$fecha', '$factura', $monto, '', 0, $idsucursal, '$ahora', $idusu, $idempresa, $idproveedor, '$timbrado', $condicion, $cuotas, 0)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select idgasto from gastos_registro where registrado_por = $idusu order by idgasto desc limit 1
		";
        $rsprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idgasto = intval($rsprox->fields['idgasto']);
        $id_gasto = $idgasto;

        $consulta = "
		update facturas_proveedores
		set
		idgasto = $id_gasto,
		total_iva = (select sum(monto_iva) from facturas_proveedores_gastos where id_factura = facturas_proveedores.id_factura and estado <> 6),
		saldo_factura=total_factura,
		cobrado_factura=0
		where
		id_factura = $id_factura
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si es a credito
        if ($condicion == 2) {

            $consulta = "
			select count(*) as plazo from tmpgastovenc where idusu = $idusu 
			";
            $rsplaz = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $plazo = $rsplaz->fields['plazo'];

            $consulta = "
			select max(idcta) as idcta from cuentas_empresa
			";
            $rscta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcta = $rscta->fields['idcta'] + 1;

            // generar cuentas_empresa
            $consulta = "
			INSERT INTO cuentas_empresa
			(idcta, idcompra, idgasto, idempresa, tipo, facturanum, factura_venc, timbrado, 
			timbrado_venc, fechacompra, 
			totalcompra, abonado_factura, quita_factura, totaliva10, totaliva5, totalex, registrado_por, registradoel, 
			idproveedor, saldo_activo, estado, clase, anulado_por, anulado_el)
			select 
			$idcta, 0, $idgasto, 1, CASE WHEN tipo_factura = 1 THEN 2 ELSE 1 END as tipo, factura_numero, fecha_compra, timbrado,
			vtotimbrado, fecha_compra, 
			total_factura, 0, 0, 0, 0, 0, $idusu, '$ahora', 
			id_proveedor, total_factura, 1, 1, 0, NULL
			from facturas_proveedores
			where
			id_factura = $id_factura
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si la condicion es credito generar operaciones
            $consulta = "
			INSERT INTO operaciones_proveedores
			(idproveedor, idfactura, monto_factura, abonado_factura, quita_factura, saldo_factura, estado, fecha_factura, 
			fecha_cancelacion, fecha_ultimopago, fecha_prox_vencimiento, saldo_atrasado, idperiodo, 
			plazo_periodo, plazo_periodo_remanente, plazo_periodo_abonado, dias_atraso, max_atraso, prom_atraso, monto_cuota, 
			idcta)
			select 
			id_proveedor, id_factura, total_factura, 0, 0, total_factura, 1, fecha_compra, 
			NULL, NULL, NULL, 0, 11,
			$plazo, $plazo, 0, 0, 0, 0, 0, 
			$idcta
			from facturas_proveedores
			where
			id_factura = $id_factura
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // busca el insertado
            $consulta = "
			select max(idoperacionprov) as idoperacionprov from operaciones_proveedores
			";
            $rsopprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idoperacionprov = $rsopprov->fields['idoperacionprov'];

            // generar detalle de operaciones
            $consulta = "
			INSERT INTO operaciones_proveedores_detalle
			(idoperacionprov, periodo, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, vencimiento, fecha_can, 
			fecha_ultpago, dias_atraso, dias_pago, estado_saldo)
			select 
			$idoperacionprov, @row_number:=@row_number+1 AS row_number, monto_cuota, 0, 0, monto_cuota, vencimiento, NULL,  
			NULL, 0, 0, 1
			from tmpgastovenc, (SELECT @row_number:=0) AS t
			where
			idusu = $idusu
			order by vencimiento asc
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        }
        // borrar temporal
        $consulta = "
		delete from tmpgastovenc where idusu = $idusu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // inserta en conceptos
        $consulta = "
		INSERT INTO cn_conceptos_mov
		(
		idconcepto, codrefer, fecha_comprobante, 
		registrado_el, registrado_por, estado, idconceptomovtipo, 
		year_comprobante, monto_comprobante, iva_comprobante
		)
		select 
		facturas_proveedores_gastos.idconcepto, unss, facturas_proveedores.fecha_compra, 
		facturas_proveedores.fecha_carga, facturas_proveedores.usuario_carga, 1, 2,
		YEAR(facturas_proveedores.fecha_compra), monto_gasto, monto_iva
		from facturas_proveedores_gastos
		inner join facturas_proveedores on facturas_proveedores.id_factura = facturas_proveedores_gastos.id_factura
		where
		facturas_proveedores_gastos.id_factura = $id_factura
		and facturas_proveedores.idgasto > 0
		and facturas_proveedores_gastos.estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // marca como finalizado
        $consulta = "
		update facturas_proveedores
		set
		estado_carga = 3
		where
		id_factura = $id_factura
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: facturas_gastos.php");
        exit;

    }

}




// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


//Generamos la lista para continuar con la carga de las facturas dejadas por la mitad
$buscar = "Select id_factura,fecha_compra,fecha_carga,factura_numero,total_factura,proveedores.nombre
from facturas_proveedores
inner join proveedores on proveedores.idproveedor=facturas_proveedores.id_proveedor
where facturas_proveedores.estado=1
and facturas_proveedores.estado_carga=1
and facturas_proveedores.id_factura = $id_factura
order by fecha_compra asc, fecha_carga asc
 ";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function agrega_cuota(){
	var monto_cuota = $("#monto_cuota").val();
	var vencimiento = $("#vencimiento").val();
	var parametros = {
				"reg"           : 'S',
				"monto_cuota"   : monto_cuota,
				"vencimiento"   : vencimiento
        };
		$.ajax({
                data:  parametros,
                url:   'registro_gastos_cuo_new.php',
                type:  'post',
                beforeSend: function () {
					$("#gasto_cuota").html('cargando...');
                },
                success:  function (response) {
					$("#gasto_cuota").html(response);
                }
        });
}
function borrar_cuo(idvenc){

	var parametros = {
				"borra"           : 'S',
				"idvenc"   : idvenc,
        };
		$.ajax({
                data:  parametros,
                url:   'registro_gastos_cuo_new.php',
                type:  'post',
                beforeSend: function () {
					$("#gasto_cuota").html('cargando...');
                },
                success:  function (response) {
					$("#gasto_cuota").html(response);
                }
        });
}

</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Gastos a Factura</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
                  
				  <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action" border="1">
  <thead>


	  <th align="center">Fecha Factura</th>
		<th align="center">Proveedor</th>
		<th align="center">Factura N&uacute;mero</th>
	  <th align="center">Total Factura</th>
		<th align="center">Fecha Carga</th>
		

  </thead>
		
  <tbody>
	  <?php //while (!$rsl->EOF){?>
	<tr>

		<td><?php echo htmlentities(date("d/m/Y", strtotime($rsl->fields['fecha_compra']))); ?></td>
		<td><?php echo htmlentities($rsl->fields['nombre']); ?></td>
		<td><?php echo htmlentities($rsl->fields['factura_numero']); ?></td>
		<td><?php echo formatomoneda($rsl->fields['total_factura']); ?></td>
		<td><?php echo date("d/m/Y H:i:s", strtotime($rsl->fields['fecha_carga'])); ?></td>
	</tr>
	  <?php //$rsl->MoveNext();}?>
  </tbody>
</table>
 </div>

<hr />
<form id="form1" name="form1" method="post" action="">


<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto gasto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="monto_gasto" id="monto_gasto" value="<?php  if (isset($_POST['monto_gasto'])) {
	    echo floatval($_POST['monto_gasto']);
	} else {
	    echo "";
	}?>" placeholder="Monto gasto" class="form-control"   />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de gasto</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
        // consulta
        $consulta = "SELECT idgasto, descripcion
  		FROM gastos_lista
		  where
		  estado = 1
		  
		  order by descripcion asc
 		 ";

// valor seleccionado
if (isset($_POST['idgasto'])) {
    $value_selected = htmlentities($_POST['idgasto']);
} else {
    $value_selected = htmlentities($rs->fields['idgasto']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'idgasto',
'id_campo' => 'idgasto',

'nombre_campo_bd' => 'descripcion',
'id_campo_bd' => 'idgasto',

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
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-plus"></span> Agregar</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
<?php
$consulta = "
select *,
(select gastos_lista.descripcion from gastos_lista where idgasto = facturas_proveedores_gastos.id_gasto) as tipogasto,
(select descripcion from cn_conceptos where idconcepto = facturas_proveedores_gastos.idconcepto) as concepto
from facturas_proveedores_gastos 
where 
 estado = 1 
 and id_factura = $id_factura
order by id_factura asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
            <th align="center">Tipo Gasto</th>
            <th align="center">Concepto</th>
			<th align="center">Monto gasto</th>
			
            
			<th align="center">Iva %</th>
			<th align="center">Monto iva</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {

    $monto_gasto_acum += $rs->fields['monto_gasto'];
    $monto_iva_acum += $rs->fields['monto_iva'];

    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="facturas_gastos_det_del.php?id=<?php echo $rs->fields['unss']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
            <td align="right"><?php echo antixss($rs->fields['tipogasto']);  ?></td>
            <td align="right"><?php echo antixss($rs->fields['concepto']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_gasto']);  ?></td>
			
			<td align="center"><?php echo intval($rs->fields['iva_porc']); ?>%</td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_iva']);  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
		<tr style="background-color:#CCC; font-weight:bold;">
			<td>Totales

			</td>
            <td align="right"></td>
            <td align="right"></td>
			<td align="right"><?php echo formatomoneda($monto_gasto_acum);  ?></td>
			
			<td align="center"></td>
			<td align="right"><?php echo formatomoneda($monto_iva_acum);  ?></td>
		</tr>
	  </tbody>
    </table>
</div>
<br /><?php


$consulta = "
select sum(monto_gasto) as monto_gasto_tot, sum(monto_iva) as monto_gasto_iva, iva_porc
from facturas_proveedores_gastos 
where 
estado = 1 
and id_factura = $id_factura
group by iva_porc
order by iva_porc asc
";
$rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">IVA %</th>
            <th align="center">Monto gasto</th>
			<th align="center">Monto iva</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rsiva->EOF) {


    ?>
		<tr>
			<td align="center"><?php if ($rsiva->fields['iva_porc'] > 0) {
			    echo intval($rsiva->fields['iva_porc']); ?>%<?php } else {  ?>EXCENTO<?php } ?></td>
            <td align="right"><?php echo formatomoneda($rsiva->fields['monto_gasto_tot']);  ?></td>
			<td align="right"><?php echo formatomoneda($rsiva->fields['monto_gasto_iva']);  ?></td>
		</tr>
<?php $rsiva->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<?php if ($condicion == 2) { ?>
<hr />
<strong>Credito:</strong><br />
<br />


<div class="col-md-3 col-sm-3 col-xs-12 form-group">
<input type="text" name="monto_cuota"  id="monto_cuota" class="form-control" placeholder="Monto Cuota" value="<?php if (intval($_GET['monto_cuota']) > 0) {
    echo intval($monto_cuota);
} ?>"   >

</div>
<div class="col-md-3 col-sm-3 col-xs-12 form-group">
<input type="date" name="vencimiento" id="vencimiento" class="form-control" placeholder="Cliente" value="<?php if (intval($_GET['vencimiento']) > 0) {
    echo intval($vencimiento);
} ?>" >
</div>

<button type="submit" class="btn btn-default" onclick="agrega_cuota();">Agregar</button>
<br />
<div class="clearfix"></div>
<div id="gasto_cuota">
<?php require("registro_gastos_cuo_new.php"); ?>
</div>
<?php } ?>
<br />
<hr />
<br />
<form id="form2" name="form2" method="post" action="">
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='facturas_gastos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="formfinal" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
