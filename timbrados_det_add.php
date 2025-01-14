<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

require_once("includes/funciones_timbrado.php");

$idtimbrado = intval($_GET['id']);
$consulta = "
select * from timbrado where idtimbrado = $idtimbrado and estado = 1
";
$rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rstimb->fields['idtimbrado']);
$timbrado = intval($rstimb->fields['timbrado']);
$valido_desde = $rstimb->fields['inicio_vigencia'];
$valido_hasta = $rstimb->fields['fin_vigencia'];
if ($idtimbrado == 0) {
    header("location: timbrados.php");
    exit;
}




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
    $reinicia_proxfac = substr(trim($_POST['reinicia_proxfac']), 0, 1);


    // si no es autoimpresor
    if (intval($_POST['idtimbradotipo']) != 2) {
        // si puso reiniciar
        if ($reinicia_proxfac == 'S') {
            $errores .= "- No puedes reiniciar la numeracion por que el tipo de documento no es autoimpresor, en casos especiales deberas hacerlo despues en contabilidad > proxima factura.<br />";
            $valido = "N";
        }
    }
    // busca si  hay timbrado activo para el mismo punto de expedision
    if ($reinicia_proxfac == 'S') {
        $timbradodatos = timbrado_tanda($_POST['sucursal'], $_POST['punto_expedicion'], $idempresa, 1, 1, $ahora);
        $idtandatimbrado = $timbradodatos['idtanda'];
        if (intval($idtandatimbrado) > 0) {
            $valido = 'N';
            $errores .= '- Ya existe una tanda de timbrado vigente para la sucursal y punto de expedicion: '.agregacero(intval($_POST['sucursal']), 3).'-'.agregacero(intval($_POST['punto_expedicion']), 3).' si desea reiniciar debe borrar primero el timbrado antiguo.<br />';
        }
    }




    $parametros_array = [
        'idtimbrado' => $idtimbrado,
        'inicio' => $_POST['inicio'],
        'fin' => $_POST['fin'],
        'punto_expedicion' => $_POST['punto_expedicion'],
        'sucursal' => $_POST['sucursal'],
        'idtipodocutimbrado' => $_POST['idtipodocutimbrado'],
        'idtimbradotipo' => $_POST['idtimbradotipo'],
        'comentario_punto' => $_POST['comentario_punto'],
        'registrado_por' => $idusu,
        'registrado_el' => $ahora
    ];
    //print_r($parametros_array);exit;
    // validar datos del documento
    $res = validar_docu_timbrado($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = "N";
        $errores .= $res['errores'];
    }



    //print_r($res);exit;

    // si todo es correcto inserta
    if ($valido == "S") {

        // registrar documento
        $res = registrar_docu_timbrado($parametros_array);
        $idtanda = $res['idtanda'];
        if ($res['registrado'] == 'S') {
            header("location: timbrados_det.php?id=$idtimbrado");
            exit;
        } else {
            echo "Ocurrio un error y no se registro el documento.";
            exit;
        }
    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function tipodoc(tipo){
	//alert(tipo);
	// si es autoimpresor
	if(tipo == 2){
		$('#reinicia_proxfac').val('').change();
		$('#reinicia_proxfac').attr("readonly",false); 
	}else{
		$('#reinicia_proxfac').val('N').change();
		$('#reinicia_proxfac').attr("readonly",true);
	}
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
                    <h2>Administracion de Timbrados</h2>
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

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="timbrado" id="timbrado" value="<?php  echo $timbrado; ?>" placeholder="Timbrado" class="form-control" disabled readonly  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipodocutimbrado, tipo_documento, estado
 FROM timbrado_tipodocu
  WHERE
  estado = 1
  order by tipo_documento asc
 ";

// valor seleccionado
if (isset($_POST['idtipodocutimbrado'])) {
    $value_selected = htmlentities($_POST['idtipodocutimbrado']);
} else {
    $value_selected = htmlentities($rs->fields['idtipodocutimbrado']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipodocutimbrado',
    'id_campo' => 'idtipodocutimbrado',

    'nombre_campo_bd' => 'tipo_documento',
    'id_campo_bd' => 'idtipodocutimbrado',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Documento *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php


// consulta
$consulta = "
SELECT idtimbradotipo, timbrado_tipo
FROM timbrado_tipo
where
estado = 1
order by timbrado_tipo asc
 ";

// valor seleccionado
if (isset($_POST['idtimbradotipo'])) {
    $value_selected = htmlentities($_POST['idtimbradotipo']);
} else {
    $value_selected = htmlentities($rs->fields['idtimbradotipo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtimbradotipo',
    'id_campo' => 'idtimbradotipo',

    'nombre_campo_bd' => 'timbrado_tipo',
    'id_campo_bd' => 'idtimbradotipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="tipodoc(this.value);" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>




<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Reiniciar Proxima Factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php

// valor seleccionado
if (isset($_POST['reinicia_proxfac'])) {
    $value_selected = htmlentities($_POST['reinicia_proxfac']);
} else {
    $value_selected = 'N';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'reinicia_proxfac',
    'id_campo' => 'reinicia_proxfac',

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

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="inicio" id="inicio" value="<?php  if (isset($_POST['inicio'])) {
	    echo intval($_POST['inicio']);
	} else {
	    echo intval($rs->fields['inicio']);
	}?>" placeholder="Inicio" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fin" id="fin" value="<?php  if (isset($_POST['fin'])) {
	    echo intval($_POST['fin']);
	} else {
	    echo intval($rs->fields['fin']);
	}?>" placeholder="Fin" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="sucursal" id="sucursal" value="<?php  if (isset($_POST['sucursal'])) {
	    echo intval($_POST['sucursal']);
	} else {
	    echo intval($rs->fields['sucursal']);
	}?>" placeholder="Sucursal" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Punto expedicion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="punto_expedicion" id="punto_expedicion" value="<?php  if (isset($_POST['punto_expedicion'])) {
	    echo intval($_POST['punto_expedicion']);
	} else {
	    echo intval($rs->fields['punto_expedicion']);
	}?>" placeholder="Punto expedicion" class="form-control" required  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario_punto" id="comentario_punto" value="<?php  if (isset($_POST['comentario_punto'])) {
	    echo antixss($_POST['comentario_punto']);
	} else {
	    echo antixss($rs->fields['comentario_punto']);
	}?>" placeholder="Comentario" class="form-control"   />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
		<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
		<button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados_det.php?id=<?php echo $idtimbrado; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
	</div>
</div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

<hr />
<strong>Donde encontrar esta informacion:</strong>
<br /><br />
<img src="img/partes_factura.jpg" class="img-thumbnail" />
<br /><br />
<img src="img/partes_factura_abajo.jpg" class="img-thumbnail" />
<br /><br />
<img src="img/timbset1.jpg" class="img-thumbnail" />
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
