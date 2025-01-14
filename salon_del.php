 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");



$idsalon = intval($_GET['id']);
if ($idsalon == 0) {
    header("location: salones.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from salon 
where 
idsalon = $idsalon
and estado_salon = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsalon = intval($rs->fields['idsalon']);
if ($idsalon == 0) {
    header("location: salones.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    //$idsalon=antisqlinyeccion($_POST['idsalon'],"text");
    //$nombre=antisqlinyeccion($_POST['nombre'],"text");


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

    $consulta = "
    select * 
    from mesas
    where
    estado_mesa > 1 
    and estadoex = 1 
    and idsalon = $idsalon
    limit 1
    ";
    $rsmesval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsmesval->fields['idmesa']);
    if ($idmesa > 0) {
        $errores .= "- No se puede borrar el salon por que existen mesas abiertas.<br />";
        $valido = "N";
    }



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update salon
        set
            estado_salon = 6,
            anulado_por=$idusu,
            anulado_el='$ahora'
        where
            idsalon = $idsalon
            and estado_salon = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        update mesas
        set
            estadoex = 6,
            estado_mesa = 1,
            anulado_por=$idusu,
            anulado_el='$ahora'
        where
            idsalon = $idsalon
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: salones.php");
        exit;

    }

}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




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
                    <h2>Salones y Mesas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idsalon *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="" name="idsalon" id="idsalon" value="<?php  if (isset($_POST['idsalon'])) {
        echo htmlentities($_POST['idsalon']);
    } else {
        echo htmlentities($rs->fields['idsalon']);
    }?>" placeholder="Idsalon" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
        echo htmlentities($_POST['nombre']);
    } else {
        echo htmlentities($rs->fields['nombre']);
    }?>" placeholder="Nombre" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='salones.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


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
