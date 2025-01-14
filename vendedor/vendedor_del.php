<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");
$idvendedor = intval($_GET['id']);
//echo $idvendedor; exit;
if ($idvendedor == 0) {
    header("location: vendedor.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *,
(SELECT descripcion FROM zona_vendedor WHERE zona_vendedor.codigo_zona = vendedor.codigo_zona) AS zona
from vendedor 
where 
idvendedor = $idvendedor
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idvendedor = intval($rs->fields['idvendedor']);
// if($idvendedor == 0){
// 	header("location: vendedor.php");
// 	exit;
// }


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $tipovendedor = antisqlinyeccion($_POST['tipovendedor'], "int");


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


    $parametros_array = [
        "idvendedor" => $idvendedor,
        "ahora" => $ahora,
        "idusu" => $idusu

    ];
    $borrado_por = $idusu;
    $borrado_el = $ahora;


    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = vendedor_delete($parametros_array);
        if ($res["valido"] == "S") {
            header("location: vendedor.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    }

}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



// se puede mover esta funcion al archivo funciones_vendedor.php y realizar un require_once
function vendedor_delete($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idvendedor = antisqlinyeccion($parametros_array['idvendedor'], "int");
    $borrado_por = antisqlinyeccion($parametros_array['idusu'], "int");
    $borrado_el = antisqlinyeccion($parametros_array['ahora'], "datetime");



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update vendedor
		set
			estado = 'I',
			borrado_por = $borrado_por,
			borrado_el = '$borrado_el'
		where
			idvendedor = $idvendedor
			and estado = 'A'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["error" => $errores,"valido" => $valido];
}


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>

function confirmarBorrado() {
    if (confirm("¿Está seguro de que desea borrar este registro?")) {
        // Si el usuario confirma, envía el formulario
        document.getElementById("form1").submit();
    } else {
        // Si el usuario cancela, no hace nada
        return false;
    }
}

</script>
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
                    <h2>Elimina Vendedor</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- AQUI SE COLOCA EL HTML -->
                  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Vendedor</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="codigo_vendedor" id="codigo_vendedor" value="<?php echo htmlentities($rs->fields['codigo_vendedor']);?>" placeholder="Código del Vendedor" class="form-control" disabled />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="nombres" id="nombres" value="<?php echo htmlentities($rs->fields['nombres']); ?>" placeholder="Nombres" class="form-control" disabled  />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos</label> 
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="apellidos" id="apellidos" value="<?php echo htmlentities($rs->fields['apellidos']); ?>" placeholder="Apellidos" class="form-control" disabled  />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Zona</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="zona" id="zona" value="<?php  echo htmlentities($rs->fields['zona']); ?>" placeholder="Zona" class="form-control" disabled  />                    
		</div>
	</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
		<button type="button" class="btn btn-danger" onclick="confirmarBorrado()"><span class="fa fa-trash-o"></span> Borrar</button>
		<button type="button" class="btn btn-primary" onClick="document.location.href=''"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo antixss($_SESSION['form_control']); ?>">
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
