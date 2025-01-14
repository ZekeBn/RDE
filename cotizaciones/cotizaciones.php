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

$consulta = "
	SELECT *, tipo_moneda.borrable, tipo_moneda.descripcion,
	(select usuario from usuarios where cotizaciones.registrado_por = usuarios.idusu) as registrado_por
	FROM cotizaciones
	inner join tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
	where
	cotizaciones.estado = 1
	and tipo_moneda.estado = 1
	order by cotizaciones.fecha desc
	limit 50
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
	function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
	// error
	if(tipo == 'error'){
		if(jqXHR.status == 404){
			alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
		}else if(jqXHR.status == 0){
			alert('Se ha rechazado la conexión.');
		}else{
			alert(jqXHR.status+' '+errorThrown);
		}
	// fail
	}else{
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
	}
}
    function agregar_cotizacion_set(){
      document.getElementById('preloader-overlay').style.display = 'flex';
      $.ajax({		
      data:{"SET":1},  
			url:   'grilla_cotizaciones.php',
			type:  'post',
			cache: false,
			timeout: 15000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
			},
			success:  function (response) {
        setTimeout(function(){ location.reload();document.getElementById('preloader-overlay').style.display = 'none'; },3000);
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
      
    }
    function ver_cotizacion_set(){
      document.getElementById('preloader-overlay').style.display = 'flex';
      $.ajax({		
      data:{"SET":1},  
			url:   'cotizacion_set_ver.php',
			type:  'post',
			cache: false,
			timeout: 15000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
			},
			success:  function (response) {
				console.log(response);
			document.getElementById('preloader-overlay').style.display = 'none';
			var res =JSON.parse(response);
			if (res.success==true){
				var usa_cot_compra = <?php echo $usa_cot_compra ? "'$usa_cot_compra'" : "'N'" ; ?>;
				var text = "Fecha: "+res['fecha']+" "+res['moneda']+" VENTA: "+res['cotizacion'];
				if(usa_cot_compra == "S"){
					text += " COMPRA: "+res['compra'];
				}
				$("#cotizacion_set").html(text)
			}else{
				var resultado = res['error']+ res['texto'];
				$("#cotizacion_set").html(resultado)
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
  <style>
    /* /////////////////////////// */
		#preloader-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 9999;
		display: none;
		align-items: center;
		justify-content: center;
		}

		.lds-facebook {
		display: inline-block;
		position: relative;
		width: 80px;
		height: 80px;
		}

		.lds-facebook div {
		display: inline-block;
		position: absolute;
		left: 8px;
		width: 16px;
		background: #fff;
		animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
		}

		.lds-facebook div:nth-child(1) {
		left: 8px;
		animation-delay: -0.24s;
		}

		.lds-facebook div:nth-child(2) {
		left: 32px;
		animation-delay: -0.12s;
		}

		.lds-facebook div:nth-child(3) {
		left: 56px;
		animation-delay: 0;
		}

		@keyframes lds-facebook {
		0% {
			top: 8px;
			height: 64px;
		}
		50%,
		100% {
			top: 24px;
			height: 32px;
		}
		}
	/* /////////////////////// */
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
                    <h2>Cotizaciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
	
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      			<br /><br />
    		
      
				<a href="cotizaciones_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
				<a href="javascript:void(0);"  onclick="agregar_cotizacion_set()" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Cotizacion Dolar SET</a>
				<a href="javascript:void(0);"  onclick="ver_cotizacion_set()" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Ver Cotizacion Dolar SET</a>
				<a href="monedas_extranjeras.php"  class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Monedas</a>
					<p align="center"><strong>(No se debe editar por que debe manterner un historico)</strong></p><br />
					<p align="center"><strong id="cotizacion_set">
					
					</strong></p><br />

				<p>&nbsp;</p>
				<?php require("./grilla_cotizaciones.php");?>

				<br />



</div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 




            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
<div id="preloader-overlay">
	<div class="lds-facebook">
		<div></div>
		<div></div>
		<div></div>
	</div>
</div>
  </body>
</html>
