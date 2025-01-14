<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";
require_once("../includes/rsusuario.php");






$consulta = "
select *,
(select usuario from usuarios where gest_depositos.idencargado = usuarios.idusu) as encargado,
(select sucursales.nombre from sucursales where sucursales.idsucu=gest_depositos.idsucursal limit 1) as sucursal,
(select tipo_sala from gest_depositos_tiposala where gest_depositos_tiposala.idtiposala = gest_depositos.tiposala) as tipo_sala
from gest_depositos 
where 
 estado = 6 
order by orden_nro asc, descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//$tdpto=$rsdpto->RecordCount();




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
                    <h2>Administrar Depositos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p> 
<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
	

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Orden nro</th>
			<th align="center">Deposito</th>
			<th align="center">Tipo Deposito</th>
			<th align="center">Sucursal</th>
			<th align="center">Encargado</th>
			<th align="center">Direccion</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_depositos_res.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Restaurar" data-toggle="tooltip" data-placement="right"  data-original-title="Restaurar"><span class="fa fa-recycle"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['orden_nro']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo_sala']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['encargado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
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
