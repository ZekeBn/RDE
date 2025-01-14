<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente


// http://localhost/desarrollo/asignar_productos/gest_deposito_productos_new.php?idpo=4
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");




$idpo = intval($_GET['idpo']);
if ($idpo == 0) {
    header("Location:gest_adm_depositos.php");
    exit;
}
$iddeposito = $idpo;
$consulta = "SELECT descripcion from gest_depositos where iddeposito = $iddeposito
    ";
$rsf = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
	document.addEventListener("DOMContentLoaded", function() {

		$("#form_buscador  input").keydown(function(event) {
		// Verifica si la tecla presionada es "Enter"
			if (event.keyCode === 13) {
				
				// Cancela el comportamiento predeterminado del formulario
				event.preventDefault();
				// Envía el formulario
				// $(this).closest("form").submit();
				$("#form_buscador #btn_agregar").click();
			}
		});

	});
function cerrar_errores_compras(){
	$('#boxErroresCompras').removeClass('show');
	$('#boxErroresCompras').addClass('hide');
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
    	var idregseriedptostk = $("#seleccionado").attr('data-hidden-idregseriedptostk')

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
		if(tipo_almacenamiento=="" || tipo_almacenamiento==undefined){
			errores=errores+"Debe indicar el tipo de almacenamiento. <br/>";
		}
		if(idalm=="" || idalm == undefined){
			errores=errores+"Debe indicar el almacenamiento. <br/>";
		}
		if(tipo_almacenamiento==1 && (fila =="" || columna =="")){
			errores=errores+"Debe indicar la fila y columna al que corresponde el articulo. <br/>";
		}
		
		var idmedida = idmedida;

		///////////////////////////////////
		if (errores==''){
			var parametros = {
				"idinsumo"		          	: insumo,
				"cantidad"		          	: cantidad,
				"lote"			            : lote,
				"vencimiento"	          	: vencimiento,
				"idpo"	          			: <?php echo $iddeposito; ?>,
				"idmedida"		          	: idmedida,
				"idalamcto"		          	: idalmacto,
				"idalm"		  	          	: idalm,
				"fila"		  	          	: fila,
				"columna"		            : columna,
				"idpasillo"		         	: idpasillo,
				"tipo_almacenamiento"   	: tipo_almacenamiento,
        		"idregseriedptostk"     	: idregseriedptostk,
				"boton_datos"				: obtenerValorClaseActiva(),
				"agregar"		            : 1
			};
			// console.log(parametros);
			$.ajax({
					data:  parametros,
					url:   'buscador_conteo_deposito.php',
					type:  'post',
					beforeSend: function () {
						$("#buscador_conteo").html('Cargando...');  
					},
					success:  function (response) {
						// console.log(response);
						$("#buscador_conteo").html(response);
            			$("#cod_lote").focus();
					}
			});

		} else {
			$("#errorestxt").html(errores);
			$("#erroresjs").show();
		}
		
		
	}
	function obtenerValorClaseActiva() {
		var botonTabla = document.getElementById("button_data_table");
		var botonGrafico = document.getElementById("button_data_graphic");

		if (botonTabla?.classList.contains("active")) {
			return 1;
		} else if (botonGrafico?.classList.contains("active")) {
			return 2;
		} else {
			return 0;
		}
	}
	function accionbtn(cod){
		$("#accion").val(cod);	
		$("#form1").submit();
		$("#submit_1").hide();
		$("#submit_2").hide();
		$("#submit_3").hide();
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
			"codbar"   : codbar
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
	function solonumerosypuntoycoma(e){
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
		// console.log(parametros);
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
		
	.hover_cancelar:hover{
		background-color: #8CB3D9;
		color: #fff !important;
		border: #8CB3D9 solid 1px;
	}
  .active{
    background-color: #8CB3D9 !important;
		color: #fff !important;
		border: #8CB3D9 solid 1px !important;
  }
	.grid-container {
      display: grid;
      
      grid-gap: 10px;
      width: 55vw;
      margin: 2vh;
    }

    .grid-item {
      background-color: #f2f2f2;
      padding: 20px;
      font-size: 30px;
      text-align: center;
    }

    .active_status{
      background-color: #E39774;
      padding: 20px;
      box-shadow: 5px 5px 5px #888888;
      color: #ffffff;
      border: 2px solid #d18d67;
      box-sizing: border-box;
    }
    .estante_container{
      display: flex;
      justify-content: center;
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
                    <h2>Deposito: <?php echo antixss($rsf->fields['descripcion']);?> </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


                  <p>
                    <a href="../deposito/gest_deposito_admin.php?idpo=<?php echo $iddeposito?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                  </p>
                  <hr />

                  <div id="buscador_conteo">
                    <?php require_once("./buscador_conteo_deposito.php"); ?>
                  </div>
                  <br>

                  


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 





			<!-- SECCION INICIO  -->
<div class="row" style="display:none;">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" >
                  <div class="x_title">
                    <h2>Detalles Estantes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" style="display: none;">



                  <!-- ///////////////////////////////////////////////////// -->

<!-- ////////////////////////////////////////////////////// -->


                  </div>
                </div>
              </div>
            </div>


                  <!-- SECCION FIN  -->
            
            
            
            
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
