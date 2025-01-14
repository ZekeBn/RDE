<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
require_once("includes/rsusuario.php");

$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 estado = 1 
order by idnotacred asc
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
                    <h2>Nota de Credito a Clientes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="nota_credito_cabeza_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idnotacred</th>
			<th align="center">Motivo</th>
			<th align="center">Sucursal</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero Nota</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Estado</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>


		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="nota_credito_cuerpo.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="nota_credito_cabeza_edit.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="nota_credito_cabeza_del.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['motivo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center">Cargando</td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


<?php
$limit = 50;
$whereadd = '';
if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
$whereadd .= " and date(fecha_nota) >= '$desde' ";
$whereadd .= " and date(fecha_nota) <= '$hasta' ";

if ($_REQUEST['desde'] != '' && $_REQUEST['hasta'] != '') {
    $limit = 1000;
}

$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 estado = 3  
 $whereadd
order by idnotacred desc
limit $limit
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>

            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimas <?php echo $limit; ?> notas finalizadas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  

					<p><a href="gest_notacredito_cliente_impmas.php" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Impresor Masivo</a></p>
					<hr />

<div class="col-md-12">
	<form action="" method="get">

				<div class="col-md-6 col-sm-6 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="date" name="desde" id="desde" value="<?php  if (isset($_GET['desde'])) {
					    echo htmlentities($_GET['desde']);
					} else {
					    echo date("Y-m-d");
					}?>" placeholder="Desde" class="form-control"  />                    
					</div>
				</div>
				
				<div class="col-md-6 col-sm-6 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="date" name="hasta" id="hasta" value="<?php  if (isset($_GET['hasta'])) {
					    echo htmlentities($_GET['hasta']);
					} else {
					    echo date("Y-m-d");
					}?>" placeholder="Hasta" class="form-control"  />                    
					</div>
				</div>

				<div class="clearfix"></div>
				<br />

				<div class="form-group">
					<div class="col-md-12 col-sm-12 col-xs-12 text-center">
					
				<button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar Notas</button>
				
					</div>
				</div>

	</form>
</div>

<div class="clearfix"></div>
<hr />

<div class="col-md-12">
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idnotacred</th>
			<th align="center">Motivo</th>
			<th align="center">Sucursal</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero Nota</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Estado</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>


		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="nota_credito_cabeza_det.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="nota_credito_cabeza_imp.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Imprimir" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir"><span class="fa fa-print"></span></a>
					 <a href="nota_credito_cabeza_imp_pdf.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" target="_blank"title="Ver NC formato TK" data-toggle="tooltip" data-placement="right"  data-original-title="Ver NC formato TK"><span class="fa fa-file-text"></span></a>
					  <a href="nota_credito_pdf.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" target="_blank"title="A4 PDF" data-toggle="tooltip" data-placement="right"  data-original-title="A4 PDF"><span class="fa fa-file"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['motivo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center">Finalizado</td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
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
<script>

function recargarpag (){
	
	window.open("nota_credito_cabeza.php");
}

</script>
        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
