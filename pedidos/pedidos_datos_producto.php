<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "619";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$idcliente = $_POST['idproducto'];

$consulta = "
select productos_listaprecios.precio, productos.* 
from productos 
inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod 
	and productos_listaprecios.estado = 1
where idprod = $idproducto";

$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$response = [];
while ($row = $rsc->fetch(PDO::FETCH_ASSOC)) {
    foreach ($row as $key => $value) {
        $response[$key] = $value;
    }
}
echo json_encode($response);
