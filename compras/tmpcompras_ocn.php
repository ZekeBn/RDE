<?php


require_once("../compras_ordenes/preferencias_compras_ordenes.php");


if ($_POST['idproveedor'] > 0) {
    $idproveedor = intval($_POST['idproveedor']);
}

$idproveedor = intval($idproveedor);
// consulta
// and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) TODO: PREFERENCIA PARA ACTIVAR
// and carga_completa='N' TODO: PREFERENCIA PARA CAMBIAR CON UNA ORDEN POR COMPRA

$preferencia_add = null;
if ($preferencias_facturas_multiples == "S") {
    // $preferencia_add=" and carga_completa='N' ";
    //por el momento el multiorden no funcionara para varias facturas

    $preferencia_add = " AND compras_ordenes.estado_orden = 1 
	and  ocnum_ref is not NULL ";

} else {
    $preferencia_add = " and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) ";
}



$consulta = "select 
ocnum,fecha,
CONCAT('Orden N.: ',ocnum,' | Fecha: ',DATE_FORMAT(compras_ordenes.fecha,\"%d/%m/%Y\")) as ocdesc,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 2 
 and compras_ordenes.idproveedor = $idproveedor
 $preferencia_add
 order by fecha desc
limit 50
 ";
//echo $consulta;
// valor seleccionado
if (isset($_POST['ocnum'])) {
    $value_selected = htmlentities($_POST['ocnum']);
} else {
    $value_selected = $ocnum;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'ocnum',
    'id_campo' => 'ocnum',

    'nombre_campo_bd' => 'ocdesc',
    'id_campo_bd' => 'ocnum',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' onclick="cambiar_tipocompra(this.value)" ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
