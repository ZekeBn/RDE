<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_stock.php");

$iddeposito = intval($_GET['iddeposito']);
$idconteo = intval($_GET['id']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function calcular_dif(id){
		conteo_guarda_tmp(id);
	}
	function accionbtn(cod){
		$("#accion").val(cod);	
		$("#form1").submit();
		$("#submit_1").hide();
		$("#submit_2").hide();
		$("#submit_3").hide();
	}
	function conteo_guarda_tmp(id){
		var campo_cant = "cont_"+id;
		var campo_name = $('#'+campo_cant).attr('name');
		var idprod_s = campo_name.split("_");
		var idprod = idprod_s[1];
		//alert(idprod);
		
		var cantidad = $("#"+campo_cant).val();
			var parametros = {
						"accion" : 1,
						"cant" : cantidad,
						"idprod" : idprod,
						"id" : <?php echo $idconteo; ?>
						
			};
			$.ajax({
						data:  parametros,
						url:   'conteo_guarda_tmp.php',
						type:  'post',
						beforeSend: function () {
							$("#dif_"+id).html('Guardando...');
						},
						success:  function (response) {
							$("#dif_"+id).html(response);
						}
				});	

	}
	function mantiene_session(){
		var f=new Date();
		cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
		var parametros = {
					"ses" : cad,
		};
		$.ajax({
					data:  parametros,
					url:   'mantiene_session.php',
					type:  'post',
					beforeSend: function () {
					},
					success:  function (response) {
						//alert(response);
					}
			});	
	}
	function buscar_producto_codbar(e){
		
		// que tecla presiono
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla==13){
			var codbar = $("#codbar").val();
			var direccionurl='conteo_stock_filtra.php';		
			var parametros = {
			"codbar"   : codbar,
			"id"       : <?php echo $idconteo; ?>
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				beforeSend: function () {
					$("#filtroprod").html('Cargando...');				
				},
				success:  function (response) {
					$("#filtroprod").html(response);
					if (tecla==13){
						$("#cont_1").focus();
					}
				}
			});
		}
	}
	function solonumerosypuntoycoma(e)
			{
				var keynum = window.event ? window.event.keyCode : e.which;
				if ((keynum == 8) || (keynum == 46) || (keynum == 190) || (keynum == 110) || (keynum == 188))
				return true;
			
				return /\d/.test(String.fromCharCode(keynum));
			}
	function seleccionar_almacenamiento(idalmacto){
		var parametros = {
			"idalmacto" 	: idalmacto,
			"iddeposito"	: <?php echo $iddeposito?>
		};
		console.log(parametros);
		$.ajax({
			data:  parametros,
			url:   'almacenamiento_dropdown.php',
			type:  'post',
			beforeSend: function () {
			},
			success:  function (response) {
				$("#dropdow_almacenamiento").html(response);
			}
		});	
	}
</script>
<style>
	.mt-1{
		margin-top: 20px !important;
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
                    <h2>Conteo <?php  ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
	<a href="conteo_stock_detalle.php?id=<?php echo $iddeposito?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

			<div id="buscador_conteo">
				<?php require_once("./buscador_conteo_deposito.php"); ?>
			</div>
			<div class="col-md-12" id="conteo_productos">
				<?php require("./conteo_productos_depositos.php");?>
			</div>


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
  </body>
</html>
