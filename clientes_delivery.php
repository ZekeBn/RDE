<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "300";
require_once("includes/rsusuario.php");


$consulta = "
select *,
(select usuario from usuarios where cliente_delivery.creado_por = usuarios.idusu) as creado_por
from cliente_delivery 
where 
 estado = 1 
order by idclientedel desc
limit 100
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
                    <h2>Clientes de Delivery</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="clientes_delivery_xls.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar Clientes</a>
<a href="clientes_delivery_dom_xls.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar Direcciones</a>
</p>
<hr />
<strong>Viendo Top 100 clientes: </strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<!--<th></th>-->
			<th align="center">Idclientedel</th>
			<th align="center">Idcliente</th>
			<th align="center">Nombres</th>
			<th align="center">Apellidos</th>
			<th align="center">Telefono</th>
			<th align="center">Fec ultactualizacion</th>
			<th align="center">Creado por</th>
			<th align="center">Creado el</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<!--<td>
				
				<div class="btn-group">
					<a href="cliente_delivery_det.php?id=<?php echo $rs->fields['idclientedel']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="cliente_delivery_edit.php?id=<?php echo $rs->fields['idclientedel']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="cliente_delivery_del.php?id=<?php echo $rs->fields['idclientedel']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>-->
			<td align="center"><?php echo intval($rs->fields['idclientedel']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['apellidos']); ?></td>
			<td align="center"><?php echo intval($rs->fields['telefono']); ?></td>
			<td align="center"><?php if ($rs->fields['fec_ultactualizacion'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fec_ultactualizacion']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['creado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['creado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['creado_el']));
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
