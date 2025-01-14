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
count(*) as filas from   costo_productos
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
select *,
(select usuario from usuarios where costo_productos.asignado_por = usuarios.idusu) as registrado_por
from costo_productos 
where costo_cif > 0 
order by cantidad_unidades asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Datos Plantilla</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- AQUI SE COLOCA EL HTML -->

	


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <p><a href="costo_productos_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Cantidad unidades</th>
			<th align="center">Precio costo</th>
			<th align="center">Cantidad</th>
			<th align="center">Id producto</th>
			<th align="center">Tipo</th>
			<th align="center">Numfactura</th>
			<th align="center">Fechacompra</th>
			<th align="center">Idproveedor</th>
			<th align="center">Idempresa</th>
			<th align="center">Registrado el</th>
			<th align="center">Idseriepkcos</th>
			<th align="center">Costo2</th>
			<th align="center">Costoex</th>
			<th align="center">Costoprov</th>
			<th align="center">Costohac</th>
			<th align="center">Lote</th>
			<th align="center">Vencimiento</th>
			<th align="center">Ubicacion</th>
			<th align="center">Transferidos</th>
			<th align="center">Disponible</th>
			<th align="center">Idcompra</th>
			<th align="center">Idproducido</th>
			<th align="center">Asignado el</th>
			<th align="center">Asignado por</th>
			<th align="center">Subprod</th>
			<th align="center">Produccion</th>
			<th align="center">Numinterno</th>
			<th align="center">Retornado</th>
			<th align="center">Ficticio</th>
			<th align="center">Costo cif</th>
			<th align="center">Costo promedio</th>
			<th align="center">Modificado el</th>
			<th align="center">Cantidad stock</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad_unidades']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['precio_costo']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['id_producto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['numfactura']); ?></td>
			<td align="center"><?php if ($rs->fields['fechacompra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fechacompra']));
			} ?></td>
			<td align="center"><?php echo intval($rs->fields['idproveedor']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idempresa']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['idseriepkcos']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo2']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costoex']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costoprov']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costohac']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
			<td align="center"><?php if ($rs->fields['vencimiento'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vencimiento']));
			} ?></td>
			<td align="center"><?php echo intval($rs->fields['ubicacion']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['transferidos']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['disponible']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idproducido']); ?></td>
			<td align="center"><?php if ($rs->fields['asignado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['asignado_el']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['asignado_por']); ?></td>
			<td align="center"><?php echo intval($rs->fields['subprod']); ?></td>
			<td align="center"><?php echo intval($rs->fields['produccion']); ?></td>
			<td align="center"><?php echo intval($rs->fields['numinterno']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['retornado']); ?></td>
			<td align="center"><?php echo intval($rs->fields['ficticio']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo_cif']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo_promedio']);  ?></td>
			<td align="center"><?php if ($rs->fields['modificado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['modificado_el']));
			}  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad_stock']);  ?></td>
		</tr>
<?php
$cantidad_unidades_acum += $rs->fields['cantidad_unidades'];
    $precio_costo_acum += $rs->fields['precio_costo'];
    $cantidad_acum += $rs->fields['cantidad'];
    $costo2_acum += $rs->fields['costo2'];
    $costoex_acum += $rs->fields['costoex'];
    $costoprov_acum += $rs->fields['costoprov'];
    $costohac_acum += $rs->fields['costohac'];
    $transferidos_acum += $rs->fields['transferidos'];
    $disponible_acum += $rs->fields['disponible'];
    $costo_cif_acum += $rs->fields['costo_cif'];
    $costo_promedio_acum += $rs->fields['costo_promedio'];
    $cantidad_stock_acum += $rs->fields['cantidad_stock'];

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
	  <tfoot>
		
	  </tfoot>
    </table>
</div>
<br />

            
            
            
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
