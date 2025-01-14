<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamentead
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$iddeposito = $_GET['id'];
$idinsumo = intval($_GET['idinsumo']);


$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);

// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    unset($queryParams['idinsumo']);

    //////////////////////////////////////////////////////////////////////////////////////////
    // unset($queryParams['idinsumo_depo']);
    //////////////////////////////////////////////////////////////////////////////////////////

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
// echo $pagina_actual;exit;


$consulta = "SELECT descripcion from gest_depositos where iddeposito=$iddeposito";
$rs_depositos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_deposito = $rs_depositos->fields['descripcion'];

$whereadd = "";
if ($idinsumo != 0) {
    $ehereadd = "and conteo.idinsumo = $idinsumo";
}

$consulta = "
select conteo.*,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select descripcion from insumos_lista where insumos_lista.idinsumo = conteo.idinsumo) as articulo,
(
	SELECT GROUP_CONCAT(grupo_insumos.nombre) AS grupos 
	from conteo_grupos 
	inner join grupo_insumos on grupo_insumos.idgrupoinsu = conteo_grupos.idgrupoinsu
	where 
	conteo_grupos.idconteo = conteo.idconteo
) as grupos
from conteo
where
estado = 3
and iddeposito = $iddeposito 
$ehereadd
order by idconteo desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




//////////////////////////////////////////////////////////////////
///////////////////////////////array select//////////////////////

$buscar = "SELECT DISTINCT(gest_depositos_stock.idproducto) as idinsumo ,gest_depositos_stock.iddeposito, insumos_lista.descripcion 
FROM gest_depositos_stock
INNER JOIN insumos_lista on insumos_lista.idinsumo = gest_depositos_stock.idproducto
WHERE 
gest_depositos_stock.iddeposito = $iddeposito
and gest_depositos_stock.disponible > 0";
$resultados_insumo = null;
$resultados_insumo2 = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idinsumo = trim(antixss($rsd->fields['idinsumo']));
    $descripcion = trim(antixss($rsd->fields['descripcion']));
    $resultados_insumo .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_producto('$descripcion',$idinsumo);\">[$idinsumo]-$descripcion</a>
	";
    $resultados_insumo2 .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_producto2('$descripcion',$idinsumo);\">[$idinsumo]-$descripcion</a>
	";
    $rsd->MoveNext();
}

////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function aplicar_filtro(event){
      event.preventDefault();
      var idinsumo = $("#form1 #idinsumo").val();
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
        delete queryParams['idinsumo'];

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
      
      window.location.href=pagina_actual + "idinsumo="+idinsumo;

      ////////////////////////////////////////////

    }
    function aplicar_filtro2(event){
      event.preventDefault();
      var idinsumo = $("#form1 #idinsumo_depo").val();
      // console.log(idinsumo)




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
        //delete queryParams['idinsumo_depo'];

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
      
      // window.location.href=pagina_actual + "idinsumo_depo="+idinsumo;

      ////////////////////////////////////////////
      var parametros = {
        "idinsumo_depo" 	: $("#idinsumo_depo").val(),
        "iddeposito"  : <?php echo $iddeposito != 0 ? $iddeposito : 0 ;?>,
        "filtrar"	: 1
      };
      // console.log(parametros);
      $.ajax({
        data:  parametros,
        url:   'conteo_stock_detalle_grilla_productos.php',
        type:  'post',
        beforeSend: function () {
        },
        success:  function (response) {
          $("#box_conteo_detalles_productos").html(response);
        }
      });	

    }
    function myFunction(event) {
            document.getElementById("myInput").classList.toggle("show");
            document.getElementById("myDropdown").classList.toggle("show");
            div = document.getElementById("myDropdown");
            $("#myInput").focus();


			
          $(document).mousedown(function(event) {
              var target = $(event.target);
              var myInput = $('#myInput');
              var myDropdown = $('#myDropdown');
              var div = $("#lista_insumo");
              var button = $("#idinsumo");
              // Verificar si el clic ocurrió fuera del elemento #my_input
              if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
                // Remover la clase "show" del elemento #my_input
                myInput.removeClass('show');
                myDropdown.removeClass('show');
              }
            
          });
    }
    function myFunction2(event) {
            document.getElementById("myInput2").classList.toggle("show");
            document.getElementById("myDropdown2").classList.toggle("show");
            div = document.getElementById("myDropdown2");
            $("#myInput2").focus();


			
          $(document).mousedown(function(event) {
            var target = $(event.target);
            var myInput = $('#myInput2');
            var myDropdown = $('#myDropdown2');
            var div = $("#lista_insumo2");
            var button = $("#idinsumo2");
            // Verificar si el clic ocurrió fuera del elemento #my_input
            if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
              // Remover la clase "show" del elemento #my_input
              myInput.removeClass('show');
              myDropdown.removeClass('show');
            }
            
          });
    }
    function filterFunction(event) {
        event.preventDefault();
        var vendedor = $("#idvendedor").val();
        var input, filter, ul, li, a, i;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        div = document.getElementById("myDropdown");
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
        var vendedor = $("#idvendedor").val();
        var input, filter, ul, li, a, i;
        input = document.getElementById("myInput2");
        filter = input.value.toUpperCase();
        div = document.getElementById("myDropdown2");
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
    function cambia_producto(nombre,idinsumo){
              $('#idinsumo').html($('<option>', {
                      value: idinsumo,
                      text: nombre
                }));
                 
                // Seleccionar opción
                $('#idinsumo').val(idinsumo);
                var myInput = $('#myInput');
                var myDropdown = $('#myDropdown');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }
    function cambia_producto2(nombre,idinsumo){
              $('#idinsumo_depo').html($('<option>', {
                      value: idinsumo,
                      text: nombre
                }));
                 
                // Seleccionar opción
                $('#idinsumo_depo').val(idinsumo);
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
            $('#idinsumo_depo').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
    };
  </script>
  <style>
        #lista_insumo,#lista_insumo2 {
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
                    <h2>Conteo de Stock en Deposito <?php if (isset($nombre_deposito)) {
                        echo $nombre_deposito;
                    } ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  <a href="conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                  

                  <h4>Conteos por Articulos Finalizados</h4>
                  <small>conteos los cuales fueron consolidados</small>
                  <div class="clearfix"></div>
                  <br>
                  <!-- //////////////////////////////////////////////////////////////////////////////////////////// -->

                  <form id="form1" name="form1" method="get" action="">

                    <div class="col-md-6 col-xs-12 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulos</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="" style="display:flex;">
                                <div class="dropdown " id="lista_insumo">
                                    <select onclick="myFunction(event)"  class="form-control" id="idinsumo" name="idinsumo">
                                      <option value="" disabled selected></option>
                                    </select>
                                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Articulo" id="myInput" onkeyup="filterFunction(event)" >
                                    <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                        <?php echo $resultados_insumo ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br />

                    <div class="form-group">
                        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
                          <a href="javascript:void(0);" onclick="aplicar_filtro(event)" class="btn btn-sm btn-default"   data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span> Buscar</a>

                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br />
                  </form>

                  <!-- ///////////////////////////////////////form fin ///////////////////////////////////////////// -->
                  
                  <div class="table-responsive">
                    <table width="100%" class="table table-bordered jambo_table bulk_action">
                    <thead>
                    <tr>
                      <th>Accion</th>
                      <th># Conteo</th>
                      <th>Deposito</th>
                      <th>Idinsumo</th>
                      <th>Articulo</th>
                      <th>Iniciado</th>
                      <th>Modificado</th>
                      <th>Afecta Stock</th>
                      <th>Estado</th>
                      </tr>
                      </thead>
                      <tbody>
                        <?php
                          $i = 1;
while (!$rs->EOF) { ?>
                              <tr>
                                <td>
                          <?php
$idconteo = $rs->fields['idconteo'];
    $iddeposito = $rs->fields['iddeposito'];
    $idinsumo = $rs->fields['idinsumo'];
    $mostrarbtn = "N";
    $mostrarbtn = "S";
    $link = "conteo_por_producto_reporte.php?id=".$idconteo."&idinsumo=".$idinsumo."&iddeposito=".$iddeposito;
    $txtbtn = "Reporte";
    $iconbtn = "search";
    $tipoboton = "default";
    if ($mostrarbtn == 'S') {
        ?>
                        <div class="btn-group">
                          <a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
                        </div>
                        <?php } ?>
                        </td>
                            <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
                            <td align="center"><?php echo $rs->fields['deposito']; ?></td>
                            <td align="center"><?php echo $rs->fields['idinsumo']; ?></td>
                            <td align="center"><?php echo $rs->fields['articulo']; ?></td>
                            <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['inicio_registrado_el'])); ?></td>
                            <td align="center"><?php if ($rs->fields['ult_modif'] != '') {
                                echo date("d/m/Y", strtotime($rs->fields['ult_modif']));
                            } ?></td>
                            <td align="center"><?php if ($rs->fields['afecta_stock'] == 'S') {
                                echo "SI";
                            } else {
                                echo "NO";
                            } ?></td>
                            <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
                        </tr>
                        <?php $i++;
    $rs->MoveNext();
} ?>
                      </tbody>
                    </table>
                  </div>
                  <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->
                  <!-- //////////////////////////////////no finalizados pendientes a consolidar////////////////////////////////////////////// -->
                  <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->
                  <h4>Articulos Disponibles en deposito</h4>
                  



                  <!-- //////////////////////////////////////////////////////////////////////////////////////////// -->

                  <form id="form1" name="form1" method="get" action="">

                    <div class="col-md-6 col-xs-12 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulos</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="" style="display:flex;">
                                <div class="dropdown " id="lista_insumo2">
                                    <select onclick="myFunction2(event)"  class="form-control" id="idinsumo_depo" name="idinsumo_depo">
                                      <option value="" disabled selected></option>
                                    </select>

                                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12" type="text" placeholder="Nombre Articulo" id="myInput2" onkeyup="filterFunction2(event)" >
                                    <div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                        <?php echo $resultados_insumo2 ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br />

                    <div class="form-group">
                        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
                          <a href="javascript:void(0);" onclick="aplicar_filtro2(event)" class="btn btn-sm btn-default"   data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span> Buscar</a>

                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br />
                  </form>

                  <!-- ///////////////////////////////////////form fin ///////////////////////////////////////////// -->

                  <hr />
                  <div id="box_conteo_detalles_productos">
                    <?php require_once("conteo_stock_detalle_grilla_productos.php");?>
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
