<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
require_once("includes/rsusuario.php");




$idserie = intval($_GET['id']);
if ($idserie == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from caja_arqueo_fpagos 
where 
idserie = $idserie
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idserie = intval($rs->fields['idserie']);
$idcaja = intval($rs->fields['idcaja']);
$id_unicasspk = intval($rs->fields['id_unicasspk']);
if ($idserie == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}


$consulta = "
select * from caja_super where estado_caja = 3 and idcaja = $idcaja and rendido = 'N'
";
$rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rscaj->fields['idcaja']) == 0) {
    header("location: caja_cierre_edit.php");
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


    // recibe parametros
    //$idserie=antisqlinyeccion($_POST['idserie'],"text");
    //$idcaja=antisqlinyeccion($_POST['idcaja'],"int");
    $idformapago = antisqlinyeccion($_POST['idformapago'], "int");
    $monto = antisqlinyeccion($_POST['monto'], "float");
    /*$idbanco=antisqlinyeccion($_POST['idbanco'],"int");
    $valor_adicional=antisqlinyeccion($_POST['valor_adicional'],"text");
    $estado=1;
    $registrado_por=$idusu;
    $registrado_el=antisqlinyeccion($ahora,"text");
    $anulado_por=antisqlinyeccion($_POST['anulado_por'],"int");
    $anulado_el=antisqlinyeccion($_POST['anulado_el'],"text");
    $id_unicasspk=antisqlinyeccion($_POST['id_unicasspk'],"int");
    $id_registrobill=antisqlinyeccion($_POST['id_registrobill'],"int");
    $id_sermone=antisqlinyeccion($_POST['id_sermone'],"int");*/


    /*
        if(intval($_POST['idformapago']) == 0){
            $valido="N";
            $errores.=" - El campo idformapago no puede ser cero o nulo.<br />";
        }
        if(floatval($_POST['monto']) <= 0){
            $valido="N";
            $errores.=" - El campo monto no puede ser cero o negativo.<br />";
        }*/



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update caja_arqueo_fpagos
		set
			estado = 6
		where
			idserie = $idserie
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update caja_vouchers
		set
		estado = 6
		where
		unicasspk = $id_unicasspk
		and idcaja = $idcaja
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recalcular caja
        recalcular_caja($idcaja);

        header("location: caja_cierre_edit_edit.php?id=$idcaja");
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
                    <h2>Editar arqueo por forma de pago</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idformapago *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idforma, descripcion
FROM formas_pago
where
estado = 1
and idforma > 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idformapago'])) {
    $value_selected = htmlentities($_POST['idformapago']);
} else {
    $value_selected = htmlentities($rs->fields['idformapago']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idformapago',
    'id_campo' => 'idformapago',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idforma',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' disabled ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>                 
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="monto" id="monto" value="<?php  if (isset($_POST['monto'])) {
	    echo floatval($_POST['monto']);
	} else {
	    echo floatval($rs->fields['monto']);
	}?>" placeholder="Monto" class="form-control" disabled />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='caja_cierre_edit_edit.php?id=<?php echo $idcaja ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
