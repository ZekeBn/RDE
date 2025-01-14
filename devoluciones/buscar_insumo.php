<?php
/*--------------------------------------------
buscando el insumo del input de compras detalles
30/5/2023
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_iva.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "613";
require_once("../includes/rsusuario.php");


function isNullAddChar($palabra)
{
    if ($palabra == "NULL") {
        return "'NULL'";
    } else {
        return $palabra;
    }
}

$valido = "S";
$error = "";
$idinsumo = $_POST['idinsumo'];
if (ctype_digit($idinsumo)) {
    $idinsumo = antisqlinyeccion(intval($_POST['idinsumo']), 'int');
} else {
    $valido = "N";
    $errores .= nl2br("El idinsumo no puede contener letras.<br>");
}

// si todo es correcto actualiza
if ($valido == "S") {
    $buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $id_cajas_edi = intval($rsd->fields['id_medida']);

    $buscar = "
	Select idinsumo,descripcion,maneja_lote,idproducto
	(	select nombre
	 	from medidas 
		where id_medida=insumos_lista.idmedida and estado=1
	) as medida,
	cant_caja_edi,cant_medida2,cant_medida3,idmedida2,idmedida3,idmedida,
	(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida2) as medida2,
	(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida3) as medida3
	from insumos_lista 
	where 
	estado='A' 
	and idempresa = $idempresa
	and hab_compra=1 
	and idinsumo=$idinsumo";
    // $buscar="Select  * from insumos_lista where idinsumo=$idinsumo";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $descripcion = trim(antixss($rsd->fields['descripcion']));
    $medida = trim(antixss($rsd->fields['medida']));
    $medida2 = trim(antixss($rsd->fields['medida2']));
    $medida3 = trim(antixss($rsd->fields['medida3']));
    $idinsumo = intval(antixss($rsd->fields['idinsumo']));
    $idmedida = intval(antixss($rsd->fields['idmedida']));
    $idmedida2 = intval(antixss($rsd->fields['idmedida2']));
    $idmedida3 = intval(antixss($rsd->fields['idmedida3']));
    $cant_medida2 = floatval(antixss($rsd->fields['cant_medida2']));
    $cant_medida3 = floatval(antixss($rsd->fields['cant_medida3']));
    $cant_medida_edi = floatval(antixss($rsd->fields['cant_caja_edi']));
    $usa_lote = (antixss($rsd->fields['maneja_lote']));
    $idproducto = (antixss($rsd->fields['idproducto']));
    // $id_cajas_edi
    if ($idinsumo > 0) {
        $respuesta = [
            "success" => true,
            "medida" => $medida,
            "medida2" => $medida2,
            "medida3" => $medida3,
            "descripcion" => $descripcion,
            "idinsumo" => $idinsumo,
            "idmedida" => $idmedida,
            "idmedida2" => $idmedida2,
            "idmedida3" => $idmedida3,
            "id_cajas_edi" => $id_cajas_edi,
            "cant_medida2" => $cant_medida2,
            "cant_medida3" => $cant_medida3,
            "cant_caja_edi" => $cant_medida_edi,
            "usa_lote" => $usa_lote,
            "idproducto" => $idproducto

        ];
    } else {
        $errores .= nl2br("El idinsumo no existe .<br>");
        $respuesta = [
            "success" => false,
            "errores" => $errores
        ];

    }
    echo json_encode($respuesta);
} else {
    $respuesta = [
        "success" => false,
        "errores" => $errores
    ];
    echo json_encode($respuesta);
}
