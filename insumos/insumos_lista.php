<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../importadora/preferencias_importadora.php");



$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);





if (trim($_GET['descripcion']) != '') {
    $descripcion = antisqlinyeccion($_GET['descripcion'], "like");
    $whereadd .= " and insumos_lista.descripcion like '%$descripcion%' ";
}

// codigo sobre escribe a todo
if ($_GET['idinsumo'] > 0) {
    $idinsumo = intval($_GET['idinsumo']);
    $whereadd = " and insumos_lista.idinsumo = $idinsumo ";
}
if ($_GET['soloart'] != '') {
    $idinsumo = intval($_GET['idinsumo']);
    if ($_GET['soloart'] == 'pr') {
        $whereadd .= " and insumos_lista.idproducto > 0 ";
    } elseif ($_GET['soloart'] == 'ar') {
        $whereadd .= " and insumos_lista.idproducto is null ";
    } else {
        $whereadd .= "";
    }
}










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
$tabla = " insumos_lista ";
$whereadd1 = "where estado = 'A' ";
$consulta_numero_filas = "
  select 
  count(*) as filas from $tabla $whereadd1 $whereadd
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
(select descripcion from produccion_centros where idcentroprod=insumos_lista.idcentroprod) as centroprod,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida
from insumos_lista 
where 
 estado = 'A' 
$whereadd
order by descripcion asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




//////////////////////////////////////////////////////////////////
///////////////////////////////array select//////////////////////

$buscar = "Select nombre, idproveedor from  proveedores where proveedores.idproveedor = 1";
$resultados_marca = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = trim(antixss($rsd->fields['idproveedor']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_proveedor('$nombre',$idproveedor);\">[$idproveedor]-$nombre</a>
	";

    $rsd->MoveNext();
}

////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////




?><!DOCTYPE html>
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
                    <h2>Articulos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="insumos_lista_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="grupo_insumos.php" class="btn btn-sm btn-default"><span class="fa fa-list-alt"></span> Grupos Stock</a>
<a href="insumos_lista_bor.php" class="btn btn-sm btn-default"><span class="fa fa-trash"></span> Articulos Borrados</a>
<?php if ($carga_masiva_importacion == "N") { ?>
	<a href="insumos_lista_importar.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
<?php } ?>
<a href="insumos_lista_csv.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar</a>
<a href="insumos_lista_concepto_grilla.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Conceptos</a>
<?php if ($carga_masiva_importacion == "S") { ?>
	<a href="insumos_lista_carga_masiva.php" class="btn btn-sm btn-default"><span class="fa fa-sitemap"></span> Carga Masiva</a>
<?php } ?>

</p>
<hr />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_GET['descripcion'])) {
	    echo htmlentities($_GET['descripcion']);
	} ?>" placeholder="Articulo" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idinsumo" id="idinsumo" value="<?php  if (intval($_GET['idinsumo']) > 0) {
	    echo intval($_GET['idinsumo']);
	} ?>" placeholder="Codigo Articulo" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            // valor seleccionado
            if (isset($_GET['soloart'])) {
                $value_selected = htmlentities($_GET['soloart']);
            } else {
                $value_selected = 'am';
            }
// opciones
$opciones = [
    'AMBOS' => 'am',
    'ARTICULOS' => 'ar',
    'CODIGO DEL ARTICULO' => 'pr',
];
// parametros
$parametros_array = [
    'nombre_campo' => 'soloart',
    'id_campo' => 'soloart',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
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
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Codigo Articulo</th>
			<th align="center">Codigo Producto</th>
			<th align="center">Articulo</th>
			<th align="center">Categoria</th>
			<th align="center">Subcategoria</th>
			<th align="center">Grupo Stock</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>
			<th align="center">Habilita compra</th>
			<th align="center">Habilita inventario</th>
			
			<th align="center">CPR</th>
			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="insumos_lista_edit.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <?php if (intval($rs->fields['idproducto']) == 0) { ?>
					<a href="insumos_lista_del.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['categoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['subcategoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['grupo_stock']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php if ($rs->fields['hab_compra'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php if ($rs->fields['hab_invent'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['centroprod']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>
<?php $rs->MoveNext();
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
  </body>
</html>
