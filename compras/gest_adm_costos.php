<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("../includes/rsusuario.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../cotizaciones/preferencias_cotizacion.php");
// poralgun motivo no pude meter esta funcion en un script aparte para poder llamarlo de otros lugares, queda pendiente de
// momento lo uso de esta forma
function buscar_cotizacion($parametros_array)
{

    global $conexion;
    global $ahora;
    // nombre del modulo al que pertenece este archivo
    $idmoneda = $parametros_array['idmoneda'];
    $ahoraSelec = $parametros_array['ahoraSelect'];
    //preferencias de cotizacion

    $preferencias_cotizacion = "SELECT * FROM preferencias_cotizacion";
    $rs_preferencias_cotizacion = $conexion->Execute($preferencias_cotizacion) or die(errorpg($conexion, $preferencias_cotizacion));

    $cotiza_dia_anterior = $rs_preferencias_cotizacion->fields["cotiza_dia_anterior"];
    $editar_fecha = $rs_preferencias_cotizacion->fields["editar_fecha"];
    /// fin de preferencias

    $res = null;


    $consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";

    $rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cotiza_moneda = intval($rscotiza->fields['cotiza']);


    if ($cotiza_moneda == 1) {

        if ($ahoraSelec != "") {
            $ahorad = date("Y-m-d", strtotime($ahoraSelec));
        } else {
            $ahorad = date("Y-m-d", strtotime($ahora));
        }



        $consulta = "SELECT 
                        cotizaciones.cotizacion,cotizaciones.idcot,cotizaciones.fecha
                    FROM 
                        cotizaciones
                    WHERE 
                        cotizaciones.estado = 1 
                        AND DATE(cotizaciones.fecha) = '$ahorad'
                        AND cotizaciones.tipo_moneda = $idmoneda
                        ORDER BY cotizaciones.fecha DESC
                        LIMIT 1
                ";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $fecha = $rsmax->fields['fecha'];
        $idcot = intval($rsmax->fields['idcot']);
        $cotizacion = $rsmax->fields['cotizacion'];
        if ($idcot > 0) {

            $res = [
                "success" => true,
                "fecha" => $fecha,
                "idcot" => $idcot,
                "cotiza" => true,
                "cotizacion" => $cotizacion
            ];
        } else {
            $formateada = date("d/m/Y", strtotime($ahorad));
            $res = [
                "success" => false,
                "cotiza" => false,
                "error" => "No hay cotizaciones para el d&iacute;a $formateada,favor cargue la cotizacion del d&iacute;a,. Favor cambielo <a target='_blank' href='..\cotizaciones\cotizaciones.php'>[ Aqui ]</a>",
            ];
        }
    } else {
        $res = [
            "success" => true,
            "cotiza" => false,
        ];
    }
    return  $res;
}

if (intval($idcompra) == 0) {
    $idcompra = $_POST['idcompra'];
}
$parametros_gastos = [
    "idcompra_ref" => $idcompra
];

relacionar_gastos($parametros_gastos);
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];

function isNullAddChar($palabra)
{
    if ($palabra == "NULL") {
        return "'NULL'";
    } else {
        return $palabra;
    }
}
$whereadd = "";
$consult = "";
$consulta = "SELECT DISTINCT codprod FROM compras_detalles WHERE idcompra = $idcompra";
$result = $conexion->GetCol($consulta) or die(errorpg($conexion, $consulta));
foreach ($result as $articulo) {
    $articulo = intval($articulo);

    if ($articulo != 0) {
        $consulta = "
		SELECT costo_productos.cantidad_stock,costo_productos.idcompra , 
		costo_productos.precio_costo, costo_productos.cantidad, 
		costo_productos.costo_cif, costo_productos.costo_promedio, 
		costo_productos.modificado_el,compras.moneda as idmoneda , 
		tipo_moneda.descripcion as moneda,compras.idcot, 
		tipo_origen.idtipo_origen,tipo_origen.tipo as origen, 
		compras.facturacompra as numero_factura,despacho.cotizacion as cotizacion,
		compras.fechacompra
		FROM costo_productos 
		INNER JOIN compras on compras.idcompra = costo_productos.idcompra
		LEFT JOIN tipo_moneda on compras.moneda = tipo_moneda.idtipo
		LEFT JOIN tipo_origen on compras.idtipo_origen = tipo_origen.idtipo_origen
		LEFT JOIN despacho on despacho.idcompra = compras.idcompra
		WHERE costo_productos.costo_promedio IS NOT NULL
		and costo_productos.id_producto=$articulo  ORDER BY modificado_el ASC
		";
        $rs_producto_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $stock_actual = intval($rs_producto_costo->fields['cantidad_stock']);
        $cantidad = intval($rs_producto_costo->fields['cantidad']);
        $precio_costo = intval($rs_producto_costo->fields['precio_costo']);
        $costo_cif = $rs_producto_costo->fields['costo_cif'];
        $costo_promedio = $rs_producto_costo->fields['costo_promedio'];

        $buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
        $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);
    }
}
$datos = relacionar_gastos($parametros_gastos);
$cantidad_deposito = $datos["cantidad_deposito"];
$respuesta = $datos["respuesta"];
$costo_promedio_calculado = $datos["costo_promedio"];
$costo_cif = $datos["costo_cif"];
$idcompra = $datos["idcompraxx"];
$c1 = $datos["c1"];
$c2 = $datos["c2"];
$c3 = $datos["c3"];
//Salida de información para este artículo
// echo "Codigo Articulo: " . $articulo . "<br>";
// echo "Cantidad: " . $cantidad . "<br>";
// echo "Stock Inicial:  " . $stock_actual . "<br>";
// echo "CostoCIF: " . $costo_cif . "<br>";
// echo "CostoPromedio acumulado: " . $costo_promedio . "<br>";
// echo "Cantidad en Deposito: " . $cantidad_deposito . "<br>";
// echo "Respuesta: " . $respuesta . "<br>";
// echo "Costo Promedio Calculado: " . $costo_promedio_calculado . "<br>";
// echo "Costo Promedio Sumado: " . $costo_promedio . "<br>";
// echo "c1: " . $c1 . "<br>";
// echo "c2: " . $c2 . "<br>";
// echo "c3: " . $c3 . "<br>";
// echo "<br>";

?>
<!--
<div class="table-responsive">
	    <table width="100%" class="table table-bordered jambo_table bulk_action">
			<thead style="border:none;">
				<tr>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="background:white;border:none;"></th>
					<th align="center" style="text-align:center;"colspan="3">USD</th>
					<th align="center" style="text-align:center;"colspan="3">GS</th>
				</tr>
			</thead>	
			<thead>
					<tr>
					<th align="center">Fecha</th>
					<th align="center">Factura</th>
					<th align="center">NRO</th>
					<th align="center">Moneda de Compra</th>
					<th align="center">Tipo Cambio</th>
					<th align="center">Stock Actual</th>
					<th align="center">Cantidad Compra</th>
					<th align="center">Costo FOB</th>
					<th align="center">Costo CIF</th>
					<th align="center">Costo Promedio</th>
					<th align="center">Costo FOB</th>
					<th align="center">Costo CIF</th>
					<th align="center">Costo Promedio</th>		
				</tr>
			</thead>
			<tbody>
			<tbody>
-->
<?php while (!$rs_producto_costo->EOF) {

    // Obtener los datos necesarios para imprimir
    $idcompra = $rs_producto_costo->fields['idcompra'];
    $cot_array = usa_cotizacion($idcompra);
    $varprint = formatomoneda($rs_producto_costo->fields['precio_costo']);
    //echo $varprint;

    // Calcular la cotización adecuada
    $cotizacion = null;
    if ($cot_array['usa_cot_despacho'] == "S") {
        $cotizacion = floatval($cot_array['cot_despacho']);
    } else {
        $cotizacion = floatval($cot_array['cot_compra']);
    }
    if ($cotizacion == 0) {
        // Si la cotización es cero, realizar consultas adicionales para obtenerla
        $fechacompra = ($rs_producto_costo->fields['fechacompra']);
        $consulta = "SELECT idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"DOLAR\" ";
        $rs_moneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idmoneda_orden = $rs_moneda->fields['idtipo'];

        $ahoraSelec = "";
        if ($cotiza_dia_anterior == "S") {
            $ahoraSelec = date("Y-m-d", strtotime($fechacompra . " -1 day"));
        } else {
            $ahoraSelec = date("Y-m-d", strtotime($fechacompra));
        }
        $cotizacion = 1;
        $parametros_array = [
            "idmoneda" => $idmoneda_orden,
            "ahoraSelect" => $ahoraSelect
        ];
        $res = buscar_cotizacion($parametros_array);
        if ($res['success'] == true && $res['cotiza'] == true) {
            $cotizacion = $res['cotizacion'];
        }
    }

    // Imprimir los valores del registro actual
    ?>
    <!--
        <tr>
            <td align="center"><?php echo antixss($rs_producto_costo->fields['modificado_el']); ?></td>
            <td align="center"><?php echo antixss($rs_producto_costo->fields['origen']); ?></td>
            <td align="center"><?php echo antixss($rs_producto_costo->fields['numero_factura']); ?></td>
            <td align="center"><?php echo antixss($rs_producto_costo->fields['moneda']); ?></td>
            <td align="center"><?php echo antixss($cotizacion) ?></td>
            <td align="center"><?php echo number_format(antixss($stock_actual), 0, '.', ''); ?></td>
			<td align="center"><?php echo number_format(antixss($cantidad), 0, '', '') ?></td>
            <td align="center"><?php echo formatomoneda($precio_costo / floatval($cotizacion), 4, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($costo_cif / floatval($cotizacion), 4, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($costo_promedio / floatval($cotizacion), 4, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($precio_costo, 2, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($costo_cif, 2, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($costo_promedio, 2, 'N'); ?></td>
        </tr> 
        -->
<?php
        // Mover al siguiente registro
        $rs_producto_costo->MoveNext();
}

?>
</tbody>

</table>
</div>

<a type="button" href="javascript:void(0)" class="btn btn-default boton_reporte" onclick="generar_reporte_compra()"><span class="fa fa-print"></span> Generar Reporte</a>
<a type="button" href="javascript:void(0)" class="btn btn-default boton_reporte" onclick="generar_reporte_costo()"><span class="fa fa-print"></span> Reporte Costo y Venta</a>
