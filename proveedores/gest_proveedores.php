<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../importadora/preferencias_importadora.php");



$idproveedor = intval($_GET['idproveedor']);
$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);






$ruc = antisqlinyeccion($_GET['ruc'], "text");
$nombre = antisqlinyeccion($_GET['nombre'], "like");
$fantasia = antisqlinyeccion($_GET['fantasia'], "like");



if (trim($_GET['ruc']) != '') {
    $whereadd .= " and proveedores.ruc = $ruc ";
}
if (trim($_GET['nombre']) != '') {
    $whereadd .= " and proveedores.nombre like '%$nombre%' ";
}

if (trim($_GET['fantasia']) != '') {
    $whereadd .= " and proveedores.fantasia = $fantasia ";
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
$tabla = "proveedores ";
$whereadd1 = "where estado = 1";
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
SELECT proveedores.idproveedor,proveedores.ruc, proveedores.nombre, proveedores.direccion,
proveedores.telefono,proveedores.acuerdo_comercial,tipo_moneda.descripcion,
paises_propio.nombre as pais, tipo_origen.tipo as origen, (select usuario from usuarios where proveedores.registrado_por = usuarios.idusu) as registrado_por, 
proveedores.registrado_el,(select usuario from usuarios where proveedores.actualizado_por = usuarios.idusu) as actualizado_por,proveedores.actualizado_el
FROM proveedores
LEFT JOIN tipo_moneda ON tipo_moneda.idtipo = proveedores.idmoneda
LEFT JOIN paises_propio ON paises_propio.idpais = proveedores.idpais
LEFT JOIN tipo_origen ON tipo_origen.idtipo_origen = proveedores.idtipo_origen
WHERE 
 proveedores.estado = 1 
$whereadd
order by proveedores.nombre asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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
                    <h2>Proveedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="proveedores_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<?php if ($tipo_servicio == "S") { ?>
  <a href="tipo_servicio.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Servicio</a>
<?php } ?>
<a href="proveedores_borrados.php" class="btn btn-sm btn-default"><span class="fa fa-trash"></span> Proveedores Borrados</a>
<a href="gest_proveedores_csv.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar</a>
<?php if ($carga_masiva_importacion == "S") { ?>
  <a href="proveedores_carga_masiva.php" class="btn btn-sm btn-default"><span class="fa fa-sitemap"></span> Carga Masiva</a>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
	    echo htmlentities($_GET['ruc']);
	} ?>" placeholder="Ruc" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_GET['nombre'])) {
	    echo htmlentities($_GET['nombre']);
	}?>" placeholder="Razon Social" class="form-control"  />                    
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
<div class="clearfix"></div>
<br /><br />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Codigo</th>
			<th align="center">Razon Social</th>
			<th align="center">Ruc</th>
			<th align="center">Direccion</th>
			<th align="center">Telefono</th>
			<th align="center">Acuerdo comercial</th>
      <?php if ($proveedores_importacion == "S") {?>
        <th align="center">Pais</th>
        <th align="center">Moneda</th>
      <?php }?>
      
        <th align="center">registrado_por</th>
        <th align="center">registrado el</th>
        
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="proveedores_det.php?id=<?php echo $rs->fields['idproveedor']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="proveedores_edit.php?id=<?php echo $rs->fields['idproveedor']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="proveedores_del.php?id=<?php echo $rs->fields['idproveedor']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idproveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
			<td align="center"><?php echo siono($rs->fields['acuerdo_comercial']); ?></td>
      <?php if ($proveedores_importacion == "S") {?>
        <td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
        <td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
      <?php }?>
      
        <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			  <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			      echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			  }  ?></td>
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
