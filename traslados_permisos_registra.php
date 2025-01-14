 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "313";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";

$idusuario = intval($_POST['idusuario']);
$iddeposito = intval($_POST['iddeposito']);
$direccion = substr($_POST['direccion'], 0, 1);

if ($idusuario == 0) {
    $valido = "N";
    $errores = "-No se indico el usuario.".$saltolinea;
}
if ($iddeposito == 0) {
    $valido = "N";
    $errores = "-No se indico el deposito.".$saltolinea;
}
if ($direccion != 'E' && $direccion != 'S') {
    $valido = "N";
    $errores = "-No se indico la direccion.".$saltolinea;
}

if ($valido == 'S') {

    // busca si existe en la bd
    $consulta = "
    select * 
    from traslados_permisos 
    where 
    estado = 1 
    and iddeposito = $iddeposito 
    and idusuario = $idusuario
    limit 1;
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($direccion == 'E') {
        $valor_viejo = $rs->fields['entrante'];
        if ($valor_viejo == 'S') {
            $valor_nuevo = "N";
        } else {
            $valor_nuevo = "S";
        }
        $setadd = "
        entrante = '$valor_nuevo'
        ";
    }
    if ($direccion == 'S') {
        $valor_viejo = $rs->fields['saliente'];
        if ($valor_viejo == 'S') {
            $valor_nuevo = "N";
        } else {
            $valor_nuevo = "S";
        }
        $setadd = "
        saliente = '$valor_nuevo'
        ";
    }
    // si existe actualiza
    if ($rs->fields['idtraspermi'] > 0) {


        // registra en la base permitido si o no segun corresponda
        $consulta = "
        update traslados_permisos
        set
        $setadd
        where
        iddeposito = $iddeposito
        and idusuario = $idusuario
        and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si no existe registra
    } else {
        if ($direccion == 'E') {
            $entrante = "'S'";
            $saliente = "'N'";
        }
        if ($direccion == 'S') {
            $entrante = "'N'";
            $saliente = "'S'";
        }

        // registra en la base permitido si o no segun corresponda
        $consulta = "
        insert into traslados_permisos 
        (iddeposito, idusuario, entrante, saliente, registrado_por, registrado_el)
         values 
        ($iddeposito, $idusuario, $entrante, $saliente, $idusu, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

}


// busca en la bd el nuevo valor
$consulta = "
select * 
from traslados_permisos 
where 
estado = 1 
and iddeposito = $iddeposito 
and idusuario = $idusuario
limit 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$checked = "";
$permitido = "N";
if ($direccion == 'E') {
    if ($rs->fields['entrante'] == 'S') {
        $checked = "checked";
        $permitido = "S";
    }
    $html_checkbox = '<input name="entrante" id="entrante_'.$iddeposito.'" type="checkbox" value="S" class="js-switch" onChange="registra_permiso(\''.$direccion.'\','.$iddeposito.'); " '.$checked.' />';
}
if ($direccion == 'S') {
    if ($rs->fields['saliente'] == 'S') {
        $checked = "checked";
        $permitido = "S";
    }
    $html_checkbox = '<input name="saliente" id="saliente_'.$iddeposito.'" type="checkbox" value="S" class="js-switch" onChange="registra_permiso(\''.$direccion.'\','.$iddeposito.'); " '.$checked.' />';
}



// genera array con los datos
$arr = [
    'html_checkbox' => $html_checkbox,
    'permitido' => $permitido, // permitido si o no
    'valido' => $valido,
    'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;

?>
