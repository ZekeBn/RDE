<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
require_once("includes/rsusuario.php");

function accion_logcaja($accion)
{
    //O: original I: insert U: update D: delete
    $accion_txt = [
        'O' => 'ORIGINAL',
        'I' => 'AGREGAR',
        'U' => 'ACTUALIZAR',
        'D' => 'BORRAR'
    ];
    return $accion_txt[$accion];

}

$consulta = "
SELECT idcajalog, idcaja, log_registrado_por, log_registrado_el, accion, 
(select monto_apertura from caja_super_log where idcaja = csl.idcaja and accion = 'O' order by idcajalog desc limit 1 ) as monto_apertura_original, 
monto_apertura as monto_apertura_nuevo,
(select usuario from usuarios where csl.log_registrado_por = usuarios.idusu) as registrado_por
FROM `caja_super_log`  csl
WHERE
csl.accion <> 'O'  
ORDER BY csl.`idcajalog` desc
limit 500
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
                    <h2>Log ediciones de apertura de caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="caja_cierre_edit.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<strong>Ultimos 500 Cambios:</strong>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idcaja</th>
			<th align="center">Monto apertura original</th>
			<th align="center">Monto apertura nuevo</th>
			<th align="center">Accion</th>
			<th align="center">Log registrado por</th>
			<th align="center">Log registrado el</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idcaja']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_apertura_original']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_apertura_nuevo']);  ?></td>
			<td align="center"><?php echo accion_logcaja($rs->fields['accion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['log_registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['log_registrado_el']));
			}  ?></td>
			
		</tr>
<?php


$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>

    </table>
</div>
<br />					  
					  
					  
					  
<div class="clearfix"></div>
<br /><br /><br />
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
