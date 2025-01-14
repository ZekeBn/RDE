<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

// desactivar facturas de timbrados inactivos
$consulta = "
update facturas 
set
estado = 'I'
where
estado = 'A'
and idtimbrado not in (select idtimbrado from timbrado where estado = 1)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select *,
(select usuario from usuarios where timbrado.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where timbrado.editado_por = usuarios.idusu) as editado_por,
(select usuario from usuarios where timbrado.borrado_por = usuarios.idusu) as borrado_por,
(SELECT count(*) as total FROM facturas where facturas.idtimbrado = timbrado.idtimbrado and estado = 'A') as total
from timbrado 
where 
 estado = 1 
order by idtimbrado asc
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Administracion de Timbrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="timbrado_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="timbrados_vencidos_del.php" class="btn btn-sm btn-default"><span class="fa fa-trash-o"></span> Borrar Vencidos</a>
<a href="timbrados_man.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Manual</a>
</p>
<hr />
<img src="img/comunicado_timbrado_largo.png" class="img-thumbnail" />
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtimbrado</th>
			<th align="center">Timbrado</th>
			<th align="center">Inicio vigencia</th>
			<th align="center">Fin vigencia</th>
            <th align="center">Total Documentos</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
			<th align="center">Editado por</th>
			<th align="center">Editado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="timbrados_det.php?id=<?php echo $rs->fields['idtimbrado']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="timbrado_edit.php?id=<?php echo $rs->fields['idtimbrado']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="timbrados_del.php?id=<?php echo $rs->fields['idtimbrado']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtimbrado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['inicio_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['inicio_vigencia']));
			} ?></td>
			<td align="center"><?php
if ($rs->fields['fin_vigencia'] != "") {
    echo date("d/m/Y", strtotime($rs->fields['fin_vigencia']));
}
    if (strtotime($rs->fields['fin_vigencia']) < strtotime(date("Y-m-d"))) {
        echo "<br /><strong style=\"color: red;\">Vencido!</strong>";
    }
    ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['editado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['editado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['editado_el']));
			}  ?></td>
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

$consulta = "
select *,
(select usuario from usuarios where timbrado.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where timbrado.editado_por = usuarios.idusu) as editado_por,
(select usuario from usuarios where timbrado.borrado_por = usuarios.idusu) as borrado_por,
(SELECT count(*) as total FROM facturas where facturas.idtimbrado = timbrado.idtimbrado and estado = 'A') as total
from timbrado 
where 
 estado = 6
order by idtimbrado asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>



            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Timbrados Desactivados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtimbrado</th>
			<th align="center">Timbrado</th>
			<th align="center">Inicio vigencia</th>
			<th align="center">Fin vigencia</th>
            <th align="center">Total Documentos</th>
			<th align="center">Borrado por</th>
			<th align="center">Borrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="timbrado_res.php?id=<?php echo $rs->fields['idtimbrado']; ?>" class="btn btn-sm btn-default" title="Reactivar" data-toggle="tooltip" data-placement="right"  data-original-title="Reactivar"><span class="fa fa-recycle"></span></a>

				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtimbrado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['inicio_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['inicio_vigencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fin_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fin_vigencia']));
			} ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['borrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['borrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['borrado_el']));
			}  ?></td>
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
