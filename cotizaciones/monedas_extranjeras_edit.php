<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "184";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idtipo = intval($_GET['id']);
if ($idtipo == 0) {
    header("location: tipo_moneda.php");
    exit;
}


// consulta a la tabla
$consulta = "
select * 
from tipo_moneda 
where 
idtipo = $idtipo
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo = intval($rs->fields['idtipo']);
if ($idtipo == 0) {
    header("location: monedas_extranjeras.php");
    exit;
}



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
    // print_r($_POST);
    // recibe parametros
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $nacional = antisqlinyeccion($_POST['nacional'], "text");
    $cotiza = antisqlinyeccion($_POST['cotiza'], "int");


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo Denominaci&oacute;n no puede estar vac&iacute;o.<br />";
    }
    if (intval($_POST['cotiza']) != 1 && intval($_POST['cotiza']) != 2) {
        $valido = "N";
        $errores .= " - El campo Cotiza no puede estar vac&iacute;o.<br />";
    }
    if (trim($_POST['nacional']) == '') {
        $valido = "N";
        $errores .= " - El campo Moneda por defecto no puede estar vac&iacute;o.<br />";
    }
    if (intval($_POST['cotiza']) == 1 && ($_POST['nacional']) == "S") {
        $valido = "N";
        $errores .= " - El campo Cotiza no puede ser si cuando es una Moneda Nacional.<br />";
    }




    // si todo es correcto actualiza
    if ($valido == "S") {
        $nomarchi = "";
        $target_dir = "../img/";
        $target_file = $target_dir . basename($_FILES["img"]["name"]);
        $uploadOk = 1;



        if (basename($_FILES["img"]["size"]) == 0) {
            $uploadOk = 0;
        }
        ////inicio de imagen
        /////////////////////////

        if ($uploadOk != 0) {
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            // Verificar FAke IMG
            if (isset($_POST["submit"])) {
                $check = getimagesize($_FILES["img"]["tmp_name"]);
                if ($check !== false) {
                    echo "Es una imagen - " . $check["mime"] . ".";
                    $uploadOk = 1;
                } else {
                    echo "Archivo no es una imagen.";
                    $uploadOk = 0;
                }
            }

            //
            if ($_FILES["img"]["size"] > 500000) {
                echo "La imagen es muy grande.";
                $uploadOk = 0;
            }
            // Permitir ciertos tipos
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif") {
                echo "Solo aceptamos JPG, JPEG, PNG & GIF.";
                $uploadOk = 0;
            }
            // verificar error
            if ($uploadOk == 0) {
                echo "Imagen no ha sido cargada por un error.";
                // subimos si todo esta ok
            } else {
                if ($rs->fields['banderita'] != "") {
                    $archivo_a_borrar = $target_dir . $rs->fields['banderita'];
                    unlink($archivo_a_borrar);
                }
                if (move_uploaded_file($_FILES["img"]["tmp_name"], $target_file)) {
                    // echo "El archivo ". basename( $_FILES["img"]["name"]). " ha sido cargado.";
                    $nomarchi = basename($_FILES["img"]["name"]);
                } else {
                    echo "Error en la carga.";
                }
            }
        }
        if (trim($_POST['nacional']) == 'S') {
            $consulta = "
			update tipo_moneda
			set
				nacional='N'
			where
				nacional='S'
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }
        if ($nomarchi != null) {

            $consulta = "
			update tipo_moneda
			set
				descripcion=$descripcion,
				banderita='$nomarchi',
				nacional=$nacional,
				cotiza=$cotiza
			where
				idtipo=$idtipo
				and estado = 1
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            $consulta = "
			update tipo_moneda
			set
				descripcion=$descripcion,
				nacional=$nacional,
				cotiza=$cotiza
			where
				idtipo=$idtipo
				and estado = 1
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        header("location: monedas_extranjeras.php");
        exit;

    }

}



?>
<!DOCTYPE html>
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
                    <h2>Ordenes de Compra en Proceso de Carga</h2>
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
<form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">* Denominaci&oacute;n</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input class="form-control" name="descripcion" type="text" required="required" id="descripcion" placeholder="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" maxlength="60" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><strong>Im&aacute;gen (v&aacute;lida para los m&oacute;dulos de venta)</strong></label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="file" name="img" id="img" aria-describedby="banderitaHelp" class="form-control"/>
		<?php if (isset($rs->fields["banderita"]) == "" or isset($rs->fields["banderita"]) == null) { ?>
			<small id="banderitaHelp" class="form-text text-muted">
				La carga de otro archivo resultará en la sobreescritura del archivo actual.
			</small>
		<?php } ?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda Nacional</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php

    if (isset($_POST['nacional'])) {
        $value_selected = htmlentities($_POST['nacional']);
    } else {
        $value_selected = $rs->fields['nacional'];
    }
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'nacional',
    'id_campo' => 'nacional',

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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotiza</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php

if (isset($_POST['cotiza'])) {
    $value_selected = htmlentities($_POST['cotiza']);
} else {
    $value_selected = $rs->fields['cotiza'];
}
// opciones
$opciones = [
    'SI' => 1,
    'NO' => 2
];
// parametros
$parametros_array = [
    'nombre_campo' => 'cotiza',
    'id_campo' => 'cotiza',

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
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='monedas_extranjeras.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

	<input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
			<!-- contenedor -->
   	
</div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 




            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>


