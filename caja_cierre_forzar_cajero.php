 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "193";
require_once("includes/rsusuario.php");


// busca si hay una caja abierta por este usuario
$buscar = "
    Select * 
    from caja_super 
    where 
    estado_caja=1 
    and cajero=$idusu 
    and tipocaja = 1
    order by fecha_apertura desc 
    limit 1
    ";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);

if ($idcaja == 0) {
    echo "Tu usuario no tiene ninguna caja abierta.";
    exit;
}

$consulta = "
select * , (select usuario from usuarios where idusu = caja_super.cajero) as cajero_usu,
(select nombre from sucursales where idempresa = $idempresa and idsucu = caja_super.sucursal) as sucursal,
(select tipo_caja from caja_gestion_tipo where idtipocaja = caja_super.tipocaja) as tipo_caja
from caja_super
where
estado_caja = 1
and idcaja = $idcaja
$whereadd2
order by caja_super.estado_caja asc, fecha_apertura desc
$limit
";
//echo $consulta; exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = intval($rs->fields['idcaja']);
if ($idcaja == 0) {
    header("location: index.php");
    exit;
}



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots




    // si todo es correcto actualiza
    if ($valido == "S") {

        if ($idcaja > 0) {

            $consulta = "
            update caja_super set estado_caja = 3, fecha_cierre='$ahora' where idcaja = $idcaja and cajero=$idusu 
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            recalcular_caja($idcaja);

            header("location: gest_administrar_caja.php");
            exit;
        }


    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


/*

*/
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
                    <h2>Forzar Cierre de Caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
            <tr align="center" valign="middle">

              <th>Idcaja</th>
              
              <th>Cajero</th>
              <th>Tipo Caja</th>
              <th>Sucursal</th>
              <th>Apertura</th>


              <th>Monto Apertura</th>
              <th>Estado</th>




            </tr>
    </thead>    
    <tbody>
<?php
while (!$rs->EOF) {

    $estado = $rs->fields['estado_caja'];
    if ($estado == 1) {
        $estadocaja = "Abierta";
    } elseif ($estado == 3) {
        $estadocaja = "Cerrada";
    } else {
        $estadocaja = "Indeterminada";
    }
    ?>
            <tr align="center" valign="middle">
  
              <td><?php echo $rs->fields['idcaja']; ?></td>
              <td><?php echo capitalizar($rs->fields['cajero_usu']); ?></td>
              <td align="left"><?php echo $rs->fields['tipo_caja']; ?></td>
              <td align="left"><?php echo $rs->fields['sucursal']; ?></td>
              <td><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_apertura'])); ?></td>

              

              <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']); ?></td>
            <td><?php echo $estadocaja?></td>

              

     
            </tr>
<?php $rs->MoveNext();
} ?>
      </tbody>
    </table>
</div>
<br />
<p  align="center">
<strong>
Esta accion no se puede deshacer, esta seguro?</strong></p>
<br /><br />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Forzar Cierre</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='index.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


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
