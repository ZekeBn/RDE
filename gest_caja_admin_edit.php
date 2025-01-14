 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "102";
require_once("includes/rsusuario.php");


//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);


$id = intval($_GET['id']);

$consulta = "
    SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa

    and usuarios.idusu = $id
    ";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rsus->fields['idusu']) == 0) {
    echo "Usuario Inexistente o no pertenece a tu sucursal!";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //     recibe variables
    $tipocaja = antisqlinyeccion($_POST['tipocaja'], "text");
    $tipotk = antisqlinyeccion($_POST['tipotk'], "text");
    $monto_fijo_chica = antisqlinyeccion($_POST['monto_fijo_chica'], "float");
    $monto_fijo_recau = antisqlinyeccion($_POST['monto_fijo_recau'], "float");
    $venta_retroactiva = antisqlinyeccion($_POST['venta_retroactiva'], "text");
    $obliga_compartir_caja = antisqlinyeccion($_POST['obliga_compartir_caja'], "text");

    // validaciones
    $valido = "S";
    $errores = "";
    if (trim($_POST['tipocaja']) <> 'V' && trim($_POST['tipocaja']) <> 'C') {
        $valido = "N";
        $errores .= "- Debe especificar un tipo de caja.<br />";
    }
    if (trim($_POST['tipotk']) <> 'V' && trim($_POST['tipotk']) <> 'C') {
        $valido = "N";
        $errores .= "- Debe especificar un tipo de tipo ticket.<br />";
    }

    // conversiones
    if (intval($_POST['monto_fijo_chica']) <= 0) {
        $monto_fijo_chica = 0;
    }
    if (intval($_POST['monto_fijo_recau']) <= 0) {
        $monto_fijo_recau = 0;
    }
    $permiteimprimir = antisqlinyeccion($_POST['permisoimpre'], 'text');

    if ($valido == 'S') {
        //todo ok, traemos los datos necesarios del usuario editado
        $buscar = "
        select * 
        from usuarios
         where 
         idusu = $id 
         and idempresa = $idempresa
        ";
        $rssecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $usuante = antisqlinyeccion($rssecu->fields['usuario'], 'text');
        $sucuante = intval($rssecu->fields['sucursal']);


        //echo $usuante.'-'.$usuario;
        //exit;

        $consulta = "
        UPDATE usuarios 
        SET
            tipocaja=$tipocaja,
            tipotk=$tipotk,
            monto_fijo_chica=$monto_fijo_chica,
            monto_fijo_recau=$monto_fijo_recau,
            reimprime_encaja=$permiteimprimir,
            venta_retroactiva=$venta_retroactiva,
            obliga_compartir_caja=$obliga_compartir_caja
        WHERE
            idempresa=$idempresa
            and idusu=$id
            and estado=1
        ";
        //echo $consulta;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        header("location: gest_caja_admin.php");
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
                    <h2>Editar Cajero</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<strong>Usuario:</strong> <?php echo $rsus->fields['usuario']; ?><hr />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_POST['tipocaja'])) {
    $value_selected = htmlentities($_POST['tipocaja']);
} else {
    $value_selected = $rsus->fields['tipocaja'];
}
// opciones
$opciones = [
    'CIEGA' => 'C',
    'VISIBLE' => 'V'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'tipocaja',
    'id_campo' => 'tipocaja',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>                 
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Ticket Cierre *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_POST['tipotk'])) {
    $value_selected = htmlentities($_POST['tipotk']);
} else {
    $value_selected = $rsus->fields['tipotk'];
}
// opciones
$opciones = [
    'TICKET CIEGO' => 'C',
    'TICKET VISIBLE' => 'V'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'tipotk',
    'id_campo' => 'tipotk',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>                 
    </div>
</div>
    

    
    
    
<?php if ($hab_monto_fijo_recau == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Fijo Caja Recaudacion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_fijo_recau" id="monto_fijo_recau" value="<?php echo floatval($rsus->fields['monto_fijo_recau']);?>" placeholder="App" class="form-control" required="required" />                    
    </div>
</div>
<?php } ?>
<?php if ($hab_monto_fijo_chica == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Fijo Caja Chica *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_fijo_chica" id="monto_fijo_chica" value="<?php echo floatval($rsus->fields['monto_fijo_chica']);?>" placeholder="App" class="form-control" required="required" />                    
    </div>
</div>
<?php }?>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ventas Retroactivas *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_POST['venta_retroactiva'])) {
    $value_selected = htmlentities($_POST['venta_retroactiva']);
} else {
    $value_selected = $rsus->fields['venta_retroactiva'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'venta_retroactiva',
    'id_campo' => 'venta_retroactiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>                    
    </div>
</div>
    
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Reimprimir Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_POST['permisoimpre'])) {
    $value_selected = htmlentities($_POST['permisoimpre']);
} else {
    $value_selected = $rsus->fields['reimprime_encaja'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'permisoimpre',
    'id_campo' => 'permisoimpre',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>                
    </div>
</div>

    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Obliga a compartir caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// valor seleccionado
if (isset($_POST['obliga_compartir_caja'])) {
    $value_selected = htmlentities($_POST['obliga_compartir_caja']);
} else {
    $value_selected = $rsus->fields['obliga_compartir_caja'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'obliga_compartir_caja',
    'id_campo' => 'obliga_compartir_caja',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>                
    </div>
</div>
    
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_caja_admin.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
