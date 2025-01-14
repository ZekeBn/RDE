 <?php
/*-------------------------------------------

12/07/2024: Usa Tarjetas delivery

------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");


$consulta = "SELECT usa_tarjetadelivery,muestra_resumen_cobros,muestra_tarjetas_rendir,max_items_resumen
FROM preferencias_caja limit 1 ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$usa_tarjeta_delivery = trim($rsprefcaj->fields['usa_tarjetadelivery']);

if (intval($idimpresoratk) > 0) {
    $consulta = "SELECT * FROM impresoratk where idimpresoratk = $idimpresoratk and idsucursal = $idsucursal and borrado = 'N'";
    $rsimp1 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $usa_fecha_impreso = trim($rsimp1->fields['muestra_fecha_impre']);
}
if (intval($idatcdet) > 0) {
    $whereadd_atcdet = "
    and tmp_ventares.idatcdet = $idatcdet
    ";
    $consulta = "
    select nombre_atc, idatcdet
    from mesas_atc_det
    where
    idatcdet = $idatcdet
    and estado = 1
    ";
    $rssub = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_subcuenta = $rssub->fields['nombre_atc'];
    $idatcdet = intval($rssub->fields['idatcdet']);
    if ($idatcdet == 0) {
        echo "Sub cuenta inexistente o fue borrada.";
        exit;
    }
}

// impresores: caja, cocina, reimpresion mesa, reimpresion ticket global
/*
PARAMETROS DE ENTRADA
$impresor_tip="CAJ";
$impresor_tip="COC";
$impresor_tip="REI";
$impresor_tip="MES";
$redir_impr="impresor_ticket_caja.php";
$consolida="S";
$idmesa=0;
$idped=0;
*/
//Preferencias
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rprf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$datos_fac_preticket = $rprf->fields['datos_fac_preticket'];
$propina_preticket = $rprf->fields['propina_preticket'];

// conversores
$idmesa = intval($idmesa); // si no llega pone 0 para validar despues
$idped = intval($idped);
//echo $datos_fiscal;exit;
if ($datos_fiscal != 'S') {
    $datos_fiscal = "N";
}
if ($idatc > 0) {
    $whereadd_atc = " and idatc = $idatc ";
}

// validar que lleguen variables del archivo padre del require
if ($impresor_tip == "") {
    echo "No inidico el tipo de impresion.";
    exit;
}
if ($redir_impr == "") {
    echo "No inidico la redireccion.";
    exit;
}
if ($consolida == "") {
    //echo "No inidico si la impresion es consolidada.";
    //exit;
    $consolida = "N";
}
if ($impresor_tip == "MES") {
    if ($idmesa == 0) {
        echo "No indico el numero de mesa.";
        exit;
    }
}
if ($impresor_tip == "REI") {
    if ($idped == 0) {
        echo "No indico el codigo de pedido.";
        exit;
    }
}

// forzar impresor a mesa
if ($idmesa > 0) {
    $impresor_tip = "MES";
}

// filtros por tipo de impresor
$filtroadd = "";
$leyenda = "";
// cocina
if ($impresor_tip == 'COC') {
    $leyenda = "$cabecera_pagina";
    $filtroadd = "
    and productos.idprod_serial in (select idproducto from producto_impresora where idimpresora = $idimpresoratk)
    and tmp_ventares.impreso_coc = 'N'
    ";
    /*$filtroaddcab="
    and impreso = 'N'
    ";*/
    $selectadd = "";
}
// caja
if ($impresor_tip == 'CAJ') {
    $leyenda = "$cabecera_pagina";
    /*$filtroadd="
    and tmp_ventares.usuario = $idusu
    ";*/
    /*$filtroaddcab="
    and impreso = 'N'
    ";*/
    $idventa = intval($_GET['v']);
    if ($idventa > 0) {
        $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
    }
    $selectadd = "";
    // si se envio id de pedido
    /*if($idped > 0){
        $idtmpventares_cab=$idped;
        $filtroadd.="
        and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
        ";
    }*/
}
// mesa
if ($impresor_tip == 'MES') {
    $leyenda = "RESUMEN DE MESA - SIN VALOR FISCAL";
    $filtroadd = "
    
    ";
    $filtroaddcab = "

    ";
    $selectadd = " sum(monto) as monto, ";
    $idventa = intval($_GET['v']);
    if ($idventa > 0) {
        $filtroadd2 = "
        and registrado = 'S'
        and idmesa=$idmesa
        ";
        $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
    } else {
        $filtroadd2 = "
        and registrado = 'N'
        and idmesa=$idmesa
        ";
    }
}
// reimpresion
if ($impresor_tip == 'REI') {
    $leyenda = "$cabecera_pagina - REIMPRESO";
    $idtmpventares_cab = $idped;
    $filtroadd = "
    and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
    ";
    $filtroaddcab = "";
    $selectadd = "";
}


// centrar nombre empresa y leyenda
$saltolinea = "
";
$leyenda = corta_nombreempresa(trim($leyenda), 40);
if ($usa_nombreemp == "S") {
    $nombreempresa_centrado = $saltolinea.corta_nombreempresa($nombreempresa);
} else {
    $nombreempresa_centrado = "";
}
if ($muestra_suc == 'S') {
    $sucursaltxt = $saltolinea."SUCURSAL: ".texto_tk(trim($nombresucursal), 30);
}
$enfasis = "";
if ($usa_enfasis == 'S') {
    $enfasis = "<BIG>";
}
/*
// colocar en el ladocliente

    $texto_ar=explode($saltolinea,$texto);
    // recorre cada linea
    foreach($texto_ar as $texto){
        // agranda letra
        if(substr($texto,0,5) == "<BIG>"){
            $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT, Printer::MODE_DOUBLE_WIDTH);
        // letra normal
        }else{
            $printer -> selectPrintMode();
        }
        //imprimir texto
        $printer -> text($texto);

    }
*/
// correccion si hay atc huerfanos
if ($idatc > 0 && $idmesa > 0) {
    $consulta = "
    select idtmpventares_cab, idatc
    from tmp_ventares_cab
    where
    idsucursal = $idsucursal
    and finalizado = 'S'
    and registrado = 'N'
    and estado = 1
    and idmesa=$idmesa
    and idatc <> $idatc
    and idatc is not null
    limit 1
    ";
    $rsexatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idatc_old = intval($rsexatc->fields['idatc']);
    if ($idatc_old > 0) {
        $consulta = "
        update tmp_ventares_cab
        set
        estado = 6
        where
        idsucursal = $idsucursal
        and finalizado = 'S'
        and registrado = 'N'
        and estado = 1
        and idmesa=$idmesa
        and idatc = $idatc_old
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

}

// si es mesa
if ($idatc > 0) {
    // si no envio ninguna subcuenta
    /*if($idatcdet == 0){
        // busca si tiene subcuentas activas
        $consulta="
        select *,
        (select usuario from usuarios where mesas_atc_det.registrado_por = usuarios.idusu) as registrado_por_txt,
        (
        Select
        sum(tmp_ventares.subtotal) as subtotal
        from
        tmp_ventares
        inner join productos on productos.idprod=tmp_ventares.idproducto
        where
        tmp_ventares.borrado = 'N'
        and tmp_ventares.idatcdet = mesas_atc_det.idatcdet
        ) as monto,
        (
        Select
        sum(tmp_ventares.cantidad) as cantidad
        from
        tmp_ventares
        inner join productos on productos.idprod=tmp_ventares.idproducto
        where
        tmp_ventares.borrado = 'N'
        and tmp_ventares.idatcdet = mesas_atc_det.idatcdet
        ) as cantidad
        from mesas_atc_det
        where
         estado = 1
         and idatc = $idatc
        order by idatcdet asc
        union all

        select sin subcuenta asignada
        ";
        $rsatcdet=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        $mesa_m_det="";
        // si existe al menos 1 registro
        if(intval($rsatcdet->fields['idatcdet']) > 0){
            $mesa_m_det=$saltolinea."----------------------------------------".$saltolinea."SUB CUENTAS:".$saltolinea;
            while(!$rsatcdet->EOF){
                $monto_subcuenta=formatomoneda($rsatcdet->fields['monto']);
                $mesa_m_det.=$rsatcdet->fields['nombre_atc'].' (#'.intval($rsatcdet->fields['idatcdet']).")".': '.$monto_subcuenta.$saltolinea;
            $rsatcdet->MoveNext(); }
            $mesa_m_det=$saltolinea.trim($mesa_m_det);
        }
    }*/
}
/*
$consulta="
select *, $selectadd (select tipo_venta from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as vtacredito,
(select idadherente from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as idadherente,
(select numero_tarjeta from tarjeta_delivery where idtarjetadelivery=tmp_ventares_cab.idtarjetadelivery) as numero_tarjeta,
(select descneto from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as descuento,
(select razon_social from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as razon_social_ven,
(select ruc from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as ruc_ven,
(select canal_venta from canal_venta where idcanalventa  = tmp_ventares_cab.idcanalventa) as canal_venta
from tmp_ventares_cab
left join ventas on ventas.idventa = tmp_ventares_cab.idventa
where
idsucursal = $idsucursal
and finalizado = 'S'
and estado <> 6
$filtroaddcab
$filtroadd2
and tmp_ventares_cab.idtmpventares_cab in (
    select tmp_ventares.idtmpventares_cab
    from tmp_ventares
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where
    tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'S'
    and tmp_ventares.idsucursal = $idsucursal
    $filtroadd
    $whereadd_atcdet
    and idtmpventaresagregado is null
    ORDER BY descripcion asc
)
order by idtmpventares_cab asc
limit 1
";*/
$consulta = "
select *, 
$selectadd 
ventas_sub.tipo_venta AS vtacredito,
ventas_sub.idadherente,
ventas_sub.descneto AS descuento,
ventas_sub.razon_social AS razon_social_ven,
ventas_sub.ruc AS ruc_ven,
tarjeta.numero_tarjeta,
canal_venta.canal_venta
from tmp_ventares_cab
LEFT JOIN (
    SELECT 
        idventa, 
        tipo_venta, 
        idadherente, 
        descneto, 
        razon_social, 
        ruc
    FROM 
        ventas
    GROUP BY 
        idventa
) AS ventas_sub ON ventas_sub.idventa = tmp_ventares_cab.idventa
LEFT JOIN (
    SELECT 
        idtarjetadelivery, 
        numero_tarjeta
    FROM 
        tarjeta_delivery
) AS tarjeta ON tarjeta.idtarjetadelivery = tmp_ventares_cab.idtarjetadelivery
LEFT JOIN (
    SELECT 
        idcanalventa, 
        canal_venta
    FROM 
        canal_venta
) AS canal_venta ON canal_venta.idcanalventa = tmp_ventares_cab.idcanalventa
where
idsucursal = $idsucursal
and finalizado = 'S'
and estado <> 6
$filtroaddcab
$filtroadd2
and tmp_ventares_cab.idtmpventares_cab in (
    select tmp_ventares.idtmpventares_cab
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'S'
    and tmp_ventares.idsucursal = $idsucursal
    $filtroadd
    $whereadd_atcdet
    and idtmpventaresagregado is null
    ORDER BY descripcion asc
)
order by idtmpventares_cab asc
limit 1
";
//echo $consulta;
//exit;
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$vtaid = intval($rscab->fields['idventa']);
$id = intval($rscab->fields['idtmpventares_cab']);
$idadherente = intval($rscab->fields['idadherente']);
$descuento = intval($rscab->fields['descuento']);
$canal_venta = $rscab->fields['canal_venta'];
if ($descuento > 0) {
    $desctxt = $saltolinea."DESCUENTO: ".formatomoneda($descuento);
}

// sub filtros
if ($impresor_tip == 'COC') {
    $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
}
if ($impresor_tip == 'CAJ') {
    $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
}
if ($impresor_tip == 'MES') {
    if ($idventa > 0) {
        $subfiltroadd = "
        and tmp_ventares.idtmpventares_cab in (
                                                select idtmpventares_cab
                                                from tmp_ventares_cab
                                                where
                                                idsucursal = $idsucursal
                                                and finalizado = 'S'
                                                and registrado = 'S'
                                                and estado = 3
                                                and idmesa=$idmesa
                                                and idventa = $idventa
                                               )
        ";
    } else {
        $subfiltroadd = "
        and tmp_ventares.idtmpventares_cab in (
                                                select idtmpventares_cab
                                                from tmp_ventares_cab
                                                where
                                                idsucursal = $idsucursal
                                                and finalizado = 'S'
                                                and registrado = 'N'
                                                and estado = 1
                                                and idmesa=$idmesa
            
                                               )
        ";

    }
}
if ($impresor_tip == 'REI') {
    $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
}

// datos del adherente
if ($idadherente > 0) {
    $consulta = "SELECT * FROM adherentes where idadherente = $idadherente";
    $rsadh = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_adherente = $saltolinea."ADH: ".texto_tk(trim($rsadh->fields['nomape']), 35);
    $codadherente = "";
    if ($rprf->fields['imprime_cod_adherente'] == 'S') {
        $consulta = "SELECT * FROM clientes_codigos where idadherente = $idadherente";
        $rsadhcod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $codadherente = $saltolinea."COD ADH: ".texto_tk($rsadhcod->fields['us_cod'], 35);

        //$nombre_adherente=$nombre_adherente.'';
    }
}




// datos utiles
$fechapedido = date("d/m/Y H:i:s", strtotime($rscab->fields['fechahora']));
$razon_social = trim(texto_tk($rscab->fields['razon_social'], 28));
$ruc = texto_tk($rscab->fields['ruc'], 28);
if (intval($vtaid) > 0 or intval($idventa) > 0) {
    $razon_social = trim(texto_tk($rscab->fields['razon_social_ven'], 28));
    $ruc = texto_tk($rscab->fields['ruc_ven'], 28);
}

$datos_fiscales = "
Razon SOC.: $razon_social
RUC NRO.  : $ruc";
if ($datos_fiscal == 'N') {
    $datos_fiscales = "";
}
//echo $datos_fiscales;exit;
if ($muestra_nombre == 'S') {
    if ($codadherente == '') {
        $datos_fiscales = $datos_fiscales.$saltolinea."TIT: $razon_social";
    }
}

// si es venta a credito
$vtacredito = intval($rscab->fields['vtacredito']);
if ($vtacredito == 2) {
    $creditotxt = "
----------------------------------------
        *** VENTA A CREDITO ***   
      RECONOZCO Y PAGARE EL MONTO       
           DE ESTA OPERACION        


FIRMA:..................................
ACLARACION: $razon_social";

} else {
    $creditotxt = "";
}
// si no quiere leyenda para credito
if ($leyenda_credito == 'N') {
    $creditotxt = "";
}
// si no hay nada para imprimir recarga la pagina
if ($id == 0) {
    echo "<title>IMP $nombre_impresora</title>No queda nada pendiente por imprimir.";
    echo '<meta http-equiv="refresh" content="4;URL=\''.$redir_impr.'?imp='.$idimpresoratk.'\'" />';
    exit;
}



$consulta = "
select tmp_ventares.*, productos.descripcion, tmp_ventares.observacion as observacion_item, productos.combinado_leyenda,
(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo,
(select idserie from log_reimpresiones_comandas where idtemporal =  tmp_ventares.idventatmp order by idserie limit 1) as idreimpresion
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'S'
and tmp_ventares.idsucursal = $idsucursal
$filtroadd
$subfiltroadd
$whereadd_atcdet
and idtmpventaresagregado is null
ORDER BY descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id = intval($rs->fields['idtmpventares_cab']);
$idreimpresion = intval($rs->fields['idreimpresion']);


// si no hay nada para imprimir recarga la pagina
if ($id == 0) {
    echo "<title>IMP $nombre_impresora</title>No queda nada pendiente por imprimir.";
    echo '<meta http-equiv="refresh" content="4;URL=\''.$redir_impr.'?imp='.$idimpresoratk.'\'" />';
    exit;
}

if ($idreimpresion > 0) {
    $texto_rei = $saltolinea.$enfasis." >> REIMPRESION << ";
}

$ahora = date("d/m/Y H:i:s");



$buscar = "Select * from impresoratk where imp=$idimpresoratk";



// no consolidado
if ($consolida != 'S') {

    $texto = "
****************************************$nombreempresa_centrado
$leyenda $texto_rei
****************************************
".$enfasis."PED: $id | $txt_codvta: $vtaid
----------------------------------------
FECHA PED : $fechapedido";



    $texto .= $saltolinea."FECHA IMP : $ahora $datos_fiscales $nombre_adherente $codadherente";

    if ($canal_venta != '') {
        $texto .= $saltolinea.$enfasis."CANAL VENTA : ".$canal_venta;
    }

    //Traemos las preferencias de mesas
    $buscar = "Select usar_tipos_coccion from mesas_preferencias limit 1";
    $rsprefmesa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $usar_punto_coccion = trim($rsprefmesa->fields['usar_tipos_coccion']);

    $texto .= "
----------------------------------------
PRODUCTOS
----------------------------------------
";
    $texto = trim($texto);
    $descontado = 0;//esta variable aalmacenara los valores descontados en cada producto, solo para caracter INFORMATIVO en el tickete
    //NO EFECTUAR CALCULOS CON ESTA VARIABLE YA QUE LOS DESCUENTOS POR PRODUCTOS  ESTAN EN EL SUB TOTAL DE LA CUENTA
    while (!$rs->EOF) {
        $descontado = $descontado + floatval($rs->fields['descuento']);
        $idventatmp = $rs->fields['idventatmp'];
        if ($usar_punto_coccion == 'S') {
            $buscar = "Select descripcion,observaciones from tmpventares_coccion 
        inner join tmp_ventares on tmp_ventares.idventatmp=tmpventares_coccion.idtmpventares
        inner join tipos_cocciones on tipos_cocciones.idtipococ=tmpventares_coccion.idtipococc
        where tmpventares_coccion.idtmpventares=$idventatmp";
            $rsprcoc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo $buscar;exit;
        }
        $observacion_item = $rs->fields['observacion_item'];
        $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];
        //echo "a";exit;
        if ($rs->fields['tipo_plato'] != '') {
            $tipoplato = trim($rs->fields['tipo_plato'])." | ";
        }
        //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['cantidad'],4,'N'),6),6).'|'.texto_tk($tipoplato.$rs->fields['descripcion'],34);
        $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['cantidad'], 4, 'N'), 6), 6).'|'.$tipoplato.$rs->fields['descripcion'];
        if ($rsprcoc->fields['descripcion'] != '') {
            $texto .= $saltolinea.$enfasis."PC: ->".$rsprcoc->fields['descripcion'];
        }
        if ($usa_precio == 'S') {
            $texto .= $saltolinea."$enfasis  Gs.".texto_tk(formatomoneda($rs->fields['precio']), 30);
            if (floatval($rs->fields['descuento']) > 0) {
                $texto .= "| DESC: -".texto_tk(formatomoneda($rs->fields['descuento']), 30);
            }
        }
        $texto = trim($texto);
        // busca si es un producto combinado
        if ($rs->fields['combinado'] == 'S') {
            $prod_1 = $rs->fields['idprod_mitad1'];
            $prod_2 = $rs->fields['idprod_mitad2'];
            $consulta = "
        select *
        from productos
        where 
        (idprod_serial = $prod_1 or idprod_serial = $prod_2)
        order by descripcion asc
        ";
            $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            while (!$rspcom->EOF) {
                $texto .= $saltolinea."$enfasis » MITAD ".texto_tk_sincorte($rspcom->fields['descripcion'], 31);
                $rspcom->MoveNext();
            }

        }
        // busca si es un combo
        if ($rs->fields['combo'] == 'S') {

            if ($muestra_grupo_combo == 'S') {
                $consulta = "
            select combos_listas.nombre, productos.descripcion, count(*) as total
            from productos 
            inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
            inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
            where 
            tmp_combos_listas.idventatmp = $idventatmp
            group by combos_listas.nombre, productos.descripcion
            order by combos_listas.idlistacombo asc
            ";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
            select * 
            from tmp_combos_listas
            inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
            where
            tmp_combos_listas.idventatmp = $idventatmp
            ";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }


            while (!$rsgrupos->EOF) {
                if ($muestra_grupo_combo == 'S') {
                    $nombre_grupo = trim($rsgrupos->fields['nombre']).": ";
                }
                $texto .= $saltolinea."$enfasis » ".texto_tk_sincorte($nombre_grupo.$rsgrupos->fields['descripcion'], 31);
                $rsgrupos->MoveNext();
            }

        }
        // busca si es un combinado extendido
        if ($rs->fields['idtipoproducto'] == 4) {
            $consulta = "
        select count(productos.idprod_serial) as total, productos.descripcion, productos.idprod_serial
        from tmp_combinado_listas
        inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
        where
        tmp_combinado_listas.idventatmp = $idventatmp
        group by productos.idprod_serial 
        order by productos.descripcion asc
        ";
            //echo $consulta;
            $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $combinado_leyenda = trim($rs->fields['combinado_leyenda']);
            while (!$rsgrupos->EOF) {
                if ($combinado_leyenda != '') {
                    if ($rsgrupos->fields['total'] != 1) {
                        $leyenda = $combinado_leyenda.' x '.$rsgrupos->fields['total'];
                    } else {
                        $leyenda = $combinado_leyenda;
                    }
                } else {
                    $leyenda = $rsgrupos->fields['total'];
                }
                $texto .= $saltolinea."$enfasis   » ".$leyenda." ".texto_tk_sincorte($rsgrupos->fields['descripcion'], 25);
                $rsgrupos->MoveNext();
            }

        }

        // busca si tiene agregado
        $idvt = $rs->fields['idventatmp'];
        $consulta = "
    select tmp_ventares_agregado.*
    from tmp_ventares_agregado
    where 
    idventatmp = $idvt
    order by alias desc
    ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera agregados si tiene
        if (trim($rsag->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis > AGREGADOS:".$saltolinea;
            while (!$rsag->EOF) {
                $texto .= "$enfasis  + ".texto_tk_sincorte($rsag->fields['alias'], 36).$saltolinea;
                $texto .= "    Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                $rsag->MoveNext();
            }
        }
        $texto = trim($texto);

        // busca si tiene sacados
        $consulta = "
    select tmp_ventares_sacado.*
    from tmp_ventares_sacado
    where 
    idventatmp = $idvt
    order by alias desc
    ";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera sacados si tiene
        if (trim($rssac->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis > EXCLUIDOS:".$saltolinea;
            while (!$rssac->EOF) {
                $texto .= "$enfasis  - ".texto_tk_sincorte($rssac->fields['alias'], 36).$saltolinea;
                $rssac->MoveNext();
            }
        }
        if (trim($observacion_item) != '') {
            //$texto.=$saltolinea."$enfasis   *OBS: ".texto_tk($observacion_item,35).$saltolinea;
            $texto .= $saltolinea."$enfasis   *OBS: ".$observacion_item.$saltolinea;
        }
        $texto = trim($texto);
        $rs->MoveNext();
    }

    // buscar usuario
    $operador = $rscab->fields['idusu'];
    $consulta = "
select usuario from usuarios where idusu = $operador
";
    $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $operador = $rsop->fields['usuario'];

    // datos de cabecera
    $chapa = texto_tk($rscab->fields['chapa'], 33);
    $telefono_carry = texto_tk($rscab->fields['telefono'], 33);
    $mesa = texto_tk($rscab->fields['idmesa'], 33);
    $mesa = texto_tk($rscab->fields['idmesa'], 33);
    $observacion = $rscab->fields['observacion'];

    // datos cabecera
    $direccion = $rscab->fields['direccion'];
    $telefono = $rscab->fields['telefono'];
    $nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
    $apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
    $telefono = $rscab->fields['telefono'];
    $llevapos = siono($rscab->fields['llevapos']);
    $cambio = formatomoneda($rscab->fields['cambio']);
    $observacion_delivery = $rscab->fields['observacion_delivery'];
    $delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
    $monto = formatomoneda($rscab->fields['monto']);
    $totalpagar_num = floatval($rscab->fields['monto']) + floatval($rscab->fields['delivery_costo']) - floatval($descuento);
    $totalpagar = formatomoneda($totalpagar_num);
    $vuelto = $rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'] - floatval($descuento));
    // si es una subcuenta
    if (intval($idatcdet) > 0) {
        $consulta_tot = "
    select sum(subtotal) as total
    from tmp_ventares 
    where 
    borrado = 'N' 
    and idatcdet = $idatcdet
    ";
        $rstotal_subcuenta = $conexion->Execute($consulta_tot) or die(errorpg($conexion, $consulta_tot));
        $total_subcuenta = floatval($rstotal_subcuenta->fields['total']);
        $monto = formatomoneda($total_subcuenta);
        $totalpagar_num = $total_subcuenta;
        $totalpagar = formatomoneda($total_subcuenta);
    }

    if ($vuelto < 0) {
        $vuelto = 0;
    }
    $vuelto = formatomoneda($vuelto);

    if ($usa_total == 'S') {
        $texto_total = $saltolinea.$enfasis."TOTAL GLOBAL: $totalpagar";
    }
    if ($usa_totaldiscreto == 'S') {
        //$texto_totaldiscreto=$saltolinea."#$totalpagar";
    }



    // totales
    $texto .= "
----------------------------------------$desctxt$texto_total$texto_totaldiscreto";

    //Vemos los saldos si es de mesa
    if ($idatc > 0) {
        //Pagos efectuados a cuenta
        $consulta = "
        SELECT sum(montoabonado) as total
        FROM mesas_cobros_deta
        where
        idatc = $idatc
        and estadopago = 1
        ";

        $rscobrito = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_pagos_cargados = floatval($rscobrito->fields['total']);


        //Descuentos por factura y por articulo
        $buscar = "Select sum(monto_descuento) as tdescontado from mesas_descuentos_facturas where idatc=$idatc and estado=1";
        $rsdescu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $descontado_factura = floatval($rsdescu->fields['tdescontado']);
        $total_neto_descuentos = floatval($descontado_factura + $descontado)    ;
        $saldo_mesa = $totalpagar_num - $descontado_factura - $total_pagos_cargados;

        if ($total_pagos_cargados == 0) {
            //mostramos, sino, abajo en el pie saldran los dscuentos aplicados
            if ($total_neto_descuentos > 0) {
                $texto .= "
-----------------------------------
";
                $texto .= $saltolinea."DESC. Aplicados(s): ".formatomoneda($total_neto_descuentos);
                if ($rscab->fields['delivery'] != "S") {

                    if ($usa_total == 'S') {
                        $texto_total_glob .= $saltolinea.$enfasis."SALDO PAGAR: ".formatomoneda($saldo_mesa);
                        //$texto_total_glob.=$enfasis."TOTAL GLOBAL: $totalpagar";
                    } else {
                        $texto_total_glob = '';
                    }
                    // totales
                    $texto .= "
----------------------------------------$desctxt
";
                    $texto .= $texto_total_glob;
                }
            } else {


            }
        }

    }


    // si no es delivery muestra el total global del consumo



    //echo $texto;exit;



    //echo 'exit';exit;


    // si es delivery
    if ($rscab->fields['delivery'] == "S") {

        $texto .= "
----------------------------------------
> DELIVERY
NOMBRE      : $nombre_deliv
APELLIDO    : $apellido_deliv
TELEFONO    : 0$telefono
LLEVAR POS  : $llevapos
TOTAL PROD  : $monto
DELIVERY GS.: $delivery_costo
TOTAL GLOBAL: $totalpagar
PAGA CON    : $cambio
VUELTO      : $vuelto
DIRECCION   : $direccion
OBS. DEL.   : $observacion_delivery";

    }

    // si tiene mesa buscar el numero
    if ($mesa > 0) {
        $consulta = " 
    select numero_mesa, nombre
    from mesas
    inner join salon on mesas.idsalon = salon.idsalon
    where 
    idmesa = $mesa
    and salon.idsucursal = $idsucursal
    ";
        $rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $numeromesa = $rsmes->fields['numero_mesa'];
        $salon = $rsmes->fields['nombre'];
        if ($idatcdet > 0) {
            $subcuenta_txt = $saltolinea."SUB CUENTA: ".$nombre_subcuenta." (#$idatcdet)";
        }
    }


    // si tiene mesa mostrar
    if ($numeromesa != '') {
        $mesa_m = $saltolinea.$enfasis."MESA: ".texto_tk($numeromesa, 34).$subcuenta_txt.$saltolinea."SALON: ".texto_tk(strtoupper($salon), 33);
        $mesa_m .= $mesa_m_det;
    }



    // si tiene chapa mostrar
    if (trim($chapa) != '') {
        if ($usa_chapa == 'S') {
            if ($chapa != '') {
                $chapa_m = $saltolinea.$enfasis."NOMBRE: ".$chapa;
            }
            if ($telefono_carry != '') {
                $telefono_carry_m = $saltolinea.$enfasis."TELEFONO: ".$telefono_carry;
            }
        } else {
            $chapa_m = "";
            $chapa = "";
            $telefono_carry_m = "";
            $telefono_carry = "";
        }
    }
    // si tiene observacion mostrar
    if (trim($observacion) != '') {
        if ($usa_obs == 'S') {
            if ($observacion != '') {
                $observacion_m = $saltolinea.$enfasis."OBSERVACION: ".strtoupper($observacion);
            }
        }
    }

    // si es mesa
    if ($impresor_tip == "MES") {

        $textomesa = "";
        //$saldo_mesa=$totalpagar_num-$total_neto_descuentos;
        $consulta = "
    SELECT idatc
    FROM mesas_atc 
    where 
    idmesa=$idmesa 
    and estado=1 
    order by idatc desc 
    limit 1
    ";
        //echo $consulta;exit;
        $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo intval($rsatc->fields['idatc']);exit;
        if (intval($rsatc->fields['idatc']) > 0) {




            $consulta = "
        SELECT mesas_cobros_deta.idcobser, idatc, montoabonado, estadopago, formas_pago.descripcion as formapago
        FROM mesas_cobros_deta
        inner join formas_pago on formas_pago.idforma = mesas_cobros_deta.idformapago  
        where
        idatc = $idatc
        and estadopago = 1
        ORDER BY mesas_cobros_deta.idcobser  ASC
        ";
            //echo $consulta;exit;
            $rscob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rscob->fields['idatc']) > 0) {
                $textomesa .= "PAGOS REALIZADOS: ".$saltolinea;
                $tpagos = 0;
                while (!$rscob->EOF) {
                    $textomesa .= $rscob->fields['formapago'].': '.formatomoneda($rscob->fields['montoabonado']).$saltolinea;
                    $tpagos += $rscob->fields['montoabonado'];
                    $rscob->MoveNext();
                }
                //$saldo_mesa=$totalpagar_num-$tpagos-$total_neto_descuentos;
                $textomesa .= "----------------------------------------".$saltolinea;
                $textomesa .= 'TOTAL MESA: '.formatomoneda($totalpagar_num).$saltolinea;
                $textomesa .= 'TOTAL PAGOS: '.formatomoneda($tpagos * -1).$saltolinea;
                if ($total_neto_descuentos > 0) {
                    $textomesa .= 'DESCUENTOS: '.formatomoneda($total_neto_descuentos * -1).$saltolinea;

                }
                $textomesa .= 'SALDO PAGAR: '.formatomoneda($saldo_mesa).$saltolinea;
                $textomesa .= "----------------------------------------".$saltolinea;
            }
        }
        //echo $textomesa;exit;
        $ahorad = date("Y-m-d");
        $consulta = "
    select tipo_moneda.descripcion as moneda,  tipo_moneda.idtipo,
            (
            select cotizaciones.cotizacion
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            /*and date(cotizaciones.fecha) = '$ahorad'*/
            and tipo_moneda.idtipo = cotizaciones.tipo_moneda
            order by cotizaciones.fecha desc
            limit 1
            ) as cotizacion
    from tipo_moneda 
    where
    estado = 1
    and borrable = 'S'
    and 
    (
        (
            borrable = 'N'
        ) 
        or  
        (
            tipo_moneda.idtipo in 
            (
            select cotizaciones.tipo_moneda 
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            /*and date(cotizaciones.fecha) = '$ahorad'*/
            )
        )
    )
    order by borrable ASC, descripcion asc
    ";
        $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsmoneda->fields['idtipo']) > 0) {
            while (!$rsmoneda->EOF) {
                $textomesa .= 'SALDO MESA '.$rsmoneda->fields['moneda']." (".formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N')."): ".formatomoneda($saldo_mesa / $rsmoneda->fields['cotizacion'], 2, 'S').$saltolinea;
                $rsmoneda->MoveNext();
            }
            $textomesa .= "----------------------------------------".$saltolinea;
        }


        if ($datos_fac_preticket == 'S') {
            $textomesa .= "RUC:____________________________________".$saltolinea;
            $textomesa .= "RAZON SOCIAL:___________________________".$saltolinea;
        }


        if (trim($propina_porcentajes) == '') {
            if ($propina_preticket == 'S') {
                $textomesa .= "PROPINA:________________________________".$saltolinea;
            }
        }
        if (trim($propina_porcentajes) != '') {
            $propina_porcentajes_ar = explode(",", $propina_porcentajes);
            sort($propina_porcentajes_ar);
            $textomesa .= "----------------------------------------".$saltolinea;
            $textomesa .= "Favor indiquenos su eleccion sobre la Propina: ".$saltolinea;
            $totalpagar_num_txt = formatomoneda($totalpagar_num, 'N', 0);
            if ($propina_sinpropina == 'S') {
                $textomesa .= '[  ] '.'Sin propina: '.$totalpagar_num_txt.' :( '.$saltolinea;
            }
            foreach ($propina_porcentajes_ar as $propina_porc) {
                $propina_monto = $totalpagar_num * ($propina_porc / 100);
                $total_pagar_con_propina = $totalpagar_num + $propina_monto;
                $totalpagar_num_txt = formatomoneda($totalpagar_num, 'N', 0);
                $propina_monto_txt = formatomoneda($propina_monto, 'N', 0);
                $total_pagar_con_propina_txt = formatomoneda($total_pagar_con_propina, 'N', 0);
                $textomesa .= '[  ] '.$propina_porc.'% - Monto: '.$total_pagar_con_propina_txt.$saltolinea;
                $textomesa .= '     (Importe Propina: '.agregaespacio_tk($propina_monto_txt, 5, 'der', 'N').')'.$saltolinea;
            }
            if ($propina_libre == 'S') {
                $textomesa .= '[  ] '.'Propina Libre:_____________________'.$saltolinea;
            }
        }


        if ($datos_fac_preticket == 'S' or $propina_preticket == 'S') {
            $textomesa .= "----------------------------------------".$saltolinea;
        }
    }

    $texto .= "
----------------------------------------
OPERADOR: $operador $mesaychapa";
    $texto .= $mesa_m;
    $texto .= $chapa_m;
    $texto .= $telefono_carry_m;
    $texto .= $observacion_m;
    $texto .= $creditotxt;
    $texto .= $sucursaltxt;
    $texto .= "
----------------------------------------
$textomesa $pie_pagina
";

} else {
    ////////////////////// consolidado ///////////////////////////////////////////////

    //echo 'consolidado';exit;




    $texto = "
****************************************$nombreempresa_centrado
$leyenda $texto_rei
****************************************
".$enfasis."PED: $id | $txt_codvta: $vtaid
----------------------------------------
FECHA PED : $fechapedido";




    if ($usa_fecha_impreso == 'S') {
        $texto .= $saltolinea."FECHA IMP : $ahora $datos_fiscales $nombre_adherente $codadherente";
    } else {
        $texto .= "$datos_fiscales $nombre_adherente $codadherente";

    }

    if ($canal_venta != '') {
        $texto .= $saltolinea.$enfasis."CANAL VENTA: ".$canal_venta;
    }


    $texto .= "
----------------------------------------
N     |PRODUCTOS
----------------------------------------
";
    $texto = trim($texto);

    // detalle sin agregado/sacado
    $consulta = "
select tmp_ventares.*, tmp_ventares.observacion as observacion, productos.descripcion, sum(cantidad) as total,sum(cantidad) as cantidad_agrupada, sum(precio) as totalprecio, sum(subtotal) as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.finalizado = 'S'
and tmp_ventares.borrado = 'N'
$subfiltroadd
and tmp_ventares.idsucursal = $idsucursal
$filtroadd
and tmp_ventares.combo = 'N'
and tmp_ventares.combinado = 'N'
and tmp_ventares.idtipoproducto = 1
and idtmpventaresagregado is null
and (
    select tmp_ventares_agregado.idventatmp 
    from tmp_ventares_agregado 
    WHERE 
    tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
    limit 1
) is null
and (
    select tmp_ventares_sacado.idventatmp 
    from tmp_ventares_sacado 
    WHERE 
    tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
    limit 1
) is null
and desconsolida_forzar is null
and tmp_ventares.observacion is null
$whereadd_atcdet
group by descripcion, receta_cambiada
ORDER BY tmp_ventares.idventatmp asc
";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rs->EOF) {
        $idventatmp = $rs->fields['idventatmp'];
        $idproducto = $rs->fields['idproducto'];
        $observacion_item = $rs->fields['observacion'];
        if ($rs->fields['tipo_plato'] != '') {
            $tipoplato = trim($rs->fields['tipo_plato']." | ");
        }
        if ($usa_precio == 'S') {
            $texto .= $saltolinea.$enfasis.agregaespacio(formatomoneda($rs->fields['cantidad_agrupada']), 6).'|'.texto_tk_sincorte($tipoplato.$rs->fields['descripcion'], 34);

        } else {
            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 4, 'N'), 6), 6).'|'.texto_tk_sincorte($tipoplato.$rs->fields['descripcion'], 34);
        }
        //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'],4,'N'),6),6).'|'.texto_tk_sincorte($tipoplato.$rs->fields['descripcion'],34);
        //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'],3,'N'),3),3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'],37);
        if ($usa_precio == 'S') {
            $texto .= $saltolinea." P.U: ".texto_tk(formatomoneda($rs->fields['subtotal'] / $rs->fields['cantidad_agrupada'], 4, 'N'))."  SubTotal: ".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
        }
        $texto = trim($texto);
        // busca si tiene agregado
        $idvt = $rs->fields['idventatmp'];
        $consulta = "
    select tmp_ventares_agregado.*
    from tmp_ventares_agregado
    where 
    idventatmp in (select idventatmp from tmp_ventares where idproducto = $idproducto and borrado = 'N' $subfiltroadd)
    order by alias desc
    ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera agregados si tiene
        /*if(trim($rsag->fields['alias']) != ''){
            $texto.=$saltolinea."   > AGREGADOS:".$saltolinea;
            while (!$rsag->EOF){
                $texto.="    + ".texto_tk($rsag->fields['alias'],36).$saltolinea;
                $texto.="      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional']),30).$saltolinea;
            $rsag->MoveNext(); }
        }
        $texto=trim($texto);

        // busca si tiene sacados
        $consulta="
        select tmp_ventares_sacado.*
        from tmp_ventares_sacado
        where
        idventatmp in (select idventatmp from tmp_ventares where idproducto = $idproducto $subfiltroadd)
        order by alias desc
        ";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        // genera sacados si tiene
        if(trim($rssac->fields['alias']) != ''){
            $texto.=$saltolinea."   > EXCLUIDOS:".$saltolinea;
            while (!$rssac->EOF){
                $texto.="    - ".texto_tk($rssac->fields['alias'],36).$saltolinea;
            $rssac->MoveNext(); }
        }*/
        if (trim($observacion_item) != '') {
            //$texto.=$saltolinea."$enfasis   *OBS: ".texto_tk($observacion_item,35).$saltolinea;
            $texto .= $saltolinea."$enfasis   *OBS: ".$observacion_item.$saltolinea;
        }
        $texto = trim($texto);
        $rs->MoveNext();
    }

    // detalle sin agregado/sacado
    $consulta = "
select tmp_ventares.*, tmp_ventares.observacion as observacion, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.finalizado = 'S'
and tmp_ventares.borrado = 'N'
$subfiltroadd
and tmp_ventares.idsucursal = $idsucursal
$filtroadd
and tmp_ventares.combo = 'N'
and tmp_ventares.combinado = 'N'
and tmp_ventares.idtipoproducto = 1
and idtmpventaresagregado is null
and 
(
    (
        select tmp_ventares_agregado.idventatmp 
        from tmp_ventares_agregado 
        WHERE 
        tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
        limit 1
    ) is not null
    or
    (
        select tmp_ventares_sacado.idventatmp 
        from tmp_ventares_sacado 
        WHERE 
        tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
        limit 1
    ) is not null
    or
    (
        desconsolida_forzar is not null
    )
    or 
    (
        tmp_ventares.observacion is not null
    )
)
$whereadd_atcdet
ORDER BY tmp_ventares.idventatmp asc
";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rs->EOF) {
        $idventatmp = $rs->fields['idventatmp'];
        $idproducto = $rs->fields['idproducto'];
        $observacion_item = $rs->fields['observacion'];

        if ($rs->fields['tipo_plato'] != '') {
            $tipoplato = trim($rs->fields['tipo_plato']." | ");
        }

        //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'],4,'N'),6),6).'|'.texto_tk($tipoplato.$rs->fields['descripcion'],34);
        $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 4, 'N'), 6), 6).'|'.$tipoplato.$rs->fields['descripcion'];
        if ($usa_precio == 'S') {
            $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
        }
        $texto = trim($texto);
        // busca si tiene agregado
        $idvt = $rs->fields['idventatmp'];
        $consulta = "
    select tmp_ventares_agregado.*
    from tmp_ventares_agregado
    where 
    idventatmp = $idventatmp
    order by alias desc
    ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera agregados si tiene
        if (trim($rsag->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis   > AGREGADOS:".$saltolinea;
            while (!$rsag->EOF) {
                $texto .= "$enfasis    + ".texto_tk($rsag->fields['alias'], 36).$saltolinea;
                $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                $rsag->MoveNext();
            }
        }
        $texto = trim($texto);

        // busca si tiene sacados
        $consulta = "
    select tmp_ventares_sacado.*
    from tmp_ventares_sacado
    where 
    idventatmp = $idventatmp
    order by alias desc
    ";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera sacados si tiene
        if (trim($rssac->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis   > EXCLUIDOS:".$saltolinea;
            while (!$rssac->EOF) {
                $texto .= "$enfasis    - ".texto_tk_sincorte($rssac->fields['alias'], 36).$saltolinea;
                $rssac->MoveNext();
            }
        }
        if (trim($observacion_item) != '') {
            //$texto.=$saltolinea."$enfasis   *OBS: ".texto_tk($observacion_item,35).$saltolinea;
            $texto .= $saltolinea."$enfasis   *OBS: ".$observacion_item.$saltolinea;
        }
        $texto = trim($texto);
        $rs->MoveNext();
    }

    // combo y combinado
    $consulta = "
select tmp_ventares.*, productos.descripcion, tmp_ventares.cantidad as total, productos.combinado_leyenda,
(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'S'
$subfiltroadd
and tmp_ventares.idsucursal = $idsucursal
$filtroadd
and (tmp_ventares.combo = 'S' or tmp_ventares.combinado = 'S' or tmp_ventares.idtipoproducto > 1)
and idtmpventaresagregado is null
$whereadd_atcdet
ORDER BY descripcion asc
";
    //echo $consulta;exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rs->EOF) {
        $idventatmp = $rs->fields['idventatmp'];
        $idproducto = $rs->fields['idproducto'];
        $observacion_item = $rs->fields['observacion'];
        $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];

        $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk_sincorte($rs->fields['descripcion'], 37);
        $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
        $texto = trim($texto);
        // busca si es un producto combinado
        if ($rs->fields['combinado'] == 'S') {
            $prod_1 = $rs->fields['idprod_mitad1'];
            $prod_2 = $rs->fields['idprod_mitad2'];
            $consulta = "
        select *
        from productos
        where 
        (idprod_serial = $prod_1 or idprod_serial = $prod_2)
        order by descripcion asc
        ";
            $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            while (!$rspcom->EOF) {
                $texto .= $saltolinea."$enfasis   » MITAD ".texto_tk_sincorte($rspcom->fields['descripcion'], 31);
                $rspcom->MoveNext();
            }

        }
        // busca si es un combo
        if ($rs->fields['combo'] == 'S') {

            if ($muestra_grupo_combo == 'S') {
                $consulta = "
            select combos_listas.nombre, productos.descripcion, productos.idprod_serial, count(*) as total
            from productos 
            inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
            inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
            where 
            tmp_combos_listas.idventatmp = $idventatmp
            group by combos_listas.nombre, productos.idprod_serial 
            order by combos_listas.idlistacombo asc
            ";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
            select count(idprod) as total, descripcion, productos.idprod_serial 
            from tmp_combos_listas
            inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
            where
            tmp_combos_listas.idventatmp = $idventatmp
            group by productos.idprod_serial 
            order by descripcion asc
            ";
                //echo $consulta;
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            while (!$rsgrupos->EOF) {
                if ($muestra_grupo_combo == 'S') {
                    $nombre_grupo = trim($rsgrupos->fields['nombre']).": ";
                }
                $texto .= $saltolinea.$enfasis."   » ".$nombre_grupo.$rsgrupos->fields['total'].' '.texto_tk($rsgrupos->fields['descripcion'], 25);
                $rsgrupos->MoveNext();
            }

        }
        // busca si es un combinado extendido
        if ($rs->fields['idtipoproducto'] == 4) {
            $consulta = "
        select count(idprod) as total, descripcion, productos.idprod_serial 
        from tmp_combinado_listas
        inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
        where
        tmp_combinado_listas.idventatmp = $idventatmp
        group by productos.idprod_serial 
        order by descripcion asc
        ";
            //echo $consulta;exit;
            $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $combinado_leyenda = trim($rs->fields['combinado_leyenda']);
            while (!$rsgrupos->EOF) {
                if ($combinado_leyenda != '') {
                    if ($rsgrupos->fields['total'] != 1) {
                        $leyenda = $combinado_leyenda.' x '.$rsgrupos->fields['total'];
                    } else {
                        $leyenda = $combinado_leyenda;
                    }
                } else {
                    $leyenda = $rsgrupos->fields['total'];
                }
                $texto .= $saltolinea."$enfasis   » ".$leyenda." ".texto_tk_sincorte($rsgrupos->fields['descripcion'], 25);
                $rsgrupos->MoveNext();
            }

        }
        //exit;
        // busca si tiene agregado
        $idvt = $rs->fields['idventatmp'];
        $consulta = "
    select tmp_ventares_agregado.*
    from tmp_ventares_agregado
    where 
    idventatmp in (select idventatmp from tmp_ventares where idproducto = $idproducto and borrado = 'N' $subfiltroadd ) and tmp_ventares_agregado.idventatmp=$idvt
    order by alias desc
    ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;exit;
        // genera agregados si tiene
        if (trim($rsag->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis   > AGREGADOS:".$saltolinea;
            while (!$rsag->EOF) {
                $texto .= "$enfasis    + ".texto_tk_sincorte($rsag->fields['alias'], 36).$saltolinea;
                $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                $rsag->MoveNext();
            }
        }
        $texto = trim($texto);

        // busca si tiene sacados
        $consulta = "
    select tmp_ventares_sacado.*
    from tmp_ventares_sacado
    where 
    idventatmp in (select idventatmp from tmp_ventares where idproducto = $idproducto $subfiltroadd )
    order by alias desc
    ";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // genera sacados si tiene
        if (trim($rssac->fields['alias']) != '') {
            $texto .= $saltolinea."$enfasis   > EXCLUIDOS:".$saltolinea;
            while (!$rssac->EOF) {
                $texto .= "$enfasis    - ".texto_tk_sincorte($rssac->fields['alias'], 36).$saltolinea;
                $rssac->MoveNext();
            }
        }
        if (trim($observacion_item) != '') {
            //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($observacion_item,35).$saltolinea;
            $texto .= $saltolinea.$enfasis."   *OBS: ".$observacion_item.$saltolinea;
        }
        $texto = trim($texto);
        $rs->MoveNext();
    }

    // buscar usuario
    $operador = $rscab->fields['idusu'];
    $consulta = "
select usuario from usuarios where idusu = $operador
";
    $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $operador = $rsop->fields['usuario'];

    // datos de cabecera
    $chapa = texto_tk($rscab->fields['chapa'], 33);
    $telefono_carry = texto_tk($rscab->fields['telefono'], 33);
    $mesa = texto_tk($rscab->fields['idmesa'], 33);
    $mesa = texto_tk($rscab->fields['idmesa'], 33);
    $observacion = $rscab->fields['observacion'];

    // datos cabecera
    $direccion = $rscab->fields['direccion'];
    $telefono = $rscab->fields['telefono'];
    $nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
    $apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
    $llevapos = siono($rscab->fields['llevapos']);
    $cambio = formatomoneda($rscab->fields['cambio']);
    $observacion_delivery = $rscab->fields['observacion_delivery'];
    $delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
    $monto = formatomoneda($rscab->fields['monto']);
    $totalpagar_num = floatval($rscab->fields['monto']) + floatval($rscab->fields['delivery_costo']) - floatval($descuento);
    $totalpagar = formatomoneda($totalpagar_num);
    $vuelto = $rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'] - floatval($descuento));
    if ($vuelto < 0) {
        $vuelto = 0;
    }
    $vuelto = formatomoneda($vuelto);

    // si es una subcuenta
    if (intval($idatcdet) > 0) {
        $consulta_tot = "
    select sum(subtotal) as total
    from tmp_ventares 
    where 
    borrado = 'N' 
    and idatcdet = $idatcdet
    ";
        $rstotal_subcuenta = $conexion->Execute($consulta_tot) or die(errorpg($conexion, $consulta_tot));
        $total_subcuenta = floatval($rstotal_subcuenta->fields['total']);
        $monto = formatomoneda($total_subcuenta);
        $totalpagar_num = $total_subcuenta;
        $totalpagar = formatomoneda($total_subcuenta);
    }



    // si no es delivery
    if ($rscab->fields['delivery'] != "S") {//Vemos los saldos si es de mesa

        if ($usa_total == 'S') {
            $texto_total = $saltolinea.$enfasis."TOTAL GLOBAL: $totalpagar";
        }
        if ($usa_totaldiscreto == 'S') {
            //$texto_totaldiscreto=$saltolinea."#$totalpagar";
        }



        // totales
        $texto .= "
----------------------------------------$desctxt$texto_total$texto_totaldiscreto";

        if ($idatc > 0) {
            //Pagos efectuados a cuenta
            $consulta = "
        SELECT sum(montoabonado) as total
        FROM mesas_cobros_deta
        where
        idatc = $idatc
        and estadopago = 1
        ";

            $rscobrito = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $total_pagos_cargados = floatval($rscobrito->fields['total']);


            //Descuentos por factura y por articulo
            $buscar = "Select sum(monto_descuento) as tdescontado from mesas_descuentos_facturas where idatc=$idatc and estado=1";
            $rsdescu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $descontado_factura = floatval($rsdescu->fields['tdescontado']);
            $total_neto_descuentos = floatval($descontado_factura + $descontado)    ;
            $saldo_mesa = $totalpagar_num - $descontado_factura - $total_pagos_cargados;

            if ($total_pagos_cargados == 0) {
                //mostramos, sino, abajo en el pie saldran los dscuentos aplicados
                if ($total_neto_descuentos > 0) {
                    $texto .= "
-----------------------------------
";
                    $texto .= $saltolinea."DESC. Aplicados(s): ".formatomoneda($total_neto_descuentos);
                    if ($rscab->fields['delivery'] != "S") {

                        if ($usa_total == 'S') {
                            $texto_total_glob .= $saltolinea.$enfasis."SALDO PAGAR: ".formatomoneda($saldo_mesa);
                            //$texto_total_glob.=$enfasis."TOTAL GLOBAL: $totalpagar";
                        } else {
                            $texto_total_glob = '';
                        }
                        // totales
                        $texto .= "
----------------------------------------$desctxt
";
                        $texto .= $texto_total_glob;
                    }
                } else {


                }
            }

        }



    }

    // si es delivery
    if ($rscab->fields['delivery'] == "S") {
        if ($usa_tarjeta_delivery == 'N') {
            $addcabeloc = "--> Delivery <--";
        } else {
            $numero_tarjeta = intval($rscab->fields['numero_tarjeta']);
            $addcabeloc = $enfasis."--> Tarjeta Delivery : $numero_tarjeta <--";
        }

        $texto .= "
----------------------------------------
$addcabeloc
NOMBRE      : $nombre_deliv
APELLIDO    : $apellido_deliv
TELEFONO    : 0$telefono
LLEVAR POS  : $llevapos
TOTAL PROD  : $monto
DELIVERY GS.: $delivery_costo
TOTAL GLOBAL: $totalpagar
PAGA CON    : $cambio
VUELTO      : $vuelto
DIRECCION   : $direccion
OBS. DEL.   : $observacion_delivery";

    }


    // si tiene mesa buscar el numero
    if ($mesa > 0) {
        $consulta = " 
    select numero_mesa, nombre
    from mesas
    inner join salon on mesas.idsalon = salon.idsalon
    where 
    idmesa = $mesa
    and salon.idsucursal = $idsucursal
    ";
        $rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $numeromesa = $rsmes->fields['numero_mesa'];
        $salon = $rsmes->fields['nombre'];
        if ($idatcdet > 0) {
            $subcuenta_txt = $saltolinea."SUB CUENTA: ".$nombre_subcuenta." (#$idatcdet)";
        }
    }



    // si tiene mesa mostrar
    if ($numeromesa != '') {
        $mesa_m = $saltolinea.$enfasis."MESA: ".texto_tk($numeromesa, 34).$subcuenta_txt.$saltolinea."SALON: ".texto_tk(strtoupper($salon), 33);
        $mesa_m .= $mesa_m_det;
    }

    // si tiene chapa mostrar
    if (trim($chapa) != '') {
        if ($usa_chapa == 'S') {
            if ($chapa != '') {
                $chapa_m = $saltolinea.$enfasis."NOMBRE: ".$chapa;
            }
            if ($telefono_carry != '') {
                $telefono_carry_m = $saltolinea.$enfasis."TELEFONO: ".$telefono_carry;
            }
        } else {
            $chapa_m = "";
            $chapa = "";
            $telefono_carry_m = "";
            $telefono_carry = "";
        }
    }
    // si tiene observacion mostrar
    if (trim($observacion) != '') {
        if ($usa_obs == 'S') {
            if ($observacion != '') {
                $observacion_m = $saltolinea.$enfasis."OBSERVACION: ".strtoupper($observacion);
            }
        }
    }

    // si es mesa
    if ($impresor_tip == "MES") {

        $textomesa = "";
        //$saldo_mesa=$totalpagar_num;
        $consulta = "
    SELECT idatc
    FROM mesas_atc 
    where 
    idmesa=$idmesa 
    and estado=1 
    order by idatc desc 
    limit 1
    ";
        //echo $consulta;exit;
        $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo intval($rsatc->fields['idatc']);exit;
        if (intval($rsatc->fields['idatc']) > 0) {
            $consulta = "
        SELECT mesas_cobros_deta.idcobser, idatc, montoabonado, estadopago, formas_pago.descripcion as formapago
        FROM mesas_cobros_deta
        inner join formas_pago on formas_pago.idforma = mesas_cobros_deta.idformapago  
        where
        idatc = $idatc
        and estadopago = 1
        ORDER BY mesas_cobros_deta.idcobser  ASC
        ";
            //echo $consulta;exit;
            $rscob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rscob->fields['idatc']) > 0) {
                $textomesa .= "PAGOS REALIZADOS: ".$saltolinea;
                $tpagos = 0;
                while (!$rscob->EOF) {
                    $textomesa .= $rscob->fields['formapago'].': '.formatomoneda($rscob->fields['montoabonado']).$saltolinea;
                    $tpagos += $rscob->fields['montoabonado'];
                    $rscob->MoveNext();
                }
                //$saldo_mesa=$totalpagar_num-$tpagos;
                $textomesa .= "----------------------------------------".$saltolinea;
                $textomesa .= 'TOTAL MESA: '.formatomoneda($totalpagar_num).$saltolinea;
                $textomesa .= 'TOTAL PAGOS: '.formatomoneda($tpagos * -1).$saltolinea;
                if ($total_neto_descuentos > 0) {
                    $textomesa .= 'DESCUENTOS: '.formatomoneda($total_neto_descuentos * -1).$saltolinea;

                }
                $textomesa .= 'SALDO PAGAR: '.formatomoneda($saldo_mesa).$saltolinea;
                //$textomesa.='SALDO MESA: '.formatomoneda($saldo_mesa).$saltolinea;

                $textomesa .= "----------------------------------------".$saltolinea;
            }
        }
        $ahorad = date("Y-m-d");
        $consulta = "
    select tipo_moneda.descripcion as moneda,  tipo_moneda.idtipo,
            (
            select cotizaciones.cotizacion
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            /*and date(cotizaciones.fecha) = '$ahorad'*/
            and tipo_moneda.idtipo = cotizaciones.tipo_moneda
            order by cotizaciones.fecha desc
            limit 1
            ) as cotizacion
    from tipo_moneda 
    where
    estado = 1
    and borrable = 'S'
    and 
    (
        (
            borrable = 'N'
        ) 
        or  
        (
            tipo_moneda.idtipo in 
            (
            select cotizaciones.tipo_moneda 
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            /*and date(cotizaciones.fecha) = '$ahorad'*/
            )
        )
    )
    order by borrable ASC, descripcion asc
    ";
        $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsmoneda->fields['idtipo']) > 0) {
            while (!$rsmoneda->EOF) {
                $textomesa .= 'SALDO MESA '.$rsmoneda->fields['moneda']." (".formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N')."): ".formatomoneda($saldo_mesa / $rsmoneda->fields['cotizacion'], 2, 'S').$saltolinea;
                $rsmoneda->MoveNext();
            }
            $textomesa .= "----------------------------------------".$saltolinea;
        }




        if ($datos_fac_preticket == 'S') {
            $textomesa .= "RUC:____________________________________".$saltolinea;
            $textomesa .= "RAZON SOCIAL:___________________________".$saltolinea;
        }
        if (trim($propina_porcentajes) == '') {
            if ($propina_preticket == 'S') {
                $textomesa .= "PROPINA:________________________________".$saltolinea;
            }
        }

        if (trim($propina_porcentajes) != '') {
            $propina_porcentajes_ar = explode(",", $propina_porcentajes);
            sort($propina_porcentajes_ar);
            $textomesa .= "----------------------------------------".$saltolinea;
            $textomesa .= "Favor indiquenos su eleccion sobre la Propina: ".$saltolinea;
            $totalpagar_num_txt = formatomoneda($totalpagar_num, 'N', 0);
            if ($propina_sinpropina == 'S') {
                $textomesa .= '[  ] '.'Sin propina: '.$totalpagar_num_txt.' :( '.$saltolinea;
            }
            foreach ($propina_porcentajes_ar as $propina_porc) {
                $propina_monto = $totalpagar_num * ($propina_porc / 100);
                $total_pagar_con_propina = $totalpagar_num + $propina_monto;
                $totalpagar_num_txt = formatomoneda($totalpagar_num, 'N', 0);
                $propina_monto_txt = formatomoneda($propina_monto, 'N', 0);
                $total_pagar_con_propina_txt = formatomoneda($total_pagar_con_propina, 'N', 0);
                $textomesa .= '[  ] '.$propina_porc.'% - Monto: '.$total_pagar_con_propina_txt.$saltolinea;
                $textomesa .= '     (Importe Propina: '.agregaespacio_tk($propina_monto_txt, 5, 'der', 'N').')'.$saltolinea;
            }
            if ($propina_libre == 'S') {
                $textomesa .= '[  ] '.'Propina Libre:_____________________'.$saltolinea;
            }
        }


        if ($datos_fac_preticket == 'S' or $propina_preticket == 'S') {
            $textomesa .= "----------------------------------------".$saltolinea;
        }
    }


    $texto .= "
----------------------------------------
OPERADOR: $operador $mesaychapa";
    $texto .= $mesa_m;
    $texto .= $chapa_m;
    $texto .= $telefono_carry_m;
    $texto .= $observacion_m;
    $texto .= $creditotxt;
    $texto .= $sucursaltxt;
    $texto .= "
----------------------------------------
$textomesa $pie_pagina
";



}

$texto = wordwrap($texto, 40, $saltolinea, true);
//echo $texto;exit;
//echo $texto;
//exit;

/*
Instrucciones para instalar EPSON TMU220D(paralela real pero con adaptador USB)
1) Desinstalar cualquier residuo de impresoras epson
2) Descargar el driver de la pagina Oficial
3) Reiniciar Windows
4) CON LA IMPRESORA APAGADA, CONECTAR EL CABLE LPT->USB
5) Esperar que windows termine de instalar
6) Ejecutar la instalacion del soft de epson, seleccionar modelo y puerto USB
7) Encender la IMPRESORA
8) Entrar a las propiedades de la impresora y compartir
9) Abrir la ventana del command y ejecutar NET USE LPT1: \\UNA IP FIJA\NOMBRE COMPARTIDO /PERSISTENT:YES
10)Entrar a las propiedades de la impresora, puertos y marcar el USB1,2 o 3 segun corresponda. Usualmente es el uno. En el panel de conntrol, siempre va salir NO DIPONIBLE. Esta Listo. Enviar una impresion
*/
/*
// para agregar espacios o centrar facturas sin tener problemas con las ñ
// http://php.net/manual/es/function.str-pad.php
function my_mb_str_pad($input, $pad_length, $pad_string=' ', $pad_type=STR_PAD_RIGHT,$encoding='UTF-8'){
    $mb_diff=mb_strlen($str, $encoding)-strlen($string);
    return str_pad($input,$pad_length+$mb_diff,$pad_string,$pad_type);
}
*/
?>
