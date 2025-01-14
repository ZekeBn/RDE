<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

function limpia_json($texto)
{
    global $saltolinea;

    $texto = str_replace($saltolinea, '', $texto);
    $texto = utf8_encode($texto);
    $respuesta = $texto;
    return $respuesta;
}


require_once("app_rsusuario.php");


if (trim($_POST['fec_actu']) != '') {
    $whereadd = " and productos.actualizado >= '$fec_actu' ";
}


$consulta = "
select url_local, ruta_img_web, ruta_img_web_dir from preferencias limit 1;
";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$RUTA_IMG_WEB = trim($rspref->fields['ruta_img_web']);
$RUTA_IMG_WEB_DIR = trim($rspref->fields['ruta_img_web_dir']);
$URL_LOCAL = trim($rspref->fields['url_local']);

$consulta = "
SELECT idprod_serial as idproducto, descripcion, idcategoria, idsubcate, idtipoproducto,
(select categorias.nombre from categorias where id_categoria = productos.idcategoria) as categoria,
(select sub_categorias.descripcion from sub_categorias where idsubcate = productos.idsubcate) as subcategoria,
productos_sucursales.precio, productos.actualizado, productos.combinado_minitem, productos.combinado_maxitem, productos.combinado_tipoprecio
FROM productos
inner join productos_sucursales on idproducto = productos.idprod_serial
where
productos.borrado = 'N'
and productos_sucursales.activo_suc = 1
and productos_sucursales.idsucursal = 1
and productos_sucursales.idempresa = 1
and productos.idtipoproducto <> 3
$whereadd
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$i = 0;
while (!$rs->EOF) {
    $idproducto = $rs->fields['idproducto'];
    $idtipoproducto = $rs->fields['idtipoproducto'];

    //limpiar variables
    $combinado = [];
    $combo = [];
    $agregados = [];
    $sacados = [];

    $productos_permitidos = "";
    // combos
    if ($idtipoproducto == 2) {
        $consulta = "
		select idlistacombo, nombre, idproducto, cantidad 
		from combos_listas 
		where 
		estado <> 6
		and idproducto = $idproducto
		";
        $rscombo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $i_combo = 0;
        while (!$rscombo->EOF) {
            $idlistacombo = $rscombo->fields['idlistacombo'];
            $consulta = "
			select idproducto
			from combos_listas_det
			where
			idlistacombo = $idlistacombo
			";
            $rscombodet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $productos_permitidos = ""; // no sacar de aca, debe estar aca y arriba tambien
            while (!$rscombodet->EOF) {
                $productos_permitidos .= $rscombodet->fields['idproducto'].',';
                $rscombodet->MoveNext();
            }
            $productos_permitidos = substr($productos_permitidos, 0, -1);
            $combo[$i_combo]['idlistacombo'] = $rscombo->fields['idlistacombo'];
            $combo[$i_combo]['nombre'] = limpia_json($rscombo->fields['nombre']);
            $combo[$i_combo]['cantidad'] = $rscombo->fields['cantidad'];
            $combo[$i_combo]['productos_permitidos'] = $productos_permitidos;
            $i_combo++;
            $rscombo->MoveNext();
        }
    } // if($idtipoproducto == 2){

    // combinados
    $productos_permitidos = "";
    if ($idtipoproducto == 4) {
        $consulta = "
		select idproducto_partes
		from productos_combinado 
		where 
		idproducto_principal = $idproducto
		";
        $rscombinado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rscombinado->EOF) {
            $productos_permitidos .= $rscombinado->fields['idproducto_partes'].',';
            $rscombinado->MoveNext();
        }
        $productos_permitidos = substr($productos_permitidos, 0, -1);
        $combinado = [
            'productos_permitidos' => $productos_permitidos,
            'minimo_items' => $rs->fields['combinado_minitem'],
            'maximo_items' => $rs->fields['combinado_maxitem'],
            'tipo_precio' => $rs->fields['combinado_tipoprecio']
        ];
    }

    // agregados
    $agregados = [];
    $consulta = "
	SELECT agregado.idproducto, agregado.idingrediente, agregado.alias, 
	agregado.precio_adicional, insumos_lista.descripcion, agregado.cantidad, 
	medidas.nombre,insumos_lista.idproducto as idproducto_ing
	FROM agregado 
	inner join ingredientes on ingredientes.idingrediente = agregado.idingrediente
	inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
	inner join medidas on insumos_lista.idmedida=medidas.id_medida
	WHERE
	agregado.idproducto = $idproducto
	and insumos_lista.idproducto > 0
	and insumos_lista.estado = 'A'
	";
    //echo $consulta;
    $rsagregado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $i_agregado = 0;
    if ($rsagregado->fields['idproducto_ing'] > 0) {
        while (!$rsagregado->EOF) {
            $agregados[$i_agregado]['idproducto_ing'] = $rsagregado->fields['idproducto_ing'];
            $agregados[$i_agregado]['precio_adicional'] = $rsagregado->fields['precio_adicional'];
            $i_agregado++;
            $rsagregado->MoveNext();
        }
    }


    // sacados
    $sacados = [];
    $consulta = "
	SELECT recetas_detalles.idprod as idproducto, recetas_detalles.ingrediente as idingrediente, recetas_detalles.alias,  insumos_lista.descripcion, recetas_detalles.cantidad, medidas.nombre,
	insumos_lista.idinsumo as idinsumo
	FROM recetas_detalles
	inner join ingredientes on ingredientes.idingrediente = recetas_detalles.ingrediente
	inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
	inner join medidas on insumos_lista.idmedida=medidas.id_medida
	WHERE
	recetas_detalles.idprod = $idproducto
	and recetas_detalles.sacar = 'S'
	";
    //echo $consulta;exit;
    $rssacado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $i_sacado = 0;
    while (!$rssacado->EOF) {
        $sacados[$i_sacado]['idinsumo'] = $rssacado->fields['idinsumo'];
        $sacados[$i_sacado]['insumo_desc'] = 'SIN '.limpia_json($rssacado->fields['descripcion']);
        $i_sacado++;
        $rssacado->MoveNext();
    }

    /*$img_prod_app=$rs->fields['img'];
    if(!file_exists($img_prod_app)){
        $img_prod_app="gfx/productos/";
    }*/
    $idproducto = $rs->fields['idproducto'];
    $img_prod_app = "https://".$URL_LOCAL."/ecom/gfx/fotosweb/wprod_".$idproducto.".jpg";
    $img_dir = "../".$RUTA_IMG_WEB_DIR."/wprod_".$idproducto.".jpg";
    //echo $img_dir;echo $img_prod_app;exit;
    if (!file_exists($img_dir)) {
        $img_prod_app = "";
    }

    //$productos_ar[]=$rs->fields;
    $productos_ar[$i] = [
        'idproducto' => $idproducto,
        'idtipoproducto' => $idtipoproducto,
        'producto' => limpia_json($rs->fields['descripcion']),
        'idcategoria' => $rs->fields['idcategoria'],
        'categoria' => limpia_json($rs->fields['categoria']),
        'idsubcategoria' => $rs->fields['idsubcate'],
        'subcategoria' => limpia_json($rs->fields['subcategoria']),
        'precio_venta' => floatval($rs->fields['precio']),
        'actualizado' => $rs->fields['actualizado'],
        'combo' => $combo,
        'combinado' => $combinado,
        'agregados' => $agregados,
        'sacados' => $sacados,

    ];
    $productos_ar[$i]['img_prod_app'] = $img_prod_app;
    //print_r($productos_ar);exit;
    $i++;
    $rs->MoveNext();
}


//print_r($productos_ar);exit;
$respuesta = [
    'status' => 200,
    'data' => $productos_ar
];

//print_r($respuesta);exit;

// convierte a formato json
$respuesta = json_encode($respuesta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
