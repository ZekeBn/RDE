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

?>
<script>
    function cerrar_errores_proveedor(event){
		event.preventDefault();
		$('#form_concepto #boxErroresProveedor').removeClass('show');
		$('#form_concepto #boxErroresProveedor').addClass('hide');
	}
    function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#form_concepto #boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#form_concepto #boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#form_concepto #boxErroresProveedor').addClass(alertaClase);
		$('#form_concepto #boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#form_concepto #boxErroresProveedor').addClass('show');
		
	}
    function agregar_proveedores_ajax(event){
        event.preventDefault();

		var parametros_array = {
            "concepto"					        : $("#form_concepto #concepto").val()
        };
        // console.log(parametros_array);
		$.ajax({		  
			data:  parametros_array,
			url:   'concepto_add_ajax.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
			// $("#submitEditarPais").text('Cargando...');
			},
			success:  function (response) {
				// console.log(response);

				if(IsJsonString(response)){
					// console.log(response)
					var obj = jQuery.parseJSON(response);
					if(obj.success == true){
						cerrar_pop();
						recargar_concepto(obj.idconcepto);
					}else{
						alerta("danger",JSON.parse(response)["errores"],"Error");
					}
				}
                
			},
			error: function(jqXHR, textStatus, errorThrown) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
	}
</script>
<form id="form_concepto" name="form_concepto" method="post" action="" enctype="multipart/form-data">
    <div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
        <button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
        <strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
    </div>

		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="concepto" id="concepto" value="<?php  if (isset($_POST['concepto'])) {
					    echo htmlentities($_POST['concepto']);
					} else {
					    echo htmlentities($rs->fields['concepto']);
					}?>" placeholder="Concepto" class="form-control"  />                    
				</div>
			</div>
			
	<div class="clearfix"></div>
	<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
            <a type="submit" href="javascript:void(0);" onClick="agregar_proveedores_ajax(event);" class="btn btn-success" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-check-square-o"></span> Guardar</a>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form_concepto" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
</form>


<div class="clearfix"></div>
<br /><br />


