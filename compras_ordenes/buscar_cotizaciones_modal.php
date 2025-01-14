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
require_once("../compras/preferencias_compras.php");

$idmoneda = intval($_POST['idmoneda']);
$cerrar_div = intval($_POST['cerrar_div']);
?>
<script>
    function buscar_cotizacion_form(){
        
        var parametros = {
                "fecha_cotizacion"  : $("#form_feha_greilla_cot #fecha_cotizacion").val(),
                "idmoneda"  : <?php echo $idmoneda; ?>,
                "cerrar_div"  : <?php echo $cerrar_div; ?>
			};
        $.ajax({		  
				data:  parametros,
				url:   'grilla_cotizaciones_select.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					
				},
				success:  function (response) {
					$("#box_grilla_cotizacion_select").html(response);
				}
			});

    }
</script>
<form id="form_feha_greilla_cot" name="form_feha_greilla_cot" method="post" action="">
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha cotizacion </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="date" name="fecha_cotizacion" id="fecha_cotizacion" value="<?php  if (isset($_POST['fecha_cotizacion'])) {
            echo htmlentities($_POST['fecha_cotizacion']);
        } else {
            echo date("Y-m-d");
        }?>"  class="form-control"  />                    
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
            <a  href="javascript:void(0)" onclick="buscar_cotizacion_form()" type="submit" class="btn btn-success" ><span class="fa fa-search"></span> Buscar</a>
        </div>
    </div>
    <div class="clearfix"></div>
</form>
<div class="clearfix"></div>
<br>
<div id="box_grilla_cotizacion_select">
    <?php require_once("./grilla_cotizaciones_select.php");?>    
<div>