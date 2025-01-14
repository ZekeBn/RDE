<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_compras.php");
require_once("../includes/funciones_stock.php");
require_once("../modelos/factura.php");
function guardar_diccionario($clave, &$diccionario, $valor)
{
    if (array_key_exists($clave, $diccionario)) {
        $diccionario[$clave][] = $valor;
    } else {
        $diccionario[$clave] = [$valor];
    }
}
// poralgun motivo no pude meter esta funcion en un script aparte para poder llamarlo de otros lugares, queda pendiente de
// momento lo uso de esta forma


/////////////////////////////////Carga de facura
$idcompra = intval($_GET['id']);

$buscar = "Select usa_cot_despacho from compras where idcompra = $idcompra";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_cot_despacho_factura = $rs->fields['usa_cot_despacho'];

$t2 = 0;
$valor_subtotal_gastos_compra = 0;
$consulta = "
	select compras.usa_cot_despacho,compras_detalles.* , compras_detalles.costo as costo, compras.idcot, insumos_lista.descripcion as descripcion, insumos_lista.idconcepto,
	(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
	(select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto,
	compras.obsfactura, compras.moneda, tipo_moneda.descripcion as nombre_moneda,compras.facturacompra as fac_compras,
	compras.total, compras.iva10,compras.iva5,facturas_proveedores_det_impuesto.monto_col,facturas_proveedores_det_impuesto.gravadoml,facturas_proveedores_det_impuesto.ivaml
	from compras_detalles 
	inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
	INNER JOIN compras on compras_detalles.idcompra = compras.idcompra 
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = (select  compras.moneda from compras where compras.idcompra = $idcompra  )
	left JOIN facturas_proveedores_det_impuesto on facturas_proveedores_det_impuesto.id_factura = compras.idcompra
	where 
	compras.idcompra_ref = $idcompra
	and compras.estado !=6
	order by insumos_lista.descripcion asc
	";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);
$facturas = [];

while (!$rs->EOF) {
    $idsub_compra = $rs->fields['idcompra'];
    $idreg = $rs->fields['idregs'];
    $usa_cot_despacho = $rs->fields['usa_cot_despacho'];
    $idinsumo = $rs->fields['codprod'];
    $producto = $rs->fields['descripcion'];
    $deposito = $rs->fields['deposito_por_defecto'];
    $concepto = $rs->fields['concepto'];
    $idconcepto = $rs->fields['idconcepto'];
    $cantidad = $rs->fields['cantidad'];
    $idmoneda = $rs->fields['moneda'];
    $idcot = $rs->fields['idcot'];
    $costo = $rs->fields['costo'];
    $subtotal = $rs->fields['subtotal'];
    $iva = $rs->fields['iva'];
    $lote = $rs->fields['lote'];
    $vencimiento = $rs->fields['vencimiento'];
    $comentarios = $rs->fields['obsfactura'];
    $nombre_moneda = $rs->fields['nombre_moneda'];
    $facturacompra = $rs->fields['fac_compras'];
    $total = $rs->fields['total'];
    $valor_total_gastos_compra += $total;
    $iva10 = $rs->fields['iva10'];
    $iva5 = $rs->fields['iva5'];
    $gravadoml = $rs->fields['gravadoml'];
    $ivaml = $rs->fields['ivaml'];
    $factura = new factura($idsub_compra, $idreg, $idinsumo, $producto, $deposito, $concepto, $idconcepto, $cantidad, $idmoneda, $costo, $subtotal, $iva, $lote, $vencimiento, $comentarios, $nombre_moneda, $usa_cot_despacho, $facturacompra, $total, $iva5, $iva10, $ivaml, $gravadoml, $idcot);





    $iva_valor = floatval($factura->iva5) != 0 ? floatval($factura->iva5) : floatval($factura->iva10);
    if ($factura->producto == "DESPACHO" || $factura->producto == "SERVICIO DE FLETE") {
        $iva_valor = $factura->ivaml;

    }
    $cotizacion_factura = 1;
    $cotizacion_despacho = 1;
    if (intval($factura->idcot) > 0 && $factura->usa_cot_despacho == "S") {

        $consulta = "
				select cotizaciones.cotizacion as cotizacion
				from compras
				LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
				where
				compras.idcompra = $idsub_compra
				";
        $rs_cot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $cotizacion_factura = floatval($rs_cot->fields['cotizacion']); //cotizacion de la factura

        $consulta = "
				SELECT despacho.cotizacion as cot_despacho FROM despacho WHERE idcompra = $idcompra 
				";
        $rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $cotizacion_despacho = floatval($rs_despa->fields['cot_despacho']); //cotizacion de despacho

    }
    //echo " -- ".$cotizacion_despacho. " -- ".$cotizacion_factura. "//" ;
    $iva_valor = ($iva_valor / $cotizacion_factura) * $cotizacion_despacho;
    $subtotal = ($subtotal / $cotizacion_factura) * $cotizacion_despacho;
    // echo json_encode($factura);
    // echo "total " .$total. " cot fa=".$cotizacion_factura." cot despa ".$cotizacion_despacho;
    $total_valor = ($total / $cotizacion_factura) * $cotizacion_despacho;
    //echo "  __  ".floatval($subtotal-$iva_valor)." __  " ;

    //cambiar por concepto de despacho y flete
    $t1 += $iva_valor;
    $t2 += $subtotal;
    $t3 += $factura->total;
    $valor_subtotal_gastos_compra += floatval($subtotal - $iva_valor);

    // guardar_diccionario($idmoneda, $facturas, $factura);

    if (array_key_exists($idmoneda, $facturas)) {
        if (array_key_exists($factura->idcompra, $facturas[$idmoneda])) {
            $facturas[$idmoneda][$factura->idcompra][] = $factura;

        } else {
            $facturas[$idmoneda][$factura->idcompra] = [$factura];

        }
    } else {
        $facturas[$idmoneda] = [];
        $facturas[$idmoneda][$factura->idcompra] = [$factura];
    }

    $rs->MoveNext();

}

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
    $cotiza_moneda = intval($rscotiza -> fields['cotiza']);


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

$impreso_el = ' Impreso el '.date("d/m/Y H:i:s");

//buscando moneda guarani
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE UPPER(tipo_moneda.descripcion) like \"%GUARANI%\" ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_guarani = $rs_guarani->fields["idtipo"];
$nombre_moneda_guarani = $rs_guarani->fields["nombre"];

//buscando moneda nacional
$consulta = "SELECT idtipo,descripcion FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["descripcion"];

if ($idcompra > 0) {
    $buscar = "Select * from empresas";
    $rse = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $empresachar = trim($rse->fields['empresa']);

    $consulta = "
    select tipo_moneda.banderita, compras.idtipo_origen ,compras.moneda as idmoneda, cotizaciones.cotizacion, tipo_moneda.descripcion as nom_moneda
    from compras
    LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
    LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras.moneda
    where
    compras.idcompra = $idcompra 
    ";
    $rs_cot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $idmoneda_select = $rs_cot->fields['idmoneda'];
    $cotizacion = $rs_cot->fields['cotizacion'];
    $nombre_moneda = $rs_cot->fields['nom_moneda'];
    $idtipo_origen = $rs_cot->fields['idtipo_origen'];
    $banderita = $rs_cot->fields['banderita'];

    $consulta = "
    SELECT despacho.cotizacion as cot_despacho FROM despacho WHERE idcompra = $idcompra 
    ";
    $rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $cot_despacho = $rs_despa->fields['cot_despacho'];

    $buscar = "
    Select compras.idtran, compras.usa_cot_despacho, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
    proveedores.nombre as proveedor, compras.facturacompra, compras.obsfactura,
    (select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
    compras.total as monto_factura, compras.ocnum, 
    (select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
    (select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
    registrado as registrado_el, compras.idcompra, compras.descripcion
    from gest_depositos_compras
    inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
    inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
    inner join compras on compras.idcompra = gest_depositos_compras.idcompra
    where 
     compras.estado <> 6
    and compras.idcompra = $idcompra
    order by gest_depositos_compras.fecha_compra desc 
    limit 1
    ";

    //echo $buscar;
    $rshd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $consulta = "
    select compras_detalles.*,    compras.total AS total,
	 compras.usa_cot_despacho , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, 
    (select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
    (select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto
    from compras_detalles 
    inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
    inner join compras on compras.idcompra = compras_detalles.idcompra
    where 
    compras_detalles.idcompra = $idcompra
    order by insumos_lista.descripcion asc
    ";
    $rs_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $buscar = "Select * from preferencias_compras limit 1";
    $rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);
    $usa_cot_despacho = $rs_detalles->fields['usa_cot_despacho'];

    $consulta = "SELECT SUM(subtotal) as gastos 
	from compras_detalles 
	where idcompra = $idcompra
	and compras_detalles.codprod not in (
	SELECT idinsumo FROM insumos_lista 
	WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%'\" 
	or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" )";
    $rs_gastos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $gastos_totales = 0;
    if ($usa_cot_despacho == "S") {
        $gastos_totales = (floatval($rs_gastos->fields['gastos']) / $cotizacion) * $cot_despacho;
    } else {
        $gastos_totales = $rs_gastos->fields['gastos'];
    }

} else {
    $error = 'Debe indicar n&uacute;mero de orden.';

}

$img = "../gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}

if ($id_moneda_nacional != $idtipo_moneda) {
    $cotizacion_mensaje = " Tipo Cambio: ".formatoMoneda($cotizacion_venta, 2, 'S');
}

if (!isset($moneda_nombre) || $moneda_nombre == null || $moneda_nombre == "") {
    $moneda_nombre = $nombre_moneda_nacional;
}


$valor_total_gastos_compra = 0;
//$valor_subtotal_gastos_compra=0;
$consulta = "
	select compras.usa_cot_despacho,compras_detalles.* , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, insumos_lista.idconcepto,
	(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
	(select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto,
	compras.obsfactura, compras.moneda, tipo_moneda.descripcion as nombre_moneda,compras.facturacompra as fac_compras,
	compras.total, compras.iva10,compras.idcot,compras.iva5,facturas_proveedores_det_impuesto.monto_col,facturas_proveedores_det_impuesto.gravadoml,facturas_proveedores_det_impuesto.ivaml
	from compras_detalles 
	inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
	INNER JOIN compras on compras_detalles.idcompra = compras.idcompra 
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = (select  compras.moneda from compras where compras.idcompra = $idcompra  )
	left JOIN facturas_proveedores_det_impuesto on facturas_proveedores_det_impuesto.id_factura = compras.idcompra
	where 
	compras.idcompra_ref = $idcompra
	and compras.estado !=6
	order by insumos_lista.descripcion asc
	";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);
$facturas = [];

while (!$rs->EOF) {
    $idsub_compra = $rs->fields['idcompra'];
    $idreg = $rs->fields['idregs'];
    $usa_cot_despacho = $rs->fields['usa_cot_despacho'];
    $idinsumo = $rs->fields['codprod'];
    $producto = $rs->fields['descripcion'];
    $deposito = $rs->fields['deposito_por_defecto'];
    $concepto = $rs->fields['concepto'];
    $idconcepto = $rs->fields['idconcepto'];
    $cantidad = $rs->fields['cantidad'];
    $idmoneda = $rs->fields['moneda'];
    $costo = $rs->fields['costo'];
    $idcot = $rs->fields['idcot'];
    $subtotal = $rs->fields['subtotal'];
    $iva = $rs->fields['iva'];
    $lote = $rs->fields['lote'];
    $vencimiento = $rs->fields['vencimiento'];
    $comentarios = $rs->fields['obsfactura'];
    $nombre_moneda = $rs->fields['nombre_moneda'];
    $facturacompra = $rs->fields['fac_compras'];
    $total = $rs->fields['total'];
    $valor_total_gastos_compra += $total;
    $iva10 = $rs->fields['iva10'];
    $iva5 = $rs->fields['iva5'];
    $gravadoml = $rs->fields['gravadoml'];
    $ivaml = $rs->fields['ivaml'];
    $factura = new factura($idsub_compra, $idreg, $idinsumo, $producto, $deposito, $concepto, $idconcepto, $cantidad, $idmoneda, $costo, $subtotal, $iva, $lote, $vencimiento, $comentarios, $nombre_moneda, $usa_cot_despacho, $facturacompra, $total, $iva5, $iva10, $ivaml, $gravadoml, $idcot);


    $iva_valor = floatval($factura->iva5) != 0 ? floatval($factura->iva5) : floatval($factura->iva10);
    $subtotal = floatval($factura->total) - $iva_valor;
    //cambiar por concepto de despacho y flete
    if ($factura->producto == "DESPACHO" || $factura->producto == "SERVICIO DE FLETE") {
        $subtotal = $factura->gravadoml;
    }
    $t1 += $iva_valor;
    $t2 += $subtotal;
    $t3 += $factura->total;
    //$valor_subtotal_gastos_compra+=$subtotal;
    // guardar_diccionario($idmoneda, $facturas, $factura);

    if (array_key_exists($idmoneda, $facturas)) {
        if (array_key_exists($factura->idcompra, $facturas[$idmoneda])) {
            $facturas[$idmoneda][$factura->idcompra][] = $factura;

        } else {
            $facturas[$idmoneda][$factura->idcompra] = [$factura];

        }
    } else {
        $facturas[$idmoneda] = [];
        $facturas[$idmoneda][$factura->idcompra] = [$factura];
    }


    $rs->MoveNext();

}

$css = "
    <style>
        @page *{
        margin-top: 0cm;
        margin-bottom: 0cm;
        margin-left: 0cm;
        margin-right: 0cm;
        }
        .fondopagina{
            border:0px solid #000000;
            width:1200px;
            height:1200px;
            margin-top:10px;
            margin-left:auto;margin-right:auto;
            
            background-image:url('gfx/presupuestos/01.jpg') no-repeat;
            background-size: cover;
        }
        .fondopagina_pagos{
            border:0px solid #FFFFFF;
            width:1200px;
            height:1200px;
            margin-top:10px;
            margin-left:auto;margin-right:auto;
            background-image:url('gfx/presupuestos/p02new.jpg') no-repeat;
            background-size: cover;
        }
        .contenedorppal{
            width:100%;
            height:50px;
            border:2px solid #b8860b;
            border-style: dotted;
        }
        .contenedorppalc{
            color:#b8860b;
            border:0.5px solid #b8860b;
            border-style: dotted;
            height:120px;
            width:650px;
            margin-left:auto;
            margin-right:auto;
        }
        .contenedorppaldire{
            color:#b8860b;
            border:0.5px solid #b8860b;
            border-style: dotted;
            height:40px;
            width:600px;
            margin-top:3%;
            margin-left:auto;
            margin-right:auto;
        }
        
        .contenedorderechamini{
            color:#b8860b;
            border:0px solid #b8860b;
            border-style: dotted;
            width:200px;
            height:60px;
            float:right;
            margin-top:5%;
            margin-right:4%;
        }
        .contenedorizqmini{
            color:#b8860b;
            #border:0.5px solid #b8860b;
            #border-style: dashed;
            width:130px;
            height:40px;
            float:left;
            margin-left:0%;
            margin-top:0%;
            
        }
        .button-1 {
          background-color: #EA4C89;
          border-radius: 8px;
          border-style: none;
          box-sizing: border-box;
          color: #FFFFFF;
          cursor: pointer;
          display: inline-block;
          font-family: \"Haas Grot Text R Web\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;
          font-size: 14px;
          font-weight: 500;
          height: 40px;
          line-height: 20px;
          list-style: none;
          margin: 0;
          outline: none;
          padding: 10px 16px;
          position: relative;
          text-align: center;
          text-decoration: none;
          transition: color 100ms;
          vertical-align: baseline;
          user-select: none;
          -webkit-user-select: none;
          touch-action: manipulation;
        }
    
        .button-1:hover,
        .button-1:focus {
          background-color: #F082AC;
        }
        .contenedorceqmini{
            #color:#b8860b;
            #border:0.5px solid #b8860b;
            #border-style: dashed;
            width:300px;
            height:40px;
            float:left;
            margin-top:0%;
            
        }
        .button-29 {
          align-items: center;
          appearance: none;
          background-image: radial-gradient(100% 100% at 100% 0, #5adaff 0, #5468ff 100%);
          border: 0;
          border-radius: 6px;
          box-shadow: rgba(45, 35, 66, .4) 0 2px 4px,rgba(45, 35, 66, .3) 0 7px 13px -3px,rgba(58, 65, 111, .5) 0 -3px 0 inset;
          box-sizing: border-box;
          color: white;
          cursor: pointer;
          display: inline-flex;
          font-family: \"JetBrains Mono\",monospace;
          height: 40px;
          justify-content: center;
          line-height: 1;
          list-style: none;
          overflow: hidden;
          padding-left: 16px;
          padding-right: 16px;
          position: relative;
          text-align: left;
          text-decoration: none;
          transition: box-shadow .15s,transform .15s;
          user-select: none;
          -webkit-user-select: none;
          touch-action: manipulation;
          white-space: nowrap;
          will-change: box-shadow,transform;
          font-size: 18px;
        }
    
        .button-29:focus {
          box-shadow: #3c4fe0 0 0 0 1.5px inset, rgba(45, 35, 66, .4) 0 2px 4px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
        }
    
        .button-29:hover {
          box-shadow: rgba(45, 35, 66, .4) 0 4px 8px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
          transform: translateY(-2px);
        }
    
        .button-29:active {
          box-shadow: #3c4fe0 0 3px 7px inset;
          transform: translateY(2px);
        }
        .contenedordermini{
            #color:#b8860b;
            #border:0.5px solid #b8860b;
            #border-style: dashed;
            width:202px;
            height:40px;
            float:left;
            margin-top:0.8%;
            
        }
        .colordorado{
             color:#b8860b;
             
        }
        .negrito{
            color:black;
        }
        table {
            border-collapse: collapse; width:100%;
            font-size:12px;
        }
         
        table,
        th,
        td {
            border: 0px solid black; align:center;
        }
        
        
    
        
        
         
        th,
        td {
            padding: 5px;
        }
    </style>
    ";

/*------------------------------------------RECEPCION DE VALORES--------------------------------*/

$proveedor = $rshd->fields['proveedor'];
$facturacompra = $rshd->fields['facturacompra'];
$tipocompra = $rshd->fields['tipocompra'];
$sucursal = $rshd->fields['sucursal'];
$fecha_compra = $rshd->fields['fecha_compra'] != "" ? date("d/m/Y", strtotime($rshd->fields['fecha_compra'])) : "";
$obsfactura = $rshd->fields['obsfactura'];

$tipo_cambio_factura_sin_formato = $cotizacion;
$tipo_cambio_despacho_sin_formato = $cot_despacho;
$tipo_cambio_factura = number_format($cotizacion, 2);
$tipo_cambio_despacho = number_format($cot_despacho, 2);

/*------------------------------------------RECEPCION DE VALORES--------------------------------*/

$html = "
$css
";

/*--------------------------CABECERA CON FILTROS----------------------------*/
$html .= "<div style='border:0px solid #000000;'>
			
			<div style=\"margin-top:0%;width:94%;  margin-left:auto;margin-right:auto;text-align:left;height:50px;\">
            
            
            <div align=\"center\">
           
          <div style=\"width:100%; border-bottom:1px solid #000000; height:40px;\">
         
			<table >
                <tr>
                    <td> <img src=\"$img\" height=\"30\" /></td>
                    <td align=\"left\">
                            $empresachar
                    </td>
                    <td align=\"center\">
                        Factura de compra N&deg;$idcompra
                    </td>
                </tr>

			</table>
            </div>
           
            


            <table width=\"700\" border=\"0\" style=\"font-size:8px;border-collapse: collapse;\">
                <tbody>
                        <tr>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" width=\"100px\"  >Fecha de Compra:</td>
                            <td  align=\"center\" width=\"100px\" >$fecha_compra</td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" width=\"90px\" >Factura N&deg;:</td>
                            <td align=\"center\" width=\"150px\" >$facturacompra</td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" width=\"90px\" >Tip Compra:</td>
                            <td align=\"center\" width=\"100px\">$tipocompra</td>
                        </tr>
                        <tr>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Proveedor:</td>
                            <td align=\"center\"  >$proveedor</td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Sucursal:</td>
                            <td align=\"center\"  >$sucursal</td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Comentario:</td>
                            <td align=\"center\"  >$obsfactura</td>
                        </tr>
                        <tr>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Tipo Cambio Factura:</td>
                            <td align=\"center\"  >$tipo_cambio_factura </td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Tipo Cambio Despacho:</td>
                            <td align=\"center\"  >$tipo_cambio_despacho </td>
                            <td align=\"center\" bgcolor=\"#F0EBEB\" >Moneda Factura</td>
                            <td align=\"center\"  >$nombre_moneda</td>
                        </tr>
                </tbody>
            </table>
        <hr />
        <br />
        <table width=\"799\"  style=\"border-collapse:collapse;border-bottom:1px solid #000;\">
                <thead>
                    <tr>
                        <td colspan=\"14\" height=\"29\" align=\"center\" bgcolor=\"#fff\"><strong><em>Montos en Moneda de Importacion</em></strong></td>
                        <td align=\"center\" bgcolor=\"#B4B4B4\" colspan=\"3\">Montos en Guarani</td>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <td width=\"85px\"  align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>C&oacute;digo</em></strong></td>
                        <td width=\"183\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Producto</em></strong></td>
                        <td width=\"110\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Deposito</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Unidades</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>FOB. Ant.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>FOB. Unit.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Dif. FOB.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Total FOB.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Gasto %.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Total Gasto.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>CIF Ant.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>CIF Uint.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Total CIF.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Dif. CIF.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Costo Uint.</em></strong></td>
                        <td width=\"79\" align=\"center\" bgcolor=\"#B4B4B4\"><strong><em>Costo total.</em></strong></td>
                    </tr>
                </thead>
				";

$to = 0;
// echo "hola".$html;exit;
$total_unidades_valor = 0;
$total_fob_valor = 0;
$total_gastos_valor = 0;
$total_cif_valor = 0;
$total_costo_valor = 0;
//echo $valor_subtotal_gastos_compra; exit;
$html .= "
                <tbody>";
while (!$rs_detalles->EOF) {
    // while (true){
    $idprod = $rs_detalles->fields['codprod'];
    $descripcion = $rs_detalles->fields['descripcion'];
    $deposito = $rs_detalles->fields['deposito_por_defecto'];
    $cantidad = $rs_detalles->fields['cantidad'];



    ///aclculo de costo promedio  para comparar cif actual y cif anterior

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
				and costo_productos.id_producto=$idprod  ORDER BY modificado_el DESC
				limit 2
				";
    $rs_producto_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $idcompra = $rs_producto_costo->fields['idcompra'];
    $cot_array = usa_cotizacion($idcompra);

    $cotizacion = 0;




    if ($cot_array['usa_cot_despacho'] == "S") {
        //verificamos la cotizacion de despacho por si la compra tenga asociada
        $cotizacion = floatval($cot_array['cot_despacho']);
    } else {

        $cotizacion = floatval($cot_array['cot_compra']);

    }

    $rs_detalles->MoveNext();
}

echo "cotizacion = $cotizacion";
