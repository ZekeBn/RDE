<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


$iddeposito = intval($_GET['iddeposito']);
$idinsumo = intval($_GET['idinsumo']);
$idconteo = intval($_GET['id']);

$consulta = "SELECT descripcion from gest_depositos where iddeposito=$iddeposito";
$rs_depositos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_deposito = $rs_depositos->fields['descripcion'];

$consulta = "select idconteo,estado from 
                conteo 
            where
                idconteo = $idconteo
                and idempresa = $idempresa
";
$rs_estado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$conteo_estado = $rs_estado->fields['estado'];
$idconteo = intval($rs_estado->fields['idconteo']);


if ($idconteo == 0 || $conteo_estado == 2) {
    $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    header("location: $location");
    exit;
}

if ($idinsumo == 0) {
    $location = "conteo_stock_detalle.php?id=".$iddeposito;
    header("location: $location");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function guardar_conteo(event){
		event.preventDefault();
		var parametros = {
				"idconteo"		  : <?php echo $idconteo; ?>,
				"guardar_sub_conteo"	: 1
			};
			$.ajax({
					data:  parametros,
					url:   'guardar_conteo.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
					},
					success:  function (response) {
						console.log(response);
						if (JSON.parse(response)['success']==true) {
							var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
							document.location.href = url;
						}
					}
			});
	}
	function volver_atras(event){
		event.preventDefault();
		var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
		document.location.href = url;
	}
	function convertirAEntero(cadena) {
		// Intentar convertir la cadena en un número entero
		const numero = parseInt(cadena);

		// Verificar si la conversión fue exitosa
		if (!isNaN(numero)) {
			return numero; // La conversión fue exitosa, retornar el número entero
		} else {
			return 0; // La conversión falló, retornar 0
		}
	}
	function agregar_insumo(idmedida,tipo_medida){
		var lote=$("#lote_valor").val();
		var vencimiento=$("#vencimiento").val();
		var iddeposito = $("#iddeposito").val();
		var idalmacto = $("#idalmacto").val();
		var select_idalm = $("#idalm")[0];
		var html_idalm = select_idalm.options[select_idalm.selectedIndex];
		var idalm = html_idalm.value;
		var tipo_almacenamiento = html_idalm.getAttribute('data-hidden-value3');
		var fila = convertirAEntero($("#fila").val());
		var columna = convertirAEntero($("#columna").val());
		var idpasillo = $("#idpasillo").val();
		var idconteo = <?php echo intval($idconteo);?>

		///verificar que el almacenamiento no se duplique 
		//ver ojito 
		$("#erroresjs").hide();
		var errores="";

		var insumo=$("#seleccionado").attr("data-hidden-ocinsumo");
		if (insumo==''){
			errores=errores+"Debe indicar el insumo a ser comprado. <br/>";
		}
		var cantidad=$("#cantidad").val();
		if (cantidad==''){
			errores=errores+"Debe indicar cantidad adquirida. <br/>";
		}
		var obliga_lote=$("#seleccionado").attr("data-hidden-lote");
		if (obliga_lote==1 && (lote == '' || vencimiento =='')){
			errores=errores+"Debe indicar lote y vencimiento. <br/>";
		}

		if(tipo_almacenamiento==2 && idpasillo ==""){
			errores=errores+"Debe indicar el pasillo al que corresponde el articulo. <br/>";
		}
		if(tipo_almacenamiento==1 && (fila =="" || columna =="")){
			errores=errores+"Debe indicar la fila y columna al que corresponde el articulo. <br/>";
		}
		
		var idmedida =idmedida;

		///////////////////////////////////
		if (errores==''){
			var parametros = {
				"idinsumo"		  		: insumo,
				"cantidad"		  		: cantidad,
				"lote"			  		: lote,
				"vencimiento"	  		: vencimiento,
				"iddeposito"	  		: <?php echo $iddeposito; ?>,
				"idmedida"		  		: idmedida,
				"idalamcto"		  		: idalmacto,
				"idalm"		  	  		: idalm,
				"fila"		  	  		: fila,
				"columna"		  		: columna,
				"idpasillo"		  		: idpasillo,
				"tipo_almacenamiento"	:tipo_almacenamiento,
				"idconteo"		  		: idconteo,
				"agregar"		  		: 1
			};
			console.log(parametros);
			$.ajax({
					data:  parametros,
					url:   'conteo_por_productos_depositos.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
						 
					},
					success:  function (response) {
						console.log(response);
						$("#conteo_productos").html(response);
					}
			});
			
		} else {
			$("#errorestxt").html(errores);
			$("#erroresjs").show();
		}
		
		
	}
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
	
	function eliminar_articulo(unicose){
		var parametros = {
                "unicose"   : unicose,
				"idinsumo"		  		: <?php echo $idinsumo; ?>,
				"idconteo"	: <?php echo intval($idconteo); ?>,
				"borrar" 	: 1	
		};
		$.ajax({
                data:  parametros,
                url:   'conteo_por_productos_depositos.php',
                type:  'post',
                beforeSend: function () {
                      $("#conteo_productos").html('Cargando...');  
                },
                success:  function (response) {
					  $("#conteo_productos").html(response);
                }
        });

	}

	function editar_articulo(unicose){

		var parametros = {
                "unicose"   : unicose,
				"idconteo"	: <?php echo intval($idconteo); ?>	
		};

		$("#titulov").html("Datos de Articulo");
		$.ajax({		  
			data:  parametros,
			url:   'editar_articulo_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
								
			},
			success:  function (response) {
				$("#cuerpov").html(response);	
				$("#ventanamodal").modal("show");
				
				
			}
		});
		}
</script>
<style>
	.mt-1{
		margin-top: 20px !important;
	}
	input:focus, select:focus {
		border: #add8e6 solid 3px !important; /* Este es un tono de azul pastel */
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
                    <h2>Conteo <?php echo "Deposito ".$nombre_deposito; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
	<a href="conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

			<div id="buscador_conteo">
				<?php require_once("./buscador_conteo_deposito.php"); ?>
			</div>
			<br>
			<h2>Conteo Actual</h2>
			<div class="col-md-12" id="conteo_productos">
				<?php require("./conteo_por_productos_depositos.php");?>
			</div>
			
			<div class="col-md-12 col-xs-12"  style="text-align:center;">
				<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />

				<button  class="btn btn-secondary " style="width:15vw;"  onclick="volver_atras(event)"><span class="fa fa-reply"></span>&nbsp;Atras</button>
				<button  class="btn btn-primary " style="width:15vw;" id="btn_agregar" onclick="guardar_conteo(event);"><span class="fa fa-save"></span>&nbsp;Guardar</button>
			</div>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


			<!-- POPUP OCULTO  -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
				<div class="modal-dialog modal-lg">
				  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title" id="titulov"></h4>
					</div>
					<div class="modal-body" id="cuerpov" >
						
					</div>
					<div class="modal-footer"  id="piev">
					  
					  <button type="button" id="cerrarpop" style="display:none;" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
					  
					</div>

				  </div>
				</div>
			</div>
			<!-- FIN POPUP -->
            

            
            
            
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
