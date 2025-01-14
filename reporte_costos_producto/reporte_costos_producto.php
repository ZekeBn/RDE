<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../cotizaciones/preferencias_cotizacion.php");
function buscar_cotizacion($parametros_array)
{

    global $conexion;
    global $ahora;
    // nombre del modulo al que pertenece este archivo
    $idmoneda = $parametros_array['idmoneda'];
    $ahoraSelec = $parametros_array['ahoraSelect'];
    //preferencias de cotizacion

    $preferencias_cotizacion = "SELECT * FROM preferencias_cotizacion";
    $rs_preferencias_cotizacion = $conexion->Execute($preferencias_cotizacion) or die(errorpg($conexion, $preferencias_cotizacion));

    $cotiza_dia_anterior = $rs_preferencias_cotizacion->fields["cotiza_dia_anterior"];
    $editar_fecha = $rs_preferencias_cotizacion->fields["editar_fecha"];
    /// fin de preferencias

    $res = null;


    $consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";

    $rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cotiza_moneda = intval($rscotiza -> fields['cotiza']);


    if ($cotiza_moneda == 1) {

        if ($ahoraSelec != "") {
            $ahorad = date("Y-m-d", strtotime($ahoraSelec));

        } else {
            $ahorad = date("Y-m-d", strtotime($ahora));
        }



        $consulta = "SELECT 
                        cotizaciones.cotizacion,cotizaciones.idcot,cotizaciones.fecha
                    FROM 
                        cotizaciones
                    WHERE 
                        cotizaciones.estado = 1 
                        AND DATE(cotizaciones.fecha) = '$ahorad'
                        AND cotizaciones.tipo_moneda = $idmoneda
                        ORDER BY cotizaciones.fecha DESC
                        LIMIT 1
                ";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $fecha = $rsmax->fields['fecha'];
        $idcot = intval($rsmax->fields['idcot']);
        $cotizacion = $rsmax->fields['cotizacion'];
        if ($idcot > 0) {

            $res = [
                "success" => true,
                "fecha" => $fecha,
                "idcot" => $idcot,
                "cotiza" => true,
                "cotizacion" => $cotizacion
            ];
        } else {
            $formateada = date("d/m/Y", strtotime($ahorad));
            $res = [
                "success" => false,
                "cotiza" => false,
                "error" => "No hay cotizaciones para el d&iacute;a $formateada,favor cargue la cotizacion del d&iacute;a,. Favor cambielo <a target='_blank' href='..\cotizaciones\cotizaciones.php'>[ Aqui ]</a>",
            ];
        }

    } else {
        $res = [
            "success" => true,
            "cotiza" => false,
        ];

    }
    return  $res;



}

$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];

function isNullAddChar($palabra)
{
    if ($palabra == "NULL") {
        return "'NULL'";
    } else {
        return $palabra;
    }
}
$whereadd = "";
$consult = "";
$articulo = intval($_GET['idinsumo']);
if ($articulo > 0) {


    // consulta a la tabla
    $consulta = "
SELECT gest_depositos_stock_gral.*, gest_depositos.descripcion as deposito
FROM gest_depositos_stock_gral 
INNER JOIN gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito
where gest_depositos_stock_gral.idproducto = $articulo
";
    $rs_producto_deposito = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $articulo_nombre = $rs_producto_deposito->fields['descripcion'];
    $consulta = "
SELECT costo_productos.cantidad_stock,costo_productos.idcompra , 
costo_productos.precio_costo, costo_productos.cantidad, 
costo_productos.costo_cif, costo_productos.costo_promedio, 
costo_productos.modificado_el,compras.moneda as idmoneda , 
tipo_moneda.descripcion as moneda,compras.idcot, 
tipo_origen.idtipo_origen,tipo_origen.tipo as origen, 
compras.facturacompra as numero_factura,despacho.cotizacion as cotizaciondesp,
compras.fechacompra
FROM costo_productos 
INNER JOIN compras on compras.idcompra = costo_productos.idcompra
LEFT JOIN tipo_moneda on compras.moneda = tipo_moneda.idtipo
LEFT JOIN tipo_origen on compras.idtipo_origen = tipo_origen.idtipo_origen
LEFT JOIN despacho on despacho.idcompra = compras.idcompra
WHERE costo_productos.costo_promedio IS NOT NULL
and costo_productos.id_producto=$articulo  ORDER BY modificado_el DESC
";
    $rs_producto_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);





} else {
    // echo "Error";exit;
}

$buscar = "Select idinsumo,descripcion, (select nombre from categorias where id_categoria=insumos_lista.idcategoria and estado=1) as categoria,
(select descripcion from sub_categorias where sub_categorias.idsubcate = insumos_lista.idsubcate) as subcate,
(select descripcion from sub_categorias_secundaria where sub_categorias_secundaria.idsubcate_sec = insumos_lista.idsubcate_sec) as subcate_sec
 from insumos_lista where estado='A' and hab_compra=1 order by descripcion asc
";
$resultados = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $descripcion = isNullAddChar(trim(antisqlinyeccion($rsd->fields['descripcion'], "text")));
    $idinsumo = intval($rsd->fields['idinsumo']);
    $categoria = isNullAddChar(antisqlinyeccion($rsd->fields['categoria'], "text"));
    $subcate = isNullAddChar(antisqlinyeccion($rsd->fields['subcate'], "text"));
    $subcate_sec = isNullAddChar(antisqlinyeccion($rsd->fields['subcate_sec'], "text"));


    $resultados .= "
	<a class='a_link_insumo' href='javascript:void(0);' data-hidden-value=$categoria onclick=\"este_producto({idinsumo: $idinsumo, descripcion: $descripcion});\">[$idinsumo]-$descripcion</a>
	";

    $rsd->MoveNext();
}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
	function este_producto(parametros){
		$('#idinsumo').html($('<option>', {
            value: parametros.idinsumo,
            text: parametros.descripcion
        }));
        // TODO:cambiar por lo de arriba
        // Seleccionar opción
        $('#idinsumo').val(parametros.idinsumo);
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
	}

	window.onload = function() {
        
        $('#idinsumo').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
    };
   
      function  habilitar_edit(ocnum){
      var parametros = {
            "ocnum"   : ocnum
            };
        $.ajax({
                    data:  parametros,
                    url:   'habilitar_edit_compras_orden.php',
                    type:  'post',
                    beforeSend: function () {
                          // $("#selecompra").html('Cargando...');  
                    },
                    success:  function (response) {
                // $("#selecompra").html(response);
                // $("#cantidad").focus();
                if(JSON.parse(response)['success'] == true){
                }
              }
            });	
            // await sleep(500);
            document.location.href='compras_ordenes_det.php?id='+ocnum;
    }
	function filterFunction2(event) {
		event.preventDefault();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput2");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown2");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			categoriaValue = a[i].getAttribute('data-hidden-value');
			if (txtValue.toUpperCase().indexOf(filter) > -1 || categoriaValue.toUpperCase().indexOf(filter) > -1) {
			a[i].style.display = "";
			} else {
			a[i].style.display = "none";
			}
            
		}
	}
	function myFunction2(event) {
            event.preventDefault();
            var idpais = $("#idinsumo").val();
                document.getElementById("myInput2").classList.toggle("show");
                document.getElementById("myDropdown2").classList.toggle("show");
                div = document.getElementById("myDropdown2");
                $("#myInput2").focus();
           
			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			var div = $("#lista_insumo");
			var button = $("#idinsumo");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
  </script>
<style type="text/css">
        #lista_insumo {
            width: 100%;
        }
       
        .a_link_insumo{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_insumo:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_insumo{
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }
        .dropdown_insumo_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_insumo_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
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
                    <h2>Histórico de Costos Promedios</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  
                  <form id="form1" name="form1" method="get" action="">
                    
				  <!-- ///////////////////////////////// -->
				  
<div class="col-md-6 col-xs-12 form-group">
<label class="control-label col-md-3 col-sm-3 col-xs-12">
<h4>Selec. el Articulo:</h4>
</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_insumo">
                <select onclick="myFunction2(event)"  class="form-control" id="idinsumo" name="idinsumo">
                <option value="" disabled selected></option>
				<?php if ($articulo) { ?>
					<option value="<?php echo $articulo; ?>"  selected><?php echo $articulo_nombre;?></option>
				
				<?php } ?>
            </select>
                <input class="dropdown_insumo_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Código / Articulo " id="myInput2" onkeyup="filterFunction2(event)" >
                <div id="myDropdown2" class="dropdown-content hide dropdown_insumo links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados ?>
                </div>
            </div>
                <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                    <span  class="fa fa-plus"></span> Agregar
                </a> -->
        </div>
    </div>
</div>



				  <!-- /////////////////////////////// -->
	</div>
  <div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Buscar</button>
        </div>
    </div>
                    
      </form>
                  


   
                <?php if (trim($errores) != "") { ?>
						<div class="alert alert-danger alert-dismissible fade in" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
							</button>
							<strong>Errores:</strong><br /><?php echo $errores; ?>
						</div>
						<?php } ?>

            <?php if ($articulo > 0) {?>

				<!-- //////////////////////////////////////////// -->

<br>
<br>
<br>
<?php if (!$articulo_nombre) {?>
<h4>Lo sentimos, pero el artículo que estás buscando no se encuentra disponible en ninguno de los depósitos</h4>
<br>
<br>
	<?php } ?>
				<div class="col-md-12">
					<div class="table-responsive ">
					    <table width="100%" class="table table-bordered jambo_table bulk_action">
						  <thead>
							<tr>
								<th align="center">Iddeposito</th>
								<th align="center">Deposito</th>
								<th align="center">Cantidad</th>
								<th align="center">Articulo</th>
								<th align="center">Ultima transferencia</th>
							
							</tr>
						  </thead>
						  <tbody>
					<?php while (!$rs_producto_deposito->EOF) { ?>
							<tr>
								
								<td align="center"><?php echo intval($rs_producto_deposito->fields['iddeposito']); ?></td>
								<td align="center"><?php echo antixss($rs_producto_deposito->fields['deposito']); ?></td>
								<td align="center"><?php echo antixss($rs_producto_deposito->fields['disponible']); ?></td>
								<td align="center"><?php echo antixss($rs_producto_deposito->fields['descripcion']); ?></td>
								<td align="center"><?php echo antixss($rs_producto_deposito->fields['last_transfer']); ?></td>
					
							</tr>
					<?php

                    $rs_producto_deposito->MoveNext();
					} //$rs->MoveFirst();?>
						  </tbody>
						  
					    </table>
					</div>
				</div>
<br />

<div class="col-md-12">
	
	
	<div class="table-responsive">
	    <table width="100%" class="table table-bordered jambo_table bulk_action">
		<thead style="border:none;">
			<tr>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="background:white;border:none;"></th>
				<th align="center" style="text-align:center;"colspan="3">USD</th>
				<th align="center" style="text-align:center;"colspan="3">GS</th>
			</tr>
		  </thead>	
		<thead>
				<tr>
				<th align="center">Fecha</th>
				<th align="center">Factura</th>
				<th align="center">NRO</th>
				<th align="center">Moneda de Compra</th>
				<th align="center">Tipo Cambio</th>
				<th align="center">Stock Actual</th>
				<th align="center">Cantidad Compra</th>
				<th align="center">Costo FOB</th>
				<th align="center">Costo CIF</th>
				<th align="center">Costo Promedio</th>
				<th align="center">Costo FOB</th>
				<th align="center">Costo CIF</th>
				<th align="center">Costo Promedio</th>
	
			</tr>
		  </thead>
		  <tbody>
	<?php while (!$rs_producto_costo->EOF) {
	    $idcompra = $rs_producto_costo->fields['idcompra'];
	    $cot_array = usa_cotizacion($idcompra);

	    $cotizacion = 0;




	    if ($cot_array['usa_cot_despacho'] == "S") {
	        //verificamos la cotizacion de despacho por si la compra tenga asociada
	        $cotizacion = floatval($cot_array['cot_despacho']);
	    } else {
	        // la cot de compra sera cero si es que la moneda no cotiza
	        $cotizacion = floatval($cot_array['cot_compra']);

	    }
	    if ($cotizacion == 0) {

	        //si la compra es en moneda local se obliga a mostrar en dolares por pedido del cliente

	        $fechacompra = ($rs_producto_costo->fields['fechacompra']);
	        $consulta = "SELECT idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"DOLAR\" ";
	        $rs_moneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
	        //agregar preferencia de multimoneda
	        $idmoneda_orden = $rs_moneda->fields['idtipo'];


	        // $no_mostrar_json=1;
	        $ahoraSelec = "";
	        if ($cotiza_dia_anterior == "S") {
	            $ahoraSelec = date("Y-m-d", strtotime($fechacompra. " -1 day"));
	        } else {
	            $ahoraSelec = date("Y-m-d", strtotime($fechacompra));

	        }

	        $cotizacion = 1;
	        $parametros_array = [
	            "idmoneda" => $idmoneda_orden,
	            "ahoraSelect" => $ahoraSelect
	        ];
	        $res = buscar_cotizacion($parametros_array);
	        if ($res['success'] == true && $res['cotiza'] == true) {
	            $cotizacion = $res['cotizacion'];
	        }

	    }



	    ?>
			<tr>
				<td align="center"><?php echo antixss($rs_producto_costo->fields['modificado_el']); ?></td>
				<td align="center"><?php echo antixss($rs_producto_costo->fields['origen']); ?></td>
				<td align="center"><?php echo antixss($rs_producto_costo->fields['numero_factura']); ?></td>
				<td align="center"><?php echo antixss($rs_producto_costo->fields['moneda']); ?></td>
				<td align="center"><?php echo antixss($cotizacion) ?></td>
				<td align="center"><?php echo intval($rs_producto_costo->fields['cantidad_stock']); ?></td>
				<td align="center"><?php echo intval($rs_producto_costo->fields['cantidad']); ?></td>
				<!-- Costos en Moneda Extranjera -->
				<td align="center"><?php echo formatomoneda($rs_producto_costo->fields['precio_costo'] / $rs_producto_costo->fields['cotizaciondesp'], 2, 'N'); ?></td>
				<td align="center"><?php echo formatomoneda($rs_producto_costo->fields['costo_cif'] / floatval($cotizacion), 2, 'N'); ?></td>
				<td align="center"><?php echo formatomoneda($rs_producto_costo->fields['costo_promedio'] / floatval($cotizacion), 2, 'N'); ?></td>
				<!-- Costos en Moneda Nacional -->
        <td align="center"><?php echo formatomoneda($rs_producto_costo->fields['precio_costo'], 2, 'N'); ?></td>
				<td align="center"><?php echo formatomoneda($rs_producto_costo->fields['costo_cif'], 2, 'N'); ?></td>
				<td align="center"><?php echo formatomoneda($rs_producto_costo->fields['costo_promedio'], 2, 'N'); ?></td>
				
					
				
	
			</tr>
	<?php $rs_producto_costo->MoveNext();
	} //$rs->MoveFirst();?>
		  </tbody>
	
	    </table>
	</div>
</div>
<br />


				<!-- /////////////////////////////////////////// -->
			
				<?php } ?>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            



            



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 



        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
