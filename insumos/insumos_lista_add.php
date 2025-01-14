<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");

// echo "preferencias iva = ".$preferencias_usa_iva_variable;exit;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// la tabla cn_conceptos es la tabla de conceptos de las mercaderias en este caso me interesa saber
// cual es el id de tipo despacho y cual el de tipo flete ya que estos dos en importacion tienen
// iva variable

// seria bueno crear un archivo global de  constantes a ser utilizadas en todo los scripts  a futuro  pero facilitara
// el desarrollo  podria realizarse de forma gradual en los siguientes scripts

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%MERCADERIAS\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_mercaderia = $rs_conceptos->fields['idconcepto'];

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
medidas.nombre LIKE \"%cajas\" order by nombre asc";
$rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = $rs_cajas->fields['id_medida'];

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 
AND medidas.nombre LIKE \"%pall%\" order by nombre asc ";
$rs_pallets = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpallet = $rs_pallets->fields['id_medida'];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// esto tiene que ver con un elemento que se genera en rusuarios que es un require que se encuentra en la parte superior
$usa_concepto = $rsco->fields['usa_concepto'];
$idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
$idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];





$buscar = "SELECT in1.idinsumo,in1.descripcion,
(select count(*) from insumos_lista as in2 where in2.idcod_alt = in1.idinsumo ) as cant_codigos_alt
FROM insumos_lista as in1 where
(in1.maneja_cod_alt = 'N' or in1.maneja_cod_alt is null) and in1.estado = 'A'
order by cant_codigos_alt asc
";

$resultados_insumos_lista = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idinsumo = trim(antixss($rsd->fields['idinsumo']));
    $nombre = trim(antixss($rsd->fields['descripcion']));
    $cant_codigos_alt = trim(antixss($rsd->fields['cant_codigos_alt']));
    $class_cod_alt = null;
    if (intval($cant_codigos_alt) > 0) {
        $class_cod_alt = "have_cod_alt";
    }
    $resultados_insumos_lista .= "
	<a class='a_link_proveedores $class_cod_alt'  href='javascript:void(0);' data-hidden-value='$idinsumo' onclick=\"cambia_cod_alt($idinsumo, '$nombre');\" >[$idinsumo]-$nombre</a>
	";

    $rsd->MoveNext();
}



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
    $idproducto = antisqlinyeccion('', "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $idconcepto = antisqlinyeccion($_POST['idconcepto'], "int");
    $idmarcaprod = antisqlinyeccion('', "int");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
    $cant_medida2 = antisqlinyeccion($_POST['cant_medida2'], "int");
    $cant_medida3 = antisqlinyeccion($_POST['cant_medida3'], "int");
    $idmedida2 = antisqlinyeccion($_POST['idmedida2'], "int");
    $idmedida3 = antisqlinyeccion($_POST['idmedida3'], "int");
    $idsubcate_sec = antisqlinyeccion($_POST['idsubcate_sec'], "int");
    $idpais = antisqlinyeccion($_POST['idpais'], "int");
    $dias_utiles = antisqlinyeccion($_POST['dias_utiles'], "float");
    $dias_stock = antisqlinyeccion($_POST['dias_stock'], "float");
    $bar_code = antisqlinyeccion($_POST['bar_code'], "float");



    if (intval($cant_medida2) > 0) {
        $idmedida2 = $idcaja;
    }
    if (intval($cant_medida3) > 0) {
        $idmedida3 = $idpallet;
    }
    $produccion = antisqlinyeccion('1', "int");
    $costo = antisqlinyeccion(floatval($_POST['costo']), "float");
    $idtipoiva_compra = antisqlinyeccion($_POST['idtipoiva_compra'], "int");
    $mueve_stock = antisqlinyeccion('S', "text");
    $paquete = antisqlinyeccion('', "text");
    $cant_paquete = antisqlinyeccion('', "float");
    $estado = antisqlinyeccion('A', "text");
    $idempresa = antisqlinyeccion(1, "int");
    $idgrupoinsu = antisqlinyeccion($_POST['idgrupoinsu'], "int");
    $ajuste = antisqlinyeccion('N', "text");
    $fechahora = antisqlinyeccion($ahora, "text");
    $registrado_por_usu = antisqlinyeccion($idusu, "int");
    $hab_compra = antisqlinyeccion($_POST['hab_compra'], "int");
    $hab_invent = antisqlinyeccion($_POST['hab_invent'], "int");
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $aplica_regalia = antisqlinyeccion('S', "text");
    $solo_conversion = antisqlinyeccion('', "int");
    $respeta_precio_sugerido = antisqlinyeccion('N', "text");
    $idprodexterno = antisqlinyeccion('', "int");
    $restaurado_por = antisqlinyeccion('', "int");
    $restaurado_el = antisqlinyeccion('', "text");
    $idcategoria = antisqlinyeccion($_POST['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($_POST['idsubcate'], "int");
    $cuentacontable = antisqlinyeccion($_POST['cuentacont'], "int");
    $centroprod = intval($_POST['cpr']);
    $idagrupacionprod = antisqlinyeccion($_POST['idagrupacionprod'], "int");
    $rendimiento_porc = antisqlinyeccion($_POST['rendimiento_porc'], "float");

    //opcionales
    // TODO: poner preferencias
    $cant_caja_edi = antisqlinyeccion($_POST['cant_caja_edi'], "float");
    $largo = antisqlinyeccion($_POST['largo'], "float");
    $ancho = antisqlinyeccion($_POST['ancho'], "float");
    $alto = antisqlinyeccion($_POST['alto'], "float");
    $peso = antisqlinyeccion($_POST['peso'], "float");
    // este elemento hace referencia a el codigo origen del proveedor el cual puede ser cargado de forma masiva en
    //codigo_origen_importar.php
    $cod_fob = antisqlinyeccion($_POST['cod_fob'], "text");
    $rs = antisqlinyeccion($_POST['rs'], "text");
    $rspa = antisqlinyeccion($_POST['rspa'], "text");
    $hab_desc = antisqlinyeccion($_POST['hab_desc'], "text");
    $modifica_precio = antisqlinyeccion($_POST['modifica_precio'], "text");
    //elemento para manejar lote o no este campo sera importante para elementos que usen lote
    // por lo tanto estaran sujetos a las normas de FEFO
    $maneja_lote = antisqlinyeccion($_POST['maneja_lote'], "text");
    $regimen_turismo = antisqlinyeccion($_POST['regimen_turismo'], "text");
    //este elementeo de maneja codigo alternativo o no es descrito en el modulo de stock del relevamiento darle una mirada
    $maneja_cod_alt = antisqlinyeccion($_POST['maneja_cod_alt'], "text");
    $idcod_alt = antisqlinyeccion($_POST['idcod_alt'], "int");


    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }

    if ($usa_concepto == 'S') {
        if (intval($_POST['idconcepto']) == 0) {
            $valido = "N";
            $errores .= " - El campo concepto no puede ser cero o nulo.<br />";
        }
    }

    if (intval($_POST['idmedida']) == 0) {
        $valido = "N";
        $errores .= " - El campo medida no puede ser cero o nulo.<br />";
    }
    /*if(floatval($_POST['costo']) <= 0){
        $valido="N";
        $errores.=" - El campo costo no puede ser cero o negativo.<br />";
    }*/

    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        if (trim($_POST['idtipoiva_compra']) == '') {
            $valido = "N";
            $errores .= " - El campo iva compra no puede estar vacio.<br />";
        }
    } else {
        $idtipoiva_compra = 0;
        $tipoiva_compra = 0;
    }

    if (intval($_POST['idgrupoinsu']) == 0) {
        $valido = "N";
        $errores .= " - El campo grupo stock no puede estar vacio.<br />";
    }

    if (trim($_POST['hab_compra']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita compra debe completarse.<br />";
    }

    if (trim($_POST['hab_invent']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita inventario debe completarse.<br />";
    }
    if ($_POST['hab_compra'] > 0) {
        if (intval($_POST['solo_conversion']) == 0) {
            if (intval($_POST['hab_invent']) == 0) {
                $valido = "N";
                $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
            }
        }
    }
    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $descripcion and borrado = 'N' limit 1
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si existe producto
    $idprod_serial = $rsexpr->fields['idprod_serial'] ? $rsexpr->fields['idprod_serial'] : "";
    if ($idprod_serial > 0) {
        $errores .= "- Ya existe un producto con el mismo nombre.<br />";
        $valido = 'N';
    }
    // validar que no hay insumo con el mismo nombre
    $buscar = "Select * from insumos_lista where descripcion=$descripcion and estado = 'A' limit 1";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsb->fields['idinsumo'] > 0) {
        $errores .= "* Ya existe un articulo con el mismo nombre.<br />";
        $valido = 'N';
    }


    /////////////////

    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        // iva compra
        $consulta = "
		select * 
		from tipo_iva
		where 
		idtipoiva = $idtipoiva_compra
		";
        $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tipoiva_compra = $rsiva->fields['iva_porc'];
        $idtipoiva_compra = $rsiva->fields['idtipoiva'];

        $contabilidad = intval($rsco->fields['contabilidad']);
        if ($contabilidad == 1) {
            if (trim($_POST['hab_compra']) == '1') {
                if (intval($_POST['cuentacont']) == 0) {
                    $valido = "N";
                    $errores .= "- Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
                }
            }
        }
    }
    /////////////////////
    if (floatval($_POST['rendimiento_porc']) <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['rendimiento_porc']) > 100) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser mayor a 100.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $buscar = "select max(idinsumo) as mayor from insumos_lista";
        $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idinsumo = intval($rsmayor->fields['mayor']) + 1;

        $consulta = "
		insert into insumos_lista
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida,idmedida2,idmedida3, cant_medida2, cant_medida3, produccion, costo,  idtipoiva, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el,
		idplancuentadet, idcentroprod, idagrupacionprod, rendimiento_porc,cant_caja_edi,largo,ancho,alto,peso,cod_fob,rs,rspa,hab_desc,modifica_precio,maneja_lote,regimen_turismo,maneja_cod_alt,idcod_alt, idpais, dias_utiles, dias_stock,bar_code, idsubcate_sec
		)
		values
		($idinsumo, $idproducto, $descripcion, $idconcepto, $idcategoria, $idsubcate, $idmarcaprod, $idmedida, $idmedida2, $idmedida3, $cant_medida2, $cant_medida3, $produccion, $costo, $idtipoiva_compra, $tipoiva_compra, $mueve_stock, $paquete, $cant_paquete, $estado, $idempresa, $idgrupoinsu, $ajuste, $fechahora, $registrado_por_usu, $hab_compra, $hab_invent, $idproveedor, $aplica_regalia, $solo_conversion, $respeta_precio_sugerido, $idprodexterno, $restaurado_por, $restaurado_el,
		$cuentacontable, $centroprod, $idagrupacionprod, $rendimiento_porc,$cant_caja_edi,$largo,$ancho,$alto,$peso,$cod_fob,$rs,$rspa,$hab_desc,$modifica_precio,$maneja_lote,$regimen_turismo,$maneja_cod_alt,$idcod_alt, $idpais, $dias_utiles, $dias_stock, $bar_code, $idsubcate_sec
		)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $insertar = "Insert into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $codarticulocontable = intval($_POST['cuentacont']);
        if ($codarticulocontable > 0) {
            //traemos los datos del plan de cuentas activo
            $buscar = "Select * from cn_plancuentas_detalles where cuenta=$codarticulocontable and estado <> 6";
            $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idplan = intval($rsvv->fields['idplan']);
            $idsercuenta = intval($rsvv->fields['idserieun']);

            $insertar = "Insert into cn_articulos_vinculados
			(idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
			values 
			($idinsumo,$idplan,$idsercuenta,current_timestamp,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        }



        if ($idconcepto_mercaderia == $idconcepto) {
            header("location: gest_insumos_convert.php?id=$idinsumo");
        } else {
            header("location: insumos_lista_add.php");
        }
        exit;

    }

}
// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	//funcion para verificar concepto para fletes y seguro 
	// solucion provisoria, lo deseable posterior ens cambiar a  el select de iva 
	function verificar_concepto(){
		var concepto = $('#idconcepto').find("option:selected").text()
		if(concepto == "DESPACHO" || concepto == "FLETE" || concepto == "SEGURO"){
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'none');
		}else{
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'block');
		}
	}
	function recargar_concepto(idconcepto){
		
		var direccionurl='insumos_lista_concepto.php';	
		var parametros = {
			"idconcepto"	  : idconcepto,
			"recargar"        : 1
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#box_concepto").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#box_concepto").html(response);	
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
	function cambiar_categorias(selectElement){
		var opcionSeleccionada = selectElement.options[selectElement.selectedIndex];
		var categoria_id = opcionSeleccionada.getAttribute('data-hidden-value');
		var idsubcate = opcionSeleccionada.getAttribute('data-hidden-value2');
		$('#idcategoria').val(categoria_id);


		var direccionurl='subcate_new.php';	
		var parametros = {
		"idsubcate" : idsubcate
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
		$('#idsubcate').val(idsubcate);
	}
	 function cambia_cod_alt(idinsumo,nombre){
		$('#idcod_alt').html($('<option>', {
            value: idinsumo,
            text: nombre
        }));
        
        $('#idcod_alt').val(idinsumo);
       
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
	}
	function habilitar_codigo_alternativo(valor){
		var box= $("#box_cod_alternativo");
		if (valor == "S"){
			box.css("display", "block");
		}else{
			box.css("display", "none");
		}
	}
	
	function cambia_prov(){
		idproveedor = $("#idproveedor").val();
		var parametros = {
					"idproveedor"    : idproveedor
					
			};
			$.ajax({
				data:  parametros,
				url:   'dropdown_proveedor_fob.php',
				type:  'post',
				beforeSend: function () {
				},
				success:  function (response) {
					$("#box_cod_fob").html(response);
					
				}
			});
	}
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


		 direccionurl='subcate_sec_new.php';	
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#subcatesecbox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#subcatesecbox").html(response);
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
	function subcategorias_secundarias(idsub_categoria){
		var direccionurl='subcate_sec_new.php';	
		var parametros = {
		"idsub_categoria" : idsub_categoria
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#subcatesecbox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#subcatesecbox").html(response);
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
	function ventana_codigo_origen(){
		var direccionurl='codigo_origen_modal_add.php';	
		var parametros = {
		"idproveedor"        : $("#idproveedor").val()
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Codigo Origen');	
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
	function ventana_subcategoria_sec(){
		var direccionurl='subcategoria_sec_prod_add.php';	
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
				$("#myModalLabel").html('Agregar Sub-Categoria Secundaria');	
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
	function ventana_proveedores_add(){
		var direccionurl='proveedores_add.php';	
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
				$("#myModalLabel").html('Agregar Proveedores');	
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

	function ventana_concepto_add(){
		var direccionurl='concepto_add.php';	
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
				$("#myModalLabel").html('Agregar Concepto');	
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
		var margen_seguridad = $("#margen_seguridad").val();	
		var parametros = {
		"add"        		: 'S',
		"categoria"  		: categoria,
		"margen_seguridad"	: margen_seguridad
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
		var margen_seguridad = $("#margen_seguridad").val();
		var subcategoria = $("#subcategoria").val();
		var parametros = {
		"add"        		: 'S',
		"categoria"  		: categoria,
		"subcategoria"  	: subcategoria,
		"margen_seguridad"	: margen_seguridad
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

	function agregar_subcategoria_sec(){
		var direccionurl='subcategoria_sec_prod_add.php';
		var subcategoria_sec = $("#form_subcate_sec #subcategoria_sec").val();
		var selectedOption = $("#form_subcate_sec #idsubcate option:selected");
 		var idcategoria = selectedOption.data("hidden-value");
		var margen_seguridad = $("#form_subcate_sec #margen_seguridad").val();
		var idsubcate = $("#form_subcate_sec #idsubcate").val();
		var parametros = {
		"add"        		: 'S',
		"subcategoria_sec"  : subcategoria_sec,
		"idsubcate"  		: idsubcate,
		"margen_seguridad"	: margen_seguridad
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Sub-Categoria Secundaria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					console.log(obj);
					console.log(JSON.stringify(obj));
					recargar_categoria(idcategoria);
					recargar_subcategoria(idcategoria,obj.idsubcate);
					recargar_subcategoria_sec(idcategoria,obj.idsubcate,obj.idsubcate_sec);
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

	
	function recargar_subcategoria_sec(idcategoria,idsubcate,idsubcate_sec){
		var direccionurl='subcate_sec_new.php';
		var parametros = {
		"idcategoria" : idcategoria,
		"idsubcate" : idsubcate,
		"idsubcate_sec": idsubcate_sec
		};
		console.log(parametros);
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				$("#subcatesecbox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#subcatesecbox").html(response);	
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
	function recargar_proveedores(){
		var direccionurl='select_proveedores.php';
		var parametros = {
		"idcategoria" : 1,
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
				$("#select_proveedores").html(response);	
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

	function agregar_proveedor(event){
		event.preventDefault();
		
		var parametros = {
				"idtransaccion"   : "idtransaccion",
				"idunico"		  : "idunico"
		};

		$("#titulov").html("Agregar Proveedores");
		$.ajax({		  
			data:  parametros,
			url:   'agregar_proveedor_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				
			},
			success:  function (response) {
				$("#ventanamodal").modal("show");
				$("#cuerpov").html(response);	
				
			}
		});

	}
	function filterFunction2(event) {
		event.preventDefault();
        var pais = $("#idpais").val();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput2");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown2");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			id_pais = a[i].getAttribute('data-hidden-value');
			if(pais ){
                if ((pais == id_pais && txtValue.toUpperCase().indexOf(filter) > -1 )){
                    a[i].style.display = "block";
                }else{
                    a[i].style.display = "none";
                }
            }else{

                if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
            
		}
	}
	function myFunction2(event) {
            event.preventDefault();
            var idpais = $("#idpais").val();
            if (!idpais) {
                document.getElementById("myInput2").classList.toggle("show");
                document.getElementById("myDropdown2").classList.toggle("show");
                div = document.getElementById("myDropdown2");
                $("#myInput2").focus();
            } else {
                var div,ul, li, a, i;
               
                div = document.getElementById("myDropdown2");
                a = div.getElementsByTagName("a");
                for (i = 0; i < a.length; i++) {
                    txtValue = a[i].textContent || a[i].innerText;
                    id_pais = a[i].getAttribute('data-hidden-value');
                    if ( id_pais==idpais ) {
                        a[i].style.display = "block";
                    } else {
                        a[i].style.display = "none";
                    }
                }

                document.getElementById("myInput2").classList.toggle("show");
                document.getElementById("myDropdown2").classList.toggle("show");
                div = document.getElementById("myDropdown2");
                $("#myInput2").focus();
            }

			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			var div = $("#lista_cod_alternativo");
			var button = $("#iddepartameto");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
	function cerrar_pop(){
		$("#dialogobox").modal("hide");
	}
	window.onload = function() {
		var idproveedor=$("#idproveedor").val();
		if (idproveedor != undefined && idproveedor != "") {
			cambia_prov();
			
		}
        $('#idcod_alt').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
    };
</script>
<style type="text/css">
        #lista_articulos,#lista_cod_alternativo {
            width: 100%;
        }
		.have_cod_alt{
			background: #6CAD3BC4;
			color:white;
		}
		.have_cod_alt:hover{
			background: #A7D9A5 !important;
			color:white !important;
		}
       
        .a_link_proveedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_proveedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_proveedores{
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }
        .dropdown_proveedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_proveedor_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }
	
</style>

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
                    <h2>Agregar Articulo</h2>
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

Esta seccion es para crear articulos que <strong style="color:#F00">NO SE VENDERAN</strong>, si desea crear un producto para vender hacerlo en:  <a href="../gest_listado_productos.php"  class="btn btn-sm btn-default"><span class="fa fa-external-link"></span> productos</a>.
<hr />
<form id="form1" name="form1" method="post" action="">


<div class="col-md-12 col-sm-12  " >
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
		    echo htmlentities($_POST['descripcion']);
		} else {
		    echo htmlentities($rs->fields['descripcion']);
		}?>" placeholder="Descripcion" class="form-control" required autofocus />                    
		</div>
	</div>

	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			Pais de Origen
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

                // consulta

                $consulta = "
				SELECT p.idpais, p.nombre, p.idmoneda FROM paises_propio p
				WHERE p.estado = 1
				order by nombre asc;
				";

// valor seleccionado
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_POST['idpais']);
} else {
    $value_selected = htmlentities($_GET['idpais']);
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'data_hidden' => 'idmoneda',
    'style_input' => 'class="form-control"',
    'acciones' => '   '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">D&iacute;as Utiles</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="dias_utiles" id="dias_utiles" value="<?php  if (isset($_POST['dias_utiles'])) {
			    echo floatval($_POST['dias_utiles']);
			} else {
			    echo $rs->fields['dias_utiles'];
			} ?>" placeholder="D&iacute;as Utiles" class="form-control"  />
			<small    class="form-text text-muted">D&iacute;as utiles del producto en relacion con el vencimiento.</small>
		
		</div>
	</div>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">D&iacute;as Estimados en Stock</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="dias_stock" id="dias_stock" value="<?php  if (isset($_POST['dias_stock'])) {
			    echo floatval($_POST['dias_stock']);
			} else {
			    echo $rs->fields['dias_stock'];
			} ?>" placeholder="D&iacute;as Estimados en Stock" class="form-control"  />
		</div>
	</div>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">C&oacute;digo de barras</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="bar_code" id="bar_code" value="<?php  if (isset($_POST['bar_code'])) {
			    echo floatval($_POST['bar_code']);
			} else {
			    echo $rs->fields['bar_code'];
			} ?>" placeholder="C&oacute;digo de barras" class="form-control"  />
		</div>
	</div>
		

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            // consulta
            $consulta = "
			SELECT id_medida, nombre
			FROM medidas
			where
			estado = 1
			order by nombre asc
			";

// valor seleccionado
if (isset($_POST['idmedida'])) {
    $value_selected = htmlentities($_POST['idmedida']);
} else {
    $value_selected = htmlentities($rs->fields['idmedida']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmedida',
    'id_campo' => 'idmedida',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_medida',

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
</div>
<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Medidas Opcionales</h2>
	<hr>
		<?php if ($preferencias_medidas_referenciales == "S") { ?>
		
			<div class="row" style="margin:0;">
				<div class="col-md-6 col-sm-6 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida 2</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<?php
                // consulta
                $consulta = "
						SELECT id_medida, nombre
						FROM medidas
						where
						estado = 1
						order by nombre asc
						";
		    // valor seleccionado
		    if (isset($_POST['idmedida'])) {
		        $value_selected = htmlentities($_POST['idmedida2']);
		    } else {
		        $value_selected = $idcaja;
		    }
		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idmedida2',
		        'id_campo' => 'idmedida2',
		        'nombre_campo_bd' => 'nombre',
		        'id_campo_bd' => 'id_medida',
		        'value_selected' => $value_selected,
		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'class="form-control"',
		        'acciones' => 'disabled aria-describedby="medida2Help"  ',
		        'autosel_1registro' => 'S'
		    ];
		    // construye campo
		    echo campo_select($consulta, $parametros_array);
		    ?>
					<small id="medida2Help"   class="form-text text-muted">Designar Medida2 que contiene el campo Medida, por defecto CAJAS contiene a el campo MEDIDA.</small>
					</div>
				</div>
				<div class="col-md-6 col-sm-6 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 2</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" aria-describedby="cant_medida2Help"  name="cant_medida2" id="cant_medida2" value="<?php  if (isset($_POST['cant_medida2'])) {
						    echo floatval($_POST['cant_medida2']);
						} else {
						    echo floatval($rs->fields['cant_medida2']);
						}?>" placeholder="cant_medida2" class="form-control"  />
						<small id="cant_medida2Help"   class="form-text text-muted">Cuantas veces el campo UNIDAD ( MEDIDA ) es contenido en cada CAJA.</small>
					</div>
				</div>
			</div>
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida 3</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
                    // consulta
                    $consulta = "
					SELECT id_medida, nombre
					FROM medidas
					where
					estado = 1
					order by nombre asc
					";

		    // valor seleccionado
		    if (isset($_POST['idmedida'])) {
		        $value_selected = htmlentities($_POST['idmedida3']);
		    } else {
		        $value_selected = $idpallet;
		    }

		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idmedida3',
		        'id_campo' => 'idmedida3',

		        'nombre_campo_bd' => 'nombre',
		        'id_campo_bd' => 'id_medida',

		        'value_selected' => $value_selected,

		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'class="form-control"',
		        'acciones' => 'disabled aria-describedby="medida3Help"  ',
		        'autosel_1registro' => 'S'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
				<small id="medida3Help"   class="form-text text-muted">Designar Medida3 que contiene el campo Medida 2, por defecto PALLETS contiene CAJAS.</small>
		
				</div>
			</div>
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 3</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" aria-describedby="cant_medida3Help"  name="cant_medida3" id="cant_medida3" value="<?php  if (isset($_POST['cant_medida3'])) {
				    echo floatval($_POST['cant_medida3']);
				} else {
				    echo floatval($rs->fields['cant_medida3']);
				}?>" placeholder="cant_medida3" class="form-control"  />
				<small id="cant_medida3Help"   class="form-text text-muted">Cuantas CAJAS son contenidas en cada PALLET.</small>
				</div>
			</div>

		<?php } ?>
		<?php if ($preferencias_medidas_edi == "S") { ?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Unidades por Caja EDI</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" aria-describedby="cantCajaEdiHelp"  name="cant_caja_edi" id="cant_caja_edi" value="<?php  if (isset($_POST['cant_caja_edi'])) {
				    echo floatval($_POST['cant_caja_edi']);
				} else {
				    echo floatval($rs->fields['cant_caja_edi']);
				}?>" placeholder="cant_caja_edi" class="form-control"  />
				<small id="cantCajaEdiHelp"   class="form-text text-muted">Cuantas veces el campo Medida es contenido en Cajas EDI.</small>
				</div>
			</div>
		<?php } ?>
</div>
<?php } ?>
<?php if ($preferencias_medidas_fisicas == "S") { ?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Medidas Fisicas</h2>
	<hr>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Largo (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="largo" id="largo" value="<?php  if (isset($_POST['largo'])) {
			    echo floatval($_POST['largo']);
			} else {
			    echo floatval($rs->fields['largo']);
			}?>" placeholder="largo" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Ancho (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="ancho" id="ancho" value="<?php  if (isset($_POST['ancho'])) {
			    echo floatval($_POST['ancho']);
			} else {
			    echo floatval($rs->fields['ancho']);
			}?>" placeholder="ancho" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Alto (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="alto" id="alto" value="<?php  if (isset($_POST['alto'])) {
			    echo floatval($_POST['alto']);
			} else {
			    echo floatval($rs->fields['alto']);
			}?>" placeholder="alto" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Peso (kl)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="peso" id="peso" value="<?php  if (isset($_POST['peso'])) {
			    echo floatval($_POST['peso']);
			} else {
			    echo floatval($rs->fields['peso']);
			}?>" placeholder="peso" class="form-control" required />
			</div>
		</div>
</div>
<?php } ?>

<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Relacionar Proveedor</h2>
	<hr>

	
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">
				<a href="javascript:void(0);" onClick="ventana_proveedores_add();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Proveedor 
			</label>
			<div id="select_proveedores" class="col-md-9 col-sm-9 col-xs-12">
				<?php require_once("select_proveedores.php"); ?>
			</div>
		</div>
		<?php if ($preferencias_codigo_fob == "S") { ?>
		<div id="box_cod_fob" class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de origen</label>
			<div  class="col-md-9 col-sm-9 col-xs-12">
		
			<?php
                    // consulta
                    $consulta = "
					SELECT idtipoiva, iva_porc, iva_describe
					FROM tipo_iva
					where
					estado = 1
					and hab_compra = 'S'
					order by iva_porc desc
					";

		    // valor seleccionado
		    if (isset($_POST['cod_fob'])) {
		        $value_selected = htmlentities($_POST['cod_fob']);
		    } else {
		        $value_selected = $rs->fields['cod_fob'];
		    }

		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'cod_fob',
		        'id_campo' => 'cod_fob',

		        'nombre_campo_bd' => 'iva_describe',
		        'id_campo_bd' => 'idtipoiva',

		        'value_selected' => $value_selected,

		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'class="form-control"',
		        'acciones' => '  disabled aria-describedby="codOrigenHelp" ',
		        'autosel_1registro' => 'S'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
			<small id="codOrigenHelp"   class="form-text text-muted">Referencte al codigo del Proveedor.</small>
		
		</div>
		<?php } ?>
</div>

</div>
<?php if ($preferencias_configuraciones_alternativas == "S") {?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Regimen Sanitario</h2>
	<hr>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">RS </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="rs" id="rs" value="<?php  if (isset($_POST['rs'])) {
			    echo antixss($_POST['rs']);
			} else {
			    echo antixss($rs->fields['rs']);
			}?>" placeholder="rs" class="form-control"  />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">RSPA </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="rspa" id="rspa" value="<?php  if (isset($_POST['rspa'])) {
			    echo antixss($_POST['rspa']);
			} else {
			    echo antixss($rs->fields['rspa']);
			}?>" placeholder="rspa" class="form-control"  />
			</div>
		</div>
</div>
<?php } ?>

<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Grupos  y Categorias</h2>
	<hr>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo Stock *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php
            // consulta
            $consulta = "
			SELECT idgrupoinsu, nombre
			FROM grupo_insumos
			where
			estado = 1
			order by nombre asc
			";

// valor seleccionado
if (isset($_POST['idgrupoinsu'])) {
    $value_selected = htmlentities($_POST['idgrupoinsu']);
} else {
    $value_selected = htmlentities($rs->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idgrupoinsu',
    'id_campo' => 'idgrupoinsu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idgrupoinsu',

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

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_categoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Categoria * </label>
		<div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
			<?php
require_once("cate_new.php");

?>
		</div>
	</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Subcategoria *</label>
			<div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
				<?php
    require_once("subcate_new.php");
?>
			</div>
		</div>
		
		<?php if ($sub_categoria_secundaria == "S") { ?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria_sec();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Sub Categoria Secundaria</label>
				<div class="col-md-9 col-sm-9 col-xs-12" id="subcatesecbox">
					<?php
    require_once("subcate_sec_new.php");
		    ?>
				</div>
			</div>
		<?php } ?>

</div>

<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Costos</h2>
	<hr>
		
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Costo *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="costo" id="costo" value="<?php  if (isset($_POST['costo'])) {
			    echo floatval($_POST['costo']);
			} else {
			    echo floatval($rs->fields['costo']);
			}?>" placeholder="Costo" class="form-control" required />
			</div>
		</div>
		
		
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Compra *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
                    // consulta
                    $consulta = "
					SELECT idtipoiva, iva_porc, iva_describe
					FROM tipo_iva
					where
					estado = 1
					and hab_compra = 'S'
					order by iva_porc desc
					";
$acciones = ' required="required" ';
if ($preferencias_usa_iva_variable = "S") {
    $acciones = ' ';
}
// valor seleccionad
if (isset($_POST['idtipoiva_compra'])) {
    $value_selected = htmlentities($_POST['idtipoiva_compra']);
} else {
    $value_selected = $idtipoiva_compra_pred;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva_compra',
    'id_campo' => 'idtipoiva_compra',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$acciones.' ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
		</div>
</div>


<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Configuraciones</h2>
	<hr>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita compra *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="hab_compra" id="hab_compra"  title="Habilita Compra" class="form-control" required>
					<option value="">Seleccionar</option>
					<option value="1" <?php if ($_POST['hab_compra'] == '1') {?> selected="selected" <?php } if (!isset($_POST['hab_compra'])) { ?>selected<?php } ?> >SI</option>
					<option value="0" <?php if ($_POST['hab_compra'] == '0') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>
		
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita inventario *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="hab_invent" id="hab_invent"  title="Habilita inventario" class="form-control" required>
					   <option value="" >Seleccionar</option>
					<option value="1" <?php if ($_POST['hab_invent'] == '1') {?> selected="selected" <?php }  if (!isset($_POST['hab_compra'])) { ?>selected<?php } ?> >SI</option>
					<option value="0" <?php if ($_POST['hab_invent'] == '0') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>
		<?php if ($preferencias_configuraciones_alternativas == "S") {?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita Descuento </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="hab_desc" id="hab_desc"  title="Habilita Descuento" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['hab_desc'] == 'S') {?> selected="selected" <?php }  if (!isset($_POST['hab_compra'])) { ?>selected<?php } ?> >SI</option>
					<option value="N" <?php if ($_POST['hab_desc'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Modifica Precio</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="modifica_precio" id="modifica_precio"  title="Modifica Precio" class="form-control">
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['modifica_precio'] == 'S') {?> selected="selected" <?php } if (!isset($_POST['hab_compra'])) { ?>selected<?php } ?> >SI</option>
					<option value="N" <?php if ($_POST['modifica_precio'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Maneja Lote</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="maneja_lote" id="maneja_lote"  title="Maneja Lote" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="1" <?php if ($_POST['maneja_lote'] == '1') {?> selected="selected" <?php } ?>>SI</option>
					<option value="0" <?php if ($_POST['maneja_lote'] == '0') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Regimen turismo</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="regimen_turismo" id="regimen_turismo"  title="Regimen turismo" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['regimen_turismo'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['regimen_turismo'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">¿Es un Codigo Alternativo?</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="maneja_cod_alt" id="maneja_cod_alt" onchange="habilitar_codigo_alternativo(this.value)"  title="Maneja Codigo Alternativo" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['maneja_cod_alt'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['maneja_cod_alt'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-xs-12 form-group" id="box_cod_alternativo" style="display:none;">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Alternativo</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<div class="" style="display:flex;">
					<div class="dropdown " id="lista_cod_alternativo">
						<select onclick="myFunction2(event)"  class="form-control" id="idcod_alt" name="idcod_alt">
						<option value="" disabled selected></option>
					</select>
						<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Articulo" id="myInput2" onkeyup="filterFunction2(event)" >
						<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
							<?php echo $resultados_insumos_lista ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Centro Produccion </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
// consulta
$consulta = "
				Select idcentroprod,  descripcion
				from produccion_centros
				where
				estado <> 6
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['cpr'])) {
    $value_selected = htmlentities($_POST['cpr']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cpr',
    'id_campo' => 'cpr',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idcentroprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
			</div>
		</div>
		
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">% Rendimiento *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="rendimiento_porc" id="rendimiento_porc" value="<?php  if (isset($_POST['rendimiento_porc'])) {
			    echo floatval($_POST['rendimiento_porc']);
			} else {
			    echo "100";
			} ?>" placeholder="Rendimiento %" class="form-control" required="required" />
			</div>
		</div>
		
	
	
	
	
	<?php if ($usa_concepto == 'S') { ?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			<a href="javascript:void(0);" onClick="ventana_concepto_add();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>Concepto *
		</label>
		<div id="box_concepto" class="col-md-9 col-sm-9 col-xs-12">
			<?php require_once("insumos_lista_concepto.php"); ?>
		</div>
	</div>
	<?php } ?>
	
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion Produccion </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php
            // consulta
            $consulta = "
			Select idagrupacionprod,  agrupacion_prod
			from produccion_agrupacion 
			where 
			estado <> 6 
			order by agrupacion_prod asc
			";

// valor seleccionado
if (isset($_POST['idagrupacionprod'])) {
    $value_selected = htmlentities($_POST['idagrupacionprod']);
} else {
    $value_selected = htmlentities($rs->fields['idagrupacionprod']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idagrupacionprod',
    'id_campo' => 'idagrupacionprod',

    'nombre_campo_bd' => 'agrupacion_prod',
    'id_campo_bd' => 'idagrupacionprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
		</div>
	</div>
	
	
	<?php
    $contabilidad = intval($rsco->fields['contabilidad']);
if ($contabilidad == 1) {
    ?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Art Contable *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

            // consulta
            $consulta = "
			SELECT cuenta, descripcion
			FROM cn_plancuentas_detalles
			where 
			estado<>6 
			and asentable='S' 
			order by idserieun asc
			";

    // valor seleccionado
    if (isset($_POST['cuentacont'])) {
        $value_selected = htmlentities($_POST['cuentacont']);
    } else {
        $value_selected = htmlentities($rs->fields['cuentacont']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'cuentacont',
        'id_campo' => 'cuentacont',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'cuenta',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
		</div>
	</div>
	<?php } ?>
	<div class="clearfix"></div>
	<br />
</div>



    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 

			
            
<?php
$consulta = "
select *,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
(select usuario from usuarios where idusu = insumos_lista.registrado_por_usu ) as registrado_por
from insumos_lista 
where 
 estado = 'A' 
order by fechahora desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos 10 Agregados</h2>
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
			<th></th>
			<th align="center">Codigo Articulo</th>
			<th align="center">Codigo Producto</th>
			<th align="center">Articulo</th>
			<th align="center">Grupo Stock</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>
			<th align="center">Habilita compra</th>
			<th align="center">Habilita inventario</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="insumos_lista_edit.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <?php if (intval($rs->fields['idproducto']) == 0) { ?>
					<a href="insumos_lista_del.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['grupo_stock']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php if ($rs->fields['hab_compra'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php if ($rs->fields['hab_invent'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if (trim($rs->fields['fechahora']) != '') {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


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
