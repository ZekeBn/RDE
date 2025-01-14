<?php

function cn_conceptos_add($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idgrupo = antisqlinyeccion($parametros_array['idgrupo'], "int");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $borrable = antisqlinyeccion($parametros_array['borrable'], "text");
    $solo_master_fran = antisqlinyeccion($parametros_array['solo_master_fran'], "text");
    $permite_carga_manual = antisqlinyeccion($parametros_array['permite_carga_manual'], "text");


    if (intval($idgrupo) == 0) {
        $valido = "N";
        $errores .= " - El campo idgrupo no puede ser cero o nulo.<br />";
    }
    if (trim($descripcion) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    /*
    borrado_por
    */
    /*
    borrado_el
    */
    if (trim($borrable) == '') {
        $valido = "N";
        $errores .= " - El campo borrable no puede estar vacio.<br />";
    }
    if (trim($solo_master_fran) == '') {
        $valido = "N";
        $errores .= " - El campo solo_master_fran no puede estar vacio.<br />";
    }
    if (trim($permite_carga_manual) == '') {
        $valido = "N";
        $errores .= " - El campo permite_carga_manual no puede estar vacio.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $idconcepto = select_max_id_suma_uno("cn_conceptos", "idconcepto")["idconcepto"];

        $consulta = "
		insert into cn_conceptos
		(idconcepto,idgrupo, descripcion, estado, registrado_por, registrado_el, borrable, solo_master_fran, permite_carga_manual)
		values
		($idconcepto, $idgrupo, $descripcion, $estado, $registrado_por, $registrado_el, $borrable, $solo_master_fran, $permite_carga_manual)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}
function cn_conceptos_delete($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], "int");
    $ahora = antisqlinyeccion($parametros_array['$ahora'], "text");
    $idusu = antisqlinyeccion($parametros_array['$idusu'], "int");




    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update cn_conceptos
		set
			estado = 6,
			borrado_por = $idusu,
			borrado_el = $ahora
		where
			idconcepto = $idconcepto
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["error" => $errores,"valido" => $valido];
}
function cn_conceptos_edit($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], "text");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");

    if (trim($idconcepto) == '') {
        $valido = "N";
        $errores .= " - El campo idconcepto no puede estar vacio.<br />";
    }

    if (trim($descripcion) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update cn_conceptos
		set
			descripcion=$descripcion
		where
			idconcepto = $idconcepto
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}
