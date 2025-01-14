<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "124";
$dirsup = "S";
require_once("../includes/rsusuario.php");

?>

<script type="text/javascript">
    function agregar_marca_link(event){
        event.preventDefault();
        console.log("hola");

		var parametros_array = {
            "marca"					        : $("#form_marcas #marca").val() 
        };
        console.log(parametros_array);
		$.ajax({		  
			data:  parametros_array,
			url:   'modal_marca_add.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
			// $("#submitEditarPais").text('Cargando...');
			},
			success:  function (response) {
			console.log(response);
                if(JSON.parse(response)["valido"] == "N"){
                    alerta("danger",JSON.parse(response)["errores"],"Error");
                    var inputElement = $("#form_proveedores #ruc");
                    $('#dialogobox').animate({
                        scrollTop: inputElement.offset().top
                    }, 150);
                }else{
                    cerrar_pop();
                    recargar_marca();
                }
                // $("#dropdown_pais").html(response);
				// $("#form_proveedores #idmoneda").val($("#moneda_pais #idmoneda").val());
				// cerrar_detalles_pais();
			},
			error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
	}


    function cerrar_errores_proveedor(event){
		event.preventDefault();
		$('#form_marcas #boxErroresProveedor').removeClass('show');
		$('#form_marcas #boxErroresProveedor').addClass('hide');
	}
    function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#form_marcas #boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#form_marcas #boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#form_marcas #boxErroresProveedor').addClass(alertaClase);
		$('#form_marcas #boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#form_marcas #boxErroresProveedor').addClass('show');
		
	}
</script>
<form id="form_marcas" name="form_marcas" method="post" action="">

<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
    <button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Marca *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="marca" id="marca" value="<?php  if (isset($_POST['marca'])) {
	    echo htmlentities($_POST['marca']);
	} else {
	    echo htmlentities($rs->fields['marca']);
	}?>" placeholder="Marca" class="form-control" required="required" />                    
	</div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
            <a type="submit" href="javascript:void(0);" onClick="agregar_marca_link(event);" class="btn btn-success" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-check-square-o"></span> Registrar</a>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form_marcas" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />







