<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "30";
$submodulo = "633";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idatc = intval($_GET['idatc']);
if ($idatc == 0) {
    header("location: mesas_smart_pin.php");
    exit;
}



$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
where 
 estadoex = 1 
and sucursales.idsucu = $idsucursal
and idatc = $idatc
order by mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$idatc = intval($rs->fields['idatc']);
if ($idatc == 0) {
    header("location: mesas_smart_pin.php");
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
    $pin = $_POST['pin'];



    $parametros_array = [

        "pin" => $pin,
        "idatc" => $idatc
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = mesas_atc_edit($parametros_array);
        if ($res["valido"] == "S") {
            header("location: mesas_smart_pin.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_mesas_atc.php y realizar un require_once
function mesas_atc_edit($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $pin = antisqlinyeccion($parametros_array['pin'], "text");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update mesas_atc
		set
			pin=$pin
		where
			idatc = $idatc
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Mesas Abiertas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Pin mesa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="pin" id="pin" value="<?php  if (isset($_POST['pin'])) {
	    echo htmlentities($_POST['pin']);
	} else {
	    echo htmlentities($rs->fields['pin']);
	}?>" placeholder="Pin mesa" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='mesas_smart_pin.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
<br />
<br />  
					  

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
           
            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
