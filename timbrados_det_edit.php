<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");




$idtanda = intval($_GET['id']);
if ($idtanda == 0) {
    header("location: timbrados.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from facturas 
where 
idtanda = $idtanda
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$idtimbrado = intval($rs->fields['idtimbrado']);
$idtipodocutimbrado = $rs->fields['idtipodocutimbrado'];
if ($idtanda == 0) {
    header("location: timbrados.php");
    exit;
}

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
    $secuencia1 = antisqlinyeccion($_POST['sucursal'], "int");
    $secuencia2 = antisqlinyeccion($_POST['punto_expedicion'], "int");
    $inicio = antisqlinyeccion($_POST['inicio'], "int");
    $fin = antisqlinyeccion($_POST['fin'], "int");
    $punto_expedicion = antisqlinyeccion($_POST['punto_expedicion'], "int");
    $sucursal = antisqlinyeccion($_POST['sucursal'], "int");
    //$timbrado=antisqlinyeccion($_POST['timbrado'],"int");
    $valido_desde = antisqlinyeccion($valido_desde, "text");
    $valido_hasta = antisqlinyeccion($valido_hasta, "text");
    $tipoimpreso = antisqlinyeccion($_POST['tipoimpreso'], "text");
    $idtimbradotipo = antisqlinyeccion($_POST['idtimbradotipo'], "int");
    $comentario_punto = antisqlinyeccion($_POST['comentario_punto'], "text");

    // conversiones
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $cobrador_asignado = antisqlinyeccion('', "int");
    $observaciones = antisqlinyeccion('', "text");
    $estado = antisqlinyeccion('A', "text");
    $asignado_el = antisqlinyeccion($ahora, "text");



    if (intval($_POST['inicio']) == 0) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['fin']) == 0) {
        $valido = "N";
        $errores .= " - El campo fin no puede ser cero o nulo.<br />";
    }


    if (intval($_POST['punto_expedicion']) == 0) {
        $valido = "N";
        $errores .= " - El campo punto_expedicion no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['sucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo sucursal no puede ser cero o nulo.<br />";
    }

    if (intval($timbrado) == 0) {
        $valido = "N";
        $errores .= " - El campo timbrado no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idtimbradotipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo documento no puede estar vacio.<br />";
    }

    $consulta = "
	select tipo_old from timbrado_tipo where idtimbradotipo = $idtimbradotipo
	";
    $rsold = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoimpreso = antisqlinyeccion(trim($rsold->fields['tipo_old']), "text");
    if (trim($rsold->fields['tipo_old']) == '') {
        $valido = "N";
        $errores .= " - El campo tipo documento no tiene un comportamiento asignado.<br />";
    }

    /*if(trim($_POST['valido_desde']) == ''){
        $valido="N";
        $errores.=" - El campo valido_desde no puede estar vacio.<br />";
    }
    if(trim($_POST['valido_hasta']) == ''){
        $valido="N";
        $errores.=" - El campo valido_hasta no puede estar vacio.<br />";
    }*/


    // valida que ya no exista el timbrado
    /*$consulta="
    select * from facturas where timbrado = $timbrado and estado = 'A'  and idtanda <> $idtanda
    ";
    $rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if(intval($rs->fields['idtanda']) > 0){
        $valido="N";
        $errores.=" - El timbrado que intentas registrar ya existe.<br />";
    }*/

    // fin no puede ser superior a inicio
    if (intval($_POST['inicio']) >= intval($_POST['fin'])) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser mayor o igual a fin.<br />";
    }

    // valido hasta no puede ser menor a valido desde
    /*if(strtotime(date("Y-m-d",strtotime($_POST['valido_desde']))) >= strtotime(date("Y-m-d",strtotime($_POST['valido_hasta'])))){
        $valido="N";
        $errores.=" - El campo valido_desde no puede ser mayor o igual a valido_hasta.<br />";
    }*/


    // si todo es correcto inserta
    if ($valido == "S") {



        $consulta = "
		update facturas
		set
			secuencia1=$secuencia1,
			secuencia2=$secuencia2,
			inicio=$inicio,
			fin=$fin,
			punto_expedicion=$punto_expedicion,
			sucursal=$sucursal,
			idempresa=$idempresa,
			timbrado=$timbrado,
			valido_desde=$valido_desde,
			valido_hasta=$valido_hasta,
			cobrador_asignado=$cobrador_asignado,
			observaciones=$observaciones,
			asignado_el=$asignado_el,
			tipoimpreso=$tipoimpreso,
			idtimbradotipo = $idtimbradotipo,
			comentario_punto=$comentario_punto
		where
			idtanda = $idtanda
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // insertar log
        $consulta = "
		INSERT INTO facturaslog
		(idtanda, idtimbrado, idtipodocutimbrado, secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, log_registrado_el, log_registrado_por, log_tipomov, tipoimpreso,idtimbradotipo, comentario_punto)
		SELECT idtanda, idtimbrado, idtipodocutimbrado,  secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, '$ahora', $idusu, 'U', tipoimpreso,idtimbradotipo, comentario_punto
		FROM facturas 
		WHERE 
		idtanda = $idtanda
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: timbrados_det.php?id=$idtimbrado");
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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="timbrado" id="timbrado" value="<?php  echo $timbrado; ?>" placeholder="Timbrado" class="form-control" readonly disabled />                    
	</div>
</div>


<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipodocutimbrado, tipo_documento, estado
 FROM timbrado_tipodocu
  WHERE
  estado = 1
  and idtipodocutimbrado = $idtipodocutimbrado
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
    'acciones' => ' required="required" disabled ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="form-group">
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
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="inicio" id="inicio" value="<?php  if (isset($_POST['inicio'])) {
	    echo intval($_POST['inicio']);
	} else {
	    echo intval($rs->fields['inicio']);
	}?>" placeholder="Inicio" class="form-control" required  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fin" id="fin" value="<?php  if (isset($_POST['fin'])) {
	    echo intval($_POST['fin']);
	} else {
	    echo intval($rs->fields['fin']);
	}?>" placeholder="Fin" class="form-control" required  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="sucursal" id="sucursal" value="<?php  if (isset($_POST['sucursal'])) {
	    echo intval($_POST['sucursal']);
	} else {
	    echo intval($rs->fields['sucursal']);
	}?>" placeholder="Sucursal" class="form-control" required  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Punto expedicion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="punto_expedicion" id="punto_expedicion" value="<?php  if (isset($_POST['punto_expedicion'])) {
	    echo intval($_POST['punto_expedicion']);
	} else {
	    echo intval($rs->fields['punto_expedicion']);
	}?>" placeholder="Punto expedicion" class="form-control" required  />                    
	</div>
</div>

<div class="form-group">
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
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados_det.php?id=<?php echo $idtimbrado; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
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
