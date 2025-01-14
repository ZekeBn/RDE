<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");


// Obtener la URL actual
$pagina_actual = $_SERVER['REQUEST_URI'];



$urlParts = parse_url($pagina_actual);

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





function formatearNumero($numero)
{
    // Convertir el número a una cadena
    $numero = strval($numero);

    // Dividir la cadena en partes
    $parte1 = substr($numero, 0, 3);
    $parte2 = substr($numero, 3, 3);
    $parte3 = substr($numero, 6);

    // Unir las partes con guiones
    $numeroFormateado = $parte1 . '-' . $parte2 . '-' . $parte3;

    return $numeroFormateado;
}

$whereadd = " where 1 ";
if (trim($_GET['idvendedor']) != '') {
    $idvendedor = $_GET['idvendedor'];
    $whereadd .= " and vdr.idvendedor = $idvendedor ";
}

if (trim($_GET['idcliente']) != '') {
    $idcliente = $_GET['idcliente'];
    $whereadd .= " and c.idcliente = $idcliente ";
}
if (trim($_GET['num_factura']) != '') {
    $num_factura = $_GET['num_factura'];
    $whereadd .= " and v.factura = $num_factura ";
}
// echo $whereadd;exit;
$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from ventas 
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 50;
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

$consulta = "SELECT v.idventa, v.fecha, v.idcliente, v.total_venta, v.tipo_venta, c.nombre, v.factura,
( SELECT (SUM(ventas_detalles.cantidad) - 
  COALESCE((
    SELECT SUM(devolucion_det.cantidad) 
    FROM devolucion_det
    INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
    where
    devolucion.estado = 3
    and devolucion.idventa = v.idventa
  ),0)) as cantidad_restante
FROM ventas_detalles
INNER JOIN ventas on ventas.idventa = ventas_detalles.idventa
where 
ventas.idventa = v.idventa ) as cantidad_restante

FROM ventas as v 
INNER JOIN cliente as c on c.idcliente = v.idcliente 
INNER JOIN vendedor as vdr on vdr.idvendedor = c.idvendedor $whereadd 
ORDER BY v.fecha, cantidad_restante DESC
$limit $offset
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));






/////////////////////////////////////////////////////////////////
/////////////////parametros para arrays

$buscar = "SELECT idvendedor, concat(nombres,' ',COALESCE(apellidos,'')) as nomape
FROM vendedor 
where 
estado = 'A' or estado = 1
order by nombres asc
";

$resultados_vendedores = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idvendedor = trim(antixss($rsd->fields['idvendedor']));
    $nombre = antisqlinyeccion(trim(antixss($rsd->fields['nomape'])), 'text');
    $resultados_vendedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idvendedor' onclick=\"cambia_vendedor( $idvendedor, $nombre);\">[$idvendedor]-$nombre</a>
	";

    $rsd->MoveNext();
}


$buscar = "SELECT c.idcliente, c.idvendedor,  c.razon_social  FROM cliente as c WHERE estado = 1
";
$resultados_cliente = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idcliente = trim(antixss($rsd->fields['idcliente']));
    $idvendedor = trim(antixss($rsd->fields['idvendedor']));
    $razon_social = antisqlinyeccion(trim(antixss($rsd->fields['razon_social'])), 'text');
    $resultados_cliente .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-vendedor='$idvendedor'  onclick=\"cambia_cliente($idcliente,$razon_social);\">[$idcliente]-$razon_social</a>
	";
    $rsd->MoveNext();
}
/////
//  falta mostrar solo si tiene articulos disponibles para devolver
//  agregar por db los lotes


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
  
    function formatearNumero(numero) {
      numero = numero.toString();
      const parte1 = numero.slice(0, 3);
      const parte2 = numero.slice(3, 6);
      const parte3 = numero.slice(6);
      const numeroFormateado = `${parte1}-${parte2}-${parte3}`;
      return numeroFormateado;
    }
    function myFunction(event) {
            event.preventDefault();
            var vendedor = $("#idvendedor").val();

            if (vendedor) {
                    var div,ul, li, a, i;
                    div = document.getElementById("myDropdown");
                    a = div.getElementsByTagName("a");
                    for (i = 0; i < a.length; i++) {
                        txtValue = a[i].textContent || a[i].innerText;
                        id_vendedor = a[i].getAttribute('data-hidden-vendedor');
                        if ( id_vendedor==vendedor ) {
                            a[i].style.display = "block";
                        } else {
                            a[i].style.display = "none";
                        }
                    }

                }


            document.getElementById("myInput").classList.toggle("show");
            document.getElementById("myDropdown").classList.toggle("show");
            div = document.getElementById("myDropdown");
            $("#myInput").focus();


			
          $(document).mousedown(function(event) {
            var target = $(event.target);
            var myInput = $('#myInput');
            var myDropdown = $('#myDropdown');
            var div = $("#lista_clientes");
            var button = $("#idcliente");
            // Verificar si el clic ocurrió fuera del elemento #my_input
            if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
            // Remover la clase "show" del elemento #my_input
            myInput.removeClass('show');
            myDropdown.removeClass('show');
            }
            
          });
    }

    function myFunction2(event) {
          event.preventDefault();
          document.getElementById("myInput2").classList.toggle("show");
          document.getElementById("myDropdown2").classList.toggle("show");
          div = document.getElementById("myDropdown2");
          $("#myInput2").focus();

            
        $(document).mousedown(function(event) {
          var target = $(event.target);
          var myInput = $('#myInput2');
          var myDropdown = $('#myDropdown2');
          var div = $("#lista_vendedores");
          var button = $("#iddepartameto");
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
            id_vendedor = a[i].getAttribute('data-hidden-vendedor');
            if (vendedor != null && vendedor != undefined ) {
              if (( vendedor == id_vendedor && txtValue.toUpperCase().indexOf(filter) > -1 )){
                
                  a[i].style.display = "block";
              }else{
                  a[i].style.display = "none";
              }
            } else {
              if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                  a[i].style.display = "block";
              } else {
                  a[i].style.display = "none";
              }
            }
        }
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
                if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
    }
      
    

    function cambia_vendedor(idvendedor,nombre){
              $('#idvendedor').html($('<option>', {
                      value: idvendedor,
                      text: nombre
                }));
                 
                // Seleccionar opción
                $('#idvendedor').val(idvendedor);
                var myInput = $('#myInput2');
                var myDropdown = $('#myDropdown2');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }
    function cambia_cliente(idcliente,nombre){
            $('#idcliente').html($('<option>', {
                value: idcliente,
                text: nombre
            }));
         
            // $("#iddepartamento").val(iddepartamento)
            
            $("#idcliente").val(idcliente)
            var myInput = $('#myInput');
            var myDropdown = $('#myDropdown');
            myInput.removeClass('show');
            myDropdown.removeClass('show');	
            
    }

    window.onload = function() {
            $('#idcliente').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
            $('#idvendedor').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
    };

    function mostrar_detalle(idventa){
      var parametros = {
				"idventa"		  : idventa
			};
      console.log(parametros);
      $.ajax({
					data:  parametros,
					url:   'ventas_det.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
					},
					success:  function (response) {
						console.log(response);
						alerta_modal("Detalle del conteo",response)
					}
			});
    }
    function alerta_modal(titulo,mensaje){
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html(titulo);
      $("#modal_cuerpo").html(mensaje);
    }

</script>
  <style type="text/css">
        #lista_clientes,#lista_vendedores {
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
                    <h2>Crear Orden de Retiro</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

  

                  
<p>
  <a href="../retiros_ordenes/retiros_ordenes.php" class="btn btn-sm btn-default"><span class="fa fa-list"></span> Ordenes de Retiro</a>
</p>
<div class="alert alert-info" role="alert">
  - Si deseas eliminar los filtros seleccionados, simplemente haz clic en 'Buscar' sin seleccionar ning&uacute;n filtro
</div>
<hr />


<form id="form1" name="form1" method="get" action="">

    <div class="col-md-6 col-xs-12 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <div class="" style="display:flex;">
                <div class="dropdown " id="lista_vendedores">
                    <select onclick="myFunction2(event)"  class="form-control" id="idvendedor" name="idvendedor">
                    <option value="" disabled selected></option>
                </select>
                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Vendedor" id="myInput2" onkeyup="filterFunction2(event)" >
                    <div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                        <?php echo $resultados_vendedores ?>
                    </div>
                </div>
                    <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                        <span  class="fa fa-plus"></span> Agregar
                    </a> -->
            </div>
        </div>
    </div>





    <div class="col-md-6 col-xs-12 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <div class="" style="display:flex;">
                <div class="dropdown " id="lista_clientes">
                    <select onclick="myFunction(event)"  class="form-control" id="idcliente" name="idcliente">
                    <option value="" disabled selected></option>
                </select>
                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Cliente" id="myInput" onkeyup="filterFunction(event)" >
                    <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                        <?php echo $resultados_cliente ?>
                    </div>
                </div>
                    <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                        <span  class="fa fa-plus"></span> Agregar
                    </a> -->
            </div>
        </div>
    </div>

    <div class="col-md-6 col-sm-12 col-xs-12 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura nro</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
          <input type="text" name="num_factura" id="num_factura" value="<?php  if (isset($_POST['num_factura'])) {
              echo htmlentities($_POST['num_factura']);
          } else {
              echo htmlentities($rs->fields['num_factura']);
          }?>" placeholder="Numero de Factura" class="form-control" />                    
        </div>
    </div>




    <div class="clearfix"></div>
    <br />
    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	      <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

    <br />
</form>

<br>
<h2>Ventas Realizadas </h2>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
      <th></th>
			<th align="center">Idventa</th>
			<th align="center">Factura</th>
			<th align="center">fecha</th>
			<th align="center">Idcliente</th>
			<th align="center">Cliente</th>
			<th align="center">Total Venta</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
      <td>
        <div class="btn-group">
					<?php if (intval($rs->fields['cantidad_restante']) > 0) {?>
          <a href="devolucion_new.php?id=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="devolver" data-toggle="tooltip" data-placement="right"  data-original-title="devolver"><span class="fa fa-list-alt"></span> Devolver</a>
          <?php } ?>
          <a href="javascript:void(0);" class="btn btn-sm btn-default" onclick="mostrar_detalle(<?php echo $rs->fields['idventa']; ?>)" title="detalles" data-toggle="tooltip" data-placement="right"  data-original-title="detalles"><span class="fa fa-search"></span></a>
				</div>
      </td>
      <td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['fecha']); ?></td>
      <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['total_venta']); ?></td>
    </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
 <tr>
                <td align="center" colspan="8">
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
