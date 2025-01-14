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

// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$selmozo = trim($rspref->fields['selmozo']);

$id = intval($_GET['id']);

$consulta = "
    SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    $whereaddsup
    and usuarios.idusu = $id
    ";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$soporte = $rsus->fields['soporte'];
if (intval($rsus->fields['idusu']) == 0) {
    echo "Usuario Inexistente o no pertenece a tu sucursal!";
    exit;
}
$mozo = $rsus->fields['mozo'];
if ($superus != 'S') {
    if ($soporte == 1) {
        echo "Lo siento, usted no tiene permisos para editar un usuario de soporte. <a href='usuarios.php'>[VOLVER]</a>";
        exit;
    }
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //     recibe variables
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $usuario = antisqlinyeccion($_POST['usuario'], "text");
    $documento = antisqlinyeccion($_POST['documento'], "int");
    $email = antisqlinyeccion($_POST['email'], "text");
    $sucursale = intval($_POST['sucursales']);
    $mozop = antisqlinyeccion($_POST['mozo'], "text");
    $idterminal_obliga = antisqlinyeccion($_POST['idterminal_obliga'], "int");

    // validaciones
    $valido = "S";
    $errores = "";
    if (trim($_POST['nombres']) == '') {
        $valido = "N";
        $errores .= "- Debe escribir un nombre.<br />";
    }
    if (trim($_POST['apellidos']) == '') {
        $valido = "N";
        $errores .= "- Debe escribir un apellido.<br />";
    }
    if (trim($_POST['usuario']) == '') {
        $valido = "N";
        $errores .= "- Debe escribir un usuario.<br />";
    }
    if (intval($_POST['sucursales']) == 0) {

        $valido = "N";
        $errores .= "- Debe indiciar una sucursal para usuario.<br />";

    } else {
        //traemos el punto de expedicion por defecto de la sucu
        $buscar = "select punto_expedicion,nombre from sucursales where idsucu=$sucursale";
        $rssu3 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $puntoex = intval($rssu3->fields['punto_expedicion']);
        $nombresucuchar = trim($rssu3->fields['nombre']);
    }

    // conversiones
    if ($_POST['mozo'] != 'S' && $_POST['mozo'] != 'N') {
        $mozop = "'N'";
    }


    // buscar que no existe usuario
    $consulta = "
    select * from usuarios where usuario = $usuario and idusu <> $id
    ";
    $rsus2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsus2->fields['idusu']) > 0) {
        $valido = "N";
        $errores .= "- El usuario seleccionado ya existe.<br />";
    }

    if ($valido == 'S') {
        //todo ok, traemos los datos necesarios del usuario editado
        $buscar = "select * from usuarios
         where idusu = $id
        ";
        $rssecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $usuante = antisqlinyeccion($rssecu->fields['usuario'], 'text');
        $sucuante = intval($rssecu->fields['sucursal']);


        //echo $usuante.'-'.$usuario;
        //exit;

        $consulta = "
        UPDATE usuarios 
        SET
            nombres=$nombres, 
            apellidos=$apellidos, 
            documento=$documento, 
            usuario=$usuario, 
            email=$email,
            sucursal=$sucursale,
            pe=$puntoex,
            mozo = $mozop,
            idterminal_obliga=$idterminal_obliga
        WHERE
            idusu=$id
            and estado=1
            $whereadd
        ";
        //echo $consulta;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $usuante = str_replace("'", "", $usuante);
        $usuario = str_replace("'", "", $usuario);
        //vemos si hay cambios de usuario y sucursal
        if ($usuante != $usuario) {

            $deschar1 = "Cambio Usuario $usuante -> $usuario"    ;

        } else {
            $deschar1 = "Edicion de usuario $usuante";

        }
        //Primer insert edita general
        $insertar = "Insert into usuarios_registro_cambios
        (tipo_cambio,deschar,realizado_por,idempresa)
        values
        (4,'$deschar1',$idusu,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        if ($sucuante != $sucursale) {

            $insertar = "Insert into usuarios_registro_cambios
            (tipo_cambio,deschar,realizado_por,idempresa)
            values
            (1,'Cambio de sucursal a $nombresucuchar para $usuante',$idusu,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        }

        header("location: usuarios.php");
        exit;

    }


}
//lista de sucursales
$buscar = "select * from sucursales where idempresa=$idempresa order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



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
                    <h2>Editar Usuario</h2>
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usuario *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="usuario" id="usuario" value="<?php  if (isset($_POST['usuario'])) {
        echo htmlentities($_POST['usuario']);
    } else {
        echo htmlentities($rsus->fields['usuario']);
    }?>" placeholder="Usuario" class="form-control" required="required" />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['sucursales'])) {
    $value_selected = htmlentities($_POST['sucursales']);
} else {
    $value_selected = htmlentities($rsus->fields['sucursal']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'sucursales',
    'id_campo' => 'sucursales',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
        echo htmlentities($_POST['nombres']);
    } else {
        echo htmlentities($rsus->fields['nombres']);
    }?>" placeholder="Nombres" class="form-control" required  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
        echo htmlentities($_POST['apellidos']);
    } else {
        echo htmlentities($rsus->fields['apellidos']);
    }?>" placeholder="Apellidos" class="form-control" required  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
        echo intval($_POST['documento']);
    } else {
        echo intval($rsus->fields['documento']);
    }?>" placeholder="Documento" class="form-control"  />                    
    </div>
</div>

                
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Terminal Obligada </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
    // consulta
    $consulta = "
    SELECT idterminal, terminal.terminal as nombre
    FROM terminal
    where
       terminal.estado = 1
    order by terminal.terminal asc
     ";

// valor seleccionado
if (isset($_POST['idterminal_obliga'])) {
    $value_selected = htmlentities($_POST['idterminal_obliga']);
} else {
    $value_selected = htmlentities($rsus->fields['idterminal_obliga']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idterminal_obliga',
    'id_campo' => 'idterminal_obliga',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idterminal',

    'value_selected' => $value_selected,

    'pricampo_name' => 'No Obligar',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>

    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mozo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input name="mozo" id="mozo" type="checkbox" value="S" class="js-switch"  <?php if ($rsus->fields['mozo'] == 'S') {
        echo "checked";
    } ?>  >
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
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
