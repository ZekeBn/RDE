<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$dirsup = "S";
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
count(*) as filas from   pedidos_cab
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
(select usuario from usuarios where pedidos_cab.registrado_por = usuarios.idusu) as registrado_por
from pedidos_cab 
where 
 estado = 1 
order by idempresa asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>

  </script>
  <style>
  	.label-cell {
           width: 150px;
      }
</style>
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
                    <h2>Carga de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- AQUI SE COLOCA EL HTML --->
                  <a href="pedidos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                  <a href="../insumos/insumos_lista_add.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Crear Insumos</a>
                  <a href="../insumos/productos_add.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Crear Productos</a>
                  <a href="../insumos/productos_add.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Buscar Cliente</a>
                  <a href="../clientes/cliente.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Editar Cliente</a>
                  <div class="clearfix"></div>
                  <hr />
<!------------------------------------------------ INICIO DE CABECERA ---------------------------------------------------->
<h2 style="text-decoration: underline;" >Datos del Cliente</h2>
<div class="col-md-12 col-xs-12">
  <div class="table-responsive">
  <table class="table table-striped jambo_table bulk_action" style="table-layout: fixed">
  <tbody>
  	<tr>
      <th class="label-cell" width="auto" style="max-width: 600px;" align="center">Cliente: </th>
    <th class="text-cell" id="codigoTextbox" style="background:#eee;" align="center"><?php echo $codigo_cliente, " - ",$nombre_cliente; ?></th>
		  <th class="label-cell" width="auto" style="max-width: 600px;" align="center">Ruc: </th>
 		<th style="background:#eee;" align="center"><?php echo $ruc_cliente; ?></th>
	</tr>
  <tr>
    <th width="auto" style="max-width: 600px;" align="center">Direccion: </th>
  <th style="background:#eee;" align="left"><?php echo $direccion; ?></th>
    <th width="auto" style="max-width: 600px;" align="center">Telefono: </th>
 	<th style="background:#eee;" align="center"><?php echo $telefono; ?></th>
  </tr>
  <tr>
    <th width="auto" style="max-width: 600px;" align="center">Vendedor: </th>
  <th style="background:#eee;" align="left"><?php echo $vendedor; ?></th>
    <th width="auto" style="max-width: 600px;" align="center">Forma de Pago: </th>
 	<th style="background:#eee;" align="center"><?php echo $telefono; ?></th>
  </tr>
  </tbody>
</table>
<hr>

<!------------------------------------------------ FIN DE CABECERA ---------------------------------------------------->
<!------------------------------------------------ INICIO DE ARTICULOS ------------------------------------------------>
<h2 style="text-decoration: underline;" >Carga de Articulos</h2>
  
<a href="#" class="btn btn-sm btn-default" data-toggle="modal" data-target="#modalIngresoProducto">
    <span class="fa fa-plus"></span> Agregar Producto
</a>

 <!-- Modal de ingreso de productos -->
 <div class="modal fade" id="modalIngresoProducto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ingresar Producto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Formulario para ingresar producto -->
                    <div class="form-group">
                        <label for="codigoProducto">Código:</label>
                        <input type="text" class="form-control" id="codigoProducto" placeholder="Código del producto">
                    </div>
                    <div class="form-group">
                        <label for="nombreProducto">Nombre:</label>
                        <input type="text" class="form-control" id="nombreProducto" placeholder="Nombre del producto">
                    </div>
                    <div class="form-group">
                        <label for="cantidadProducto">Cantidad:</label>
                        <input type="number" class="form-control" id="cantidadProducto" placeholder="Cantidad">
                    </div>
                    <div class="form-group">
                        <label for="precioProducto">Precio:</label>
                        <input type="number" class="form-control" id="precioProducto" placeholder="Precio">
                    </div>
                    <div class="form-group">
                        <label for="unidadProducto">Unidad:</label>
                        <input type="text" class="form-control" id="unidadProducto" placeholder="Unidad">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="agregarProducto()">Agregar Producto</button>
                </div>
            </div>
        </div>
    </div>

<table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Codigo</th>
			<th align="center">Producto</th>
			<th align="center">Caja</th>
			<th align="center">Unidad</th>
			<th align="center">UxC</th>
			<th align="center">Cant. Unit</th>
			<th align="center">Tipo cliente</th>
			<th align="center">Precio Lista</th>
			<th align="center">Precio Unitario</th>
			<th align="center">Desc %</th>
			<th align="center">IVA %</th>
			<th align="center">IVA Monto</th>
			<th align="center">Total Final</th>
			<th align="center">Cant. Max.Ped. Bloq.</th>			
	  </thead>
	  <tbody id="grillaProductos">
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="pedidos_cab_edit.php?id=<?php echo $rs->fields['idempresa']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="pedidos_cab_del.php?id=<?php echo $rs->fields['idempresa']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['idempresa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipodoc']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['moneda']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['documento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigo_cliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo_cliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre_cliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['dv']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['telefonos']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipoprecio']); ?></td>
			<td align="center"><?php if ($rs->fields['emision'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['emision']));
			} ?></td>
		</tr>
<!-- <?php
$moneda_acum += $rs->fields['moneda'];
    $total_costo_acum += $rs->fields['total_costo'];
    $total_bruto_acum += $rs->fields['total_bruto'];
    $total_neto_acum += $rs->fields['total_neto'];
    $total_final_acum += $rs->fields['total_final'];
    $total_impuestos_acum += $rs->fields['total_impuestos'];
    $total_descuento_acum += $rs->fields['total_descuento'];
    $impuesto1_acum += $rs->fields['impuesto1'];
    $impuesto2_acum += $rs->fields['impuesto2'];
    $impuesto3_acum += $rs->fields['impuesto3'];
    $baseimpo1_acum += $rs->fields['baseimpo1'];
    $baseimpo2_acum += $rs->fields['baseimpo2'];
    $baseimpo3_acum += $rs->fields['baseimpo3'];
    $exento_acum += $rs->fields['exento'];
    $factor_cambio_acum += $rs->fields['factor_cambio'];
    $porbackorder_acum += $rs->fields['porbackorder'];
    $importado_acum += $rs->fields['importado'];

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
	  </tbody> -->
	  <!-- <tfoot>
		<tr>
			<td>Totales</td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($moneda_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($total_costo_acum); ?></td>
			<td align="center"><?php echo formatomoneda($total_bruto_acum); ?></td>
			<td align="center"><?php echo formatomoneda($total_neto_acum); ?></td>
			<td align="center"><?php echo formatomoneda($total_final_acum); ?></td>
			<td align="center"><?php echo formatomoneda($total_impuestos_acum); ?></td>
			<td align="center"><?php echo formatomoneda($total_descuento_acum); ?></td>
			<td align="center"><?php echo formatomoneda($impuesto1_acum); ?></td>
			<td align="center"><?php echo formatomoneda($impuesto2_acum); ?></td>
			<td align="center"><?php echo formatomoneda($impuesto3_acum); ?></td>
			<td align="center"><?php echo formatomoneda($baseimpo1_acum); ?></td>
			<td align="center"><?php echo formatomoneda($baseimpo2_acum); ?></td>
			<td align="center"><?php echo formatomoneda($baseimpo3_acum); ?></td>
			<td align="center"><?php echo formatomoneda($exento_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($factor_cambio_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($porbackorder_acum); ?></td>
			<td align="center"><?php echo formatomoneda($importado_acum); ?></td>
		</tr>
	  </tfoot> -->
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
