<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once('./simple_html_dom.php');

require_once("./preferencias_cotizacion.php");
global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;

$set = intval($_POST['SET']);
if ($set == 1) {
    function reemplazar_coma($valor)
    {
        $cadenaConComa = "$valor";
        $cadenaConPunto = str_replace('.', '', $cadenaConComa);
        $cadenaConPunto = str_replace(',', '.', $cadenaConPunto);
        $valorFlotante = floatval($cadenaConPunto);
        return $valorFlotante;
    }

    //////////////


    $cotizacion = null;
    $errores = "";
    $meses = [
        1 => "Enero",
        2 => "Febrero",
        3 => "Marzo",
        4 => "Abril",
        5 => "Mayo",
        6 => "Junio",
        7 => "Julio",
        8 => "Agosto",
        9 => "Septiembre",
        10 => "Octubre",
        11 => "Noviembre",
        12 => "Diciembre"
    ];

    // Obtener el número del mes actual
    $hoy = new DateTime();
    $ayer = $hoy->modify('-1 day');



    $numeroMes = $ayer->format('n');
    $dia_ayer = $ayer->format('d');

    $anho = $ayer->format('Y');

    // Obtener el nombre del mes en español
    $nombreMes = $meses[$numeroMes];
    // Establecer las opciones de cURL
    $url = "https://www.set.gov.py/web/portal-institucional/cotizaciones";

    $dom = file_get_html($url);
    $dia = null;
    $fecha = null;
    $cotizacion = null;
    $cotizacion_compra = null;

    foreach ($dom->find('h4') as $col) {
        if (strpos(($col->plaintext), "del mes de $nombreMes $anho")) {
            foreach (($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr")) as $td) {
                if ($td->find('td', 0)->plaintext == $dia_ayer) {

                    $dia = $td->find('td', 0)->plaintext;
                    $fecha = $anho."-".$numeroMes."-".$dia_ayer;
                    $fecha = $ayer->format('Y-m-d'); //H:i:s
                    $cotizacion = $td->find('td', 2)->plaintext;
                    $cotizacion_compra = $td->find('td', 1)->plaintext;
                    break;
                }
            }
        }
    }
    $cotizacion = reemplazar_coma($cotizacion);
    $cotizacion_compra = reemplazar_coma($cotizacion_compra);
    $estado = antisqlinyeccion(1, "int");
    $consulta = "SELECT tipo_moneda.idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"%DOLAR%\" ";
    $respuestas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_moneda = $respuestas->fields['idtipo'];
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;




    // validaciones basicas
    $valido = "S";
    $alerta = "N";
    $errores = "";

    if (!is_numeric($cotizacion)) {
        $valido = "N";
        $alerta = "S";
        $errores .= "Comuníquese con el soporte técnico, ya que set.gov.py puede estar fuera de servicio o alterado estructuralmente";
    }
    if (intval($cotizacion) == 0) {
        $valido = "N";
        $alerta = "S";
        // $errores.=" - El campo cotizacion no puede ser cero o nulo.<br />";
    }
    if (intval($tipo_moneda) == 0) {
        $valido = "N";
        $alerta = "S";
        // $errores.=" - El campo tipo_moneda no puede ser cero o nulo.<br />";
    }

    if (intval($fecha) == "") {
        $valido = "N";
        $alerta = "S";
        // $errores.=" - El campo fecha no puede ser nulo.<br />";
    } else {
        if (intval($tipo_moneda) != 0) {
            $consulta = "SELECT 
            count(*) as cotizaciones_datos
        FROM 
            cotizaciones
        WHERE 
            cotizaciones.estado = 1 
            AND DATE(cotizaciones.fecha) = '$fecha'
            AND cotizaciones.tipo_moneda = $tipo_moneda
            ORDER BY cotizaciones.fecha DESC
            LIMIT 1";

            $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $num_cot = intval($rsmax->fields['cotizaciones_datos']);
            if ($num_cot > 0) {
                $valido = "N";
                $fecha_format = date("d/m/Y", strtotime($fecha.""));
                // $errores.=" - Ya existe una cotizacion con la fecha $fecha_format.<br />";
            }
        }

    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
        insert into cotizaciones
        (cotizacion,compra, estado, fecha, tipo_moneda, registrado_por, registrado_el)
        values
        ($cotizacion, $cotizacion_compra, $estado, '$fecha', $tipo_moneda, $registrado_por, $registrado_el)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        exit;

    } else {
        if ($alerta == "S") {

            $errores = "Comuníquese con el soporte técnico, ya que set.gov.py puede estar fuera de servicio o alterado estructuralmente";

            $errores;
        }
    }

    ///////////////////
}
$consulta = "
	SELECT *, tipo_moneda.borrable, tipo_moneda.descripcion,
	(select usuario from usuarios where cotizaciones.registrado_por = usuarios.idusu) as registrado_por
	FROM cotizaciones
	inner join tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
	where
	cotizaciones.estado = 1
	and tipo_moneda.estado = 1
	order by cotizaciones.fecha desc
	limit 50
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





?>
<div class="table-responsive">
  <table width="90%" border="1" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr align="center" >
        <td><strong>Fecha/Hora</strong></td>
        <td><strong>Moneda Extranjera</strong></td>
        <td><strong>Cotizacion (Venta)</strong></td>
        <?php if ($usa_cot_compra == "S") { ?>
            <td><strong>Cotizacion (Compra)</strong></td>
            <?php } ?>
        <td><strong>Registrado_por</strong></td>
        <td><strong>Registrado_el</strong></td>
        <td><strong>Borrar</strong></td>
        </tr>
    </thead>
    <tbody>
    <?php while (!$rs->EOF) {  ?>
      <tr>
        <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
        <td align="center"><?php echo $rs->fields['descripcion']; ?></td>
        <td align="right"><?php echo formatomoneda($rs->fields['cotizacion'], 2, "S"); ?></td>
        <?php if ($usa_cot_compra == "S") { ?>
            <td align="right"><?php echo formatomoneda($rs->fields['compra'], 2, "S"); ?></td>
        <?php } ?>
        <td align="center"><?php echo $rs->fields['registrado_por']; ?></td>
        <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el'])); ?></td>
        <td align="center"><strong><a href="cotizaciones_del.php?id=<?php echo $rs->fields['idcot']; ?>">[Borrar]</a></strong></td>
        </tr>
  <?php $rs->MoveNext();
    } ?>
    </tbody>
  </table>
</div>