 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

if ($superus != 'S') {
    $whereadd = "
    and super = 'N'
    ";
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    $consulta = "
    select tipo_cierre_caja_pred, tipo_tk_cierre_caja_pred from preferencias_caja limit 1
    ";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_cierre_caja_pred = $rsprefcaj->fields['tipo_cierre_caja_pred'];
    $tipo_tk_cierre_caja_pred = $rsprefcaj->fields['tipo_tk_cierre_caja_pred'];

    //     recibe variables
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $usuario = antisqlinyeccion($_POST['usuario'], "text");
    $documento = antisqlinyeccion($_POST['documento'], "int");
    $email = antisqlinyeccion($_POST['email'], "text");
    $clave = antisqlinyeccion(md5(trim($_POST['clave'])), "clave");
    $sucursale = intval($_POST['sucursales']);

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
    if (trim($_POST['clave']) == '') {
        $valido = "N";
        $errores .= "- La clave no puede estar vacia.<br />";
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



    // buscar que no existe usuario
    $consulta = "
    select * from usuarios where usuario = $usuario
    ";
    $rsus2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsus2->fields['idusu']) > 0) {
        $valido = "N";
        $errores .= "- El usuario seleccionado ya existe.<br />";
    }

    if ($valido == 'S') {
        $ahora = date("Y-m-d H:i:s");
        $consulta = "
        INSERT INTO usuarios
        (
        nombres, apellidos, documento, usuario, clave, nivel,  estado, timeout, login, sucursal, idempresa, registrado_el, registrado_por, pe, delivery, email, super, 
        tipocaja, tipotk
        ) 
        VALUES
        (
        $nombres,$apellidos,$documento,$usuario,$clave,1,1,900,0,$sucursale,$idempresa,'$ahora',$idusu,
        $puntoex,0,$email, 'N', 
        '$tipo_cierre_caja_pred','$tipo_tk_cierre_caja_pred')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $yuser = trim($_POST['usuario']);
        $insertar = "Insert into usuarios_registro_cambios
        (tipo_cambio,deschar,realizado_por,idempresa)
        values
        (5,'Nuevo usuario registrado: $yuser',$idusu,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        header("location: usuarios.php");

    }


}

//lista de sucursales
$buscar = "select * from sucursales where idempresa=$idempresa order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function generar_clave(longitud){
  var caracteres = "ABCDEFGHIJKLMNPQRTUVWXYZ2346789";
  var contrasena = "";
  for (i=0; i<longitud; i++) contrasena += caracteres.charAt(Math.floor(Math.random()*caracteres.length));
  //return contraseña;
  document.getElementById('clave').value=contrasena;
  document.getElementById('clavefake').value=contrasena;
}
</script>
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
                    <h2>Agregar Usuario</h2>
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
        echo htmlentities($rs->fields['usuario']);
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
    $value_selected = htmlentities($rs->fields['idsucu']);
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Clave *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="clavefake" id="clavefake" value="<?php if (isset($_POST['clave'])) {
        echo htmlentities(trim($_POST['clave']));
    } ?>" placeholder="Clave" class="form-control" style="font-weight:bold; color:#F00;" disabled readonly />                    
    </div>
</div>
<input type="hidden" name="clave" id="clave" value="<?php if (isset($_POST['clave'])) {
    echo htmlentities(trim($_POST['clave']));
} ?>" />



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
        echo htmlentities($_POST['nombres']);
    } else {
        echo htmlentities($rs->fields['nombres']);
    }?>" placeholder="Nombres" class="form-control" required  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
        echo htmlentities($_POST['apellidos']);
    } else {
        echo htmlentities($rs->fields['apellidos']);
    }?>" placeholder="Apellidos" class="form-control" required  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
        echo intval($_POST['documento']);
    } else {
        echo intval($rs->fields['documento']);
    }?>" placeholder="Documento" class="form-control"  />                    
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
<?php if (!isset($_POST['clave'])) { ?>
<script>

$( document ).ready(function() {
    generar_clave(8);
});

</script>
<?php } ?>
  </body>
</html>
