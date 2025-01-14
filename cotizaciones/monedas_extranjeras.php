<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "184";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


$consulta = "
select * 
from tipo_moneda
where
estado = 1
and idempresa = $idempresa
and borrable = 'S'
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
select * 
from tipo_moneda
where
estado = 1
and idempresa = $idempresa
and borrable = 'S' and
nacional = 'S'
";
$rsmoneda_defecto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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
                    <h2>Monedas Cargas</h2>
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
      <br /><br />
      
      <a href="cotizaciones.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar a  Cotizaciones</a>
      <div class="divstd">
        <h2 class="resaltaditomenor">
          Monedas por Defecto<br />
       <hr /><br />
          <?php if (intval($rsmoneda_defecto->fields['idtipo']) > 0) {?>
          <div class="table-responsive">
                <table width="100%" class="table table-bordered jambo_table bulk_action">
                  <thead>
                  <tr>
                    <th align="center">Moneda</th>
                    <th align="center">Imagen</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php while (!$rsmoneda_defecto->EOF) {
                        $banderita = trim($rsmoneda_defecto->fields['banderita']);
                        ?>
                        <tr>
                          
                          <td align="center"><?php echo antixss($rsmoneda_defecto->fields['descripcion']); ?></td>
                          <td align="center"><?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>" height="40px" width="60px" /><?php }?></td>

                        </tr>
                    <?php

                    $rsmoneda_defecto->MoveNext();
                    } //$rs->MoveFirst();?>
                  </tbody>
                
                  </table>
              </div>
              <?php } else { ?>
              <p class="alert alert-danger">No se ha seleccionado una moneda por defecto.</p>
              <?php } ?>
    		</h2>
    		<h2 class="resaltaditomenor">
    			Registrar Monedas <br />
    		</h2>
 		</div>
<hr /><br />






<p><a href="monedas_extranjeras_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Moneda</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Moneda</th>
			<th align="center">Imagen</th>
			<th align="center">Moneda Defecto</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $banderita = trim($rs->fields['banderita']);
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="monedas_extranjeras_edit.php?id=<?php echo $rs->fields['idtipo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="monedas_extranjeras_del.php?id=<?php echo $rs->fields['idtipo']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
      <td align="center"><?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>" height="40px" width="60px" /><?php }?></td>
			<td align="center"><?php echo siono($rs->fields['nacional']); ?></td>

		</tr>
<?php

$rs->MoveNext();
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
