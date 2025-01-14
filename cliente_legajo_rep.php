<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");


$whereadd = "";

if (intval($_GET['idtipodocumento']) > 0) {
    $whereadd .= " and cliente_legajo.idtipodocumento = ".intval($_GET['idtipodocumento']);
}
if (trim($_GET['nombre_antiguo_arch']) != '') {
    $nombre_antiguo_arch = antisqlinyeccion($_GET['nombre_antiguo_arch'], "like");
    $whereadd .= " and cliente_legajo.nombre_antiguo_arch like '%".$nombre_antiguo_arch."%'";
}
if (trim($_GET['comentario']) != '') {
    $comentario = antisqlinyeccion($_GET['comentario'], "like");
    $whereadd .= " and cliente_legajo.comentario like '%".$comentario."%'";
}
if (trim($_GET['ruc']) != '') {
    $ruc = antisqlinyeccion($_GET['ruc'], "like");
    $whereadd .= " and cliente.ruc like '%".$ruc."%'";
}

$consulta = "
SELECT *, cliente_legajo.comentario as comentario, cliente_legajo.registrado_el as registrado_el,
(select usuario from usuarios where idusu=cliente_legajo.registrado_por) as quien
FROM cliente_legajo
inner join cliente on cliente.idcliente = cliente_legajo.idcliente
inner join tipos_documentos on tipos_documentos.idtipodoc =cliente_legajo.idtipodocumento 
where
unsf is not null
AND cliente_legajo.estado = 1
$whereadd
order by unsf desc
limit 1000
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Reporte de Legajos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
					  
<a href="#" class="btn btn-sm btn-primary"><span class="fa fa-search"></span> Busqueda de Legajos</a>
<a href="cliente_legajo_rep_sinleg.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Clientes Activos sin Legajos</a>
<hr />
<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipodoc, descripcion
FROM tipos_documentos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['idtipodocumento'])) {
    $value_selected = htmlentities($_GET['idtipodocumento']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipodocumento',
    'id_campo' => 'idtipodocumento',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipodoc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>	                 
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC cliente </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
	    echo htmlentities($_GET['ruc']);
	} ?>" placeholder="RUC cliente " class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Antiguo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre_antiguo_arch" id="nombre_antiguo_arch" value="<?php  if (isset($_GET['nombre_antiguo_arch'])) {
	    echo htmlentities($_GET['nombre_antiguo_arch']);
	} ?>" placeholder="Nombre Antiguo" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_GET['comentario'])) {
	    echo htmlentities($_GET['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br />		  
<strong>Buscar Legajos</strong> 
<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
<thead>

	<tr>
		<th></th>
		<th>Cliente</th>
		<th>Tipo Documento</th>
		<th>Archivo / Imagen</th>
		<th>Nombre Antiguo</th>
		<th>Comentario</th>
		<th>Registrado el</th>
		<th>Registrado por</th>

	</tr>
</thead>
<tbody>
<?php while (!$rs->EOF) {
    $ids = $rs->fields['unsf'];

    ?>
<tr>
	<td><a href="cliente_legajo_visor.php?unico=<?php echo $rs->fields['unsf']; ?>" target="_blank" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a></td>
	<td ><?php echo $rs->fields['razon_social']; ?><br />
		<?php echo $rs->fields['ruc']; ?><br />
		<?php echo $rs->fields['fantasia']; ?></td>
	<td ><?php echo $rs->fields['descripcion']; ?></td>

	<td><?php echo antixss($rs->fields['archivo']); ?></td>
	<td><?php echo antixss($rs->fields['nombre_antiguo_arch']); ?></td>
	<td><?php echo antixss($rs->fields['comentario']); ?></td>
	<td><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el'])); ?></td>
	<td><?php echo antixss($rs->fields['quien']); ?></td>

</tr>
<?php $rs->MoveNext();
} ?>
</tbody>
</table>
</div>

<br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
