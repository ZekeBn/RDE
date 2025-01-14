<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("./preferencias_cotizacion.php");
global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;



if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // recibe parametros
    $cotizacion = antisqlinyeccion($_POST['cotizacion'], "float");
    $compra = antisqlinyeccion($_POST['compra'], "folat");
    $estado = antisqlinyeccion(1, "int");
    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $tipo_moneda = antisqlinyeccion($_POST['tipo_moneda'], "int");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;

    if ($compra == "'NULL'" || $compra == "") {
        $compra = "NULL";
    }
    if ($editar_fecha == "N") {
        $fecha = "'$ahora'";
    }


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if ($cotizacion == 0) {
        $valido = "N";
        $errores .= " - El campo cotizacion no puede ser cero o nulo.<br />";
    }
    if ($tipo_moneda == 0) {
        $valido = "N";
        $errores .= " - El campo tipo_moneda no puede ser cero o nulo.<br />";
    }
    if ($editar_fecha == 'S') {

        if ($fecha == "") {
            $valido = "N";
            $errores .= " - El campo fecha no puede ser nulo.<br />";
        } else {
            if ($tipo_moneda != 0) {
                $fecha_format = "";
                if ($cotiza_dia_anterior == "S") {
                    $fecha_format = date("Y-m-d", strtotime($_POST['fecha']) . " -1 day");
                } else {
                    $fecha_format = date("Y-m-d", strtotime($_POST['fecha']));
                }

                $consulta = "SELECT 
				count(*) as cotizaciones_datos
			FROM 
				cotizaciones
			WHERE 
				cotizaciones.estado = 1 
				AND DATE(cotizaciones.fecha) = '$fecha_format'
				AND cotizaciones.tipo_moneda = $tipo_moneda
				ORDER BY cotizaciones.fecha DESC
				LIMIT 1";
                // echo $consulta; exit;

                $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $num_cot = intval($rsmax->fields['cotizaciones_datos']);
                if ($num_cot > 0) {
                    $valido = "N";
                    $fecha_format = date("d/m/Y", strtotime($_POST['fecha']));
                    $errores .= " - Ya existe una cotizacion con la fecha $fecha_format.<br />";
                }
            }

        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
		insert into cotizaciones
		(cotizacion, compra, estado, fecha, tipo_moneda, registrado_por, registrado_el)
		values
		($cotizacion, $compra, $estado, $fecha, $tipo_moneda, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: cotizaciones.php");
        exit;

    }

}



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<script>
		function recargar_moneda(){
			var direccionurl='moneda_extranjera_select.php';
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
					$("#box_moneda_extranjera_id").html('Cargando...');				
				},
				success:  function (response, textStatus, xhr) {
					$("#box_moneda_extranjera_id").html(response);	
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
		function cerrar_pop(){
			$("#dialogobox").modal("hide");
		}
		function alerta_modal(titulo,mensaje){
			$('#dialogobox').modal('show');
			$("#myModalLabel").html(titulo);
			$("#modal_cuerpo").html(mensaje);
		}
		function agregar_moneda_modal(){
			var direccionurl='moneda_add_modal.php';	
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
                    <h2>Agregar Cotizaciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">  
					  <a href="cotizaciones.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
     	 	


<p align="center">&nbsp;</p>
<p align="center">&nbsp;</p>
<p>&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-12 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">* Cotizaci&oacute;n (venta) </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="cotizacion" id="cotizacion" value="<?php  if (isset($_POST['cotizacion'])) {
		    echo htmlentities($_POST['cotizacion']);
		} else {
		    echo htmlentities($rs->fields['cotizacion']);
		}?>" placeholder="cotizacion(venta)" required="required" style="height: 40px; width: 99%;" />
	</div>
</div>
<?php if ($usa_cot_compra == "S") { ?>
	<div class="col-md-6 col-sm-12 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Cotizaci&oacute;n (compra) </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="compra" id="compra" value="<?php  if (isset($_POST['compra'])) {
			    echo htmlentities($_POST['compra']);
			} else {
			    echo htmlentities($rs->fields['compra']);
			}?>" placeholder="cotizacion(compra)" required="required" style="height: 40px; width: 99%;" />
		</div>
	</div>
<?php } ?>
	
<?php if ($editar_fecha == 'S') { ?>
	<div class="col-md-6 col-sm-12 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> * Fecha  </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="date" name="fecha" id="fecha" value="<?php  if (isset($_POST['fecha'])) {
			    echo htmlentities($_POST['fecha']);
			} else {
			    echo date("Y-m-d", strtotime($ahora));
			}?>" placeholder="fecha" required="required" style="height: 40px; width: 99%;" />
		</div>
	</div>
<?php } ?>
<div id="box_moneda_extranjera_id">
	<?php require_once("./moneda_extranjera_select.php"); ?>
</div>


<br />
<p align="center">
  <input type="hidden" name="MM_insert" value="form1" />
</p>
<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
        </div>
    </div>
<br />
</form>
<br /><br /><br />


</div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


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