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
$consulta_button_group = "";
$button_group = "";
$whereadd = " ";
if (trim($_GET['filter']) != '') {
    $button_group = $_GET['filter'];
    if ($button_group == 1) {
        $consulta_button_group = "
      GROUP BY devolucion_det.idproducto ";
    }
    if ($button_group == 2) {
        $consulta_button_group = "
      GROUP BY cliente.idcliente ";
    }
    if ($button_group == 3) {
        $consulta_button_group = "
      group by vendedor.idvendedor ";
    }
    if ($button_group == 4) {
        $consulta_button_group = "
      GROUP BY devolucion_det.iddeposito ASC ";
    }
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


if ($consulta_button_group != "") {

    $consulta = "SELECT cliente.idcliente,vendedor.idvendedor, devolucion_det.iddevolucion, devolucion_det.idproducto,devolucion_det.iddeposito, SUM(devolucion_det.cantidad) as cantidad_sumada , cliente.razon_social as rs_cliente
  , CONCAT(COALESCE(vendedor.nombres,'--'),' ',COALESCE(vendedor.apellidos,'--')) as nombre_vendedor, productos.descripcion,
  (SELECT gest_depositos.descripcion from gest_depositos where gest_depositos.iddeposito = devolucion_det.iddeposito) as deposito_detalle
  FROM devolucion_det
  INNER JOIN devolucion ON devolucion.iddevolucion = devolucion_det.iddevolucion
  INNER JOIN ventas ON ventas.idventa = devolucion.idventa
  INNER JOIN cliente ON cliente.idcliente = ventas.idcliente
  INNER JOIN vendedor ON cliente.idvendedor = vendedor.idvendedor
  INNER JOIN productos ON productos.idprod = devolucion_det.idproducto
  INNER JOIN productos_sucursales on productos_sucursales.idproducto = devolucion_det.idproducto
  INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
  WHERE
  productos.borrado = 'N'
  and productos_sucursales.idsucursal = $idsucursal
  and productos_sucursales.activo_suc = 1
  and retiros_ordenes.estado = 3
  $consulta_button_group order by cantidad_sumada DESC $limit $offset
  ";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}


// SELECT cliente.idcliente,vendedor.idvendedor, devolucion_det.iddevolucion, devolucion_det.idproducto,devolucion_det.iddeposito, (devolucion_det.cantidad) as cantidad_sumada , cliente.razon_social as rs_cliente
// , CONCAT(COALESCE(vendedor.nombres,'--'),' ',COALESCE(vendedor.apellidos,'--')) as nombre_vendedor, productos.descripcion,
// (SELECT gest_depositos.descripcion from gest_depositos where gest_depositos.iddeposito = devolucion_det.iddevolucion_det) as deposito_detalle,
// retiros_ordenes.iddeposito as deposito_orden,
// (SELECT gest_depositos.descripcion from gest_depositos where gest_depositos.iddeposito = retiros_ordenes.iddeposito) as deposito_orden
// FROM devolucion_det
// INNER JOIN devolucion ON devolucion.iddevolucion = devolucion_det.iddevolucion
// INNER JOIN ventas ON ventas.idventa = devolucion.idventa
// INNER JOIN cliente ON cliente.idcliente = ventas.idcliente
// INNER JOIN vendedor ON cliente.idvendedor = vendedor.idvendedor
// INNER JOIN productos ON productos.idprod = devolucion_det.idproducto
// INNER JOIN productos_sucursales on productos_sucursales.idproducto = devolucion_det.idproducto
// INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
// WHERE
// productos.borrado = 'N'
// and productos_sucursales.idsucursal = 1
// and productos_sucursales.activo_suc = 1





/////////////////////////////////////////////////////////////////
/////////////////parametros para arrays

$buscar = "SELECT idvendedor, concat(nombres,' ',COALESCE(apellidos,'')) as nomape
FROM vendedor 
where 
estado = '1'
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


$buscar = "SELECT productos.descripcion, productos.idprod as idproducto 
from productos 
INNER JOIN insumos_lista on insumos_lista.idproducto = productos.idprod
INNER JOIN productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
WHERE
insumos_lista.estado = 'A'
and productos.borrado = 'N'
and productos_sucursales.idsucursal = $idsucursal
and productos_sucursales.activo_suc = 1
ORDER BY productos.descripcion
";
$resultados_productos = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproducto = trim(antixss($rsd->fields['idproducto']));
    $nombre = antisqlinyeccion(trim(antixss($rsd->fields['descripcion'])), 'text');
    $resultados_productos .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idproducto' onclick=\"cambia_vendedor( $idproducto, $nombre);\">[$idproducto]-$nombre</a>
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
    function handleClick(button) {
      // Obtener el texto del botón presionado
      var buttonText = button.innerText;
      var dataHidden = button.getAttribute('data-hidden-value');
      // Obtener la URL actual
      var currentUrl = document.location.href;

      paramName = "filter=";
      var regex = new RegExp("([\\?&])" + paramName + "([^&#]*)");
      // Verificar si el parámetro ya existe en la URL
      if (regex.test(currentUrl)) {
        // Si el parámetro ya existe, reemplazar su valor con uno nuevo
        var resultado = regex.exec(currentUrl);
        if (resultado) {
          var nuevoUrl = currentUrl.replace(resultado[0], resultado[1] + paramName + dataHidden);
          document.location.href = nuevoUrl;
        }
      } else {
        // Si el parámetro no existe, agregarlo a la URL
        if (currentUrl.indexOf('?') !== -1) {
          document.location.href = currentUrl + "&" + paramName + dataHidden;
        } else {
          document.location.href = currentUrl + "?" + paramName + dataHidden;
        }
      }

    }
  
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

    function myFunction3(event) {
          event.preventDefault();
          document.getElementById("myInput3").classList.toggle("show");
          document.getElementById("myDropdown3").classList.toggle("show");
          div = document.getElementById("myDropdown3");
          $("#myInput3").focus();

            
        $(document).mousedown(function(event) {
          var target = $(event.target);
          var myInput = $('#myInput3');
          var myDropdown = $('#myDropdown3');
          var div = $("#lista_productos");
          var button = $("#idproducto");
          // Verificar si el clic ocurrió fuera del elemento #my_input
          if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown3").length && myInput.hasClass('show')) {
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
            $('#idproducto').on('mousedown', function(event) {
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


	

        .listas_dropdown_select {
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
                    <h2>Ranking Devoluciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

  

                  
<p>
  <a href="../retiros_ordenes/retiros_ordenes.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a>
</p>

<hr />

<div style="width:100%;">
  <div class="btn-group" role="group" aria-label="Basic example" style="display: grid;grid-template-columns: repeat(4, 1fr);">
    <button type="button" class="btn btn-default   hover_cancelar  <?php echo $button_group == 1 ? 'active' : "" ?>" data-hidden-value="1" onclick="handleClick(this)">Productos</button>
    <button type="button" class="btn btn-default   hover_cancelar <?php echo $button_group == 2 ? 'active' : "" ?>" data-hidden-value="2" onclick="handleClick(this)">Clientes</button>
    <button type="button" class="btn btn-default   hover_cancelar <?php echo $button_group == 3 ? 'active' : "" ?>" data-hidden-value="3" onclick="handleClick(this)">Vendedores</button>
    <button type="button" class="btn btn-default   hover_cancelar <?php echo $button_group == 4 ? 'active' : "" ?>" data-hidden-value="4" onclick="handleClick(this)">Deposito</button>
  </div>
</div>




<br>
<?php if ($consulta_button_group != "") { ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <?php if ($button_group == 1) { ?>
          <tr>
            <th align="center">Idproducto</th>
            <th align="center">Cantidad</th>
            <th align="center">Articulo</th>
          </tr>
        <?php } ?>
        <?php if ($button_group == 2) { ?>
          <tr>
            <th align="center">idcliente</th>
            <th align="center">Cantidad de Articulos Devueltos</th>
            <th align="center">Cliente</th>
          </tr>
        <?php } ?>
        <?php if ($button_group == 3) { ?>
          <tr>
            <th></th>
            <th align="center">idvendedor</th>
            <th align="center">Cantidad de Articulos Devueltos</th>
            <th align="center">Vendedores</th>
          </tr>
        <?php } ?>
        <?php if ($button_group == 4) { ?>
          <tr>
            <th></th>
            <th align="center">iddeposito</th>
            <th align="center">Cantidad de Articulos Devueltos</th>
            <th align="center">Deposito</th>
          </tr>
        <?php } ?>
      </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
  <?php if ($button_group == 1) { ?>
		<tr>
      <td align="center"><?php echo intval($rs->fields['idproducto']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['cantidad_sumada']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
    </tr>
  <?php } ?>
  <?php if ($button_group == 2) { ?>
		<tr>
      <td align="center"><?php echo antixss($rs->fields['idcliente']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['cantidad_sumada']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['rs_cliente']); ?></td>
    </tr>
  <?php } ?>
  <?php if ($button_group == 3) { ?>
		<tr>
      <td>
				<div class="btn-group">
					<a href="devolucion_vendedor_det.php?id=<?php echo $rs->fields['idvendedor']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>
			</td>
      <td align="center"><?php echo antixss($rs->fields['idvendedor']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['cantidad_sumada']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['nombre_vendedor']); ?></td>
    </tr>
  <?php } ?>
  <?php if ($button_group == 4) { ?>
		<tr>
      <td>
				<div class="btn-group">
					<a href="devolucion_deposito_det.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>
			</td>
      <td align="center"><?php echo antixss($rs->fields['iddeposito']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['cantidad_sumada']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['deposito_detalle']); ?></td>
    </tr>
  <?php } ?>
<?php $rs->MoveNext();
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
<?php } ?>






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
		  
       

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
