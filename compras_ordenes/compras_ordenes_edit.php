<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../compras/preferencias_compras.php");





$ocnum = intval($_GET['id']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}

// consulta a la tabla
$consulta = "
select compras_ordenes.* ,compras_ordenes.idproveedor, prov.nombre as proveedor, tipo_o.tipo as tipo_origen
from compras_ordenes 
left join proveedores as prov on prov.idproveedor = compras_ordenes.idproveedor 
left join tipo_origen as tipo_o on tipo_o.idtipo_origen = compras_ordenes.idtipo_origen  
where 
compras_ordenes.ocnum = $ocnum
and compras_ordenes.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocnum = intval($rs->fields['ocnum']);
$idcot = intval($rs->fields['idcot']);
$idproveedor_res = intval($rs->fields['idproveedor']);
$proveedor_res = antixss($rs->fields['proveedor']);


$buscar = "SELECT cotizacion FROM cotizaciones where idcot = $idcot ";

$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$cotizacion = floatval($rsd->fields['cotizacion']);


// echo $proveedor_res;exit;
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}




$buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);



$buscar = "SELECT idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra
FROM proveedores
";

$resultados_proveedores = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = trim(antixss($rsd->fields['idproveedor']));
    $idmoneda = trim(antixss($rsd->fields['idmoneda']));
    $idtipo_origen = trim(antixss($rsd->fields['idtipo_origen']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $tipocompra = trim(antixss($rsd->fields['tipocompra']));
    $ruc = trim(antixss($rsd->fields['ruc']));
    $resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$ruc' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra);\">[$idproveedor]-$nombre</a>
	";

    $rsd->MoveNext();
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
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
    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $idcot = antisqlinyeccion($_POST['idcot'], "text");
    //$generado_por=antisqlinyeccion($_POST['generado_por'],"int");
    $tipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
    $idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");
    if ($idtipo_origen_importacion == $idtipo_origen) {

        $idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
    } else {
        if ($multimoneda_local == "S") {
            $idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
        } else {
            $idtipo_moneda = 0;
        }
    }
    $fecha_entrega = antisqlinyeccion($_POST['fecha_entrega'], "text");
    if ($fecha_entrega == "NULL") {
        $fecha_entrega = "00-00-00";
    }
    if (intval($idtipo_moneda) == 0 && $idtipo_origen_importacion == $idtipo_origen) {
        $valido = "N";
        $errores .= " - No indico el tipo de Moneda en una Orden de Compra tipo Importacion.<br />";
    }
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");




    if (trim($_POST['fecha']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha no puede estar vacio.<br />";
    }

    if (intval($_POST['idtipocompra']) == 0) {
        $valido = "N";
        $errores .= " - No indico si la compra sera contado o credito.<br />";
    }


    if (intval($_POST['idproveedor']) == 0) {
        $valido = "N";
        $errores .= " - El indico el proveedor.<br />";
    }



    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update compras_ordenes
		set
		fecha=$fecha,
		tipocompra=$tipocompra,
		fecha_entrega=$fecha_entrega,
		idproveedor=$idproveedor,
		idcot=$idcot,
		idtipo_origen=$idtipo_origen,
		idtipo_moneda=$idtipo_moneda
		where
		ocnum = $ocnum
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: compras_ordenes.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// echo json_encode($resultados_proveedores);exit;


?>
<!DOCTYPE html>
<html lang="en">
	<head>
	<?php require_once("../includes/head_gen.php"); ?>
	<script>
			function verificar_cotizacion_moneda(){
				var parametros = {
				"idmoneda"   : $("#idtipo_moneda").val()
				};
				$.ajax({
					data:  parametros,
					url:   '../cotizaciones/cotizaciones_hoy.php',
					type:  'post',
					beforeSend: function () {
						
					},
					success:  function (response) {
						console.log(response);
						if(JSON.parse(response)['success']==false){
							alerta_modal("Alerta!",JSON.parse(response)['error']);
							$("#idcot").css('border', '1px solid red');
							// $('#idcot').prop('readonly', true);
						}else{
							
							var cotiza = JSON.parse(response)['cotiza'];
							if(cotiza == true){
								var idcot = JSON.parse(response)['idcot'];
								var cotizacion = JSON.parse(response)['cotizacion'];
								$('#idcot').html($('<option>', {
									value: idcot,
									text: cotizacion
								}));
								
							
								// Seleccionar opción
								$('#idcot').val(idcot);
								$('#idcotizacion').val(idcot);
								$('#idcot').prop('disabled', true);
								$("#idcot").css('border', '1px solid #ccc');

							}else{
								$('#idcot').html("");
								$('#idcot').prop('disabled', true);
								$("#idcot").css('border', '1px solid #ccc');
							}
							
						
						}
					}
				});
			}
		function cambia_proveedor(idtipo_origen, idmoneda, idproveedor, nombre,idtipo_compra){
			// alerta_modal("contenido",idtipo_origen+ " "+idmoneda);
			$('#idproveedor').html($('<option>', {
					value: idproveedor,
					text: nombre
				}));
		
				var myInput = $('#myInput2');
				var myDropdown = $('#myDropdown2');
				myInput.removeClass('show');
				myDropdown.removeClass('show');
				$("#idmoneda").val(idmoneda);
				$("#idtipo_origen").val(idtipo_origen);
				$("#idtipocompra").val(idtipo_compra);
				verificar_tipo(idtipo_origen);
			
		}
		function myFunction2(event) {
			event.preventDefault();
			document.getElementById("myInput2").classList.toggle("show");
			document.getElementById("myDropdown2").classList.toggle("show");
			div = document.getElementById("myDropdown2");
			$("#myInput2").focus();
			

				
			$(document).mousedown(function(event) {
				var target = $(event.target);
				var myInput = $('#myInput2');
				var myDropdown = $('#myDropdown2');
				var div = $("#lista_proveedores");
				var button = $("#iddepartameto");
				// Verificar si el clic ocurrió fuera del elemento #my_input
				if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
				// Remover la clase "show" del elemento #my_input
				myInput.removeClass('show');
				myDropdown.removeClass('show');
				}
				
			});
		}

		function filterFunction2(event) {
			event.preventDefault();
			var input, filter, ul, li, a, i;
			input = document.getElementById("myInput2");
			filter = input.value.toUpperCase();
			div = document.getElementById("myDropdown2");
			a = div.getElementsByTagName("a");
			for (i = 0; i < a.length; i++) {
				txtValue = a[i].textContent || a[i].innerText;
				rucValue = a[i].getAttribute('data-hidden-value');
				

					if(txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 ) {
						a[i].style.display = "block";
					} else {
						a[i].style.display = "none";
					}
				
				
			}
		}

		function alerta_modal(titulo,mensaje){
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html(titulo);
			$("#modal_cuerpo").html(mensaje);
		}
		function verificar_tipo(clase){
			var idimportacion = <?php echo $idtipo_origen_importacion; ?>;
			if (clase==idimportacion){
				$("#monedas").show();
				
			}else{
				<?php if ($multimoneda_local == "S") { ?>
					$("#monedas").show();					
				<?php } else { ?>
					$("#monedas").hide();					
				<?php } ?>
			}
				
		}
		function recuperar_cambio(){
			
			var parametros = {
			"idmoneda"   : $("#idtipo_moneda").val()
			};
			$.ajax({
					data:  parametros,
					url:   'cotizaciones_hoy.php',
					type:  'post',
					beforeSend: function () {
						// $("#listaprodudiv").html('Cargando...');  
					},
					success:  function (response) {
						// $("#listaprodudiv").html(response);
						console.log(JSON.parse(response));
						if(JSON.parse(response)['success']==false){
							alerta_modal("Alerta!",JSON.parse(response)['error']);
						}else{
							$("#cot_ref").val(JSON.parse(response)['cotizacion']);
							$("#cotRefHelp").html("Fecha: " + JSON.parse(response)['fecha']);
						}
					}
			});
					
		}

				
		window.onload = function() {
		
			$('#idproveedor').on('mousedown', function(event) {
				// Evitar que el select se abra
				event.preventDefault();
			});
			<?php if ($idtipo_origen_importacion == 0) { ?>
				alerta_modal("Alerta !", "<h2>El Elemento Tipo Origen: Importacion no fue Creado.</h2>");
			
			<?php }?>
				verificar_tipo($("#idtipo_origen").val());
		};

	</script>
	<style type="text/css">
        #lista_proveedores {
            width: 100%;
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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Orden de Compra</h2>
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

				<div class=" form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<div class="" style="display:flex;">
							<div class="dropdown " id="lista_proveedores">
								<select onclick="myFunction2(event)"  class="form-control" id="idproveedor" name="idproveedor">
								<option value="" disabled ></option>
								<?php if (intval($idproveedor_res) > 0) { ?>
									asd
									<option value="<?php echo $idproveedor_res; ?>"   selected><?php echo $proveedor_res; ?></option>
								<?php } ?>
							</select>
								<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre/ruc Proveedor" id="myInput2" onkeyup="filterFunction2(event)" >
								<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
									<?php echo $resultados_proveedores ?>
								</div>
							</div>
								<!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
									<span  class="fa fa-plus"></span> Agregar
								</a> -->
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
				<?php if ($proveedores_importacion == "S") { ?>
				<div class=" form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Origen *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

                        // consulta

                        $consulta = "
						SELECT *
						FROM tipo_origen
						order by tipo asc
						";

				    // valor seleccionado
				    if (isset($_POST['idtipo_origen'])) {
				        $value_selected = htmlentities($_POST['idtipo_origen']);
				    } else {
				        $value_selected = $rs->fields['idtipo_origen'];
				    }



				    // parametros
				    $parametros_array = [
				        'nombre_campo' => 'idtipo_origen',
				        'id_campo' => 'idtipo_origen',

				        'nombre_campo_bd' => 'tipo',
				        'id_campo_bd' => 'idtipo_origen',

				        'value_selected' => $value_selected,

				        'pricampo_name' => 'Seleccionar...',
				        'pricampo_value' => '',
				        'style_input' => 'class="form-control"',
				        'acciones' => ' required="required" onchange="verificar_tipo(this.value)" "'.$add,
				        'autosel_1registro' => 'N'

				    ];

				    // construye campo
				    echo campo_select($consulta, $parametros_array);

				    ?>
					</div>
				</div>
				<?php } ?>

				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Orden *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="date" name="fecha" id="fecha" value="<?php  if (isset($_POST['fecha'])) {
					    echo htmlentities($_POST['fecha']);
					} else {
					    echo htmlentities($rs->fields['fecha']);
					}?>" placeholder="Fecha" class="form-control" required="required" />                    
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipocompra *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<?php

                        // consulta

                        $consulta = "
						SELECT *
						FROM tipocompra
						order by tipocompra asc
						";

// valor seleccionado
if (isset($_POST['idtipocompra'])) {
    $value_selected = htmlentities($_POST['idtipocompra']);
} else {
    $value_selected = $rs->fields['tipocompra'];
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idtipocompra',
    'id_campo' => 'idtipocompra',

    'nombre_campo_bd' => 'tipocompra',
    'id_campo_bd' => 'idtipocompra',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha entrega </label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="date" name="fecha_entrega" id="fecha_entrega" value="<?php  if (isset($_POST['fecha_entrega'])) {
					    echo htmlentities($_POST['fecha_entrega']);
					} else {
					    echo htmlentities($rs->fields['fecha_entrega']);
					}?>" placeholder="Fecha entrega" class="form-control"  />                    
					</div>
				</div>
				<div id="monedas" class="form-group" style="display:none;">
					<div class="form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
							<?php

                                // consulta


                                $consulta = "
								Select * from tipo_moneda where estado=1 
								";

// valor seleccionado
if (isset($_POST['idtipo_moneda'])) {
    $value_selected = htmlentities($_POST['idtipo_moneda']);
} else {
    $value_selected = $rs->fields['idtipo_moneda'];
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idtipo_moneda',
    'id_campo' => 'idtipo_moneda',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => 'onchange="verificar_cotizacion_moneda(this.value)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotizacion </label>
						<div class="col-md-9 col-sm-9 col-xs-12">
							<select readonly name="idcot" id="idcot" aria-describedby="cotRefHelp"  class="form-control">
								<?php if (intval($idcot) > 0) { ?>
									
									<option readonly selected value="<?php echo $idcot;?>"><?php echo $cotizacion;?></option>
								<?php } ?>
							</select>
							<small id="cotRefHelp"><small> 
							<input type="hidden" name="idcotizacion" id="idcotizacion" value="<?php  if (isset($_POST['idcotizacion'])) {
							    echo htmlentities($_POST['idcotizacion']);
							} else {
							    echo htmlentities($rs->fields['idcot']);
							}?>" />   
						</div>
					</div>
					<div class="clearfix"></div>
					<br>
				</div><div class="form-group">
					<div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
						<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
						<button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes.php'"><span class="fa fa-ban"></span> Cancelar</button>
					</div>
				</div>
				
				<input type="hidden" name="MM_update" value="form1" />
				<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
				<br>
				<br />
			</form>
				


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            




<!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->



            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
