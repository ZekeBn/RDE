<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);

$idinsumo_get = intval($_GET['idart']);
$idproveedor_get = intval($_GET['idprov']);

// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    unset($queryParams['pag']);
    // Reconstruir los parámetros GET sin 'pag'
    $newQuery = http_build_query($queryParams);
    // Reconstruir la URL completa
    if (isset($newQuery) == false || empty($newQuery)) {
        $newUrl = $urlParts['path'].'?' ;
    } else {
        $newUrl = $urlParts['path'] . '?' . $newQuery .'&';
    }

    $pagina_actual = $newUrl;
} else {
    $pagina_actual = $urlParts['path'].'?' ;
}


// paginado del index

$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from   embarque
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 20;
$paginas_num_max = ceil($num_filas / $filas_por_pagina);

$limit = "  LIMIT $filas_por_pagina";


$num_pag = intval($_GET['pag']);
$offset = null;
if (($_GET['pag']) > 0) {
    $numero = (intval($_GET['pag']) - 1) * $filas_por_pagina;
    $offset = " offset $numero";
} else {
    $offset = " ";
    $num_pag = 1;
}

$buscar = "select idproveedor, nombre from proveedores where estado=1";
$resultados_insumo = null;
$resultados_insumo2 = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = trim($rsd->fields['idproveedor']);
    $prov_nombre = trim($rsd->fields['nombre']);
    $resultados_proveedor .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_proveedor($idproveedor,'$prov_nombre');\">[$idproveedor]-$prov_nombre</a>
	";
    $rsd->MoveNext();
}

$buscar_art = "select idinsumo, descripcion from insumos_lista where estado='A' and hab_compra=1";
$resultados_insumo = null;
$resultados_insumo2 = null;
$rsd = $conexion->Execute($buscar_art) or die(errorpg($conexion, $buscar_art));
while (!$rsd->EOF) {
    $idinsumo = trim($rsd->fields['idinsumo']);
    $art_descripcion = trim($rsd->fields['descripcion']);
    $resultados_art .= "
	<a class='a_link_art'  href='javascript:void(0);'  onclick=\"cambia_art($idinsumo,'$art_descripcion');\">[$idinsumo]-$art_descripcion</a>
	";
    $rsd->MoveNext();
}
////////////////////////////////



$whereadd = "";
if ($idinsumo_get > 0) {
    $whereadd .= " and compras_detalles.codprod = $idinsumo_get";
}
if ($idproveedor_get > 0) {
    $whereadd .= " and compras.idproveedor = $idproveedor_get";
}
$consulta = "
select (compras.idcompra), compras.idproveedor, proveedores.nombre as proveedor, compras_detalles.codprod, compras.fechacompra, compras.total, compras.facturacompra
from compras
INNER JOIN compras_detalles on compras_detalles.idcompra = compras.idcompra
INNER JOIN gest_depositos_compras on gest_depositos_compras.idcompra = compras.idcompra
INNER JOIN proveedores on proveedores.idproveedor = compras.idproveedor
where
gest_depositos_compras.registrado_por > 0
$whereadd
and  compras.idcompra_ref is NULL
group by compras.idcompra
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<!DOCTYPE html>
<html lang="en">
  
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function aplicar_filtro(event){
      event.preventDefault();
      var idinsumo = $("#form1 #selectart").val();
      var idproveedor = $("#form1 #selectprov").val();
      console.log(idinsumo)




      ///////////////////////////////////


      var pagina_actual = window.location.href;
      var urlParts = pagina_actual.split('?');

      // Verificar si hay parámetros GET
      if (urlParts.length > 1) {
        var queryParams = {};
        var queryString = urlParts[1];
        queryString.split('&').forEach(function (param) {
          var parts = param.split('=');
          queryParams[parts[0]] = parts[1] ? decodeURIComponent(parts[1]) : null;
        });

        // Eliminar el parámetro 'idinsumo' si existe
        delete queryParams['idprov'];
        delete queryParams['idart'];

        // Reconstruir los parámetros GET sin 'idinsumo'
        var newQuery = Object.keys(queryParams)
          .map(function (key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(queryParams[key]);
          })
          .join('&');

        // Reconstruir la URL completa
        var newUrl = urlParts[0] + (newQuery ? '?' + newQuery + '&' : '?');
        pagina_actual = newUrl;
      } else {
        pagina_actual = urlParts[0] + '?';
      }
      if (idinsumo == null) {
        idinsumo ="";
      }
      if (idproveedor == null) {
        idproveedor ="";
      }
      window.location.href=pagina_actual + "idart="+idinsumo+"&idprov="+idproveedor;

      ////////////////////////////////////////////

    }
    function buscaprov(event) {
            document.getElementById("input_prov").classList.toggle("show");
            document.getElementById("drop_prov").classList.toggle("show");
            div = document.getElementById("drop_prov");
            $("#input_prov").focus();


			
          $(document).mousedown(function(event) {
              var target = $(event.target);
              var input_prov = $('#input_prov');
              var drop_prov = $('#drop_prov');
              var div = $("#lista_prov");
              var button = $("#selectprov");
              // Verificar si el clic ocurrió fuera del elemento #my_input
              if (!target.is(input_prov) && !target.is(button) && !target.closest("#drop_prov").length && input_prov.hasClass('show')) {
                // Remover la clase "show" del elemento #my_input
                input_prov.removeClass('show');
                drop_prov.removeClass('show');
              }
            
          });
    }
    function buscaart(event) {
            document.getElementById("input_art").classList.toggle("show");
            document.getElementById("drop_art").classList.toggle("show");
            div = document.getElementById("drop_art");
            $("#input_art").focus();


			
          $(document).mousedown(function(event) {
              var target = $(event.target);
              var input_art = $('#input_art');
              var drop_art = $('#drop_art');
              var div = $("#lista_art");
              var button = $("#selectart");
              // Verificar si el clic ocurrió fuera del elemento #my_input
              if (!target.is(input_art) && !target.is(button) && !target.closest("#drop_art").length && input_art.hasClass('show')) {
                // Remover la clase "show" del elemento #my_input
                input_art.removeClass('show');
                drop_art.removeClass('show');
              }
            
          });
    }

    function filterFunction(event) {
        event.preventDefault();
        var vendedor = $("#idproveedor").val();
        var input, filter, ul, li, a, i;
        input = document.getElementById("input_prov");
        filter = input.value.toUpperCase();
        div = document.getElementById("drop_prov");
        a = div.getElementsByTagName("a");
        for (i = 0; i < a.length; i++) {
            txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                a[i].style.display = "block";
            } else {
                a[i].style.display = "none";
            }
        }
    }
    function filterFunction2(event) {
        event.preventDefault();
        var vendedor = $("#idinsumo").val();
        var input, filter, ul, li, a, i;
        input = document.getElementById("input_art");
        filter = input.value.toUpperCase();
        div = document.getElementById("drop_art");
        a = div.getElementsByTagName("a");
        for (i = 0; i < a.length; i++) {
            txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                a[i].style.display = "block";
            } else {
                a[i].style.display = "none";
            }
        }
    }
    function cambia_proveedor(id_proveedor,nombre_prov) {
      $('#selectprov').html($('<option>', {
                      value: id_proveedor,
                      text: nombre_prov
                }));
                 
                // Seleccionar opción
                $('#selectprov').val(id_proveedor);
                var myInput = $('#input_prov');
                var myDropdown = $('#drop_prov');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }
    window.onload = function() {
            $('#selectprov').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
    };
    function cambia_art(idinsumo,nombre_art) {
      $('#selectart').html($('<option>', {
                      value: idinsumo,
                      text: nombre_art
                }));
                 
                // Seleccionar opción
                $('#selectart').val(idinsumo);
                var myInput = $('#input_art');
                var myDropdown = $('#drop_art');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }
    window.onload = function() {
            $('#selectart').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
            $('#selectprov').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
    };
  </script>
  <style>
        #lista_prov,#lista_art {
            width: 100%;
        }
        .selected_pag{
            background: #c2c2c2;
        }
       
        .a_link_proveedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_proveedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_proveedores{
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
        .dropdown_proveedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_proveedor_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }

        .a_link_art{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_art:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_art{
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
        .dropdown_art_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_art_select{
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
                    <h2>Generador de Reporte en PDF</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  <a href="conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                  

                  <h4>Opciones de Filtrado:</h4>
                 
                  <div class="clearfix"></div>
                  <br>
                  <!-- //////////////////////////////////////////////////////////////////////////////////////////// -->

                  <form id="form1" name="form1" method="get" action="">

                    <div class="col-md-6 col-xs-12 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedores</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="" style="display:flex;">
                                <div class="dropdown " id="lista_prov">
                                    <select onclick="buscaprov(event)"  class="form-control" id="selectprov" name="selectprov">
                                      <option value="" disabled selected></option>
                                    </select>
                                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Proveedor" id="input_prov" onkeyup="filterFunction(event)" >
                                    <div id="drop_prov" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                        <?php echo $resultados_proveedor ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <div class="col-md-6 col-xs-12 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulos</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="" style="display:flex;">
                                <div class="dropdown " id="lista_art">
                                    <select onclick="buscaart(event)"  class="form-control" id="selectart" name="selectart">
                                      <option value="" disabled selected></option>
                                    </select>
                                    <input class="dropdown_art_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Articulo" id="input_art" onkeyup="filterFunction2(event)" >
                                    <div id="drop_art" class="dropdown-content hide dropdown_art links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                        <?php echo $resultados_art?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />

                    <div class="form-group">
                        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
                          <a href="javascript:void(0);" onclick="aplicar_filtro(event)" class="btn btn-sm btn-default"   data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span> Buscar</a>

                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br />
                  </form>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">idcompra</th>
			<th align="center">Factura</th>
			<th align="center">Fecha Compra</th>
			<th align="center">Proveedor</th>
			<th align="center">Total</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="embarque_det.php?id=<?php echo $rs->fields['idembarque']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="embarque_edit.php?id=<?php echo $rs->fields['idembarque']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-print"></span> Generar Reporte</a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
			<td align="center"><?php if ($rs->fields['fechacompra'] != "") {
			    echo date("d/m/Y ", strtotime($rs->fields['fechacompra']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['total'], 0, "N"); ?></td>
			
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
<tr>
	<td align="center" colspan="20">
		<div class="btn-group">
			<?php
            $last_index = 0;
if ($num_pag + 10 > $paginas_num_max) {
    $last_index = $paginas_num_max;
} else {
    $last_index = $num_pag + 10;
}
if ($num_pag != 1) { ?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag - 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-arrow-left"></span></a>
			<?php }
$inicio_pag = 0;
if ($num_pag != 1 && $num_pag - 5 > 0) {
    $inicio_pag = $num_pag - 5;
} else {
    $inicio_pag = 1;
}
for ($i = $inicio_pag; $i <= $last_index; $i++) {
    ?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
				<?php if ($i == $last_index && ($num_pag + 1 < $paginas_num_max)) {?>
					<a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-arrow-right"></span></a>
				<?php } ?>
			<?php } ?>
		</div>
	</td>
</tr>
	  </tbody>
	  
    </table>
</div>
<br />

	


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
