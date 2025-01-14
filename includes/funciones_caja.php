 <?php
function imprime_cierre_caja($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $nombreempresa;



    $idcaja = intval($parametros_array['idcaja']);
    $tipo_ticket = trim($parametros_array['tipo_ticket']); //V: visible C: ciego
    //$tipo_ticket='V';

    $buscar = "
    Select * ,
        (select nombre from sucursales where idsucu = caja_super.sucursal) as sucursal,
    (select usuario from usuarios where idusu = caja_super.cajero) as cajero
    from caja_super 
    where 
    estado_caja=3 
    and idcaja = $idcaja
    ";
    $rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $nombreempresa_centrado = corta_nombreempresa($nombreempresa);

    //datos de la caja
    $fecha_apertura = date("d/m/Y H:i:s", strtotime($rscaja->fields['fecha_apertura']));
    $fecha_cierre = date("d/m/Y H:i:s", strtotime($rscaja->fields['fecha_cierre']));
    $montoaper = $rscaja->fields['monto_apertura'];
    $montocierre = $rscaja->fields['monto_cierre'];
    $faltante = $rscaja->fields['faltante'];
    $sobrante = $rscaja->fields['sobrante'];

    $consulta = "
    SELECT sum(gest_pagos.total_cobrado) as total
    FROM gest_pagos
    where
    gest_pagos.estado <> 6
    and gest_pagos.idcaja = $idcaja
    and gest_pagos.tipomovdinero = 'E'
    ";
    $rsent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    SELECT sum(gest_pagos.total_cobrado)*-1 as total
    FROM gest_pagos
    where
    gest_pagos.estado <> 6
    and gest_pagos.idcaja = $idcaja
    and gest_pagos.tipomovdinero = 'S'
    ";
    $rssal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;
    $total_sistema = $rsent->fields['total'] + $rssal->fields['total'] + $rscaja->fields['monto_apertura'];

    $tickete = "
----------------------------------------
$nombreempresa_centrado
             CIERRE DE CAJA
----------------------------------------
FECHA APERTURA : $fecha_apertura
FECHA CIERRE   : $fecha_cierre
NRO CAJA       : $idcaja
SUCURSAL       : ".$rscaja->fields['sucursal']."
CAJERO         : ".$rscaja->fields['cajero']."";

    if ($tipo_ticket == 'V') {
        $tickete .= "
----------------------------------------
[            BALANCE DE CAJA           ]
----------------------------------------
TOTALES SEGUN SISTEMA:
MONTO APERTURA    : ".formatomoneda($montoaper)."
(+) INGRESOS      : ".formatomoneda($rsent->fields['total'])."
(-) EGRESOS       : ".formatomoneda($rssal->fields['total'])."
(=) TOTAL SISTEMA : ".formatomoneda($total_sistema)."";
    } else {
        $tickete .= "
MONTO APERTURA : ".formatomoneda($montoaper)."";
    }

    $tickete .= "
----------------------------------------
TOTALES DECLARADOS AL CIERRE:
";

    $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    while (!$rsarq->EOF) {
        /*
        lindo con 40 cols pero feo con menos
        $tickete.=agregaespacio(antixss(strtoupper($rsarq->fields['formapago'])),23,'N').": ".agregaespacio_tk(formatomoneda($rsarq->fields['total']),15,'der','N').$saltolinea;*/
        $tickete .= agregaespacio("(+) ".antixss(strtoupper($rsarq->fields['formapago'])), 23, 'N').": ".agregaespacio_tk(formatomoneda($rsarq->fields['total']), 15, 'izq', 'N').$saltolinea;
        $total_dec_acum += $rsarq->fields['total'];
        $rsarq->MoveNext();
    }


    if ($tipo_ticket == 'V') {

        $diferencia = $total_dec_acum - $total_sistema;
        if ($diferencia < 0) {
            $resultado = "FALTANTE";
            $color = "#F00";
        }
        if ($diferencia > 0) {
            $resultado = "SOBRANTE";
            $color = "#00F";
        }
        if ($diferencia == 0) {
            $resultado = "SIN DIFERENCIAS";
            $color = "#090";
        }

        $tickete .= "(=) TOTAL DECLARADO    : ".formatomoneda($total_dec_acum)."
----------------------------------------
TOTALES DECLARADO - SISTEMA
(+) TOTAL DECLARADO : ".formatomoneda($total_dec_acum)."
(-) TOTAL SISTEMA   : ".formatomoneda($total_sistema * -1)."
(=) DIFERENCIA      : ".formatomoneda($diferencia)."
(*) RESULTADO       : ".$resultado."
----------------------------------------";

        $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
and formas_pago.idforma = 1
group by formas_pago.descripcion
order by formas_pago.descripcion asc
limit 1
";
        $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $tickete .= "
[ EFECTIVO DECLARADO SIN MONTO APERTURA]
----------------------------------------
TOTAL EFECTIVO S/ APER : ".formatomoneda($rsarq->fields['total'] - $montoaper, 4, 'N')."
----------------------------------------";


    } else { //if($tipo_ticket == 'V'){

        $tickete .= "(=) TOTAL DECLARADO    : ".formatomoneda($total_dec_acum)."
----------------------------------------";

    }

    $buscar = "Select valor,cantidad,subtotal,registrobill from caja_billetes
inner join gest_billetes
on gest_billetes.idbillete=caja_billetes.idbillete
where 
idcaja=$idcaja 
and caja_billetes.estado=1
order by valor asc";
    $rsbilletitos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tbilletes = $rsbilletitos->RecordCount();
    //echo $buscar;

    if ($tbilletes > 0) {
        $tg = 0;
        $add1 = '';
        while (!$rsbilletitos->EOF) {
            $valor = trim($rsbilletitos->fields['valor']);
            $cantidad = trim($rsbilletitos->fields['cantidad']);
            $subtotal = trim($rsbilletitos->fields['subtotal']);
            $tg = $tg + $subtotal;
            $add1 .= agregaespacio_tk($cantidad, 5, 'der', 'N').' | '
            .agregaespacio_tk(formatomoneda($valor), 12, 'der', 'N').' | '
            .agregaespacio_tk(formatomoneda($subtotal), 17, 'der', 'N')." \n";

            $rsbilletitos->MoveNext();
        }
    }




    if ($tipo_ticket == 'V') {
        //descuento x productos
        $consulta = "
Select count(ventas.idventa) as cantidad,  sum(ventas_detalles.descuento) as total
from ventas_detalles 
inner join ventas on ventas.idventa=ventas_detalles.idventa 
inner join productos on productos.idprod_serial=ventas_detalles.idprod
where 
ventas.estado <> 6
and ventas_detalles.descuento > 0
and ventas.idcaja=$idcaja
";
        $rsdescp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // descuentos x factura
        $consulta = "
select count(ventas.idventa) as cantidad, sum(descneto) as total
from ventas 
where
descneto > 0
and idcaja = $idcaja
and estado <> 6
";
        //echo $consulta;
        $rsdesctot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
inner join usuarios on ventas.anulado_por = usuarios.idusu
where
ventas.idcaja = $idcaja
and ventas.estado = 6
";
        $rsanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 2
";
        $rsvcred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 1
";
        $rsvcont = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
select count(idtmpventares_cab) as cantidad, sum(monto) as total
from tmp_ventares_cab
inner join usuarios on tmp_ventares_cab.anulado_por = usuarios.idusu
where
tmp_ventares_cab.estado = 6
and tmp_ventares_cab.anulado_idcaja = $idcaja
and tmp_ventares_cab.monto > 0
";
        //echo $consulta;
        $rspedborra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
select sum(cantidad) as cantidad, sum(subtotal) as total
from 
(
select sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares
inner join usuarios on tmp_ventares.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares.idproducto
where
tmp_ventares.borrado = 'S'
and tmp_ventares.borrado_mozo = 'S'
and tmp_ventares.borrado_mozo_idcaja = $idcaja

UNION

select  sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares_bak
inner join usuarios on tmp_ventares_bak.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares_bak.idproducto
where
tmp_ventares_bak.borrado = 'S'
and tmp_ventares_bak.borrado_mozo = 'S'
and tmp_ventares_bak.borrado_mozo_idcaja = $idcaja
) pedbor
";
        //echo $consulta;
        $rspedborraprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $tickete .= "
[       INFORMACIONES RELEVANTES       ]
----------------------------------------
DESCUENTO S/ PRODUCTOS    : ".formatomoneda($rsdescp->fields['total'])."
DESCUENTO S/ FACTURAS    : ".formatomoneda($rsdesctot->fields['total'])."
VENTAS ANULADAS         : ".formatomoneda($rsanul->fields['total'])."
VENTAS A CREDITO        : ".formatomoneda($rsvcred->fields['total'])."
VENTAS AL CONTADO       : ".formatomoneda($rsvcont->fields['total'])."
VENTAS TOTALES          : ".formatomoneda($rsvcont->fields['total'] + $rsvcred->fields['total'])."
PEDIDOS BORRADOS        : ".formatomoneda($rspedborra->fields['total'])."
PROD BORRADO EN PEDIDOS    : ".formatomoneda($rspedborraprod->fields['total'])."
----------------------------------------";


        $consulta = "
SELECT 
caja_gestion_mov_tipos.tipo_movimiento, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    count(gest_pagos.idpago)*-1
ELSE
    count(gest_pagos.idpago)
END as cantidad, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    sum(gest_pagos.total_cobrado)*-1
ELSE
    sum(gest_pagos.total_cobrado)
END as total

FROM `gest_pagos`
INNER JOIN caja_gestion_mov_tipos on caja_gestion_mov_tipos.idtipocajamov = gest_pagos.idtipocajamov 
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
group by caja_gestion_mov_tipos.tipo_movimiento
order by caja_gestion_mov_tipos.tipo_movimiento asc
";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cantidad_acum = 0;
        $total_acum = 0;
        $tickete .= "
[        RESUMEN DE MOVIMIENTOS        ]
----------------------------------------
";
        while (!$rs->EOF) {
            $tickete .= agregaespacio(strtoupper($rs->fields['tipo_movimiento']), 23, 'N')." : ".formatomoneda($rs->fields['total']).$saltolinea;
            $rs->MoveNext();
        }
        $tickete .= "----------------------------------------";

        $consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'E'
and gest_pagos.idtipocajamov = 12
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rs->fields['formapago'] != '') {
            $tickete .= "
[   COBROS CANTINA POR FORMA DE PAGO   ]
----------------------------------------
";
            while (!$rs->EOF) {
                $tickete .= agregaespacio(strtoupper($rs->fields['formapago']), 23, 'N')." : ".formatomoneda($rs->fields['total']).$saltolinea;
                $rs->MoveNext();
            }
            $tickete .= "----------------------------------------";
        }

        $consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'E'
and gest_pagos.idtipocajamov = 1
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rs->fields['formapago'] != '') {
            $tickete .= "
[       VENTAS POR FORMA DE PAGO       ]
----------------------------------------
";
            while (!$rs->EOF) {
                $tickete .= agregaespacio(strtoupper($rs->fields['formapago']), 23, 'N')." : ".formatomoneda($rs->fields['total']).$saltolinea;
                $rs->MoveNext();
            }
            $tickete .= "----------------------------------------";
        } // if($rs->fields['formapago'] != ''){

    } // if($tipo_ticket == 'V'){


    $tickete .= "
[        ARQUEO DE BILLETES            ]
CANT  | VALOR        | SUBTOTAL             
----------------------------------------
$add1----------------------------------------
";

    $consulta = "
Select descripcion as billete,cantidad,subtotal as total,sermone 
from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where 
idcaja=$idcaja 
and caja_moneda_extra.estado=1
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (trim($rs->fields['billete']) != '') {

        $tickete .= "[      ARQUEO MONEDA EXTRANGERA        ]
MONEDA     | CANT    | SUBTOTAL             
----------------------------------------
";
        while (!$rs->EOF) {

            $tickete .= agregaespacio_tk(strtoupper($rs->fields['billete']), 10, 'izq', 'N')." | "
            .agregaespacio_tk(formatomoneda($rs->fields['cantidad']), 7, 'der', 'N').' | '
            .agregaespacio_tk(formatomoneda($rs->fields['total']), 17, 'der', 'N').$saltolinea;
            $rs->MoveNext();
        }
        $tickete .= "----------------------------------------
";
    }


    $res = [
        'ticket' => $tickete
    ];

    return $res;

}

/*
$idcaja=intval($_GET['idcaja']);
$whereadd="";
if($idcaja > 0){
    $whereadd=" and idcaja = $idcaja ";
}



// validar que la caja pertenece al usuario
$buscar="
Select *
from caja_super
where
estado_caja=3
and cajero=$idusu
$whereadd
order by fecha_cierre desc
limit 1
";
$rscaj=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$idcaja=intval($rscaj->fields['idcaja']);
if($idcaja == 0){
    echo "La caja que intentas imprimir no existe o no pertenece a tu usuario.";
    exit;
}

function imprime_cierre_caja_old($parametros_array){
    global $saltolinea;
    global $conexion;
    global $nombreempresa;
    global $ahora;

    $idempresa=1;

    // centrar nombre de empresa
    $nombreempresa_centrado=corta_nombreempresa($nombreempresa);


    $idcaja=$parametros_array['idcaja'];
    $tipotk=$parametros_array['tipotk'];

    $buscar="
    select estado_caja,fecha,monto_apertura, monto_cierre, faltante, sobrante, fecha_apertura, fecha_cierre,
    (select nombre from sucursales where idsucu = caja_super.sucursal) as sucursal,
    (select usuario from usuarios where idusu = caja_super.cajero) as cajero
    from caja_super
    where
    idcaja=$idcaja
    limit 1
    ";
    $rscaja=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $faltante=$rscaja->fields['faltante'];
    $sobrante=$rscaja->fields['sobrante'];
    $fecha_apertura=date("d/m/Y H:i:s",strtotime($rscaja->fields['fecha_apertura']));
    $fecha_cierre=date("d/m/Y H:i:s",strtotime($rscaja->fields['fecha_cierre']));




    //Reposiciones de Dinero (desde el tesorero al cajero
    $buscar="Select  sum(monto_recibido) as recibe from caja_reposiciones where idcaja=$idcaja  and estado=1";
    $rsrepo=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $trepo=floatval($rsrepo->fields['recibe']);

    //Pagos por caja
    $consulta="
    select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6),0)) as totalp
    from cuentas_empresa_pagos
    inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
    where
    cuentas_empresa_pagos.idcaja = $idcaja
    and cuentas_empresa_pagos.idempresa = $idempresa
    and cuentas_empresa_pagos.estado <> 6
    ORDER BY cuentas_empresa_pagos.fecha_pago asc
    ";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $tpagos=floatval($rsv->fields['totalp']);



    //Retiros(entrega de plata)desde el cajero al supervisor
    $buscar="Select
    count(*) as cantidad,sum(monto_retirado) as tretira
    from caja_retiros
    where
    idcaja=$idcaja
     and estado=1";
    $rsretiros=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $tretiros=intval($rsretiros->fields['cantidad']);
    $tretirosgs=intval($rsretiros->fields['tretira']);

        $tickete="
----------------------------------------
$nombreempresa_centrado
             CIERRE DE CAJA
FECHA APERTURA  : $fecha_apertura
FECHA CIERRE    : $fecha_cierre
NRO CAJA        : $idcaja
SUCURSAL        : ".$rscaja->fields['sucursal']."
CAJERO          : ".$rscaja->fields['cajero']."
----------------------------------------
MONTO APERTURA      : ".agregaespacio_tk(formatomoneda($rscaja->fields['monto_apertura']),17,'der','N')."
MONTO CIERRE        : ".agregaespacio_tk(formatomoneda($rscaja->fields['monto_cierre']),17,'der','N')."
C. CHICA APERTURA   : ".agregaespacio_tk(formatomoneda($ape_ch),17,'der','N')."
C. CHICA CIERRE     : ".agregaespacio_tk(formatomoneda($caja_chica_cierre),17,'der','N')."
----------------------------------------
           INGRESOS TEORICOS
Monto Apertura      : ".agregaespacio_tk(formatomoneda($rscaja->fields['monto_apertura']),17,'der','N')."
Recep Valores       : ".agregaespacio_tk(formatomoneda($trepo),17,'der','N')."".$saltolinea.$saltolinea;

    $consulta="
    SELECT formas_pago.descripcion, gest_pagos_det.idformapago, sum(gest_pagos_det.monto_pago_det) as totalformapago
    FROM `gest_pagos`
    inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
    inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
    where
    gest_pagos.idventa > 0
    and gest_pagos.idcaja = $idcaja
    and gest_pagos.estado <> 6
    group by gest_pagos_det.idformapago
    order by formas_pago.descripcion asc
    ";
    $rsfpag=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $ventas_acum=0;
    while(!$rsfpag->EOF){
        $ventas_acum+=$rsfpag->fields['totalformapago'];
        $tickete.=agregaespacio_tk($rsfpag->fields['descripcion'],20,'izq','N').': '.agregaespacio_tk(formatomoneda($rsfpag->fields['totalformapago']),17,'der','N').$saltolinea;
    $rsfpag->MoveNext(); }

    $total_ingresos=$ventas_acum+$rscaja->fields['monto_apertura']+$trepo;
    $total_egresos=$tpagos+$tretirosgs;
    $total_teorico=$total_ingresos-$total_egresos;


    $tickete.="Total Ventas        : ".agregaespacio_tk(formatomoneda($ventas_acum),17,'der','N')."

Total Ingresos      : ".agregaespacio_tk(formatomoneda($total_ingresos),17,'der','N')."
----------------------------------------".$saltolinea;
    $tickete.="            EGRESOS TEORICOS
PAGOS POR CAJA      : ".agregaespacio_tk(formatomoneda($tpagos),17,'der','N')."
ENTREGA DE VALORES  : ".agregaespacio_tk(formatomoneda($tretirosgs),17,'der','N')."

Total Egresos       : ".agregaespacio_tk(formatomoneda($total_egresos),17,'der','N')."
----------------------------------------
              INFORMATIVO
Ventas a Credito    :
Pendientes Rend.    :
----------------------------------------";

    //total en monedas extranjeras pero convertidas a gs
    $buscar="select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja  and estado=1";
    $extra=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $textra=floatval($extra->fields['tmone']);



    //total en monedas arqueadas
    $buscar="select sum(subtotal) as total from caja_billetes where idcaja=$idcaja and estado=1";
    $tarqueo=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $tarquegs=intval($tarqueo->fields['total']);

    // total vouchers
    $consulta="
    select sum(total_vouchers) as totalvouchers
    from caja_vouchers
    where
    estado <> 6
    and idcaja = $idcaja
    ";
    $rsvo = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $totalvouchers=floatval($rsvo->fields['totalvouchers']);
    $totaldeclarado=$tarquegs+$textra+$totalvouchers;

    if ($sobrante < 0){
        $sobrante=0;
    }
    if ($faltante < 0){
        $faltante=0;
    }

$tickete.="
Tot Ingresos-Egresos: ".agregaespacio_tk(formatomoneda($total_teorico),17,'der','N')."
----------------------------------------
          TOTALES DECLARADOS
Moneda Nacional     : ".agregaespacio_tk(formatomoneda($tarquegs),17,'der','N')."
Moneda Extranjera   : ".agregaespacio_tk(formatomoneda($textra),17,'der','N')."
Total Vouchers      : ".agregaespacio_tk(formatomoneda($totalvouchers),17,'der','N')."

Total Declarado     : ".agregaespacio_tk(formatomoneda($totaldeclarado),17,'der','N')."
----------------------------------------
Faltante            : ".agregaespacio_tk(formatomoneda($faltante),17,'der','N')."
Sobrante            : ".agregaespacio_tk(formatomoneda($sobrante),17,'der','N')."
----------------------------------------";


$tickete.="
          ARQUEO DE BILLETES
CANT  | VALOR        | SUBTOTAL
----------------------------------------
";



$buscar="Select valor,cantidad,subtotal,registrobill from caja_billetes
inner join gest_billetes
on gest_billetes.idbillete=caja_billetes.idbillete
where
idcaja=$idcaja
and caja_billetes.estado=1
order by valor asc";
$rsbilletitos=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$tbilletes=$rsbilletitos->RecordCount();
if ($tbilletes > 0){
    $tg=0;
    $add1='';
    while (!$rsbilletitos->EOF){
        $valor=trim($rsbilletitos->fields['valor']);
        $cantidad=trim($rsbilletitos->fields['cantidad']);
        $subtotal=trim($rsbilletitos->fields['subtotal']);
        $tg=$tg+$subtotal;
        $add1.=agregaespacio_tk($cantidad,5,'der','N').' | '
        .agregaespacio_tk(formatomoneda($valor),12,'der','N').' | '
        .agregaespacio_tk(formatomoneda($subtotal),17,'der','N').$saltolinea;

    $rsbilletitos->MoveNext();    }
}

$add1=substr($add1,0,-2);
$tickete.=$add1;
    $tickete.="
----------------------------------------
Impreso el: $ahora
";


    $tickete.="";

    return $tickete;
}
*/

function caja_pdf($parametros_array)
{

    global $conexion;
    global $idusu;
    global $soporte;

    $idcaja = intval($parametros_array['idcaja']);
    $idempresa = 1;


    if ($idusu != 2 && $idusu != 3) {
        $whereadd2 = "
    and cajero <> 3
    and cajero <> 2
    ";
    }
    if ($soporte <> 1) {
        $whereadd2 .= "
    and cajero not in (select idusu from usuarios where soporte = 1)
    ";
    }
    $consulta = "
select * , (select usuario from usuarios where idusu = caja_super.cajero) as cajero_usu,
(select nombre from sucursales where idempresa = $idempresa and idsucu = caja_super.sucursal) as sucursal
from caja_super
where
estado_caja <> 6
and idcaja = $idcaja
$whereadd2
order by caja_super.estado_caja asc, fecha_apertura desc
$limit
";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rs->fields['idcaja']);
    $rendido = intval($rs->fields['rendido']);
    if ($idcaja == 0) {
        //header("location: informe_caja_new.php");
        echo "No existe la caja enviada.";
        exit;
    }



    $img = "gfx/empresas/emp_".$idempresa.".png";
    if (!file_exists($img)) {
        $img = "gfx/empresas/emp_0.png";
    }

    $html .= '<!DOCTYPE html>
<html lang="en">
  <head>
  
<style>
table{
    border:1px solid #000;
    border-collapse:collapse;
}
td{
    border:1px solid #000;
}
th{
    border:1px solid #000;
}
</style>
  </head>

  <body class="nav-md">
    <div class="container body" style="padding:10px;">
      <div class="main_container">


            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    
   
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<table width="100%" border="0" style="border:0px;">
  <tr>
    <td align="center" style="border:0px;"><img src="'.$img.'" height="80" alt="" /></td>
    <td  style="border:0px;"><h3>'.$nombreempresa.'</h3><br /><h3>Informe de Caja</h3></td>
  </tr>
</table>

                


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
            <tr align="center" valign="middle">
        
              <th>Idcaja</th>
              <th>Sucursal</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th>Estado</th>
              <th>Cajero</th>
              <th>Monto Apertura</th>
              <th>Monto al cierre</th>
              <th>Sobrante</th>
              <th>Faltante</th>
              


            </tr>
    </thead>    
    <tbody>';



    $estado = $rs->fields['estado_caja'];
    if ($estado == 1) {
        $estadocaja = "Abierta";
    } elseif ($estado == 3) {
        $estadocaja = "Cerrada";
    } else {
        $estadocaja = "Indeterminada";
    }

    $html .= '<tr align="center" valign="middle">
      
              <td>'.$rs->fields['idcaja'].'</td>
              <td align="left">'.$rs->fields['sucursal'].'</td>
              <td>'.date("d/m/Y H:i:s", strtotime($rs->fields['fecha_apertura'])).'</td>
              <td>';
    if ($rs->fields['fecha_cierre'] != '') {
        $html .= date("d/m/Y H:i:s", strtotime($rs->fields['fecha_cierre']));
    }
    $html .= '</td>
              <td>'.$estadocaja.'</td>
              <td>'.capitalizar($rs->fields['cajero_usu']).'</td>
              <td align="right">'.formatomoneda($rs->fields['monto_apertura']).'</td>
              <td>';
    if ($estado == 3) {
        $html .= formatomoneda($rs->fields['monto_cierre']);
    } else {
        $html .= "Caja Abierta";
    }
    $html .= '</td>
              <td align="right">';
    if ($estado == 3) {
        $html .= formatomoneda($rs->fields['sobrante']);
    } else {
        $html .= "Caja Abierta";
    }
    $html .= '</td>
              <td align="right" style="color:#FF0000;">';

    if ($estado == 3) {
        $html .= formatomoneda($rs->fields['faltante']);
    } else {
        $html .= "Caja Abierta";
    }

    $html .= '</td>
  
            </tr>

      </tbody>
    </table>
</div>
<br />

<strong>Balance de Caja:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Totales Sistema</th>

            <th align="center">Monto</th>

        </tr>
      </thead>
      <tbody>
        <tr>

            <td align="left">Monto Apertura</td>

            <td align="right">'.formatomoneda($rs->fields['monto_apertura']).'</td>
        </tr>
        <tr>';

    $consulta = "
SELECT sum(gest_pagos.total_cobrado) as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'E'
";
    $rsent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
SELECT sum(gest_pagos.total_cobrado)*-1 as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'S'
";
    $rssal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;
    $total_sistema = $rsent->fields['total'] + $rssal->fields['total'] + $rs->fields['monto_apertura'];

    $html .= '
            <td align="left">(+) Total Ingresos Sistema</td>

            <td align="right">'.formatomoneda($rsent->fields['total']).'</td>
        </tr>
        <tr>

            <td align="left">(-) Total Egresos Sistema</td>

            <td align="right">'.formatomoneda($rssal->fields['total']).'</td>
        </tr>
        <tr>

            <td align="left">(=) Total Sistema</td>

            <td align="right">'.formatomoneda($total_sistema).'</td>
        </tr>


      </tbody>';
    if ($estado == 3) {

        $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
        $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $html .= '
      <thead>
        <tr>
            <th align="left">Totales Declarados</th>

            <th align="center">Monto</th>

        </tr>
        </thead>
        <tbody>';
        while (!$rsarq->EOF) {
            $html .= '<tr>

            <td align="left">(+) '.antixss($rsarq->fields['formapago']).'</td>

            <td align="right">'.formatomoneda($rsarq->fields['total']).'</td>
        </tr>';

            $total_dec_acum += $rsarq->fields['total'];
            $rsarq->MoveNext();
        }

        $html .= '<tr>

            <td align="left">(=) Totales Declarados</td>
            <td align="right">'.formatomoneda($total_dec_acum).'</td>
        </tr>
        </tbody>
      <thead>
        <tr>
            <th align="left">Total Declarado - Total Sistema</th>

            <th align="center">Monto</th>

        </tr>
        </thead>
        <tbody>
        <tr>

            <td align="left">(+) Totales Declarado</td>
            <td align="right">'.formatomoneda($total_dec_acum).'</td>
        </tr>
        <tr>

            <td align="left">(-) Totales Sitema</td>
            <td align="right">'.formatomoneda($total_sistema * -1).'</td>
        </tr>

        </tbody>
        <tfoot>
        <tr>';


        $diferencia = $total_dec_acum - $total_sistema;
        if ($diferencia < 0) {
            $resultado = "FALTANTE";
            $color = "#F00";
        }
        if ($diferencia > 0) {
            $resultado = "SOBRANTE";
            $color = "#00F";
        }
        if ($diferencia == 0) {
            $resultado = "SIN DIFERENCIAS";
            $color = "#090";
        }

        $html .= '<td align="left"  style="color:'.$color.';">(=) Diferencia ('.$resultado.') </td>
            <td align="right" style="color:'.$color.';">'.formatomoneda($diferencia).'</td>
        </tr>
      </tfoot>';
    } //if($estado == 3){
    $html .= '
    </table>
</div>
<br />';

    if ($estado == 3) {

        $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
and formas_pago.idforma = 1
group by formas_pago.descripcion
order by formas_pago.descripcion asc
limit 1
";
        $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $html .= '
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>

                <th align="left">Efectivo Declarado</th>
                <th align="left">(-) Monto Apertura</th>
                <th align="left">(=) Efectivo Sin Apertura</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="right">'.formatomoneda($rsarq->fields['total'], 4, 'N').'</td>
                <td align="right">'.formatomoneda($rs->fields['monto_apertura'], 4, 'N').'</td>
                <td align="right">'.formatomoneda($rsarq->fields['total'] - $rs->fields['monto_apertura'], 4, 'N').'</td>
            </tr>
        </tbody>
   </table>
</div>
<br />';

    }

    $html .= '
<strong>Informaciones Relevantes:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Informacion</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>

        <tr>';


    //descuento x productos
    $consulta = "
Select count(ventas.idventa) as cantidad,  sum(ventas_detalles.descuento) as total
from ventas_detalles 
inner join ventas on ventas.idventa=ventas_detalles.idventa 
inner join productos on productos.idprod_serial=ventas_detalles.idprod
where 
ventas.estado <> 6
and ventas_detalles.descuento > 0
and ventas.idcaja=$idcaja
";
    $rsdescp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $html .= '
            <td align="left">Descuentos sobre Productos</td>
            <td align="center">'.formatomoneda($rsdescp->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsdescp->fields['total']).'</td>
        </tr>
        <tr>';


    // descuentos x factura
    $consulta = "
select count(ventas.idventa) as cantidad, sum(descneto) as total
from ventas 
where
descneto > 0
and idcaja = $idcaja
and estado <> 6
";
    //echo $consulta;
    $rsdesctot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
            <td align="left">Descuentos sobre Facturas</td>
            <td align="center">'.formatomoneda($rsdesctot->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsdesctot->fields['total']).'</td>
        </tr>
        <tr>';

    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
inner join usuarios on ventas.anulado_por = usuarios.idusu
where
ventas.idcaja = $idcaja
and ventas.estado = 6
";
    $rsanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
            <td align="left">Ventas Anuladas</td>
            <td align="center">'.formatomoneda($rsanul->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsanul->fields['total']).'</td>
        </tr>';

    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 2
";
    $rsvcred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
        <tr>
          <td align="left">Ventas a Credito</td>
            <td align="center">'.formatomoneda($rsvcred->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsvcred->fields['total']).'</td>
          </tr>
        <tr>';

    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 1
";
    $rsvcont = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
        <tr>
          <td align="left">Ventas al Contado</td>
            <td align="center">'.formatomoneda($rsvcont->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsvcont->fields['total']).'</td>
          </tr>
        <tr>';

    $html .= '
        <tr>
          <td align="left">Ventas Totales</td>
            <td align="center">'.formatomoneda($rsvcont->fields['cantidad'] + $rsvcred->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rsvcont->fields['total'] + $rsvcred->fields['total']).'</td>
          </tr>
        <tr>';



    $consulta = "
select count(idtmpventares_cab) as cantidad, sum(monto) as total
from tmp_ventares_cab
inner join usuarios on tmp_ventares_cab.anulado_por = usuarios.idusu
where
tmp_ventares_cab.estado = 6
and tmp_ventares_cab.anulado_idcaja = $idcaja
and tmp_ventares_cab.monto > 0
";
    //echo $consulta;
    $rspedborra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
            <td align="left">Pedidos Borrados</td>
            <td align="center">'.formatomoneda($rspedborra->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rspedborra->fields['total']).'</td>
        </tr>
        <tr>';

    $consulta = "
select sum(cantidad) as cantidad, sum(subtotal) as total
from 
(
select sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares
inner join usuarios on tmp_ventares.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares.idproducto
where
tmp_ventares.borrado = 'S'
and tmp_ventares.borrado_mozo = 'S'
and tmp_ventares.borrado_mozo_idcaja = $idcaja

UNION

select  sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares_bak
inner join usuarios on tmp_ventares_bak.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares_bak.idproducto
where
tmp_ventares_bak.borrado = 'S'
and tmp_ventares_bak.borrado_mozo = 'S'
and tmp_ventares_bak.borrado_mozo_idcaja = $idcaja
) pedbor
";
    //echo $consulta;
    $rspedborraprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $html .= '
            <td align="left">Productos Borrados de Pedidos Activos</td>
            <td align="center">'.formatomoneda($rspedborraprod->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rspedborraprod->fields['total']).'</td>
        </tr>

      </tbody>

    </table>
</div>
<br />



<br />';

    $consulta = "
SELECT 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    'SALIDA'
ELSE
    'ENTRADA'
END AS tipomovdinero, 
CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    count(gest_pagos.idpago)*-1
ELSE
    count(gest_pagos.idpago)
END as cantidad, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    sum(gest_pagos.total_cobrado)*-1
ELSE
    sum(gest_pagos.total_cobrado)
END as total

FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
group by gest_pagos.tipomovdinero
order by gest_pagos.tipomovdinero asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;


    $html .= '
<strong>Resumen por Entrada/Salida:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Entrada/Salida</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>';
    while (!$rs->EOF) {
        $html .= '<tr>


            <td align="left">'.$rs->fields['tipomovdinero'].'</td>
            <td align="center">'.formatomoneda($rs->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rs->fields['total']).'</td>
        </tr>';

        $cantidad_acum += $rs->fields['cantidad'];
        $total_acum += $rs->fields['total'];
        $rs->MoveNext();
    }

    $html .= '</tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center">'.formatomoneda($cantidad_acum).'</td>
            <td align="right">'.formatomoneda($total_acum).'</td>

        </tr>
      </tfoot>
    </table>
</div>
<br />
';

    $consulta = "
SELECT 
caja_gestion_mov_tipos.tipo_movimiento, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    count(gest_pagos.idpago)*-1
ELSE
    count(gest_pagos.idpago)
END as cantidad, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    sum(gest_pagos.total_cobrado)*-1
ELSE
    sum(gest_pagos.total_cobrado)
END as total

FROM `gest_pagos`
INNER JOIN caja_gestion_mov_tipos on caja_gestion_mov_tipos.idtipocajamov = gest_pagos.idtipocajamov 
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
group by caja_gestion_mov_tipos.tipo_movimiento
order by caja_gestion_mov_tipos.tipo_movimiento asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;


    $html .= '<strong>Resumen por tipos de movimiento:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Tipo Movimiento</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>';
    while (!$rs->EOF) {
        $html .= '<tr>

            <td align="left">'.strtoupper($rs->fields['tipo_movimiento']).'</td>
            <td align="center">'.formatomoneda($rs->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rs->fields['total']).'</td>
        </tr>';

        $cantidad_acum += $rs->fields['cantidad'];
        $total_acum += $rs->fields['total'];
        $rs->MoveNext();
    }
    $html .= '</tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center">'.formatomoneda($cantidad_acum).'</td>
            <td align="right">'.formatomoneda($total_acum).'</td>

        </tr>
      </tfoot>
    </table>
</div>
<br />';




    $consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'E'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;
    $html .= '<strong>Resumen por formas de pago (Entrantes):</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Forma de Pago</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>';
    while (!$rs->EOF) {
        $html .= '<tr>

            <td align="left">'.strtoupper($rs->fields['formapago']).'</td>
            <td align="center">'.formatomoneda($rs->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rs->fields['total']).'</td>
        </tr>';

        $cantidad_acum += $rs->fields['cantidad'];
        $total_acum += $rs->fields['total'];
        $rs->MoveNext();
    }
    $html .= '</tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center">'.formatomoneda($cantidad_acum).'</td>
            <td align="right">'.formatomoneda($total_acum).'</td>

        </tr>
      </tfoot>
    </table>
</div>
<br />';

    $consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet)*-1 as cantidad, sum(gest_pagos_det.monto_pago_det)*-1 as total

FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'S'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;

    $html .= '<strong>Resumen por formas de pago (Salientes):</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Forma de Pago</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>';
    while (!$rs->EOF) {
        $html .= '<tr>

            <td align="left">'.strtoupper($rs->fields['formapago']).'</td>
            <td align="center">'.formatomoneda($rs->fields['cantidad']).'</td>
            <td align="right">'.formatomoneda($rs->fields['total']).'</td>
        </tr>';

        $cantidad_acum += $rs->fields['cantidad'];
        $total_acum += $rs->fields['total'];
        $rs->MoveNext();
    }
    $html .= '</tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center">'.formatomoneda($cantidad_acum).'</td>
            <td align="right">'.formatomoneda($total_acum).'</td>

        </tr>
      </tfoot>
    </table>
</div>


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            


  </body>
</html>';

    $res = [
        'html' => $html
    ];
    return $res;

}

?>
