<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../cotizaciones/preferencias_cotizacion.php");

global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;

$agregar = intval($_POST['agregar']);

if ($agregar == 1) {

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
		( idcot,cotizacion, compra, estado, fecha, tipo_moneda, registrado_por, registrado_el)
		values
		( $idcot,$cotizacion, $compra, $estado, $fecha, $tipo_moneda, $registrado_por, $registrado_el)";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $fecha_cotizacion = date("d/m/Y", strtotime($fecha));
    }
}

if ($idcot != 0) {

    $consulta = "select * from cotizaciones where idcot = $idcot";
    $rscot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $fecha = $rscot->fields['fecha'];
    $fecha_cotizacion = date("d/m/Y", strtotime($fecha));
}
?>
<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotizacion <a href="javascript:void(0);" onclick="verificar_cotizacion_moneda();">[<span class="fa fa-search"></span>]</a></label>
<div class="col-md-9 col-sm-9 col-xs-12" onclick="buscar_cotizacion_moneda()">
    <select readonly name="idcot" id="idcot" aria-describedby="cotRefHelp" class="form-control">
        <?php if ($idcot != 0) { ?>
            <option value="<?php echo $idcot; ?>"><?php echo $cotizacion; ?></option>
        <?php } ?>
    </select>
    <p id="fecha_cotizacion_text" style="display:inline;" class="<?php if ($idcot == 0) { ?>hide <?php } ?>">Fecha cotizacion: <div id="fecha_cotizacion" style="display:inline;"><?php echo $fecha_cotizacion; ?></div> </p>

    <p id="cotRefHelp"><p>
            <input type="hidden" name="idcotizacion" id="idcotizacion" value="<?php echo $idcot != 0 ? $idcot : ""; ?>" />
</div>