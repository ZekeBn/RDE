 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "102";
require_once("includes/rsusuario.php");

if ($soporte != 1) {
    $whereadd = " and soporte = 0 ";
}

$consulta = "
SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso,
(select nombre from sucursales where idsucu=usuarios.sucursal and idempresa=$idempresa) as sucuchar
FROM usuarios
where
estado = 1
and idusu in (select idusu from modulo_usuario where estado = 1 and submodulo = 22)
$whereadd
order by usuario asc
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
                    <h2>Parametros de Caja por Cajero</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<a href="gest_caja_admin_mas.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Modificaion Masiva</a>
<hr />
<strong>Parametros por cajero:</strong>
 <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
                  <tr>
                      <th></th>
                    <th>Usuario</th>
                    <th>Nombre y Apellido</th>
                    <th>Sucursal</th>
                    <th>Ultimo Acceso</th>
                    <th  align="center" bgcolor="#FF0000" style="color:#fff;">Tipo de Caja</th>
                    <th  align="center" bgcolor="#FF0000" style="color:#fff;">Ticket Cierre</th>
                     <th>Reimprime Factura</th>
                    
                  </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
                  <tr>
                    <td >
                      
                <div class="btn-group">
                    <a href="gest_caja_admin_edit.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                </div>
                      
                      </td>
                    <td align="center"><?php echo $rs->fields['usuario']; ?></td>
                    <td align="center"><?php echo $rs->fields['nombres']; ?> <?php echo $rs->fields['apellidos']; ?></td>
                    <td align="center"><?php echo $rs->fields['sucuchar']; ?></td>
                    <td align="center"><?php if ($rs->fields['ultacceso'] != '') {
                        echo date("d/m/Y H:i:s", strtotime($rs->fields['ultacceso']));
                    } ?></td>
                    <td  align="center"><?php if ($rs->fields['tipocaja'] == 'C') {
                        echo "Caja Ciega";
                    } elseif ($rs->fields['tipocaja'] == 'V') {
                        echo "Visible";
                    } else {
                        echo "Indeterminado";
                    } ?></td>
                    <td  align="center"><?php if ($rs->fields['tipotk'] == 'C') {
                        echo "Ticket Ciego";
                    } elseif ($rs->fields['tipotk'] == 'V') {
                        echo "Ticket Visible";
                    } else {
                        echo "Indeterminado";
                    } ?></td>
                    <td align="center"><?php echo siono($rs->fields['reimprime_encaja']); ?></td>

                  </tr>
<?php $rs->MoveNext();
} ?>
                </tbody>
              </table>
</div>

<br />
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
