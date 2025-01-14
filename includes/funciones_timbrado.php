<?php

function validar_docu_timbrado($parametros_array)
{
    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    if (intval($parametros_array['idtimbrado']) == 0) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['inicio']) == 0) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['fin']) == 0) {
        $valido = "N";
        $errores .= " - El campo fin no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['punto_expedicion']) == 0) {
        $valido = "N";
        $errores .= " - El campo punto_expedicion no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['sucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo sucursal no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idtipodocutimbrado']) == 0) {
        $valido = "N";
        $errores .= " - El campo documento no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idtimbradotipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo documento no puede estar vacio.<br />";
    }
    if (intval($parametros_array['registrado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo registrado_por no puede estar vacio.<br />";
    }
    if (trim($parametros_array['registrado_el']) == '') {
        $valido = "N";
        $errores .= " - El campo registrado_el no puede estar vacio.<br />";
    }
    // fin no puede ser superior a inicio
    if (intval($parametros_array['inicio']) >= intval($parametros_array['fin'])) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser mayor o igual a fin.<br />";
    }
    $idtimbradotipo = antisqlinyeccion($parametros_array['idtimbradotipo'], "int");
    $consulta = "
	select tipo_old from timbrado_tipo where idtimbradotipo = $idtimbradotipo
	";
    $rsold = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoimpreso = antisqlinyeccion(trim($rsold->fields['tipo_old']), "text");
    if (trim($rsold->fields['tipo_old']) == '') {
        $valido = "N";
        $errores .= " - El campo tipo documento no tiene un comportamiento asignado.<br />";
    }
    $idtimbrado = antisqlinyeccion($parametros_array['idtimbrado'], "int");
    $consulta = "
	select idtimbrado from timbrado where idtimbrado = $idtimbrado and estado = 1
	";
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rstimb->fields['idtimbrado']) == 0) {
        $valido = "N";
        $errores .= " - No se envio el idtimbrado o el idtimbrado enviado no esta activo.<br />";
    }

    $punto_expedicion = antisqlinyeccion($parametros_array['punto_expedicion'], "int");
    $sucursal = antisqlinyeccion($parametros_array['sucursal'], "int");
    $idtipodocutimbrado = antisqlinyeccion($parametros_array['idtipodocutimbrado'], "int");
    $consulta = "
	select idtanda 
	from facturas
	where
	punto_expedicion = $punto_expedicion
	and sucursal = $sucursal
	and idtimbrado = $idtimbrado
	and idtipodocutimbrado = $idtipodocutimbrado
	and estado = 'A'
	limit 1
	";
    //echo $consulta;exit;
    $rstimbdoc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rstimbdoc->fields['idtanda']) > 0) {
        $valido = "N";
        $errores .= " - Ya existe otra tanda activa con la misma sucursal y punto expedicion, editelo.<br />";
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res;

}
function registrar_docu_timbrado($parametros_array)
{
    global $conexion;

    $idtimbrado = antisqlinyeccion($parametros_array['idtimbrado'], "int");
    $inicio = antisqlinyeccion($parametros_array['inicio'], "int");
    $fin = antisqlinyeccion($parametros_array['fin'], "int");
    $punto_expedicion = antisqlinyeccion($parametros_array['punto_expedicion'], "int");
    $sucursal = antisqlinyeccion($parametros_array['sucursal'], "int");
    $idtipodocutimbrado = antisqlinyeccion($parametros_array['idtipodocutimbrado'], "int");
    $idtimbradotipo = antisqlinyeccion($parametros_array['idtimbradotipo'], "int");
    $comentario_punto = antisqlinyeccion($parametros_array['comentario_punto'], "text");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");

    // conversiones
    $secuencia1 = $sucursal;
    $secuencia2 = $punto_expedicion;
    $idempresa = 1;

    $consulta = "
	select tipo_old from timbrado_tipo where idtimbradotipo = $idtimbradotipo
	";
    $rsold = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoimpreso = antisqlinyeccion(trim($rsold->fields['tipo_old']), "text");

    $consulta = "
	select * from timbrado where idtimbrado = $idtimbrado
	";
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $timbrado = antisqlinyeccion(trim($rstimb->fields['timbrado']), "text");
    $valido_desde = antisqlinyeccion(trim($rstimb->fields['inicio_vigencia']), "text");
    $valido_hasta = antisqlinyeccion(trim($rstimb->fields['fin_vigencia']), "text");

    $consulta = "
	select max(idtanda) as idtanda from facturas
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtanda = intval($rsmax->fields['idtanda']) + 1;

    $consulta = "
	insert into facturas
	(idtanda, idtimbrado, idtipodocutimbrado, secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, tipoimpreso, idtimbradotipo, comentario_punto)
	values
	($idtanda, $idtimbrado, $idtipodocutimbrado, $secuencia1, $secuencia2, $inicio, $fin, $punto_expedicion, $sucursal, $idempresa, $timbrado, $valido_desde, $valido_hasta, $registrado_por, $registrado_el, NULL, NULL, 'A', $registrado_el, $tipoimpreso,$idtimbradotipo, $comentario_punto)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // insertar log
    $consulta = "
	INSERT INTO facturaslog
	(idtanda, idtimbrado, idtipodocutimbrado, secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, log_registrado_el, log_registrado_por, log_tipomov, tipoimpreso, idtimbradotipo, comentario_punto)
	SELECT idtanda, idtimbrado, idtipodocutimbrado,  secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el, $registrado_el, $registrado_por, 'I', tipoimpreso, idtimbradotipo, comentario_punto
	FROM facturas 
	WHERE 
	idtanda = $idtanda
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $ano = date("Y");
    // busca si existe algun registro
    $buscar = "
	Select idsuc, numfac as mayor 
	from lastcomprobantes 
	where 
	idsuc=$sucursal
	and pe=$punto_expedicion
	and idempresa = $idempresa
	order by ano desc 
	limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
    // si no existe inserta
    if (intval($rsfactura->fields['idsuc']) == 0) {
        $consulta = "
		INSERT INTO lastcomprobantes
		(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
		numhoja, hojalevante, idempresa) 
		VALUES
		($sucursal, 0, 0, NULL, 0, NULL, 0, $ano, $punto_expedicion, NULL, 
		NULL, 0, '', $idempresa)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    $res = [
        'registrado' => 'S',
        'idtanda' => $idtanda
    ];
    return $res;

}

function validar_correccion_correlatividad($parametros_array)
{
    global $conexion;
    global $ahora;
    $numfac = intval($parametros_array['numfac']) - 1;
    $factura_suc = intval($parametros_array['factura_suc']);
    $factura_pexp = intval($parametros_array['factura_pexp']);
    $idusu = intval($parametros_array['idusu']);
    //$idtipodocutimbrado=intval($parametros_array['idtipodocutimbrado']);

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (intval($parametros_array['numfac']) == 0) {
        $valido = "N";
        $errores .= " - El campo numfac no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['factura_suc']) == 0) {
        $valido = "N";
        $errores .= " - El campo factura_suc no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['factura_pexp']) == 0) {
        $valido = "N";
        $errores .= " - El campo factura_pexp no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idusu']) == 0) {
        $valido = "N";
        $errores .= " - El campo idusu no puede ser cero o nulo.<br />";
    }
    /*if(intval($parametros_array['idtipodocutimbrado']) == 0){
        $valido="N";
        $errores.=" - El campo idtipodocutimbrado no puede ser cero o nulo.<br />";
    }*/


    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];
    return $res;

}
function registrar_correccion_correlatividad($parametros_array)
{
    global $conexion;
    global $ahora;
    global $idempresa;

    $numfac = antisqlinyeccion(($parametros_array['numfac'] - 1), "int");
    $factura_suc = antisqlinyeccion(($parametros_array['factura_suc']), "int");
    $factura_pexp = antisqlinyeccion(($parametros_array['factura_pexp']), "int");
    $idusu = antisqlinyeccion(($parametros_array['idusu']), "int");
    //$idtipodocutimbrado=intval($parametros_array['idtipodocutimbrado']);

    $parte1 = intval($factura_suc);
    $parte2 = intval($factura_pexp);
    if ($parte1 == 0 or $parte2 == 0) {
        $parte1f = '001';
        $parte2f = '001';
    } else {
        $parte1f = agregacero($parte1, 3);
        $parte2f = agregacero($parte2, 3);
    }
    $factura_completa = antisqlinyeccion($parte1f.$parte2f.agregacero($numfac, 7), "text");
    $ano = date("Y");

    // busca si existe en la db
    $consulta = "
	SELECT * 
	FROM lastcomprobantes 
	where 
	idsuc=$factura_suc
	and pe=$factura_pexp
	and idempresa=$idempresa 
	order by ano desc 
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si no existe inserta
    if (intval($rs->fields['idsuc']) == 0) {

        $consulta = "
		INSERT INTO lastcomprobantes
		(idsuc, factura, numfac,  ano, pe, numhoja, hojalevante, idempresa)
		values
		($factura_suc, $factura_completa, $numfac, $ano, $factura_pexp, 0, '', $idempresa)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    // registra el log
    $consulta = "
	INSERT INTO lastcomprobantes_log
	(registrado_por, registrado_el, idsuc, pe, ano, numfac_ant, numfac_new, numero_nc_ant, numero_nc_new) 
	select 
	$idusu, '$ahora', idsuc, pe, ano, numfac, $numfac, numero_nc, numero_nc
	from lastcomprobantes
	where 
	idsuc=$factura_suc
	and pe=$factura_pexp
	and idempresa=$idempresa 

	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // cambia
    $consulta = "
	update lastcomprobantes
	set
		numfac=$numfac,
		factura=$factura_completa
	where 
	idsuc=$factura_suc
	and pe=$factura_pexp
	and idempresa=$idempresa 

	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = [
        'registrado' => 'S'
    ];
    return $res;

}
