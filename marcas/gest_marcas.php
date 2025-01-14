<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "124";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../importadora/preferencias_importadora.php");

$idmarca = intval($_GET['idmarca']);
$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);



$whereadd1 = "";
if ($idmarca > 0) {
    $whereadd1 = " and marca.idmarca = $idmarca";
}


//////////////////////////////////////////////////////////////////
///////////////////////////////array select//////////////////////

$buscar = "Select marca, idmarca from marca where marca.idestado = 1";
$resultados_marca = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idmarca = trim(antixss($rsd->fields['idmarca']));
    $marca = trim(antixss($rsd->fields['marca']));
    $resultados_marca .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_proveedor('$marca',$idmarca);\">[$idmarca]-$marca</a>
	";

    $rsd->MoveNext();
}

////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////





// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    // unset($queryParams['idmarca']);
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



// paginado del index

$limit = "";
$tabla = "marca ";

$whereadd = "where idestado = 1";
$consulta_numero_filas = "
select 
count(*) as filas from $tabla $whereadd $whereadd1
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 20;
$paginas_num_max = ceil($num_filas / $filas_por_pagina);

$limit = "  LIMIT $filas_por_pagina";

if ($idmarca != 0) {
    $num_pag = 1;
} else {
    $num_pag = intval($_GET['pag']);
}
$offset = null;
if (($num_pag) > 0) {
    $numero = (intval($num_pag) - 1) * $filas_por_pagina;
    $offset = " offset $numero";
} else {
    $offset = " ";
    $num_pag = 1;
}
////////////////////////////////



$consulta = "
select * from marca where idempresa = $idempresa and idestado = 1 $whereadd1 order by marca asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    window.onload = function() {
      var btnElement = document.querySelector('.btn.btn-success');
      btnElement.style.transition = 'none';
    }

    ////////////////////////////////

    function aplicar_filtro(event){
      event.preventDefault();
      var idmarca = $("#form1 #idmarca").val();
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

        // Eliminar el parámetro 'idmarca' si existe
        delete queryParams['idmarca'];

        // Reconstruir los parámetros GET sin 'idmarca'
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
      
      if(idmarca==null){

        window.location.href= urlParts[0];
      }else{

        window.location.href=pagina_actual + "idmarca="+idmarca;
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
              var button = $("#idmarca");
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

    function cambia_proveedor(nombre,idmarca){
              $('#idmarca').html($('<option>', {
                      value: idmarca,
                      text: nombre
                }));
                 
                // Seleccionar opción
                $('#idmarca').val(idmarca);
                var myInput = $('#myInput');
                var myDropdown = $('#myDropdown');
                myInput.removeClass('show');
                myDropdown.removeClass('show');	
              
    }


  </script>
 </head>
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
                    <h2>Marca</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">






                  



	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
    <div class="divstd">
    

    </div>
    <p>
      <a href="gest_marcas_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
      <?php if ($carga_masiva_importacion == "S") { ?>
        <a href="marcas_carga_masiva.php" class="btn btn-sm btn-default"><span class="fa fa-sitemap"></span> Carga Masiva</a>
      <?php } ?>

    </p>



    <!-- //////////////////////////////////////////////////////////////////////////////////////////// -->
    <form id="form1" name="form1" method="get" action="">
                      <div class="col-md-6 col-xs-12 form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Marca</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                              <div class="" style="display:flex;">
                                  <div class="dropdown " id="lista_proveedor">
                                      <select onclick="myFunction(event)"  class="form-control" id="idmarca" name="idmarca">
                                        <option value="" disabled selected></option>
                                      </select>
                                      <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Proveedor" id="myInput" onkeyup="filterFunction(event)" >
                                      <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                                          <?php echo $resultados_marca ?>
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




    <h4 align="center">
    
    Uso de Marca
      </h4>
      <div style="display: flex;justify-content:center;">
         <?php if ($rsco->fields['usa_marca'] == 'S') { ?><div class="btn btn-success" role="alert"><?php echo "Activo";
         } else { ?><div class="btn btn-danger" role="alert"><?php echo "Inactivo";
         } ?> </div> <a class="btn btn-default" href="gest_marcas_uso.php"> Cambiar</a>
      </div>
      <div class="clearfix"></div>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Marca</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_marcas_edit.php?id=<?php echo $rs->fields['idmarca']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="gest_marcas_del.php?id=<?php echo $rs->fields['idmarca']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['marca']); ?></td>
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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
<div id="preloader-overlay">
<div class="lds-facebook">
	<div></div>
	<div></div>
	<div></div>
</div>
</div>
  </body>
</html>
