<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "599";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idproveedor = intval($_GET['idproveedor']);
$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);


$whereadd = "";
if ($idproveedor > 0) {
    $whereadd = " and proveedores.idproveedor = $idproveedor";
}

// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    unset($queryParams['idproveedor']);
    unset($queryParams['pag']);
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







///////////////contador de paginas


// paginado del index

$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from despacho 
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
////////////////////////////////



$consulta = "
select 
  despacho.*, 
  (
    select 
      usuario 
    from 
      usuarios 
    where 
      despacho.registrado_por = usuarios.idusu
  ) as registrado_por, 
  (
    select 
      descripcion 
    from 
      tipo_moneda 
    where 
      tipo_moneda.idtipo = despacho.tipo_moneda
  ) as moneda, 
  (
    select 
      aduana.descripcion 
    from 
      aduana 
    where 
      aduana.idaduana = despacho.idaduana
  ) as aduana, 
  (
    SELECT 
      proveedores.nombre 
    from 
      proveedores 
    where 
      proveedores.idproveedor = despacho.iddespachante
  ) as despachante,
  proveedores.nombre as proveedor
from 
  despacho
  LEFT JOIN compras on compras.idcompra = despacho.idcompra
  LEFT JOIN proveedores on proveedores.idproveedor = compras.idproveedor
where 
  despacho.estado = 1
  $whereadd 
order by 
  iddespacho asc
  $limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



//////////////////////////////////////////////////////////////////
///////////////////////////////array select//////////////////////

$buscar = "Select nombre, idproveedor from proveedores where proveedores.estado = 1";
$resultados_proveedor = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = trim(antixss($rsd->fields['idproveedor']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $resultados_proveedor .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_proveedor('$nombre',$idproveedor);\">[$idproveedor]-$nombre</a>
	";

    $rsd->MoveNext();
}

////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////






?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
   function aplicar_filtro(event){
      event.preventDefault();
      var idproveedor = $("#form1 #idproveedor").val();
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

        // Eliminar el parámetro 'idproveedor' si existe
        delete queryParams['idproveedor'];

        // Reconstruir los parámetros GET sin 'idproveedor'
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
      
      if(idproveedor==null){

        window.location.href= urlParts[0];
      }else{

        window.location.href=pagina_actual + "idproveedor="+idproveedor;
      }

      ////////////////////////////////////////////

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
              var div = $("#lista_proveedor");
              var button = $("#idproveedor");
              // Verificar si el clic ocurrió fuera del elemento #my_input
              if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
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

    function cambia_proveedor(nombre,idproveedor){
              $('#idproveedor').html($('<option>', {
                      value: idproveedor,
                      text: nombre
                }));
                 
                // Seleccionar opción
                $('#idproveedor').val(idproveedor);
                var myInput = $('#myInput');
                var myDropdown = $('#myDropdown');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }

  </script>
  <style>
        #lista_proveedor,#lista_proveedor2 {
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
                    <h2>Tipo Cambio Despacho</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




                  <!-- //////////////////////////////////////////////////////////////////////////////////////////// -->

                  <form id="form1" name="form1" method="get" action="">

                    <div class="col-md-6 col-xs-12 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedores</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <div class="" style="display:flex;">
                                <div class="dropdown " id="lista_proveedor">
                                    <select onclick="myFunction(event)"  class="form-control" id="idproveedor" name="idproveedor">
                                      <option value="" disabled selected></option>
                                    </select>
                                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Proveedor" id="myInput" onkeyup="filterFunction(event)" >
                                    <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                        <?php echo $resultados_proveedor ?>
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

	

 




                  
                  
<p>
  <!-- <a href="despacho_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a> -->
  <a href="../compras/gest_reg_compras_resto_new.php" class="btn btn-sm btn-default"><span class="fa fa-list-ul"></span> Registro de Compras</a>

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Iddespacho</th>
			<th align="center">Idcompra</th>
			<th align="center">Proveedor</th>
			<th align="center">Tipo moneda</th>
			<th align="center">Despachante</th>
			<th align="center">Idaduana</th>
			<th align="center">Cotizacion</th>
			<th align="center">Registrado por</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="despacho_det.php?id=<?php echo $rs->fields['iddespacho']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="despacho_edit.php?id=<?php echo $rs->fields['iddespacho']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<!-- <a href="despacho_del.php?id=<?php echo $rs->fields['iddespacho']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a> -->
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['iddespacho']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['despachante']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['aduana']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cotizacion']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
<?php
$cotizacion_acum += $rs->fields['cotizacion'];
    $estado_acum += $rs->fields['estado'];

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
