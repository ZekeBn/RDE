<?php

function aduana_add($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idusu;


    // recibe parametros
    $descripcion = $parametros_array['descripcion'];
    $idpais = $parametros_array['idpais'];
    $idpto = $parametros_array['iddepartamento'];
    $idciudad = $parametros_array['idciudad'];
    $registrado_por = $idusu;
    $estado = 1;




    // validaciones basicas
    $valido = "S";
    $errores = "";



    if (trim($descripcion) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $idempresa = 1;
        $idaduana = select_max_id_suma_uno("aduana", "idaduana")["idaduana"];
        $consulta = "
		insert into aduana
		(idaduana, descripcion, idpais, idempresa, idpto, idciudad, registrado_por, estado)
		values
		($idaduana, $descripcion, $idpais, $idempresa, $idpto, $idciudad, $registrado_por, $estado)
		";

        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }
    return ["error" => $errores,"valido" => $valido];


}
function aduana_edit($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idusu;




    // recibe parametros
    $descripcion = $parametros_array['descripcion'];
    $idpais = $parametros_array['idpais'];
    $idpto = $parametros_array['iddepartamento'];
    $idciudad = $parametros_array['idciudad'];
    $idaduana = $parametros_array['idaduana'];
    $registrado_por = $idusu;
    $estado = 1;


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($descripcion) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {
        $consulta = "
		update aduana
		set
			descripcion=$descripcion,
			idpais=$idpais,
			idpto=$idpto,
			idciudad=$idciudad,
            registrado_por=$registrado_por
		where
			idaduana = $idaduana
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }
    return ["error" => $errores,"valido" => $valido];
}
function aduana_delete($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idusu;


    // recibe parametros
    // recibe parametros
    $idaduana = $parametros_array['idaduana'];


    // validaciones basicas
    $valido = "S";
    $errores = "";

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update aduana
		set
			estado = 6,
			borrado_por = $idusu,
			borrado_el = '$ahora'
		where
			idaduana = $idaduana
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: aduana.php");
        exit;

    }
}
