 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");



$idcarritofactutmp = intval($_GET['id']);
if ($idcarritofactutmp == 0) {
    header("location: facturar_recurrente_det.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *,
(select usuario from usuarios where tmp_carrito_factu.registrado_por = usuarios.idusu) as registrado_por
from tmp_carrito_factu
inner join detalle on detalle.iddetalle = tmp_carrito_factu.iddetalle
inner join operacion on operacion.idoperacion = detalle.idoperacion 
inner join operacion_clase on operacion_clase.idoperacion_clase = operacion.idoperacion_clase
where 
 tmp_carrito_factu.estado = 1 

 and tmp_carrito_factu.registrado_por = $idusu
 and tmp_carrito_factu.idcarritofactutmp = $idcarritofactutmp
order by tmp_carrito_factu.idcarritofactutmp asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;exit;


$idcarritofactutmp = intval($rs->fields['idcarritofactutmp']);
$idcliente = intval($rs->fields['idcliente']);
if ($idcarritofactutmp == 0) {
    header("location: facturar_recurrente_det.php");
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

        $consulta = "
        update tmp_carrito_factu
        set
            estado = 6
        where
            idcarritofactutmp = $idcarritofactutmp
            and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: facturar_recurrente_det.php?id=$idcliente");
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
                    <h2>Borrar del Carrito de Obligaciones</h2>
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vencimiento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="vencimiento" id="vencimiento" value="<?php  if (isset($_POST['vencimiento'])) {
        echo htmlentities($_POST['vencimiento']);
    } else {
        echo htmlentities($rs->fields['vencimiento']);
    }?>" placeholder="factura" class="form-control" required readonly disabled="disabled" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="operacion_clase" id="operacion_clase" value="<?php  if (isset($_POST['operacion_clase'])) {
        echo htmlentities($_POST['operacion_clase']);
    } else {
        echo htmlentities($rs->fields['operacion_clase']);
    }?>" placeholder="Idcta" class="form-control" required readonly disabled="disabled" />                    
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Abonar *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_abonar" id="monto_abonar" value="<?php  echo formatomoneda($rs->fields['monto_abonar']); ?>" placeholder="Idcta" class="form-control" required readonly disabled="disabled" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='facturar_recurrente_det.php?id=<?php echo $idcliente ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
