<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");


function format_estado($estado)
{
    if ($estado == 1) {
        return "Pendiente";
    }
    if ($estado == 3) {
        return "Finalizado";
    }

}

// Obtener la URL actual
$pagina_actual = $_SERVER['REQUEST_URI'];

$urlParts = parse_url($pagina_actual);
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





//////////get
$idvendedor_filtro = $_GET['idvendedor'];
$idcliente_filtro = $_GET['idcliente'];
$estado_filtro = $_GET['estado'];




$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from retiros_ordenes 
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



///////////////arrays



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
    function alerta_modal(titulo,mensaje){
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html(titulo);
      $("#modal_cuerpo").html(mensaje);
    }
    function cambiar_estado_retiro(estado,idorden_retiro){
      var parametros = {
					"idorden_retiro"		    : idorden_retiro,
          "estado"                : estado,
          "editar_estado"         : 1
			};
      console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   'grilla_retiros_pendientes.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
									
				},
				success:  function (response) {
          console.log(response);
          $('#grilla_retiros_pendientes').html(response);
				}
			});
    }
    function orden_retiro_articulos(idorden_retiro){
			var parametros = {
					"idorden_retiro"		    : idorden_retiro,
          "mostrar_fecha"         : 1
			};
      console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   'articulos_retirar_list.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
									
				},
				success:  function (response) {
          console.log(response);
          
          alerta_modal("",response)
					
				}
			});
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
            // TODO:cambiar por lo de arriba
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
                    <h2>Devolucion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 


                  
<p>
  <a href="../devoluciones/devolucion.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Crear Devolucion</a>
  <a href="../devoluciones/devolucion_ranking.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ranking  Devolucion</a>
</p>
                  

<div class="alert alert-info" role="alert">
  - Para registrar la devoluci&oacute;n asociada a una factura de venta e ingresar el stock correspondiente, simplemente haz clic en 'Finalizar Devoluci&oacute;n' y sigue los pasos indicados. Las nuevas solicitudes se crean autom&aacute;ticamente en estado 'pendiente' por defecto. Utiliza los filtros para visualizar las solicitudes pendientes o en tr&aacute;nsito.
<br>
  - Si deseas eliminar los filtros seleccionados, simplemente haz clic en 'Buscar' sin seleccionar ning&uacute;n filtro
</div>
<hr />
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
            </div>
        </div>
    </div>


    <div class="col-md-6 col-xs-12 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
          <?php

            // valor seleccionado
            if (isset($_POST['estado'])) {
                $value_selected = htmlentities($_POST['estado']);
            } else {
                $value_selected = null;
            }
// opciones
$opciones = [
  'Pendiente' => 1,
  'Transito' => 2,
  'Finalizado' => 3
];
// parametros
$parametros_array = [
  'nombre_campo' => 'estado',
  'id_campo' => 'estado',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '  ',
  'autosel_1registro' => 'S',
  'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
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

<div id="grilla_retiros_pendientes">
  <?php require_once("grilla_retiros_pendientes.php"); ?>
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
