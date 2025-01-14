<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../cotizaciones/preferencias_cotizacion.php");
global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;

$agregar = $_POST['agregar'];
$fecha_cotizacion = "";
if ($agregar) {

    // recibe parametros
    $cotizacion = antisqlinyeccion($_POST['cotizacion'], "float");
    $compra = antisqlinyeccion($_POST['compra'], "folat");
    $estado = antisqlinyeccion(1, "int");
    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $tipo_moneda = antisqlinyeccion($_POST['tipo_moneda'], "int");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    if ($compra == "'NULL'" || $compra == "") {
        $compra = "NULL";
    }
    if ($editar_fecha == "N") {
        $fecha = "'$ahora'";
    }


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if ($cotizacion == 0) {
        $valido = "N";
        $errores .= " - El campo cotizacion no puede ser cero o nulo.<br />";
    }
    if ($tipo_moneda == 0) {
        $valido = "N";
        $errores .= " - El campo tipo_moneda no puede ser cero o nulo.<br />";
    }
    if ($editar_fecha == 'S') {

        if ($fecha == "") {
            $valido = "N";
            $errores .= " - El campo fecha no puede ser nulo.<br />";
        } else {
            if ($tipo_moneda != 0) {
                $fecha_format = "";
                if ($cotiza_dia_anterior == "S") {
                    $fecha_format = date("Y-m-d", strtotime($_POST['fecha']) . " -1 day");
                } else {
                    $fecha_format = date("Y-m-d", strtotime($_POST['fecha']));
                }

                $consulta = "SELECT 
				count(*) as cotizaciones_datos
			FROM 
				cotizaciones
			WHERE 
				cotizaciones.estado = 1 
				AND DATE(cotizaciones.fecha) = '$fecha_format'
				AND cotizaciones.tipo_moneda = $tipo_moneda
				ORDER BY cotizaciones.fecha DESC
				LIMIT 1";
                // echo $consulta; exit;

                $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $num_cot = intval($rsmax->fields['cotizaciones_datos']);
                if ($num_cot > 0) {
                    $valido = "N";
                    $fecha_format = date("d/m/Y", strtotime($_POST['fecha']));
                    $errores .= " - Ya existe una cotizacion con la fecha $fecha_format.<br />";
                }
            }

        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $idcot = select_max_id_suma_uno("cotizaciones", "idcot")["idcot"];
        $consulta = "
		insert into cotizaciones
		(idcot, cotizacion, compra, estado, fecha, tipo_moneda, registrado_por, registrado_el)
		values
		($idcot, $cotizacion, $compra, $estado, $fecha, $tipo_moneda, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // $fecha_cotizacion=  date('m/d/Y', strtotime($fecha));
        $fecha_sin_comillas = str_replace("'", "", $fecha);
        $partes_fecha = explode('-', $fecha_sin_comillas);
        $fecha_cotizacion = $partes_fecha[2]."/".$partes_fecha[1]."/".$partes_fecha[0];

    }



}

?>
<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotizacion </label>
<div class="col-md-9 col-sm-9 col-xs-12"  onclick="buscar_cotizacion_moneda()">
    <select  name="idcot" id="idcot" class="form-control" >
        <option value="<?php echo $idcot; ?>" readonly select><?php echo formatomoneda($cotizacion, "N", 2); ?></option>
    </select>
	
	<small id="fecha_cotizacion_text" class="<?php if ($idcot == 0) { ?>hide <?php } ?>">Fecha cotizacion: <div id="fecha_cotizacion"><?php echo $fecha_cotizacion; ?></div> </small>
	
    <input type="hidden" name="idcotizacion" id="idcotizacion" value="<?php echo $idcot; ?>" />   
</div>