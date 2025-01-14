<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

//Vemos si posee activo el sistema contable o no
$consulta = "Select usa_concepto, master_franq,contabilidad from preferencias limit 1";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_concepto = $rspref->fields['usa_concepto'];
$master_franq = $rspref->fields['master_franq'];
$contabilidad = intval($rspref->fields['contabilidad']);
//echo $contabilidad;exit;

//Categorias
$buscar = "Select * from categorias where idempresa = $idempresa order by nombre ASC";
$rscate2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$idinsu = intval($_GET['id']);
if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}
$buscar = "
select *,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
(select descripcion from productos where idprod_serial = insumos_lista.idproducto) as producto,
idtipoiva as idtipoiva_compra
from insumos_lista 
where 
 estado = 'A' 
 and idinsumo=$idinsu 
limit 1
 ";
$rsconecta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idinsu = intval($rsconecta->fields['idinsumo']);
$idproducto = intval($rsconecta->fields['idproducto']);
$idcpr = intval($rsconecta->fields['idcentroprod']);

if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}


$buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros

    $costo = antisqlinyeccion(floatval($_POST['costo']), "float");


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



    // si todo es correcto actualiza
    if ($valido == "S") {


        // busca si existe en el log
        $consulta = "
		select * from insumos_lista_log where idinsumo = $idinsu limit 1;
		";
        $rsinsulog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no existe crea
        if (intval($rsinsulog->fields['idinsumo']) == 0) {
            $consulta = "
			insert into insumos_lista_log 
			(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
			log_registrado_el,log_registrado_por, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
			rendimiento_porc
			)
			select
			idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
			fechahora, 0, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
			rendimiento_porc
			from insumos_lista
			where 
			idinsumo = $idinsu
			limit 1;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }



        // actualiza costo en insumos
        $consulta = "
		update insumos_lista
		set
			costo=$costo
		where
			idinsumo = $idinsu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // backup del costo de la factura solo si nunca se habia hecho backup anteriormente and costoprov = 0
        $consulta = "
		update costo_productos 
		set 
			costoprov = precio_costo
		where 
		id_producto = $idinsu 
		and disponible > 0
		and costoprov = 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza costo en tandas de costo
        $consulta = "
		update costo_productos 
		set 
			precio_costo = $costo,
			asignado_el = '$ahora',
			asignado_por = $idusu
		where 
		id_producto = $idinsu 
		and disponible > 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // inserta en el log luego de actualizar
        $consulta = "
		insert into insumos_lista_log 
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
		log_registrado_el,log_registrado_por, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
		rendimiento_porc
		)
		select
		idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
		'$ahora',$idusu, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
		rendimiento_porc
		from insumos_lista
		where 
		idinsumo = $idinsu
		limit 1;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: insumos_lista_ultcosto.php");
        exit;

    }

}


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function subcategorias(idcategoria){
	var direccionurl='subcate_new.php';	
	var parametros = {
	  "idcategoria" : idcategoria
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#subcatebox").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
				$("#subcatebox").html(response);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function tipo_producto(idtipoproducto){
	//producto
	if(idtipoproducto == 1){	

	}
	// combo
	if(idtipoproducto == 2){	
	
	}
	// combinado
	if(idtipoproducto == 3){	
		$("#div_combinado_tipoprecio").show();
	}
	// combinado extendido
	if(idtipoproducto == 4){	
		$("#div_combinado_minitem").show();
		$("#div_combinado_maxitem").show();
		$("#div_combinado_tipoprecio").show();	
	}else{
		$("#div_combinado_minitem").hide();
		$("#div_combinado_maxitem").hide();
		if(idtipoproducto != 3){
			$("#div_combinado_tipoprecio").hide();	
		}
	}
	// agregado
	if(idtipoproducto == 5){	
	
	}
	// delivery
	if(idtipoproducto == 6){	
	
	}	
	// servicio
	if(idtipoproducto == 7){	
	
	}	
	
	
}
function alerta_modal(titulo,mensaje){
	$('#dialogobox').modal('show');
	$("#myModalLabel").html(titulo);
	$("#modal_cuerpo").html(mensaje);

	
}
function ventana_categoria(){
	var direccionurl='categoria_prod_add.php';	
	var parametros = {
	  "add"        : 'N'
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#myModalLabel").html('Agregar Categoria');	
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			$("#modal_cuerpo").html(response);	
			$('#dialogobox').modal('show');
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
function ventana_subcategoria(){
	var direccionurl='subcategoria_prod_add.php';	
	var parametros = {
	  "add"        : 'N'
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#myModalLabel").html('Agregar Sub-Categoria');	
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			$("#modal_cuerpo").html(response);	
			$('#dialogobox').modal('show');
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
function agregar_categoria(){
	var direccionurl='categoria_prod_add.php';
	var categoria = $("#categoria").val();	
	var parametros = {
	  "add"        : 'S',
	  "categoria"  : categoria
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#myModalLabel").html('Agregar Categoria');	
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				recargar_categoria(obj.idcategoria);
				$("#modal_cuerpo").html('');
				$('#dialogobox').modal('hide');

			}else{
				$("#modal_cuerpo").html(response);	
			}

		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
function agregar_subcategoria(){
	var direccionurl='subcategoria_prod_add.php';
	var categoria = $("#categoria").val();	
	var subcategoria = $("#subcategoria").val();
	var parametros = {
	  "add"        : 'S',
	  "categoria"  : categoria,
	  "subcategoria"  : subcategoria
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#myModalLabel").html('Agregar Sub-Categoria');	
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				recargar_categoria(obj.idcategoria);
				recargar_subcategoria(obj.idcategoria,obj.idsubcategoria);
				$("#modal_cuerpo").html('');
				$('#dialogobox').modal('hide');

			}else{
				$("#modal_cuerpo").html(response);	
			}

		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
function recargar_categoria(idcategoria){
	var direccionurl='cate_new.php';
	var parametros = {
	  "idcategoria" : idcategoria,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {	
			$("#categoriabox").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			$("#categoriabox").html(response);	
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
function recargar_subcategoria(idcategoria,idsubcategoria){
	var direccionurl='subcate_new.php';
	var parametros = {
	  "idcategoria" : idcategoria,
	  "idsubcate" : idsubcategoria,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {	
			$("#subcatebox").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			$("#subcatebox").html(response);	
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
	
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Articulo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Codigo Articulo</th>
			<th align="center">Articulo</th>
            <th align="center">Producto Vinculado</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>

			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php
$rs = $rsconecta;
?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center">
            <?php if ($rsconecta->fields['idproducto'] > 0) { ?>
			<?php echo antixss($rs->fields['producto']); ?> 
            &nbsp;<a href="gest_eliminar_productos.php?id=<?php echo $rsconecta->fields['idproducto']; ?>"><img src="img/borrar.png" width="20" height="20" alt="" title="Eliminar Producto Definitivamente" /></a><br />
            <a href="producto_precio_asigna.php?id=<?php echo $rsconecta->fields['idproducto']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Precio por Sucursal</a>
            <?php } else { ?>
            No es un producto <a href="gest_insumos_convert.php?id=<?php echo $rsconecta->fields['idinsumo'] ?>" class="btn btn-sm btn-default"><span class="fa fa-cogs"></span> Convertir</a>
            <?php } ?>
            </td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>

	  </tbody>
    </table>
</div>
<hr />



<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ultimo Costo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="costo" id="costo" class="form-control" value="<?php if (isset($_POST['costo'])) {
	    echo htmlentities($_POST['costo']);
	} else {
	    echo floatval($rsconecta->fields['costo']);
	} ?>" placeholder="costo" required  />
	</div>
</div>


<br />
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br /><br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
