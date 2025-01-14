<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
?>
<script>
    function agregar_moneda_extranjera_ajax(){
        event.preventDefault();

        var parametros_array = {
            "descripcion"			        : $("#form_moneda_extranjera #descripcion").val(), 
            "nacional"					    : $("#form_moneda_extranjera #nacional").val(),
            "cotiza"					    : $("#form_moneda_extranjera #cotiza").val()
          
        };
        // console.log(parametros_array);
		$.ajax({		  
			data:  parametros_array,
			url:   'moneda_add_modal_ajax.php',
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
                   
                }else{
                    cerrar_pop();
                    recargar_moneda();
                }
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
		$('#form_moneda_extranjera #boxErroresProveedor').removeClass('show');
		$('#form_moneda_extranjera #boxErroresProveedor').addClass('hide');
	}
	function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#form_moneda_extranjera #boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#form_moneda_extranjera #boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#form_moneda_extranjera #boxErroresProveedor').addClass(alertaClase);
		$('#form_moneda_extranjera #boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#form_moneda_extranjera #boxErroresProveedor').addClass('show');
		
	}
</script>
<form id="form_moneda_extranjera" name="form_moneda_extranjera" method="post" action="" enctype="multipart/form-data">

    <div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
        <button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
        <strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
    </div>


    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">* Denominaci&oacute;n</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" name="descripcion" type="text" required="required" id="descripcion" placeholder="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
            echo htmlentities($_POST['descripcion']);
        } else {
            echo htmlentities($rs->fields['descripcion']);
        }?>" maxlength="60" />
        </div>
    </div>


    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda Nacional</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <?php

        if (isset($_POST['nacional'])) {
            $value_selected = htmlentities($_POST['nacional']);
        } else {
            $value_selected = 'N';
        }
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'nacional',
    'id_campo' => 'nacional',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
        </div>
    </div>


    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Cotiza *</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <?php

if (isset($_POST['cotiza'])) {
    $value_selected = htmlentities($_POST['cotiza']);
} else {
    $value_selected = 2;
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 2
];
// parametros
$parametros_array = [
    'nombre_campo' => 'cotiza',
    'id_campo' => 'cotiza',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 2,
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
        </div>
    </div>

    <div class="clearfix"></div>
    <br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <a type="submit" href="javascript:void(0);" onClick="agregar_moneda_extranjera_ajax(event);" class="btn btn-success" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-check-square-o"></span> Registrar</a>

        </div>
    </div>

	<input type="hidden" name="MM_update" value="form_moneda_extranjera" />
    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
    <br />
</form>
<div class="clearfix"></div>
<br /><br />