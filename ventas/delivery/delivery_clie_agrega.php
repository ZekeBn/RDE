<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

$consulta = "select usa_lista_zonas from preferencias";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usarzonadelivery = trim($rspref->fields['usa_lista_zonas']);

// AIzaSyCpPoaeqAUHAJ0X8OXb4vey2wWy2bTSCQU ekaru
// AIzaSyDCRfkUJQw3bE0t5u9iMrMghtTl7nuBdO4 miguel
$consulta = "
select gmaps_apikey, lat_defecto, lng_defecto, zoom, ultactu_maps_js, usa_maps
from preferencias_caja 
limit 1;
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$lng_defecto = $rsprefcaj->fields['lng_defecto'];
$lat_defecto = $rsprefcaj->fields['lat_defecto'];
$gmaps_apikey = $rsprefcaj->fields['gmaps_apikey'];
$zoom = $rsprefcaj->fields['zoom'];
$ultactu_maps_js = date("YmdHis", strtotime($rsprefcaj->fields['ultactu_maps_js']));
$usa_maps = trim($rsprefcaj->fields['usa_maps']);
if ($usarzonadelivery != 'S') {
    $usa_maps = "N";
}

$consulta = "
select lng_defecto, lat_defecto from sucursales where idsucu  = $idsucursal limit 1
";
$rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (trim($rssuc->fields['lng_defecto']) != '') {

    $lng_defecto = $rssuc->fields['lng_defecto'];
    $lat_defecto = $rssuc->fields['lat_defecto'];
}


$telefono_g = '0'.intval($_GET['tel']);

$consulta = "
select * from cliente where borrable = 'N' and estado <> 6 order by idcliente asc limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred = trim($rscli->fields['ruc']);

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // recibe parametros
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $iddomicilio = antisqlinyeccion($_POST['iddomicilio'], "int");
    $telefono = antisqlinyeccion($_POST['telefono'], "int");

    // recibe parametros dom
    $iddomicilio = antisqlinyeccion($_POST['iddomicilio'], "int");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $referencia = antisqlinyeccion($_POST['referencia'], "text");
    $nombre_domicilio = antisqlinyeccion($_POST['nombre_domicilio'], "text");
    $idclientedel = antisqlinyeccion($_POST['idclientedel'], "int");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $idzonadel = intval($_POST['zonad']);
    $latitud = antisqlinyeccion(trim($_POST['latitud']), "text");
    $longitud = antisqlinyeccion(trim($_POST['longitud']), "text");
    $url_maps = antisqlinyeccion($_POST['url_maps'], "textbox");
    $idcliente_pos = antisqlinyeccion($_POST['idcliente'], "int");


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_POST['nombres']) == '') {
        $valido = "N";
        $errores .= " - El campo nombres no puede estar vacio.<br />";
    }
    if (trim($_POST['apellidos']) == '') {
        $valido = "N";
        $errores .= " - El campo apellidos no puede estar vacio.<br />";
    }
    if (intval($_POST['telefono']) == 0) {
        $valido = "N";
        $errores .= " - El campo telefono no puede ser cero o nulo.<br />";
    }



    // validaciones dom
    if (trim($_POST['direccion']) == '') {
        $valido = "N";
        $errores .= " - El campo direccion no puede estar vacio.<br />";
    }
    if (trim($_POST['nombre_domicilio']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre_domicilio no puede estar vacio.<br />";
    }


    // validaciones si completo ruc
    if (trim($_POST['ruc']) != '' && trim($_POST['ruc']) != $ruc_pred) {

        // validar digito verificador del ruc
        $rucar = trim($_POST['ruc']);
        $ruc_array = explode("-", $rucar);
        $ruc_pri = $ruc_array[0];
        $ruc_dv = $ruc_array[1];
        $dv_correcto = calcular_ruc($ruc_pri);

        if ($ruc_pri <= 0) {
            $errores .= "- El ruc no puede ser cero o menor.<br />";
            $valido = "N";
        }
        if (strlen($ruc_dv) <> 1) {
            $errores .= "- El digito verificador del ruc no puede tener 2 numeros.<br />";
            $valido = "N";
        }
        if (calcular_ruc($ruc_pri) <> $ruc_dv) {
            $digitocor = calcular_ruc($ruc_pri);
            $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
            $valido = "N";
        }
        if (trim($_POST['ruc']) == $ruc_pred && trim(strtoupper($_POST['razon_social'])) <> $razon_social_pred) {
            $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
            $valido = "N";
        }
        if (trim($_POST['ruc']) <> $ruc_pred && trim(strtoupper($_POST['razon_social'])) == $razon_social_pred) {
            $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
            $valido = "N";
        }



    }

    /*$consulta="
    select *
    from cliente_delivery
    where
    idempresa = $idempresa
    and telefono = $telefono
    ";
    $rsdelcliex=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if(intval($rsdelcliex->fields['idclientedel']) > 0){
        $errores.="- El telefono ya existe, edite el cliente con este telefono.<br />";
        $valido="N";
    }*/


    // conversiones
    if (trim($_POST['ruc']) == '') {
        $idcliente = "NULL";
    }


    // si envio ruc
    if (trim($_POST['ruc']) != '') {

        // busca en clientes si existe
        $consulta = "
    	select * from cliente where ruc = $ruc and estado <> 6  order by idcliente asc limit 1
    	";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rscli->fields['idcliente']);
        // sino existe valida para insertar
        if ($idcliente == 0) {

            $parametros_array = [
                'idclientetipo' => 1,
                'ruc' => $_POST['ruc'],
                'razon_social' => $_POST['razon_social'],
                'documento' => $_POST['dc'],
                'fantasia' => $_POST['fantasia'],
                'nombre' => $_POST['nombres'],
                'apellido' => $_POST['apellidos'],


                'idvendedor' => '',
                'sexo' => '',

                'nombre_corto' => $_POST['nombre_corto'],
                'idtipdoc' => $_POST['idtipdoc'],

                'telefono' => $_POST['telefono'],
                'celular' => $_POST['celular'],
                'email' => $_POST['email'],
                'direccion' => $_POST['direccion'],
                'comentario' => $_POST['comentario'],
                'fechanac' => $_POST['fechanac'],

                'ruc_especial' => $_POST['ruc_especial'],
                'idsucursal' => $idsucursal,
                'idusu' => $idusu,

            ];


            $res = validar_cliente($parametros_array);
            if ($res['valido'] != 'S') {
                $valido = $res['valido'];
                $errores .= nl2br($res['errores']);
            }

        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        // si envio ruc
        if (trim($_POST['ruc']) != '') {
            // busca en clientes
            $consulta = "
			select * from cliente where ruc = $ruc and estado <> 6  order by idcliente asc limit 1
			";
            $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente = intval($rscli->fields['idcliente']);
            // sino existe inserta
            if ($idcliente == 0) {
                /*$consulta="
                Insert into cliente
                (idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social)
                values
                ($idempresa,$nombres,$apellidos,$ruc,NULL,$direccion,$telefono,$razon_social)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                // busca en clientes el que acabamos de insertar
                $consulta="
                select * from cliente where ruc = $ruc and idempresa = $idempresa order by idcliente asc limit 1
                ";
                $rscli=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                $idcliente=intval($rscli->fields['idcliente']);*/
                $res = registrar_cliente($parametros_array);
                $idcliente = $res['idcliente'];
                $consulta = "
				select * from cliente where idcliente = $idcliente
				";
                $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            }
            //print_r($_POST);
            // si envio el ruc generico
            if ($ruc_pred == trim($_POST['ruc'])) {

                // si envio el idcliente
                if (intval($_POST['idcliente']) > 0) {
                    // busca si existe en la bd
                    $consulta = "
					select * from cliente where idcliente = $idcliente_pos
					";
                    $rscli_pos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idcliente_posbd = $rscli_pos->fields['idcliente'];
                    // si existe en la bd
                    if (intval($idcliente_posbd) > 0) {
                        // asigna el idcliente
                        $idcliente = $idcliente_posbd;
                    }
                }
            }


        }
        //echo $idcliente;exit;
        // cliente delivery
        $consulta = "
		insert into cliente_delivery
		(idcliente, nombres, apellidos, telefono, fec_ultactualizacion, creado_por, creado_el, idempresa)
		values
		($idcliente, $nombres, $apellidos,  $telefono, '$ahora', $idusu, '$ahora', $idempresa)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // busca el cliente insertado
        $consulta = "
		select * 
		from cliente_delivery
		where
		creado_por = $idusu
		and idempresa = $idempresa
		order by idclientedel desc
		limit 1
		";
        $rsdelcli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idclientedel = intval($rsdelcli->fields['idclientedel']);


        //cliente delivery domicilio
        $consulta = "
		insert into cliente_delivery_dom
		(direccion, referencia, nombre_domicilio, idclientedel, creado_el, creado_por, ultactu_el, ultactu_por, idempresa,idzonadel,
		latitud, longitud,url_maps)
		values
		($direccion, $referencia, $nombre_domicilio, $idclientedel, '$ahora', $idusu, '$ahora', $idusu, $idempresa,$idzonadel,
		$latitud,$longitud, $url_maps)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select iddomicilio from cliente_delivery_dom where creado_por = $idusu order by iddomicilio desc limit 1;
		";
        $rsdom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddomicilio = $rsdom->fields['iddomicilio'];

        header("location: delivery_pedidos_dir.php?id=".$idclientedel."&iddomicilio=".$iddomicilio);
        exit;

    }

}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../../includes/head_gen.php"); ?>
<?php if ($usa_maps == "S") { ?>
        <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css?nc=<?php echo $ultactu_maps_js;  ?>" />
<script>
function coordenadas() {
    fetch('maps.php')
        .then(res => {
            if (res.ok) {
                return res.json();
            } else {
                console.log('error');
            }
        })
        .then(data => {
            if (data.status == 'success') {
                initMap(true, data);
            } else {
                initMap(false);
            }
        });
}
function marcar(valor){
	//$('#zonad option[value="'+valor+'"]').attr("selected", "selected");
	$('#zonad').val(valor);
}
function busca_cliente(){
	var direccionurl='busqueda_cliente_del.php';		
	var parametros = {
	  "m" : '1'		  
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		beforeSend: function () {
			$('#cuadro_pop').modal('show');
			$("#myModalLabel").html('Busqueda de Cliente');
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response) {
			$("#modal_cuerpo").html(response);
		}
	});
	
}
function busca_cliente_res(tipo){
	var ruc = $("#ruc_del").val();
	var razon_social = $("#razon_social_del").val();
	var fantasia = $("#fantasia_del").val();
	var documento = $("#documento_del").val();
	if(tipo == 'ruc'){
		razon_social = '';
		fantasia = '';
		documento = '';
		$("#razon_social_del").val('');
		$("#fantasia_del").val('');
		$("#documento_del").val('');
	}
	if(tipo == 'razon_social'){
		ruc = '';
		fantasia = '';
		documento = '';
		$("#ruc_del").val('');
		$("#fantasia_del").val('');
		$("#documento_del").val('');
	}
	if(tipo == 'fantasia'){
		ruc = '';
		razon_social = '';
		documento = '';
		$("#ruc_del").val('');
		$("#razon_social_del").val('');
		$("#documento_del").val('');
	}
	if(tipo == 'documento'){
		ruc = '';
		razon_social = '';
		fantasia = '';
		$("#ruc_del").val('');
		$("#razon_social_del").val('');
		$("#fantasia_del").val('');
	}
	var direccionurl='busqueda_cliente_res_del.php';		
	var parametros = {
	  "ruc"            : ruc,
	  "razon_social"   : razon_social,
	  "fantasia"   	   : fantasia,
	  "documento"      : documento
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		beforeSend: function () {
			$("#busqueda_cli").html('Cargando...');				
		},
		success:  function (response) {
			$("#busqueda_cli").html(response);
		}
	});
}
function seleccionar_item(idcliente,razon_social,ruc,nombre,apellido){
	$("#idcliente").val(idcliente);
	$("#ruc").val(ruc);
	$("#razon_social").val(razon_social);
	$("#nombres").val(nombre);
	$("#apellidos").val(apellido);
	$('#cuadro_pop').modal('hide');
}
function agrega_cliente(){
		var direccionurl='cliente_agrega_deliv.php';
		var parametros = {
              "new" : 'S',
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#cuadro_pop').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#cuadro_pop').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html(response);
					if (document.getElementById('ruccliente')){
						document.getElementById('ruccliente').focus();
					}
					$("#idpedido").html(idpedido);
                }
        });	
}

function registrar_cliente(){
	var ruc_add = $("#ruccliente").val();
	var nombres_add = $("#nombreclie").val();
	var apellidos_add = $("#apellidosclie").val();
	var razon_social_add = $("#rz1").val();
	var documento_add = $("#cedulaclie").val();
	var tipo_cliente_add = $("#ruccliente").val();
	
	if($('#r1').is(':checked')) { var idclientetipo=1; }
	if($('#r2').is(':checked')) { var idclientetipo=2; }
	
	if(idclientetipo == 1){
	   var razon_social_add = nombres_add+' '+apellidos_add;
	}
	
	
		var direccionurl='cliente_agrega_deliv.php';
		var parametros = {
			"new" : 'S',
			"MM_insert" : 'form1',
			"ruc" : ruc_add,
			"nombre" : nombres_add,
			"apellido" : apellidos_add,
			"documento" : documento_add,
			"razon_social" : razon_social_add,
			"idclientetipo" : idclientetipo,
			
			
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#cuadro_pop').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#cuadro_pop').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html(response);
					//{"ruc":"X","razon_social":"JUAN PEREZ","nombre_ruc":"JUAN","apellido_ruc":"PEREZ","idcliente":"341","valido":"S"}
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						//alert(obj.error);
						if(obj.valido == 'S'){
							var new_ruc = obj.ruc;
							var new_rz = obj.razon_social;
							var new_nom = obj.nombre_ruc;
							var new_ape = obj.apellido_ruc;
							var idcli = obj.idcliente;
							$("#ruc").val(new_ruc);
							$("#razon_social").val(new_rz);
							$("#nombres").val(new_nom);
							$("#apellidos").val(new_ape);
							$("#idcliente").val(idcli);
							//if(parseInt(idcli)>0){
								//nclie(tipocobro,idpedido);
								//selecciona_cliente(idcli,tipocobro,idpedido);
							//}
							$('#cuadro_pop').modal('hide');
						}else{
							$("#ruc").val(vruc);
							$("#razon_social").val('');
							alert(obj.errores);
						}
					}else{
						alert(response);
					}
                }
        });	
}
function cambia(valor){
	if (valor==1){
		$("#nombreclie_box").show();
		$("#apellidos_box").show();
		$("#rz1").val("");
		$("#rz1_box").hide();
		$("#cedula_box").show();
	}
	if (valor==2){
		$("#nombreclie").val("");
		$("#apellidosclie").val("");
		$("#nombreclie_box").hide();
		$("#apellidos_box").hide();
		$("#rz1_box").show();
		$("#cedula_box").hide();
	}
	
}
</script>
<?php } ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function carga_ruc_h(){
	var vruc = $("#ruc").val();
	var txtbusca="Buscando...";
	if(vruc.toUpperCase() == '<?php echo $ruc_pred; ?>'){
	   busca_cliente();
	}else{
		if(txtbusca != vruc){
			var parametros = {
					"ruc" : vruc
			};
			$.ajax({
					data:  parametros,
					url:   'ruc_extrae_deliv.php',
					type:  'post',
					beforeSend: function () {
						$("#ruc").val(txtbusca);
					},
					success:  function (response) {
						if(IsJsonString(response)){
							var obj = jQuery.parseJSON(response);
							//alert(obj.error);
							if(obj.error == ''){
								var new_ruc = obj.ruc;
								var new_rz = obj.razon_social;
								var new_nom = obj.nombre_ruc;
								var new_ape = obj.apellido_ruc;
								var idcli = obj.idcliente;
								$("#ruc").val(new_ruc);
								$("#razon_social").val(new_rz);
								$("#nombres").val(new_nom);
								$("#apellidos").val(new_ape);
								//if(parseInt(idcli)>0){
									//nclie(tipocobro,idpedido);
									//selecciona_cliente(idcli,tipocobro,idpedido);
								//}
							}else{
								$("#ruc").val(vruc);
								$("#razon_social").val('');

							}
						}else{

							alert(response);

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
	}
}
function asigna_latlon(valor){
	var latlon =valor.split(',');
	var latitud = latlon[0].trim();
	var longitud = latlon[1].trim();
	$("#latitud").val(latitud);
	$("#longitud").val(longitud);
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Cliente Delivery</h2>
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
<form id="form1" name="form1" method="post" action="">
<!--
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="busca_cliente();" class="btn btn-sm btn-default" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span>  Cliente Existente </a></label>
<div class="clearfix"></div>
<br />-->
<?php if (trim($_GET['app']) != 's') { ?>		
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="number" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
        echo htmlentities($_POST['telefono']);
    } else {
        echo htmlentities($telefono_g);
    }?>" placeholder="telefono" required class="form-control"   />
	</div>
</div>
<?php } else { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="telefonofake" id="telefonofake" value="No proporcionado por la APP" placeholder="telefono" required class="form-control" disabled readonly   />
	<input type="hidden" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities($telefono_g);
	}?>" placeholder="telefono" required class="form-control"   />
	</div>
</div>
<?php } ?>
<div class="col-md-6 col-sm-6 form-group">

	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="carga_ruc_h();" class="btn btn-sm btn-default" title="Buscar en la SET" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar en la SET"><span class="fa fa-search"></span></a> 
		
		RUC * </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
        echo htmlentities($_POST['ruc']);
    } else {
        echo htmlentities($ruc_pred);
    }?>" placeholder="ruc" required class="form-control"  />	    
    <input type="hidden" name="idcliente" id="idcliente" value="<?php  if (isset($_POST['idcliente'])) {
        echo htmlentities($_POST['idcliente']);
    } else {
        echo htmlentities($rs->fields['idcliente']);
    }?>" placeholder="idcliente" required />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	
	
	
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
        <input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
            echo htmlentities($_POST['razon_social']);
        } else {
            echo htmlentities($razon_social_pred);
        }?>" placeholder="razon_social" required class="form-control"  />
	</div>
	
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
        echo htmlentities($_POST['nombres']);
    } else {
        echo htmlentities($rs->fields['nombres']);
    }?>" placeholder="nombres" required class="form-control" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
        echo htmlentities($_POST['apellidos']);
    } else {
        echo htmlentities($rs->fields['apellidos']);
    }?>" placeholder="apellidos" required  class="form-control" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="nombre_domicilio" id="nombre_domicilio" value="<?php  if (isset($_POST['nombre_domicilio'])) {
        echo htmlentities($_POST['nombre_domicilio']);
    } elseif (trim($rs->fields['nombre_domicilio']) != '') {
        echo htmlentities($rs->fields['nombre_domicilio']);
    } else {
        echo "CASA";
    }?>" placeholder="nombre_domicilio" required class="form-control" />
	</div>
</div>
<?php if (trim($_GET['app']) != 's') { ?>	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
        echo htmlentities($_POST['direccion']);
    } else {
        echo htmlentities($rs->fields['direccion']);
    }?>" placeholder="direccion" required class="form-control" />
	</div>
</div>
<?php } else { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="direccionfake" id="direccionfake" value="No proporcionado por la APP" placeholder="direccion" required class="form-control" disabled readonly   />
	<input type="hidden" name="direccion" id="direccion" value="x" placeholder="direccion" required class="form-control" />
	</div>
</div>
<?php } ?>
<?php if (trim($_GET['app']) != 's') { ?>	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Referencia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="referencia" id="referencia" value="<?php  if (isset($_POST['referencia'])) {
        echo htmlentities($_POST['referencia']);
    } else {
        echo htmlentities($rs->fields['referencia']);
    }?>" placeholder="referencia" class="form-control"  />
	</div>
	
</div>
<?php } ?>
<?php if ($usarzonadelivery == 'S') {
    $buscar = "Select* from zonas_delivery where estado=1 order by  describezona asc";
    $rsz = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
    <div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Asignar Zona </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <?php
// consulta
$consulta = "
Select idzonadel, CONCAT(describezona,' | ',COALESCE(obs,'')) as zona
from zonas_delivery 
where 
estado=1 
order by  describezona asc
 ";

    // valor seleccionado
    if (isset($_POST['zonad'])) {
        $value_selected = htmlentities($_POST['zonad']);
    } else {
        $value_selected = htmlentities($rs->fields['idzonadel']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'zonad',
        'id_campo' => 'zonad',

        'nombre_campo_bd' => 'zona',
        'id_campo_bd' => 'idzonadel',

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
<?php if (trim($_GET['app']) != 's') { ?>	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">URL maps </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="url_maps" id="url_maps" value="<?php  if (isset($_POST['url_maps'])) {
            echo htmlentities($_POST['url_maps']);
        } else {
            echo htmlentities($rs->fields['url_maps']);
        }?>" placeholder="url maps"  class="form-control"  />
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Latitud,Longitud </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="latlon" id="latlon" value="" placeholder="Latitud, Longitud"  class="form-control" onChange="asigna_latlon(this.value);"  />
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Latitud (GPS) </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="latitud" id="latitud" value="<?php  if (isset($_POST['latitud'])) {
            echo htmlentities($_POST['latitud']);
        } else {
            echo htmlentities($rs->fields['latitud']);
        }?>" placeholder="latitud"  class="form-control" readonly />
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Longitud (GPS) </label>
	<div class="col-md-9 col-sm-9 col-xs-12">   
        <input type="text" name="longitud" id="longitud" value="<?php  if (isset($_POST['longitud'])) {
            echo htmlentities($_POST['longitud']);
        } else {
            echo htmlentities($rs->fields['longitud']);
        }?>" placeholder="longitud"  class="form-control" readonly />
	</div>
</div>
<?php }?>



    
<?php }?>


<?php if (trim($_GET['app']) != 's') { ?>	
<textarea name="coordinates" id="coordinates" cols="70" rows="3" style="width:100%; display:none;"><?php  if (isset($_POST['coordinates'])) {
    echo htmlentities($_POST['coordinates']);
} else {
    echo htmlentities($rs->fields['coordenadas']);
}?></textarea>
<?php }?>
<div class="clearfix"></div>
<br /><br /><br />









    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_pedidos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


<?php if (trim($_GET['app']) != 's') { ?>	
<?php if ($usarzonadelivery == "S") { ?>
<strong>Indicar Coordenadas: </strong><br />
<br />

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <input
      id="pac-input"
      class="form-control"
      type="text"
      placeholder="Buscar calle..."
    />
	</div>
</div>


    <div id="map-container">
        <div id="map"></div>
    </div>
<?php } ?>
<?php } ?>
<div class="clearfix"></div>
<br /><br /> 

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
<!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="cuadro_pop">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
              </button>
              <h4 class="modal-title" id="myModalLabel">Titulo</h4>
            </div>

            <div class="modal-body" id="modal_cuerpo">
            	Cuerpo
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>
<!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../../includes/footer_gen.php"); ?>
<?php if (trim($_GET['app']) != 's') {  ?>
<?php if ($usa_maps == "S") { ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_apikey; ?>&libraries=places,drawing,geometry,places&v=weekly"></script>
<script>
<?php

$zoom = $rsprefcaj->fields['zoom'];
    if (trim($rscab_old->fields['latitud']) == '') {
        $lat_cliente = $lat_defecto;
        $lng_cliente = $lng_defecto;
    } else {
        $lat_cliente = $rscab_old->fields['latitud'];
        $lng_cliente = $rscab_old->fields['longitud'];
    }
    ?>
var latCliente = <?php echo $lat_cliente; ?>;
var lngCliente = <?php echo $lng_cliente; ?>;
latLng = new google.maps.LatLng(latCliente, lngCliente);
const map = new google.maps.Map(document.getElementById("map"), {
    zoom: <?php echo $zoom;  ?>,
    center: { lat: latCliente, lng: lngCliente },
    mapTypeId: "terrain",
});
//latLng = new google.maps.LatLng(-25.282197, -57.635099999999966);
</script>
<script src="js/asignar.js?nc=<?php echo $ultactu_maps_js;  ?>"></script>
<?php } ?>
<?php } ?>
  </body>
</html>
