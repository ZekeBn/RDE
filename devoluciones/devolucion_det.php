<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");


$iddevolucion = intval($_GET['id']);

// $urlParts = parse_url($pagina_actual);

$consulta = "select * from  devolucion where iddevolucion = $iddevolucion
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = $rs->fields['idventa'];

$consulta = "
SELECT 
  ventas_detalles.pventa, SUM(ventas_detalles.cantidad) as cantidad, 
  productos.descripcion as producto
FROM
  ventas_detalles 
  INNER JOIN productos on productos.idprod = ventas_detalles.idprod 
WHERE 
  idventa = $idventa
  GROUP BY ventas_detalles.idprod, ventas_detalles.pventa
";
$rs_ventas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
SELECT 
  cliente.razon_social as cliente, 
  CONCAT(
    COALESCE(vendedor.nombres, ''), 
    ' ', 
    COALESCE(vendedor.apellidos, '')
  ) as vendedor 
FROM 
  ventas 
  INNER JOIN cliente on cliente.idcliente = ventas.idcliente 
  INNER JOIN vendedor on vendedor.idvendedor = cliente.idvendedor 
WHERE 
  idventa = $idventa
";
$rs_data = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("../includes/head_gen.php"); ?>
    <script>
      function guardar_devolucion(){
        var parametros = {
            "iddevolucion"       : <?php echo $iddevolucion; ?>
        };
        $.ajax({		  
          data:  parametros,
          url:   'guardar_devolucion.php',
          type:  'post',
          cache: false,
          timeout: 3000,  // I chose 3 secs for kicks: 3000
          crossDomain: true,
          beforeSend: function () {	
                    
          },
          success:  function (response) {
          console.log(response);
          if(JSON.parse(response)['success']==true){
            location.href="devolucion.php";
          }
          }
        });
      
      }
      
    
      function alerta_modal(titulo,mensaje){
        $('#modal_ventana').modal('show');
        $("#modal_titulo").html(titulo);
        $("#modal_cuerpo").html(mensaje);
      }
      function eliminar_articulo(iddevolucion_det){
        var parametros = {
            "iddevolucion_det"   : iddevolucion_det,
            "iddevolucion"       : <?php echo $iddevolucion; ?>,
            "idventa"			  		  : <?php echo $idventa ? $idventa : 0; ?>,
            "borrar"             : 1
        };
        $.ajax({		  
          data:  parametros,
          url:   'conteo_por_productos_depositos.php',
          type:  'post',
          cache: false,
          timeout: 3000,  // I chose 3 secs for kicks: 3000
          crossDomain: true,
          beforeSend: function () {	
                    
          },
          success:  function (response) {
            $("#conteo_productos").html(response);
          }
        });
      }

      function agregar_insumo(idmedida,tipo_medida){
        var lote=$("#lote_valor").val();
        var vencimiento = $("#vencimiento").val();
        var iddeposito = $("#iddeposito").val();
        var comentario = $("#comentario").val();
        var cantidad_restante = $("#seleccionado").attr("data-hidden-cantidad-restante");
        cantidad_restante = parseFloat(cantidad_restante);
        var iddevolucion = <?php echo $iddevolucion; ?>;
        
        $("#erroresjs").hide();
        var errores="";

        var insumo=$("#seleccionado").attr("data-hidden-ocinsumo");
        var idproducto=$("#seleccionado").attr("data-hidden-idproducto");
        if (insumo==''){
          errores=errores+"Debe indicar el insumo a ser devuelto. <br/>";
        }
        if (iddeposito=='' || iddeposito==undefined){
          errores=errores+"Debe indicar el deposito a ser ingresado. <br/>";
        }
        var cantidad=$("#cantidad").val();
        cantidad = parseFloat(cantidad);
        if (cantidad==''){
          errores=errores+"Debe indicar cantidad adquirida. <br/>";
        }
        if (cantidad > cantidad_restante) {
          errores=errores+"No se puede devolver  mas cantidad de la que fue declarada en la venta. <br/>";
        }

        var obliga_lote=$("#seleccionado").attr("data-hidden-lote");
        if (obliga_lote==1 && (lote == '' || vencimiento =='')){
          errores=errores+"Debe indicar lote y vencimiento. <br/>";
        }

        
        var idmedida =idmedida;

        ///////////////////////////////////
        if (errores==''){
          var parametros = {
            "idinsumo"		  		: insumo,
            "idproducto"        : idproducto,
            "cantidad"		  		: cantidad,
            "iddevolucion"      : iddevolucion,
            "lote"			  		  : lote,
            "vencimiento"	  		: vencimiento,
            "iddeposito"	  		: iddeposito,
            "comentario"        : comentario,
            "idmedida"		  		: idmedida,
            "iddevolucion"      : <?php echo $iddevolucion ? $iddevolucion : 0 ; ?>,
            "idventa"			  		  : <?php echo $idventa ? $idventa : 0; ?>,
            "agregar"		  		  : 1
          };

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

   

          
        ////////////////////////////////////////////////
	    }
      function recargar_buscador_productos(){
        var parametros = {
            "iddevolucion"      : <?php echo $iddevolucion ? $iddevolucion : 0 ; ?>,
            "idventa"			  		  : <?php echo $idventa ? $idventa : 0; ?>
          };

          $.ajax({
              data:  parametros,
              url:   'buscador_conteo_deposito.php',
              type:  'post',
              beforeSend: function () {
                $("#buscador_conteo").html('Cargando...');  
                
              },
              success:  function (response) {
                console.log(response);
                $("#buscador_conteo").html(response);
              }
            });
      }
      function editar_articulo(iddevolucion_det){

        var parametros = {
            "iddevolucion_det"   : iddevolucion_det,
            "iddevolucion"      : <?php echo $iddevolucion ? $iddevolucion : 0 ; ?>,
            "idventa"			  		  : <?php echo $idventa ? $idventa : 0; ?>
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
            alerta_modal("Editar Articulo",response)
          }
        });
      }
      function volver_atras(event){
        event.preventDefault();
        var url  = 'devolucion.php';
        document.location.href = url;
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
                    <h2>Devolucion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">





<br>


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
		<tr>
			<th align="center">Cliente</th>
			<td align="center"><?php echo antixss($rs_data->fields['cliente']); ?></td>
		</tr>
        <tr>
			<th align="center">Vendedor</th>
			<td align="center"><?php echo antixss($rs_data->fields['vendedor']); ?></td>
		</tr>
    </table>
</div>


<br />

<!-- require formulario para devolver -->

<div class="col-md-12" id="conteo_productos">
  <?php  require("./conteo_por_productos_depositos.php");?>
</div>
<div class="clearfix"></div>


<br>
  <div class="alert alert-info" role="alert" >
     Productos relacionados con esta Venta
	</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Precio Venta</th>
			<th align="center">cantidad</th>
			<th align="center">Producto</th>
		</tr>
	  </thead>
	  <tbody>
      <?php while (!$rs_ventas->EOF) { ?>
        <tr>
                <td align="center"><?php echo formatomoneda($rs_ventas->fields['pventa']); ?></td>
                <td align="center"><?php echo formatomoneda($rs_ventas->fields['cantidad']); ?></td>
                <td align="center"><?php echo antixss($rs_ventas->fields['producto']); ?></td>
        </tr>
      <?php $rs_ventas->MoveNext();
      } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>




<div class="col-md-12 col-xs-12"  style="text-align:center;">
				<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />

				<button  class="btn btn-secondary " style="width:15vw;"  onclick="volver_atras(event)"><span class="fa fa-reply"></span>&nbsp;Atras</button>
				<button  class="btn btn-primary " style="width:15vw;" id="btn_agregar" onclick="guardar_devolucion(event);"><span class="fa fa-save"></span>&nbsp;Guardar</button>
</div>

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

           

            
            
          </div>
        </div>
        <!-- /page content -->
		  
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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
