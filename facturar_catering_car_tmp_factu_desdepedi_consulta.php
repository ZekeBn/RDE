 <?php
    require_once("includes/conexion.php");
require_once("includes/funciones.php");

// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
require_once("includes/rsusuario.php");
$consulta = "
    select *
    from tmp_detalles_eventos_factu 
    where 
    estado <> 6 and ualta = $idusu
    ";
$valido = "N";
$totalaplicado = 0;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rs->fields['idevento']) > 0) {
    $valido = "S";
    $errores = "Eventos encontrados";
    ?>



                        <?php } else {
                            $valido = "N";
                            $errores = "Eventos no encontrados";

                        };

while (!$rs->EOF) {
    $totalaplicado += $rs->fields['aplicado'];

    $rs->MoveNext();
}

//genera array con los datos
$arr = [
'valido' => $valido,
'errores' => $errores,
'aplicado' => $totalaplicado
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;



?>    


