<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");
require_once("../insumos/funciones_insumos.php");



$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);


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
?>
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
<script>
    function verificar_concepto(){
		var concepto = $('#idconcepto').find("option:selected").text()
		if(concepto == "DESPACHO" || concepto == "FLETE" ){
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'none');
		}else{
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'block');
		}
	}
    function agregar_insumo_ajax(event){
        event.preventDefault();
        var valido ="S";
        var errores = "";
        var descripcion=$("#descripcion").val();
        var idconcepto=$("#idconcepto").val();
        var idmedida=$("#idmedida").val();
        var cant_medida2=$("#cant_medida2").val();
        var cant_medida3=$("#cant_medida3").val();
        var idmedida2=$("#idmedida2").val();
        var idmedida3=$("#idmedida3").val();
        var idsubcate_sec=$("#idsubcate_sec").val();
        var idpais=$("#idpais").val();
        var dias_utiles=$("#dias_utiles").val();
        var dias_stock=$("#dias_stock").val();
        var bar_code=$("#bar_code").val();
        var costo=$("#costo").val();
        var idtipoiva_compra=$("#idtipoiva_compra").val();
        var idgrupoinsu=$("#idgrupoinsu").val();
        var hab_compra=$("#hab_compra").val();
        var hab_invent=$("#hab_invent").val();
        var idproveedor=$("#idproveedor").val();
        var idcategoria=$("#idcategoria").val();
        var idsubcate=$("#idsubcate").val();
        var cuentacont=$("#cuentacont").val();
        var cpr=$("#cpr").val();
        var idagrupacionprod=$("#idagrupacionprod").val();
        var rendimiento_porc=$("#rendimiento_porc").val();
        var cant_caja_edi=$("#cant_caja_edi").val();
        var largo=$("#largo").val();
        var ancho=$("#ancho").val();
        var alto=$("#alto").val();
        var peso=$("#peso").val();
        var cod_fob=$("#cod_fob").val();
        var rs=$("#rs").val();
        var rspa=$("#rspa").val();
        var hab_desc=$("#hab_desc").val();
        var modifica_precio=$("#modifica_precio").val();
        var maneja_lote=$("#maneja_lote").val();
        var regimen_turismo=$("#regimen_turismo").val();
        var maneja_cod_alt=$("#maneja_cod_alt").val();
        var idcod_alt=$("#idcod_alt").val();

		var direccionurl='./compras_buscador_productos.php';	
		var parametros = {
			"descripcion"                   : descripcion,
            "idconcepto"                    : idconcepto,
            "idmedida"                      : idmedida,
            "cant_medida2"                  : cant_medida2,
            "cant_medida3"                  : cant_medida3,
            "idmedida2"                     : idmedida2,
            "idmedida3"                     : idmedida3,
            "idsubcate_sec"                 : idsubcate_sec,
            "idpais"                        : idpais,
            "dias_utiles"                   : dias_utiles,
            "dias_stock"                    : dias_stock,
            "bar_code"                      : bar_code,
            "costo"                         : costo,
            "idtipoiva_compra"              : idtipoiva_compra,
            "idgrupoinsu"                   : idgrupoinsu,
            "hab_compra"                    : hab_compra,
            "hab_invent"                    : hab_invent,
            "idproveedor"                   : idproveedor,
            "idcategoria"                   : idcategoria,
            "idsubcate"                     : idsubcate,
            "cuentacont"                    : cuentacont,
            "cpr"                           : cpr,
            "idagrupacionprod"              : idagrupacionprod,
            "rendimiento_porc"              : rendimiento_porc,
            "cant_caja_edi"                 : cant_caja_edi,
            "largo"                         : largo,
            "ancho"                         : ancho,
            "alto"                          : alto,
            "peso"                          : peso,
            "cod_fob"                       : cod_fob,
            "rs"                            : rs,
            "rspa"                          : rspa,
            "hab_desc"                      : hab_desc,
            "modifica_precio"               : modifica_precio,
            "maneja_lote"                   : maneja_lote,
            "regimen_turismo"               : regimen_turismo,
            "maneja_cod_alt"                : maneja_cod_alt,
            "idcod_alt"                     : idcod_alt,
            "agregar_insumo_lprod"       	: 1
            
			
		};

		//verificacion con js


		//fin verificacion

       /* $.ajax({		  
                data:  parametros,
                url:   "verificar_insumo.php",
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {
                            
                },
                success:  function (response, textStatus, xhr) {
				console.log(response);
				console.log(parametros);
                    /////////////////////////////////////////////////////
                    /////////////////////////////////////////////////////
					if(IsJsonString(response)){	
											// convierte a objeto
						var obj = jQuery.parseJSON(response);	
                    if(obj.valido == 'N'){
						alerta_error(obj.errores);
						$('#modal_ventana').animate({
							scrollTop: $('#boxErroresCabecera').offset().top
						}, 300);
					}else{*/
						$.ajax({		  
							data:  parametros,
							url:   direccionurl,
							type:  'post',
							cache: false,
							timeout: 3000,  // I chose 3 secs for kicks: 3000
							crossDomain: true,
							beforeSend: function () {
										
							},
							success:  function (response, textStatus, xhr) {
								// console.log(response);
								if(IsJsonString(response)){	
									var obj = jQuery.parseJSON(response);	
									if(obj.success == false){
										alerta_error(obj.errores);
										$('#modal_ventana').animate({
											scrollTop: $('#boxErroresCabecera').offset().top
										}, 300);
									}
								}else{
									$("#busqueda_productos").html(response);
									cerrar_pop2();

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


			/*		}
				}
                    
                    
                    
                    
                    //////////////////////////////////////////////////////

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
	*/
		

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
    function cambia_prov(){
		idproveedor = $("#idproveedor").val();
		var parametros = {
					"idproveedor"    : idproveedor,
                    "oculta_add"     : 1
					
			};
			$.ajax({
				data:  parametros,
				url:   '../insumos/dropdown_proveedor_fob.php',
				type:  'post',
				beforeSend: function () {
				},
				success:  function (response) {
					$("#box_cod_fob").html(response);
					
				}
			});
	}
    function subcategorias(idcategoria){
		var direccionurl='../insumos/subcate_new.php';	
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


		 direccionurl='../insumos/subcate_sec_new.php';	
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
		var direccionurl='../insumos/subcate_sec_new.php';	
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
    function cambiar_categorias(selectElement){
		var opcionSeleccionada = selectElement.options[selectElement.selectedIndex];
		var categoria_id = opcionSeleccionada.getAttribute('data-hidden-value');
		var idsubcate = opcionSeleccionada.getAttribute('data-hidden-value2');
		$('#idcategoria').val(categoria_id);


		var direccionurl='../insumos/subcate_new.php';	
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
    function habilitar_codigo_alternativo(valor){
		var box= $("#box_cod_alternativo");
		if (valor == "S"){
			box.css("display", "block");
		}else{
			box.css("display", "none");
		}
	}
    function cerrar_errores_cabecera(event){
		event.preventDefault();
		$('#boxErroresCabecera').removeClass('show');
		$('#boxErroresCabecera').addClass('hide');
	}
    function alerta_error(error){
		$("#erroresCabecera").html(error);
		$('#boxErroresCabecera').addClass('show');
	}
</script>

<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresCabecera">
	<button type="button" class="close" onclick="cerrar_errores_cabecera(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong>Errores:</strong><br /><p id="erroresCabecera"></p>
</div>


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
				 Proveedor 
			</label>
			<div id="select_proveedores" class="col-md-9 col-sm-9 col-xs-12">
				<?php require_once("../insumos/select_proveedores.php"); ?>
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
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Categoria * </label>
		<div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
			<?php
require_once("../insumos/cate_new.php");

?>
		</div>
	</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"> Subcategoria *</label>
			<div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
				<?php
    require_once("../insumos/subcate_new.php");
?>
			</div>
		</div>
		
		<?php if ($sub_categoria_secundaria == "S") { ?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12"> Sub Categoria Secundaria</label>
				<div class="col-md-9 col-sm-9 col-xs-12" id="subcatesecbox">
					<?php
    require_once("../insumos/subcate_sec_new.php");
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
		
	
	
	
	
	<?php
    $usa_concepto = $rsco->fields['usa_concepto'];
if ($usa_concepto == 'S') {
    ?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
		    Concepto *
		</label>
		<div id="box_concepto" class="col-md-9 col-sm-9 col-xs-12">
			<?php require_once("../insumos/insumos_lista_concepto.php"); ?>
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
	        <button onclick="agregar_insumo_ajax(event)" type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
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

	