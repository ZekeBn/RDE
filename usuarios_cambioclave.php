 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu 
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereaddsup = "
    and franq_m = 'N'
    ";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereaddsup .= "
    and super = 'N'
    ";
}

$id = intval($_GET['id']);

$consulta = "
    SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    and usuarios.idusu = $id
    $whereaddsup
    ";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$soporte = $rsus->fields['soporte'];
if (intval($rsus->fields['idusu']) == 0) {
    echo "Usuario Inexistente o ya esta activo!";
    exit;
}
if ($id == $idusu) {
    header("location: cambiar_clave.php");
    exit;
}
if ($superus != 'S') {
    if ($soporte == 1) {
        echo "Lo siento, usted no tiene permisos para cambiar la clave a otro usuario de soporte. <a href='usuarios.php'>[VOLVER]</a>";
        exit;
    }
}



$consulta = "
select * from usuarios where idusu = $id  $whereadd
";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //recibe parametros
    $claveactual = $rsus->fields['clave'];
    $clavenueva = $_POST['clavenueva'];
    $clavenueva2 = $_POST['clavenueva2'];
    $valido = "S";
    $errores = "";

    // clave nueva no puede estar vacia
    if (trim($clavenueva) == '') {
        $valido = "N";
        $errores .= "- Clave nueva no puede estar vacia.<br />";
    }
    // clave actual no puede ser igual a la clave nueva
    if ($claveactual == $clavenueva) {
        $valido = "N";
        $errores .= "- Clave actual no puede ser igual a la clave nueva.<br />";
    }
    // clave nueva no puede tener espacios
    if (trim($clavenueva) != $clavenueva) {
        $valido = "N";
        $errores .= "- La clave nueva no puede contener espacios.<br />";
    }

    // clave nueva tiene que coincidir con clave nueva 2
    if ($clavenueva != $clavenueva2) {
        $valido = "N";
        $errores .= "- Ambas claves nuevas tienen que coincidir.<br />";
    }

    // conversiones
    $clavenueva = md5(trim($_POST['clavenueva']));
    $clavenueva2 = md5(trim($_POST['clavenueva2']));

    if ($valido == 'S') {
        $consulta = "
        UPDATE usuarios 
        SET
            clave='$clavenueva' 
        WHERE
        
            idempresa=$idempresa
            and idusu=$id
            and estado=1
            $whereadd
        ";
        //echo $consulta;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $buscar = "Select * from usuarios where idusu=$id and idempresa=$idempresa $whereadd ";
        $rstm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $yuser = trim($rstm->fields['usuario']);
        $insertar = "Insert into usuarios_registro_cambios
        (tipo_cambio,deschar,realizado_por,idempresa)
        values
        (3,'Clave modificada para: $yuser',$idusu,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        header("location:usuarios.php");
        exit;


    }


}





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
                    <h2>Cambiar Contrase&ntilde;a</h2>
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



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idusuario</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" disabled name="idusuario" id="idusuario" value="<?php echo $rsus->fields['idusu']; ?>" placeholder="" class="form-control" required="required" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usuario</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" disabled name="usuario" id="usuario" value="<?php echo $rsus->fields['usuario']; ?>" placeholder="" class="form-control" required="required" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Clave Nueva *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="password" name="clavenueva" id="clavenueva" value="" placeholder="Clave Nueva" class="form-control" required="required" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vuelva a escribir *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="password" name="clavenueva2" id="clavenueva2" value="" placeholder="Vuelva a escribir la clave nueva" class="form-control" required="required" />                    
    </div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='usuarios.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
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
