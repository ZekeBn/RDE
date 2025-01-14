<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
$modulo = "1";
$submodulo = "8";

require_once("includes/rsusuario.php");

$consulta = "
UPDATE marca 
JOIN (
    SELECT idmarca
    FROM (
        SELECT idmarca,
               ROW_NUMBER() OVER (PARTITION BY marca ORDER BY idmarca) as rn
        FROM marca
    ) as subquery
    WHERE rn > 1
) as duplicados ON marca.idmarca = duplicados.idmarca SET idestado = 6
";

$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
