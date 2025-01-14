<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "30";
$submodulo = "486";
require_once("includes/rsusuario.php");

$id_empresa = 1;

$consulta_proveedores = "select * from proveedores where idempresa = $id_empresa and estado = 1";
$rs_proveedores = $conexion->Execute($consulta_proveedores) or die(errorpg($conexion, $consulta_proveedores));

$consulta_vehiculos = "select * from vehiculos where idempresa = 1 and estado = 1";
$rs_vehiculos = $conexion->Execute($consulta_vehiculos) or die(errorpg($conexion, $consulta_vehiculos));

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // CONFIGURACION
    $allowed = ["image/bmp","image/gif","image/jpeg","image/jpeg","image/pjpeg","image/png","image/x-png","application/pdf","application/x-pdf"];
    $tamanomax = 5120000; // bytes
    $timestamp = date("YmdHis");
    $ruta_nueva = "gfx/remisiones/img_comp_".$timestamp.".jpg";
    $techo_px = 1000; // tamano maximo permitido para el lado mas grande, si supera se ajusta nomas
    $piso_px = 800; // tamano minimo permitido para el lado mas pequeno si es menor no permite la suba de imagen

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
    $numero = antisqlinyeccion(antixss($_POST['numero']), "text");
    $fecha_correspondiente = antisqlinyeccion(antixss($_POST['fecha_correspondiente']), "text");
    $timbrado = antisqlinyeccion(antixss($_POST['timbrado']), "text");
    $fecha_inicio_vigencia_timbrado = antisqlinyeccion(antixss($_POST['fecha_inicio_vigencia_timbrado']), "text");
    $fecha_fin_vigencia_timbrado = antisqlinyeccion(antixss($_POST['fecha_fin_vigencia_timbrado']), "text");
    $tipo_comprobante = antisqlinyeccion(antixss($_POST['tipo_comprobante']), "int");
    $cdc = antisqlinyeccion(antixss($_POST['cdc']), "text");
    $idproveedor = antisqlinyeccion(antixss($_POST['idproveedor']), "int");
    $razon_social = antisqlinyeccion(antixss($_POST['razon_social']), "text");
    $fecha_inicio_traslado = antisqlinyeccion(antixss($_POST['fecha_inicio_traslado']), "text");
    $fecha_termino_traslado = antisqlinyeccion(antixss($_POST['fecha_termino_traslado']), "text");
    /*$marca_vehiculo_traslado=antisqlinyeccion(antixss($_POST['marca_vehiculo_traslado']),"text");
    $numero_chapa_vehiculo_traslado=antisqlinyeccion(antixss($_POST['numero_chapa_vehiculo_traslado']),"text");
    $nombre_transportista=antisqlinyeccion(antixss($_POST['nombre_transportista']),"text");
    $ci_ruc_transportista=antisqlinyeccion(antixss($_POST['ci_ruc_transportista']),"text");
    $nombre_conductor=antisqlinyeccion(antixss($_POST['nombre_conductor']),"text");
    $ci_ruc_conductor=antisqlinyeccion(antixss($_POST['ci_ruc_conductor']),"text");
    $direccion_conductor=antisqlinyeccion(antixss($_POST['direccion_conductor']),"text");*/
    $idvehiculo = antisqlinyeccion(antixss($_POST['idvehiculo']), "int");
    $motivo_traslado = ((antixss($_POST['motivo_traslado']) != 0) ? antisqlinyeccion(antixss($_POST['motivo_traslado']), "int") : antisqlinyeccion(0, "int"));
    $motivo_traslado_otro = antisqlinyeccion(antixss($_POST['motivo_traslado_otro']), "text");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");

    if (trim($_POST['numero']) == '') {
        $valido = "N";
        $errores .= " - El campo numero no puede estar vacio.<br />";
    }
    if (trim($_POST['fecha_correspondiente']) == '') {
        $valido = "N";
        $errores .= " - El campo Fecha correspondiente no puede estar vacio.<br />";
    }
    if (trim($_POST['timbrado']) == '') {
        $valido = "N";
        $errores .= " - El Timbrado no puede estar vacio.<br />";
    }
    if (intval($_POST['idproveedor']) == 0) {
        $valido = "N";
        $errores .= " - El campo Proveedor no puede ser cero o nulo.<br />";
    }
    /*if(trim($_POST['fecha_inicio_traslado']) == ''){
        $valido="N";
        $errores.=" - El campo Fecha de inicio del traslado no puede estar vacio.<br />";
    }
    if(trim($_POST['fecha_termino_traslado']) == ''){
        $valido="N";
        $errores.=" - El campo Fecha de inicio del traslado no puede estar vacio.<br />";
    }
    if(trim($_POST['marca_vehiculo_traslado']) == ''){
        $valido="N";
        $errores.=" - El campo Marca del vehiculo de traslado no puede estar vacio.<br />";
    }
    if(trim($_POST['numero_chapa_vehiculo_traslado']) == ''){
        $valido="N";
        $errores.=" - El campo Nro. de chapa del vehiculo de traslado no puede estar vacio.<br />";
    }*/
    /*
    nombre_transportista
        if(trim($_POST['nombre_transportista']) == ''){
            $valido="N";
            $errores.=" - El campo nombre_transportista no puede estar vacio.<br />";
        }
    */
    /*
    ci_ruc_transportista
        if(trim($_POST['ci_ruc_transportista']) == ''){
            $valido="N";
            $errores.=" - El campo ci_ruc_transportista no puede estar vacio.<br />";
        }

        if(trim($_POST['nombre_conductor']) == ''){
            $valido="N";
            $errores.=" - El campo Nombre del conductor no puede estar vacio.<br />";
        }
    /*
    ci_ruc_conductor
        if(trim($_POST['ci_ruc_conductor']) == ''){
            $valido="N";
            $errores.=" - El campo ci_ruc_conductor no puede estar vacio.<br />";
        }
    */
    /*
    direccion_conductor
        if(trim($_POST['direccion_conductor']) == ''){
            $valido="N";
            $errores.=" - El campo direccion_conductor no puede estar vacio.<br />";
        }
    */
    if (trim($_POST['motivo_traslado']) == 0 && trim($_POST['motivo_traslado_otro']) == '') {
        $valido = "N";
        $errores .= " - El campo motivo_traslado no puede estar vacio.<br />";
    }

    if (trim($_POST['motivo_traslado']) > 12) {
        $valido = "N";
        $errores .= " - El campo motivo_traslado no existe.<br />";
    }

    /*
    motivo_traslado_otro
        if(trim($_POST['motivo_traslado_otro']) == ''){
            $valido="N";
            $errores.=" - El campo motivo_traslado_otro no puede estar vacio.<br />";
        }
    */

    // si todo es correcto inserta
    if ($valido == "S") {

        $inicio = strtotime($_POST['fecha_correspondiente']);
        $fin = strtotime($_POST['fecha_fin_vigencia_timbrado']);

        if ($inicio <= $fin) {

            //if (!empty($_FILES['images']['name'][0])){

            if (!empty($_FILES['images']['name'][0])) {

                $arr_detalles_img = getimagesize($_FILES['images']['tmp_name'][0]);
                $widht_imagen = intval($arr_detalles_img[0]);
                $height_imagen = intval($arr_detalles_img[1]);
                $lado_mas_grande = ($widht_imagen > $height_imagen) ? $widht_imagen : $height_imagen;
                $lado_mas_chico = ($widht_imagen < $height_imagen) ? $widht_imagen : $height_imagen;
                $nombre_imagen_final = '';

                $porcentaje_menor_del_mayor = (($lado_mas_chico / $lado_mas_grande) * 100);
                if ($height_imagen > $widht_imagen) {

                    if ($height_imagen >= $techo_px) {

                        $height_imagen_final = $techo_px;
                        $widht_imagen_final = floor(($height_imagen_final * ($porcentaje_menor_del_mayor / 100)));
                    } else {

                        $height_imagen_final = $height_imagen;
                        $widht_imagen_final = $widht_imagen;
                    }
                }

                if ($widht_imagen > $height_imagen) {

                    if ($widht_imagen >= $techo_px) {

                        $widht_imagen_final = $techo_px;
                        $height_imagen_final = floor(($widht_imagen_final * ($porcentaje_menor_del_mayor / 100)));
                    } else {

                        $height_imagen_final = $height_imagen;
                        $widht_imagen_final = $widht_imagen;
                    }
                }

                $nombre_imagen_final = antisqlinyeccion(strtolower('img_comp_'.$timestamp.'.jpg'), "text");
                $total = count($_FILES['images']['name']);

                if ($_FILES['images']['type'][0] == "image/png") {

                    $imgGrande = imagecreatefrompng($_FILES['images']['tmp_name'][0]);

                } else {

                    $imgGrande = imagecreatefromjpeg($_FILES['images']['tmp_name'][0]);

                }


                $imgRedimensionada = create_tb($imgGrande, $widht_imagen_final, $height_imagen_final, "FFFFFF");
                imagejpeg($imgRedimensionada, $ruta_nueva, 60);


            } else {

                $nombre_imagen_final = antisqlinyeccion('', 'text');

            }

            $consulta = "
				insert into nota_remision_cabecera
				(idempresa, numero, timbrado, fecha_inicio_vigencia_timbrado, fecha_fin_vigencia_timbrado, cdc, fecha_correspondiente, idproveedor, fecha_inicio_traslado, fecha_termino_traslado, /*marca_vehiculo_traslado, 
				numero_chapa_vehiculo_traslado, nombre_transportista, ci_ruc_transportista, nombre_conductor, 
				ci_ruc_conductor, direccion_conductor,*/ idvehiculo, motivo_traslado, motivo_traslado_otro, estado, 
				registrado_por, registrado_el, img, tipo_comprobante)
				values
				($id_empresa, $numero, $timbrado, $fecha_inicio_vigencia_timbrado, $fecha_fin_vigencia_timbrado, $cdc, $fecha_correspondiente, $idproveedor, $fecha_inicio_traslado, $fecha_termino_traslado, /*$marca_vehiculo_traslado, 
				$numero_chapa_vehiculo_traslado, $nombre_transportista, $ci_ruc_transportista, $nombre_conductor, 
				$ci_ruc_conductor, $direccion_conductor,*/ $idvehiculo, $motivo_traslado, $motivo_traslado_otro, $estado, $registrado_por, 
				$registrado_el, $nombre_imagen_final, $tipo_comprobante)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            //Agrego la nota de remision a la tabla de transacciones_remisiones para que se pueda mover el stock

            $consulta_nrt = "select max(id) as maxid from nota_remision_cabecera";
            $rs_nrt = $conexion->getRow($consulta_nrt) or die(errorpg($conexion, $consulta_nrt));

            $nrt_id = antisqlinyeccion($rs_nrt['maxid'], "int");

            $inserta_transaccion = "
				insert into transacciones_remisiones (id_nota_remision, tipo, fecha) 
				values ($nrt_id, 1, now());
				";
            $conexion->Execute($inserta_transaccion) or die(errorpg($conexion, $inserta_transaccion));

            header("location: nota_remision.php");
            exit;

            /*}else{

                $valido = "N";
                $errores .= " - Debe seleccionar una foto del certificado<br />";

            }*/

        } else {

            $valido = "N";
            $errores .= " - La fecha de vencimiento de la tanda de remisiones no debe ser menor la fecha correspondiente<br />";

        }

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

function create_tb($img_o, $w_tb, $h_tb, $bg_color)
{
    $img_return = imagecreatetruecolor($w_tb, $h_tb);
    if (isset($bg_color) && $bg_color != "") {
        $color = imagecolorallocate($img_return, hexdec(substr($bg_color, 0, 2)), hexdec(substr($bg_color, 2, 2)), hexdec(substr($bg_color, 4, 2)));
    } else {
        $color = imagecolorallocate($img_return, 255, 255, 255);
    }
    imagefilledrectangle($img_return, 0, 0, $w_tb, $h_tb, $color);
    $wo = imagesx($img_o);
    $ho = imagesy($img_o);
    $datos = [
        'wo' => $wo,
        'ho' => $ho,
        'w_tb' => $w_tb,
        'h_tb' => $h_tb,

    ];
    //print_r($datos);exit;
    // si el Ancho original es mayor al Alto original
    if ($wo > $ho) {
        $wtb_copy = $w_tb;
        $htb_copy = ($ho * (($w_tb * 100) / $wo)) / 100;
        $xtb_copy = 0;
        $ytb_copy = round(($h_tb / 2) - ($htb_copy / 2), 0);
        //$ytb_copy=round($h_tb-$htb_copy,0);
        //$ytb_copy=0;
        // si el Alto original es mayor o igual al Ancho original
    } elseif ($ho >= $wo) {
        $wtb_copy = ($wo * (($h_tb * 100) / $ho)) / 100;
        $htb_copy = $h_tb;
        $xtb_copy = round(($w_tb / 2) - ($wtb_copy / 2), 0);
        $ytb_copy = 0;
    }
    imagecopyresampled($img_return, $img_o, $xtb_copy, $ytb_copy, 0, 0, $wtb_copy, $htb_copy, $wo, $ho);
    return $img_return;
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
	<link type="text/css" rel="stylesheet" href="css/image-uploader.min.css">
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
                    <h2>Nueva nota de remisi&oacute;n</h2>
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
						<label class="control-label col-md-3 col-sm-3 col-xs-12">N&uacute;mero *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" name="numero" id="numero" maxlength="15" value="<?php  if (isset($_POST['numero'])) {
						    echo htmlentities($_POST['numero']);
						} else {
						    echo htmlentities($rs->fields['numero']);
						}?>" placeholder="N&uacute;mero" class="form-control" required="required" />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha correspondiente *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="date" name="fecha_correspondiente" id="fecha_correspondiente" value="<?php  if (isset($_POST['fecha_correspondiente'])) {
						    echo htmlentities($_POST['fecha_correspondiente']);
						} else {
						    echo htmlentities($rs->fields['fecha_correspondiente']);
						}?>" placeholder="Fecha correspondiente" class="form-control" required="required" />                    
						</div>
					</div>


					<div class="col-md-12 form-group">

						<div class="row">
							<div class="col"><hr></div>
						</div>

					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" name="timbrado" id="timbrado" value="<?php  if (isset($_POST['timbrado'])) {
						    echo htmlentities($_POST['timbrado']);
						} else {
						    echo htmlentities($rs->fields['timbrado']);
						}?>" placeholder="Nro. Timbrado" class="form-control" required="required" />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha inicio vigencia timbrado</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="date" name="fecha_inicio_vigencia_timbrado" id="fecha_inicio_vigencia_timbrado" value="<?php  if (isset($_POST['fecha_inicio_vigencia_timbrado'])) {
						    echo htmlentities($_POST['fecha_inicio_vigencia_timbrado']);
						} else {
						    echo htmlentities($rs->fields['fecha_inicio_vigencia_timbrado']);
						}?>" placeholder="Inicio de vigencia del timbrado" class="form-control" />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha fin vigencia timbrado</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="date" name="fecha_fin_vigencia_timbrado" id="fecha_fin_vigencia_timbrado" value="<?php  if (isset($_POST['fecha_fin_vigencia_timbrado'])) {
						    echo htmlentities($_POST['fecha_fin_vigencia_timbrado']);
						} else {
						    echo htmlentities($rs->fields['fecha_fin_vigencia_timbrado']);
						}?>" placeholder="Fin de vigencia del timbrado" class="form-control" />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de comprobante</label>
						<div class="col-md-6">
							<select id='tipo_comprobante' name='tipo_comprobante' placeholder="Tipo de comprobante" class="form-control" >
								<option value='1'>Preimpreso</option>
								<option value='2'>Autoimpreso</option>
								<option value='3'>Virtual</option>
								<option value='4'>Electr&oacute;nico</option>
							</select>
				  		</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">CDC</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" name="cdc" id="cdc" value="<?php  if (isset($_POST['cdc'])) {
						    echo htmlentities($_POST['cdc']);
						} else {
						    echo htmlentities($rs->fields['cdc']);
						}?>" placeholder="CDC" class="form-control" />                    
						</div>
					</div>

					<div class="col-md-12 form-group">

						<div class="row">
							<div class="col"><hr></div>
						</div>

					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
							<select name="idproveedor" id="idproveedor" placeholder="Proveedor" class="form-control" data-live-search="true" required="required">
								<option value="0">- Seleccione -</option>
								<?php foreach ($rs_proveedores as $row) { ?>									
									<option  value="<?php echo antixss($row['idproveedor']); ?>"><?php echo antixss($row['nombre']); ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha inicio traslado *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="date" name="fecha_inicio_traslado" id="fecha_inicio_traslado" value="<?php  if (isset($_POST['fecha_inicio_traslado'])) {
						    echo htmlentities($_POST['fecha_inicio_traslado']);
						} else {
						    echo htmlentities($rs->fields['fecha_inicio_traslado']);
						}?>" placeholder="Fecha inicio traslado" class="form-control" required="required" />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha t&eacute;rmino traslado *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="date" name="fecha_termino_traslado" id="fecha_termino_traslado" value="<?php  if (isset($_POST['fecha_termino_traslado'])) {
						    echo htmlentities($_POST['fecha_termino_traslado']);
						} else {
						    echo htmlentities($rs->fields['fecha_termino_traslado']);
						}?>" placeholder="Fecha t&eacute;rmino traslado" class="form-control" required="required" />                    
						</div>
					</div>
 
					<div class="col-md-6 col-sm-6 form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Veh&iacute;culo</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
							<select name="idvehiculo" id="idvehiculo" placeholder="Veh&iacute;culo" class="form-control" data-live-search="true" required="required">
								<option value="0">- Seleccione -</option>
								<?php foreach ($rs_vehiculos as $row) { ?>									
									<option  value="<?php echo antixss($row['id']); ?>"><?php echo antixss($row['nombre_vehiculo']); ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="col-md-12 form-group">

						<div class="row">
							<div class="col"><hr></div>
						</div>

					</div>


					<div class="col-md-6 col-sm-6 form-group">
							<label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo traslado *</label>
							<div class="col-md-6">
								<select id='motivo_traslado' name='motivo_traslado' placeholder="Motivo traslado" class="form-control" >
									<option value='0'>- Seleccione -</option>
									<option value='1'>Venta</option>
									<option value='2'>Exportaci&oacute;n</option>
									<option value='3' selected>Compra</option>
									<option value='4'>Importaci&oacute;n</option>
									<option value='5'>Consignaci&oacute;n</option>
									<option value='6'>Devoluci&oacute;n</option>
									<option value='7'>Traslado locales empresa</option>
									<option value='8'>Traslado para transformaci&oacute;n</option>
									<option value='9'>Reparaci&oacute;n</option>
									<option value='10'>Emisi&oacute;n m&oacute;vil</option>
									<option value='11'>Exhibici&oacute;n</option>
									<option value='12'>Ferias</option>
								</select>
				  			</div>
							<div class="col-md-1">
								<input type="checkbox" name="otros" id="otros" value="otros">
								<label for="otros" class="indented-checkbox-text">Otro</label>
							</div>
					</div>

					<div class="col-md-6 col-sm-6 form-group">
						<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" name="motivo_traslado_otro" style="display:none" id="motivo_traslado_otro" value="<?php  if (isset($_POST['motivo_traslado_otro'])) {
						    echo htmlentities($_POST['motivo_traslado_otro']);
						} else {
						    echo htmlentities($rs->fields['motivo_traslado_otro']);
						}?>" placeholder="Otro motivo traslado" class="form-control"  />                    
						</div>
					</div>

					<div class="col-md-6 col-sm-6">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Comprobante </label>
						<div class="col-md-9 col-sm-9 col-xs-12 input-field">
							<div class="input-images-2" style="margin: 0px;"></div>
						</div>
					</div>

					<div class="col-md-12 form-group">

						<div class="row">
							<div class="col"><hr></div>
						</div>

					</div>

					<div class="clearfix"></div>
					<br />

						<div class="form-group">
							<div class="col-md-12 col-sm-12 col-xs-12 text-center">
							
						<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
						<button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_remision.php'"><span class="fa fa-ban"></span> Cancelar</button>
							</div>
						</div>

					<input type="hidden" name="MM_insert" value="form1" />
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

		<script type="text/javascript" src="js/jquery.min.js"></script>
    	<script type="text/javascript" src="js/image-uploader.min.js"></script>

		<script>

			$('#numero').keyup(function(event) {

				if($('#numero').val().length == 13){

					var primero = $('#numero').val().substr(0, 3);
					var segundo = $('#numero').val().substr(3, 3);
					var tercero = $('#numero').val().substr(7);
					var separador = '-';

					$('#numero').val(primero + separador + segundo + separador + tercero);

				}

			});

      		$('.input-images-2').imageUploader({
                maxSize: 2 * 1024 * 1024,
                maxFiles: 1
        	});
    	</script>
		
      </div>
    </div>
	<?php require_once("includes/footer_gen.php"); ?>

	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

	<script>

	$('#idproveedor').selectpicker({
  		size: 10,
    });

	$('#idvehiculo').selectpicker({
  		size: 10,
    });

	$(document).ready(function () {
		var ckbox = $('#otros');

		$('input').on('click',function () {
			if (ckbox.is(':checked')) {
				$('#motivo_traslado_otro').show();
				$('#motivo_traslado_otro').val('');
				$('#motivo_traslado').val('0');
				$('#motivo_traslado').prop( "disabled", true );
			} else {
				$('#motivo_traslado_otro').hide();
				$('#motivo_traslado_otro').val('');
				$('#motivo_traslado').prop( "disabled", false );
			}
		});
	});

	</script>
  </body>
</html>
