 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");



if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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


    // recibe parametros
    $tipovendedor = antisqlinyeccion(1, "int");
    $idtipodoc = antisqlinyeccion(1, "int");
    $nrodoc = antisqlinyeccion($_POST['nrodoc'], "int");
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $estado = antisqlinyeccion(1, "text");
    $idempresa = antisqlinyeccion($idempresa, "int");
    $pin = antisqlinyeccion($_POST['pin'], "text");
    $nomape = antisqlinyeccion(trim($_POST['nombres']).' '.trim($_POST['apellidos']), "text");
    $registrado_por = antisqlinyeccion($parametros_array['idusu'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['ahora'], "int");



    /*if(intval($_POST['tipovendedor']) == 0){
        $valido="N";
        $errores.=" - El campo tipovendedor no puede ser cero o nulo.<br />";
    }
    if(intval($_POST['idtipodoc']) == 0){
        $valido="N";
        $errores.=" - El campo idtipodoc no puede ser cero o nulo.<br />";
    }*/
    if (intval($_POST['nrodoc']) == 0) {
        $valido = "N";
        $errores .= " - El campo nrodoc no puede ser cero o nulo.<br />";
    }
    if (trim($_POST['nombres']) == '') {
        $valido = "N";
        $errores .= " - El campo nombres no puede estar vacio.<br />";
    }
    /*
    apellidos
        if(trim($_POST['apellidos']) == ''){
            $valido="N";
            $errores.=" - El campo apellidos no puede estar vacio.<br />";
        }
    */
    /*if(trim($_POST['estado']) == ''){
        $valido="N";
        $errores.=" - El campo estado no puede estar vacio.<br />";
    }
    if(intval($_POST['idempresa']) == 0){
        $valido="N";
        $errores.=" - El campo idempresa no puede ser cero o nulo.<br />";
    }*/


    if (trim($_POST['pin']) != '') {
        $consulta = "
        select * 
        from vendedor 
        where 
        pin = $pin
        and estado = 1
        limit 1
        ";
        $rsven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idvendedor_ex = intval($rsven->fields['idvendedor']);
        if (intval($idvendedor_ex) > 0) {
            $valido = 'N';
            $errores .= '- El pin indicado ya esta asignado a otro vendedor activo.<br />';
        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into vendedor
		(tipovendedor, idtipodoc, nrodoc, nomape, nombres, apellidos, estado, idempresa, carnet, pin, motivo, registrado_por, registrado_el)
		values
		($tipovendedor, $idtipodoc, $nrodoc, $nomape, $nombres, $apellidos, $estado, $idempresa, $carnet, $pin, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: vendedores.php");
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
            
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Vendedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nrodoc *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="nrodoc" id="nrodoc" value="<?php  if (isset($_POST['nrodoc'])) {
        echo intval($_POST['nrodoc']);
    } else {
        echo intval($rs->fields['nrodoc']);
    }?>" placeholder="Nrodoc" class="form-control" required="required" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
        echo htmlentities($_POST['nombres']);
    } else {
        echo htmlentities($rs->fields['nombres']);
    }?>" placeholder="Nombres" class="form-control" required="required" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
        echo htmlentities($_POST['apellidos']);
    } else {
        echo htmlentities($rs->fields['apellidos']);
    }?>" placeholder="Apellidos" class="form-control"  />                    
    </div>
</div>
    
<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pin </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="password" name="pin" id="pin" value="<?php  if (isset($_POST['pin'])) {
        echo htmlentities($_POST['pin']);
    } else {
        echo htmlentities($rs->fields['pin']);
    }?>" placeholder="Pin" class="form-control" autocomplete="new-password"  />                    
    </div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='vendedores.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
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
