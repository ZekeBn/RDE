<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");
$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);


$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);



//Vemos si posee activo el sistema contable o no
$consulta = "Select usa_concepto, master_franq,contabilidad from preferencias limit 1";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_concepto = $rspref->fields['usa_concepto'];
$master_franq = $rspref->fields['master_franq'];
$contabilidad = intval($rspref->fields['contabilidad']);
//echo $contabilidad;exit;

//Categorias
$buscar = "Select * from categorias where idempresa = $idempresa order by nombre ASC";
$rscate2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$idinsu = intval($_GET['id']);
if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}
$buscar = "
select *,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
(select nombre from medidas where id_medida = insumos_lista.idmedida2 ) as medida2,
(select nombre from medidas where id_medida = insumos_lista.idmedida3 ) as medida3,
(select descripcion from productos where idprod_serial = insumos_lista.idproducto) as producto,
(select descripcion from insumos_lista as insm where insumos_lista.idcod_alt = insm.idinsumo) as cod_alt_nombre,
idtipoiva as idtipoiva_compra
from insumos_lista 
where 
 estado = 'A' 
 and idinsumo=$idinsu 
limit 1
 ";
$rsconecta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idinsu = intval($rsconecta->fields['idinsumo']);
$idproducto = intval($rsconecta->fields['idproducto']);
$idcpr = intval($rsconecta->fields['idcentroprod']);
$idfob = intval($rsconecta->fields['cod_fob']);

if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}


$buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//var parametros='idi='+id+'&desc='+descripcion+'&pr='+produce+'&tipoiva='+tipoiva;
/*if($_POST['edita'] == 's'){

    //print_r($_POST);
    // obtiene variables
    $descripcion=antisqlinyeccion($_POST['desc'],"text");
    $produce=antisqlinyeccion($_POST['pr'],"text");
    $tipoiva=antisqlinyeccion($_POST['tipoiva'],"int");
    $gr=intval($_POST['gr']);
    $consulta="
    update insumos_lista
    set
    descripcion = $descripcion,
    produccion = $produce,
    tipoiva = $tipoiva,
    idgrupoinsu=$gr
    where
    idinsumo=$idinsu
    and idempresa = $idempresa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

    //echo '<h1>Editado Correctamente!</h1> <meta http-equiv="refresh" content="0; url=gest_insumos.php"> ';
    header("location: gest_insumos.php");
    exit;

}*/


$buscar = "SELECT in1.idinsumo,in1.descripcion,
(select count(*) from insumos_lista as in2 where in2.idcod_alt = in1.idinsumo ) as cant_codigos_alt
FROM insumos_lista as in1 where
(in1.maneja_cod_alt = 'N' or in1.maneja_cod_alt is null) and in1.estado = 'A'
order by cant_codigos_alt asc
";

$resultados_insumos_lista = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idinsumo = trim(antixss($rsd->fields['idinsumo']));
    $nombre = trim(antixss($rsd->fields['descripcion']));
    $cant_codigos_alt = trim(antixss($rsd->fields['cant_codigos_alt']));
    $class_cod_alt = null;
    if (intval($cant_codigos_alt) > 0) {
        $class_cod_alt = "have_cod_alt";
    }
    $resultados_insumos_lista .= "
	<a class='a_link_proveedores $class_cod_alt'  href='javascript:void(0);' data-hidden-value='$idinsumo' onclick=\"cambia_cod_alt($idinsumo, '$nombre');\" >[$idinsumo]-$nombre</a>
	";

    $rsd->MoveNext();
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $idinsumo = antisqlinyeccion($idinsu, "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $idcategoria = antisqlinyeccion('', "int");
    $idsubcate = antisqlinyeccion('', "int");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
    $idmedida2 = antisqlinyeccion($_POST['idmedida2'], "int");
    $idmedida3 = antisqlinyeccion($_POST['idmedida3'], "int");
    $cant_medida2 = antisqlinyeccion($_POST['cant_medida2'], "int");
    $cant_medida3 = antisqlinyeccion($_POST['cant_medida3'], "int");

    $produccion = antisqlinyeccion(1, "int");
    $costo = antisqlinyeccion($_POST['costo'], "text");
    $idtipoiva_compra = antisqlinyeccion($_POST['idtipoiva_compra'], "int");
    $paquete = antisqlinyeccion($_POST['paquete'], "text");
    $cant_paquete = antisqlinyeccion($_POST['cant_paquete'], "float");
    $idgrupoinsu = antisqlinyeccion($_POST['idgrupoinsu'], "int");
    $ajuste = antisqlinyeccion($_POST['ajuste'], "text");
    //$idproducto=antisqlinyeccion($_POST['idproducto'],"int");
    $mueve_stock = antisqlinyeccion('S', "text");
    $hab_compra = antisqlinyeccion($_POST['hab_compra'], "int");
    $hab_invent = antisqlinyeccion($_POST['hab_invent'], "int");
    $idconcepto = antisqlinyeccion($_POST['idconcepto'], "int");
    $aplica_regalia = antisqlinyeccion($_POST['aplica_regalia'], "text");
    $solo_conversion = antisqlinyeccion($_POST['solo_conversion'], "int");
    $respeta_precio_sugerido = antisqlinyeccion(substr($_POST['respeta_precio_sugerido'], 0, 1), "text");
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $acepta_devolucion = antisqlinyeccion($_POST['acepta_devolucion'], "text");
    //$acuerdo_comercial=antisqlinyeccion($_POST['acuerdo_comercial'],"int");
    $idcategoria = antisqlinyeccion($_POST['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($_POST['idsubcate'], "int");
    $cuentacontable = antisqlinyeccion($_POST['cuentacont'], "int");
    $centroprod = intval($_POST['cpr']);
    $idagrupacionprod = antisqlinyeccion($_POST['idagrupacionprod'], "int");
    $rendimiento_porc = antisqlinyeccion($_POST['rendimiento_porc'], "float");
    $idsubcate_sec = antisqlinyeccion($_POST['idsubcate_sec'], "int");

    //print_r($_POST);


    //opcionales
    // TODO: poner preferencias
    $cant_caja_edi = antisqlinyeccion($_POST['cant_caja_edi'], "float");
    $largo = antisqlinyeccion($_POST['largo'], "float");
    $ancho = antisqlinyeccion($_POST['ancho'], "float");
    $alto = antisqlinyeccion($_POST['alto'], "float");
    $peso = antisqlinyeccion($_POST['peso'], "float");
    $cod_fob = antisqlinyeccion($_POST['cod_fob'], "float");
    $rs = antisqlinyeccion($_POST['rs'], "text");
    $rspa = antisqlinyeccion($_POST['rspa'], "text");
    $hab_desc = antisqlinyeccion($_POST['hab_desc'], "text");
    $modifica_precio = antisqlinyeccion($_POST['modifica_precio'], "text");
    $maneja_lote = antisqlinyeccion($_POST['maneja_lote'], "text");
    $regimen_turismo = antisqlinyeccion($_POST['regimen_turismo'], "text");
    $maneja_cod_alt = antisqlinyeccion($_POST['maneja_cod_alt'], "text");
    $idcod_alt = antisqlinyeccion($_POST['idcod_alt'], "int");

    $idpais = antisqlinyeccion($_POST['idpais'], "int");
    $dias_utiles = antisqlinyeccion($_POST['dias_utiles'], "float");
    $dias_stock = antisqlinyeccion($_POST['dias_stock'], "float");
    $bar_code = antisqlinyeccion($_POST['bar_code'], "text");
    // validaciones basicas
    $valido = "S";
    $errores = "";



    if (intval($idinsu) == 0) {
        $valido = "N";
        $errores .= " - El campo idinsumo no puede ser cero o nulo.<br />";
    }

    // campo no obligatorio por base de datos, pero quizas tu necesites que sea obligatorio
    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    if ($master_franq == 'S') {
        if (trim($_POST['aplica_regalia']) != 'N' && trim($_POST['aplica_regalia']) != 'S') {
            $valido = "N";
            $errores .= " - El campo aplica regalia no puede estar vacio.<br />";
        }

    } else {
        $aplica_regalia = antisqlinyeccion('S', "text");
    }



    /*if(intval($_POST['produccion']) == 0){
        $valido="N";
        $errores.=" - El campo produccion no puede ser cero o nulo.<br />";
    }*/
    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        if (trim($_POST['idtipoiva_compra']) == '') {
            $valido = "N";
            $errores .= " - El campo iva compra no puede estar vacio.<br />";
        }
    } else {
        $idtipoiva_compra = 0;
        $tipoiva_compra = 0;
    }
    /*if(trim($_POST['mueve_stock']) != 'S' && trim($_POST['mueve_stock']) != 'N'){
        $valido="N";
        $errores.=" - Debe indicar si mueve stock.<br />";
    }*/
    if (trim($_POST['hab_compra']) != '1' && trim($_POST['hab_compra']) != '0') {
        $valido = "N";
        $errores .= " - Debe indicar si se habilita la compra.<br />";
    }
    if (trim($_POST['hab_invent']) != '1' && trim($_POST['hab_invent']) != '0') {
        $valido = "N";
        $errores .= " - Debe indicar si se habilita el inventario.<br />";
    }
    /*if(trim($_POST['acuerdo_comercial']) != '1' && trim($_POST['acuerdo_comercial']) != '0'){
        $valido="N";
        $errores.=" - Debe indicar si existe un acuerdo comercial con el proveedor.<br />";
    }*/
    if ($_POST['hab_compra'] > 0) {
        if (intval($_POST['solo_conversion']) == 0) {
            if (intval($_POST['hab_invent']) == 0) {
                $valido = "N";
                $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
            }
        }
    }
    if (intval($_POST['solo_conversion']) > 0) {
        if (intval($_POST['hab_invent']) > 0) {
            $valido = "N";
            $errores .= " - Cuando es un producto de conversion no debe habilitarse inventario.<br />";
        }
    }
    if ($_POST['respeta_precio_sugerido'] != 'S' && $_POST['respeta_precio_sugerido'] != 'N') {
        $valido = "N";
        $errores .= " - Debe indicar si se respeta o no precio sugerido.<br />";
    }

    /*
        // campo no obligatorio por base de datos, pero quizas tu necesites que sea obligatorio
        if(trim($_POST['paquete']) == ''){
            $valido="N";
            $errores.=" - El campo paquete no puede estar vacio.<br />";
        }
    */

    /*
        // campo no obligatorio por base de datos, pero quizas tu necesites que sea obligatorio
        if(floatval($_POST['cant_paquete']) <= 0){
            $valido="N";
            $errores.=" - El campo cant_paquete no puede ser cero o negativo.<br />";
        }
    */



    if (intval($_POST['idgrupoinsu']) == 0) {
        $valido = "N";
        $errores .= " - El campo idgrupoinsu no puede ser cero o nulo.<br />";
    }

    // conversiones
    if (trim($_POST['mueve_stock']) != 'N') {
        $mueve_stock = antisqlinyeccion('S', "text");
    }


    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        // iva compra
        $consulta = "
		select * 
		from tipo_iva
		where 
		idtipoiva = $idtipoiva_compra
		";
        $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tipoiva_compra = intval($rsiva->fields['iva_porc']);
        $idtipoiva_compra = intval($rsiva->fields['idtipoiva']);
        // si es un producto
        if ($idproducto > 0) {

            $consulta = "
			select idtipoiva from productos where idprod_serial = $idproducto limit 1;
			";
            $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtipoiva = intval($rsp->fields['idtipoiva']);

            // iva venta
            $consulta = "
			select * 
			from tipo_iva
			where 
			idtipoiva = $idtipoiva
			";
            $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $tipoiva = intval($rsiva->fields['iva_porc']);
            $idtipoiva = intval($rsiva->fields['idtipoiva']);
            $iguala_compra_venta = antisqlinyeccion($rsiva->fields['iguala_compra_venta'], "text");



            if ($iguala_compra_venta == 'S') {
                if ($idtipoiva <> $idtipoiva_compra) {
                    $valido = "N";
                    $errores .= "-El iva compra y venta debe ser el mismo para el tipo de iva venta seleccionado.<br />";
                }
            }

        }
    }



    if ($usa_concepto == 'S') {
        if (intval($_POST['idconcepto']) == 0) {
            $errores .= "* Debe indicar el concepto del articulo.<br />";
            $valido = 'N';
        }
    } else {
        $idconcepto = antisqlinyeccion('', "int");
    }

    if ($contabilidad == 1) {
        if (trim($_POST['hab_compra']) == '1') {
            if (intval($_POST['cuentacont']) == 0) {
                $valido = "N";
                $errores .= "- Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
            }
        }
    }

    if (floatval($_POST['rendimiento_porc']) <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['rendimiento_porc']) > 100) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser mayor a 100.<br />";
    }


    // conversiones
    $solo_conversion = intval($_POST['solo_conversion']);
    // para que convierta a null y no cargar la BD con ceros
    if ($solo_conversion == 0) {
        $solo_conversion = "";
    } else {
        // si es solo conversion no habilita inventario
        $hab_compra = 1;
        $hab_invent = 0;
    }
    $solo_conversion = antisqlinyeccion($solo_conversion, "int");


    // si todo es correcto actualiza
    if ($valido == "S") {


        // busca si existe en el log
        $consulta = "
		select * from insumos_lista_log where idinsumo = $idinsu limit 1;
		";
        $rsinsulog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no existe crea
        if (intval($rsinsulog->fields['idinsumo']) == 0) {
            $consulta = "
			insert into insumos_lista_log 
			(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
			log_registrado_el,log_registrado_por, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
			rendimiento_porc
			)
			select
			idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
			fechahora, 0, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
			rendimiento_porc
			from insumos_lista
			where 
			idinsumo = $idinsu
			limit 1;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }



        $consulta = "
		update insumos_lista
		set
			descripcion=$descripcion,
			produccion=$produccion,
			idtipoiva=$idtipoiva_compra,
			tipoiva=$tipoiva_compra,
			mueve_stock=$mueve_stock,
			paquete=$paquete,
			cant_paquete=$cant_paquete,
			estado='A',
			idgrupoinsu=$idgrupoinsu,
			ajuste='N', 
			hab_compra=$hab_compra,
			hab_invent=$hab_invent,
			idconcepto=$idconcepto,
			aplica_regalia=$aplica_regalia,
			solo_conversion=$solo_conversion,
			respeta_precio_sugerido=$respeta_precio_sugerido,
			idproveedor=$idproveedor,
			acepta_devolucion = $acepta_devolucion,
			idcategoria=$idcategoria,
			idsubcate=$idsubcate,
			idcentroprod=$centroprod,
			idplancuentadet=$cuentacontable,
			idagrupacionprod=$idagrupacionprod,
			rendimiento_porc=$rendimiento_porc,
			cant_caja_edi=$cant_caja_edi,
			largo=$largo,
			ancho=$ancho,
			alto=$alto,
			peso=$peso,
			cod_fob=$cod_fob,
			rs=$rs,
			rspa=$rspa,
			hab_desc=$hab_desc,
			modifica_precio=$modifica_precio,
			maneja_lote=$maneja_lote,
			regimen_turismo=$regimen_turismo,
			maneja_cod_alt=$maneja_cod_alt,
			idcod_alt=$idcod_alt,
			costo=$costo, 
			idpais=$idpais, 
			dias_utiles=$idpais,
			dias_stock=$dias_stock,
			bar_code=$bar_code,
			idsubcate_sec=$idsubcate_sec
		where
			idempresa=$idempresa
			and idinsumo = $idinsu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // inserta en el log luego de actualizar
        $consulta = "
		insert into insumos_lista_log 
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
		log_registrado_el,log_registrado_por, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
		rendimiento_porc
		)
		select
		idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, hab_compra, hab_invent, borrado_el, borrado_por, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno,
		'$ahora',$idusu, $acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
		rendimiento_porc
		from insumos_lista
		where 
		idinsumo = $idinsu
		limit 1;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // si tiene vinculado un producto
        $tipoiva = intval($tipoiva);
        if ($idproducto > 0) {
            $consulta = "
			update productos
			set 
			descripcion = $descripcion, 
			tipoiva=$tipoiva,
			idcategoria = $idcategoria,
			idsubcate = $idsubcate
			where
			idprod_serial = $idproducto
			and idempresa = $idempresa
			and idprod_serial in (select idproducto from insumos_lista where idinsumo = $idinsumo)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        // si es solo conversion
        if ($solo_conversion == 1) {
            // desactiva para la venta
            $consulta = "
			update productos_sucursales set activo_suc = 0 where idproducto = $idproducto
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        //Si varió el articulo contable, actualizamos segun corresponda


        if ($contabilidad == 1) {
            $idcuentacontable = intval($_POST['cuentacont']);
            //echo $idcuentacontable;exit;
            if ($idcuentacontable > 0) {
                $buscar = "Select idsercuenta,trim(cuenta)as cuentacont from cn_articulos_vinculados 
				inner join cn_plancuentas_detalles on cn_plancuentas_detalles.idserieun=cn_articulos_vinculados.idsercuenta
				where cn_articulos_vinculados.idinsumo=$idinsumo ";
                $rscon1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $actual = trim($rscon1->fields['cuentacont']);
                //echo $actual;exit;
                if ($actual != $idcuentacontable) {

                    $buscar = "Select * from cn_plancuentas_detalles where cuenta=$idcuentacontable and estado <> 6";
                    $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idplan = intval($rsvv->fields['idplan']);
                    $idsercuenta = intval($rsvv->fields['idserieun']);


                    //actualizamos
                    if ($actual != '') {
                        $update = "update cn_articulos_vinculados set idsercuenta=$idsercuenta,idplancuenta=$idplan where idinsumo=$idinsumo ";
                        $conexion->Execute($update) or die(errorpg($conexion, $update));
                    } else {
                        //insertar
                        $insertar = "Insert into cn_articulos_vinculados
						(idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
						values 
						($idinsumo,$idplan,$idsercuenta,current_timestamp,$idusu)";
                        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                    }

                }


            }

        }




        header("location: gest_insumos.php");
        exit;

    }

}
$buscar = "
Select * 
from productos 
where 
idempresa=$idempresa 
and borrado = 'N' 
and idprod_serial not in (select idproducto from insumos_lista where idinsumo <> $idinsu and idproducto is not null and idempresa=$idempresa)
order by descripcion asc";
//echo $buscar;
$gr2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$rsminip = $rsconecta;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function verificar_concepto(){
		var concepto = $('#idconcepto').find("option:selected").text()
		if(concepto == "DESPACHO"||concepto == "FLETE" ){
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'none');
		}else{
			$('#idtipoiva_compra').val("");
			$("#idtipoiva_compra").css('display', 'block');
		}
	}
	function cambia_prov(){
		idproveedor = $("#idproveedor").val();
		idfob = <?php echo $idfob ? $idfob : 0; ?>;
		var parametros = {
					"idproveedor"    : idproveedor,
					"idfob": idfob
					
			};
			$.ajax({
				data:  parametros,
				url:   'dropdown_proveedor_fob.php',
				type:  'post',
				beforeSend: function () {
				},
				success:  function (response) {
					$("#box_cod_fob").html(response);
					
				}
			});
	}
	function subcategorias(idcategoria){
		var direccionurl='subcate_new.php';	
		var parametros = {
		"idcategoria" : idcategoria
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#subcatebox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#subcatebox").html(response);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
	}
	function tipo_producto(idtipoproducto){
		//producto
		if(idtipoproducto == 1){	

		}
		// combo
		if(idtipoproducto == 2){	
		
		}
		// combinado
		if(idtipoproducto == 3){	
			$("#div_combinado_tipoprecio").show();
		}
		// combinado extendido
		if(idtipoproducto == 4){	
			$("#div_combinado_minitem").show();
			$("#div_combinado_maxitem").show();
			$("#div_combinado_tipoprecio").show();	
		}else{
			$("#div_combinado_minitem").hide();
			$("#div_combinado_maxitem").hide();
			if(idtipoproducto != 3){
				$("#div_combinado_tipoprecio").hide();	
			}
		}
		// agregado
		if(idtipoproducto == 5){	
		
		}
		// delivery
		if(idtipoproducto == 6){	
		
		}	
		// servicio
		if(idtipoproducto == 7){	
		
		}	
		
		
	}
	function alerta_modal(titulo,mensaje){
		$('#dialogobox').modal('show');
		$("#myModalLabel").html(titulo);
		$("#modal_cuerpo").html(mensaje);

		
	}
	function ventana_categoria(){
		var direccionurl='categoria_prod_add.php';	
		var parametros = {
		"add"        : 'N'
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Categoria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#modal_cuerpo").html(response);	
				$('#dialogobox').modal('show');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function ventana_subcategoria(){
		var direccionurl='subcategoria_prod_add.php';	
		var parametros = {
		"add"        : 'N'
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Sub-Categoria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#modal_cuerpo").html(response);	
				$('#dialogobox').modal('show');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function agregar_categoria(){
		var direccionurl='categoria_prod_add.php';
		var categoria = $("#categoria").val();	
		var parametros = {
		"add"        : 'S',
		"categoria"  : categoria
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Categoria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					recargar_categoria(obj.idcategoria);
					$("#modal_cuerpo").html('');
					$('#dialogobox').modal('hide');

				}else{
					$("#modal_cuerpo").html(response);	
				}

			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function agregar_subcategoria(){
		var direccionurl='subcategoria_prod_add.php';
		var categoria = $("#categoria").val();	
		var subcategoria = $("#subcategoria").val();
		var parametros = {
		"add"        : 'S',
		"categoria"  : categoria,
		"subcategoria"  : subcategoria
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Sub-Categoria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					recargar_categoria(obj.idcategoria);
					recargar_subcategoria(obj.idcategoria,obj.idsubcategoria);
					$("#modal_cuerpo").html('');
					$('#dialogobox').modal('hide');

				}else{
					$("#modal_cuerpo").html(response);	
				}

			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function recargar_categoria(idcategoria){
		var direccionurl='cate_new.php';
		var parametros = {
		"idcategoria" : idcategoria,
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				$("#categoriabox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#categoriabox").html(response);	
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function recargar_subcategoria(idcategoria,idsubcategoria){
		var direccionurl='subcate_new.php';
		var parametros = {
		"idcategoria" : idcategoria,
		"idsubcate" : idsubcategoria,
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				$("#subcatebox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#subcatebox").html(response);	
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function filterFunction2(event) {
		event.preventDefault();
        var pais = $("#idpais").val();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput2");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown2");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			id_pais = a[i].getAttribute('data-hidden-value');
			if(pais ){
                if ((pais == id_pais && txtValue.toUpperCase().indexOf(filter) > -1 )){
                    a[i].style.display = "block";
                }else{
                    a[i].style.display = "none";
                }
            }else{

                if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
            
		}
	}
	function myFunction2(event) {
            event.preventDefault();
            var idpais = $("#idpais").val();
            if (!idpais) {
                document.getElementById("myInput2").classList.toggle("show");
                document.getElementById("myDropdown2").classList.toggle("show");
                div = document.getElementById("myDropdown2");
                $("#myInput2").focus();
            } else {
                var div,ul, li, a, i;
               
                div = document.getElementById("myDropdown2");
                a = div.getElementsByTagName("a");
                for (i = 0; i < a.length; i++) {
                    txtValue = a[i].textContent || a[i].innerText;
                    id_pais = a[i].getAttribute('data-hidden-value');
                    if ( id_pais==idpais ) {
                        a[i].style.display = "block";
                    } else {
                        a[i].style.display = "none";
                    }
                }

                document.getElementById("myInput2").classList.toggle("show");
                document.getElementById("myDropdown2").classList.toggle("show");
                div = document.getElementById("myDropdown2");
                $("#myInput2").focus();
            }

			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			var div = $("#lista_cod_alternativo");
			var button = $("#iddepartameto");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
	function cambia_cod_alt(idinsumo,nombre){
		$('#idcod_alt').html($('<option>', {
            value: idinsumo,
            text: nombre
        }));
        
        $('#idcod_alt').val(idinsumo);
       
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
	}
	function habilitar_codigo_alternativo(valor){
		var box= $("#box_cod_alternativo");
		if (valor == "S"){
			box.css("display", "block");
		}else{
			box.css("display", "none");
		}
	}
	function ventana_codigo_origen(){
		var direccionurl='codigo_origen_modal_add.php';	
		var parametros = {
		"idproveedor"        : $("#idproveedor").val()
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Codigo Origen');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#modal_cuerpo").html(response);	
				$('#dialogobox').modal('show');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function cerrar_pop(){
		$("#dialogobox").modal("hide");
	}
	window.onload = function() {
		var idproveedor=$("#idproveedor").val();
		if (idproveedor != undefined && idproveedor != "") {
			cambia_prov();
			
		}
        $('#idcod_alt').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
    };
	function ventana_subcategoria_sec(){
		var direccionurl='subcategoria_sec_prod_add.php';	
		var parametros = {
		"add"        : 'N'
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Sub-Categoria Secundaria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#modal_cuerpo").html(response);	
				$('#dialogobox').modal('show');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function agregar_subcategoria_sec(){
		var direccionurl='subcategoria_sec_prod_add.php';
		var subcategoria_sec = $("#form_subcate_sec #subcategoria_sec").val();
		var selectedOption = $("#form_subcate_sec #idsubcate option:selected");
 		var idcategoria = selectedOption.data("hidden-value");
		var margen_seguridad = $("#form_subcate_sec #margen_seguridad").val();
		var idsubcate = $("#form_subcate_sec #idsubcate").val();
		var parametros = {
		"add"        		: 'S',
		"subcategoria_sec"  : subcategoria_sec,
		"idsubcate"  		: idsubcate,
		"margen_seguridad"	: margen_seguridad
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#myModalLabel").html('Agregar Sub-Categoria Secundaria');	
				$("#modal_cuerpo").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					console.log(obj);
					console.log(JSON.stringify(obj));
					recargar_categoria(idcategoria);
					recargar_subcategoria(idcategoria,obj.idsubcate);
					recargar_subcategoria_sec(idcategoria,obj.idsubcate,obj.idsubcate_sec);
					$("#modal_cuerpo").html('');
					$('#dialogobox').modal('hide');

				}else{
					$("#modal_cuerpo").html(response);	
				}

			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function recargar_subcategoria_sec(idcategoria,idsubcate,idsubcate_sec){
		var direccionurl='subcate_sec_new.php';
		var parametros = {
		"idcategoria" : idcategoria,
		"idsubcate" : idsubcate,
		"idsubcate_sec": idsubcate_sec
		};
		console.log(parametros);
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				$("#subcatesecbox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				$("#subcatesecbox").html(response);	
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		
	}
	function cambiar_categorias(selectElement){
		var opcionSeleccionada = selectElement.options[selectElement.selectedIndex];
		var categoria_id = opcionSeleccionada.getAttribute('data-hidden-value');
		var idsubcate = opcionSeleccionada.getAttribute('data-hidden-value2');
		$('#idcategoria').val(categoria_id);


		var direccionurl='subcate_new.php';	
		var parametros = {
		"idsubcate" : idsubcate
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#subcatebox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#subcatebox").html(response);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
		$('#idsubcate').val(idsubcate);
	}

	function subcategorias_secundarias(idsub_categoria){
		var direccionurl='subcate_sec_new.php';	
		var parametros = {
		"idsub_categoria" : idsub_categoria
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#subcatesecbox").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#subcatesecbox").html(response);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
			if (jqXHR.status === 0) {
		
				alert('No conectado: verifique la red.');
			
			} else if (jqXHR.status == 404) {
			
				alert('Pagina no encontrada [404]');
			
			} else if (jqXHR.status == 500) {
			
				alert('Internal Server Error [500].');
			
			} else if (textStatus === 'parsererror') {
			
				alert('Requested JSON parse failed.');
			
			} else if (textStatus === 'timeout') {
			
				alert('Tiempo de espera agotado, time out error.');
			
			} else if (textStatus === 'abort') {
			
				alert('Solicitud ajax abortada.'); // Ajax request aborted.
			
			} else {
			
				alert('Uncaught Error: ' + jqXHR.responseText);
			
			}
			
		});
	}
</script>
<style type="text/css">
		.have_cod_alt{
			background: #6CAD3BC4;
			color:white;
		}
		.have_cod_alt:hover{
			background: #A7D9A5 !important;
			color:white !important;
		}
		
        #lista_articulos,#lista_cod_alternativo {
            width: 100%;
        }
       
        .a_link_proveedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_proveedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_proveedores{
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }
        .dropdown_proveedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_proveedor_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }
	
</style>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Articulo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Codigo Articulo</th>
			<th align="center">Articulo</th>
            <th align="center">Producto Vinculado</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>

			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php
$rs = $rsconecta;
?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center">
            <?php if ($rsconecta->fields['idproducto'] > 0) { ?>
			<?php echo antixss($rs->fields['producto']); ?> 
            &nbsp;<a href="gest_eliminar_productos.php?id=<?php echo $rsconecta->fields['idproducto']; ?>"><img src="../img/borrar.png" width="20" height="20" alt="" title="Eliminar Producto Definitivamente" /></a><br />
            <a href="producto_precio_asigna.php?id=<?php echo $rsconecta->fields['idproducto']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Precio por Sucursal</a>
            <?php } else { ?>
            No es un producto <a href="gest_insumos_convert.php?id=<?php echo $rsconecta->fields['idinsumo'] ?>" class="btn btn-sm btn-default"><span class="fa fa-cogs"></span> Convertir</a>
            <?php } ?>
            </td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>

	  </tbody>
    </table>
</div>
<hr />



<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-12 col-sm-12">
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
			    echo htmlentities($_POST['descripcion']);
			} else {
			    echo htmlentities($rs->fields['descripcion']);
			}?>" placeholder="Descripcion" class="form-control"  required />
		</div>
	</div>

	
	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			Pais de Origen
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

                // consulta

                $consulta = "
				SELECT p.idpais, p.nombre, p.idmoneda FROM paises_propio p
				WHERE p.estado = 1
				order by nombre asc;
				";

// valor seleccionado
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_POST['idpais']);
} else {
    $value_selected = htmlentities($rs->fields['idpais']);
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'data_hidden' => 'idmoneda',
    'style_input' => 'class="form-control"',
    'acciones' => '   '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Dias Utiles</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="dias_utiles" id="dias_utiles" value="<?php  if (isset($_POST['dias_utiles'])) {
			    echo floatval($_POST['dias_utiles']);
			} else {
			    echo $rs->fields['dias_utiles'];
			} ?>" placeholder="Dias Utiles" class="form-control"  />
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">D&iacute;as Estimados en Stock</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="dias_stock" id="dias_stock" value="<?php  if (isset($_POST['dias_stock'])) {
			    echo floatval($_POST['dias_stock']);
			} else {
			    echo $rs->fields['dias_stock'];
			} ?>" placeholder="D&iacute;as Estimados en Stock" class="form-control"  />
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">C&oacute;digo de barras</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="bar_code" id="bar_code" value="<?php  if (isset($_POST['bar_code'])) {
			    echo floatval($_POST['bar_code']);
			} else {
			    echo $rs->fields['bar_code'];
			} ?>" placeholder="C&oacute;digo de barras" class="form-control"  />
		</div>
	</div>
		

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="medida" id="medida" class="form-control" value="<?php if (isset($_POST['medida'])) {
		    echo htmlentities($_POST['medida']);
		} else {
		    echo $rsconecta->fields['medida'];
		} ?>" placeholder="medida" readonly style="cursor:pointer;" onClick="document.location.href='gest_insumos_edit_medida.php?id=<?php echo intval($_GET['id']); ?>'"   />
		</div>
	</div>
</div>

<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Medidas Opcionales</h2>
	<hr>

	
	<?php if ($preferencias_medidas_referenciales == "S") { ?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida2 *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="medida2" id="medida2" class="form-control" value="<?php if (isset($_POST['medida2'])) {
		    echo htmlentities($_POST['medida2']);
		} else {
		    echo $rsconecta->fields['medida2'];
		} ?>" placeholder="medida2" readonly style="cursor:pointer;" onClick="document.location.href='gest_insumos_edit_medida.php?id=<?php echo intval($_GET['id']); ?>'"   />
		</div>
	</div>
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 2</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input readonly style="cursor:pointer;" onClick="document.location.href='gest_insumos_edit_medida.php?id=<?php echo intval($_GET['id']); ?>'"  type="text" aria-describedby="cant_medida2Help"  name="cant_medida2" id="cant_medida2" value="<?php  if (isset($_POST['cant_medida2'])) {
			    echo floatval($_POST['cant_medida2']);
			} else {
			    echo floatval($rs->fields['cant_medida2']);
			}?>" placeholder="cant_medida2" class="form-control" required />
			<small id="cant_medida2Help"   class="form-text text-muted">Cuantas veces Medida es contenido en Medidas2.</small>
		</div>
	</div>



	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida3 *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="medida3" id="medida3" class="form-control" value="<?php if (isset($_POST['medida3'])) {
			    echo htmlentities($_POST['medida3']);
			} else {
			    echo $rsconecta->fields['medida3'];
			} ?>" placeholder="medida3" readonly style="cursor:pointer;" onClick="document.location.href='gest_insumos_edit_medida.php?id=<?php echo intval($_GET['id']); ?>'"   />
		</div>
	</div>
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 3</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input readonly style="cursor:pointer;" onClick="document.location.href='gest_insumos_edit_medida.php?id=<?php echo intval($_GET['id']); ?>'"  type="text" aria-describedby="cant_medida3Help"  name="cant_medida3" id="cant_medida3" value="<?php  if (isset($_POST['cant_medida3'])) {
		    echo floatval($_POST['cant_medida3']);
		} else {
		    echo floatval($rs->fields['cant_medida3']);
		}?>" placeholder="cant_medida3" class="form-control" required />
		<small id="cant_medida3Help"   class="form-text text-muted">Cuantas veces Medida2 es contenido en Medidas3.</small>
		</div>
	</div>

	<?php } ?>
	<?php if ($preferencias_medidas_edi == "S") { ?>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Unidades por Caja EDI</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" aria-describedby="cantCajaEdiHelp"  name="cant_caja_edi" id="cant_caja_edi" value="<?php  if (isset($_POST['cant_caja_edi'])) {
		    echo floatval($_POST['cant_caja_edi']);
		} else {
		    echo floatval($rs->fields['cant_caja_edi']);
		}?>" placeholder="cant_caja_edi" class="form-control"  />
		<small id="cantCajaEdiHelp"   class="form-text text-muted">Cuantas veces Medida es contenido en Cajas EDI.</small>
		</div>
	</div>

	<?php } ?>


</div>

<?php } ?>
<?php if ($preferencias_medidas_fisicas == "S") { ?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Medidas Fisicas</h2>
	<hr>

	<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Largo (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="largo" id="largo" value="<?php  if (isset($_POST['largo'])) {
			    echo floatval($_POST['largo']);
			} else {
			    echo floatval($rs->fields['largo']);
			}?>" placeholder="largo" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Ancho (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="ancho" id="ancho" value="<?php  if (isset($_POST['ancho'])) {
			    echo floatval($_POST['ancho']);
			} else {
			    echo floatval($rs->fields['ancho']);
			}?>" placeholder="ancho" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Alto (cm)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="alto" id="alto" value="<?php  if (isset($_POST['alto'])) {
			    echo floatval($_POST['alto']);
			} else {
			    echo floatval($rs->fields['alto']);
			}?>" placeholder="alto" class="form-control" required />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Peso (kl)</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="peso" id="peso" value="<?php  if (isset($_POST['peso'])) {
			    echo floatval($_POST['peso']);
			} else {
			    echo floatval($rs->fields['peso']);
			}?>" placeholder="peso" class="form-control" required />
			</div>
		</div>

</div>
<?php } ?>

<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Relacionar Proveedor</h2>
	<hr>

	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
			<?php
            // consulta
            $consulta = "
			SELECT idproveedor, nombre
			FROM proveedores
			where
			estado = 1
			order by nombre asc
			";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($rsconecta->fields['idproveedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => ' onchange="cambia_prov()" class="form-control" ',
    'acciones' => ' ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>

<?php if ($preferencias_codigo_fob == "S") { ?>
		<div id="box_cod_fob" class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de origen</label>
			<div  class="col-md-9 col-sm-9 col-xs-12">
		
			<?php
        // consulta
        $idproveedor = $rs->fields['idproveedor'];
    if (intval($idproveedor) > 0) {
        $consulta = "SELECT idfob, codigo_articulo
						FROM proveedores_fob
					where
						idproveedor = $idproveedor
						and estado = 1
						order by codigo_articulo desc
					";
        // valor seleccionado
        if (isset($_POST['cod_fob'])) {
            $value_selected = htmlentities($_POST['cod_fob']);
        } else {
            $value_selected = $rsconecta->fields['cod_fob'];
        }

        // parametros
        $parametros_array = [
            'nombre_campo' => 'cod_fob',
            'id_campo' => 'cod_fob',

            'nombre_campo_bd' => 'cod_fob',
            'id_campo_bd' => 'idfob',

            'value_selected' => $value_selected,

            'pricampo_name' => 'Seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => '   aria-describedby="codOrigenHelp" ',
            'autosel_1registro' => 'S'

        ];

        // construye campo
        echo campo_select($consulta, $parametros_array);

    } else {

        $consulta = "SELECT idfob, codigo_articulo
						FROM proveedores_fob
						order by codigo_articulo desc
					";
        // valor seleccionado
        if (isset($_POST['cod_fob'])) {
            $value_selected = htmlentities($_POST['cod_fob']);
        } else {
            $value_selected = $rs->fields['cod_fob'];
        }

        // parametros
        $parametros_array = [
            'nombre_campo' => 'cod_fob',
            'id_campo' => 'cod_fob',

            'nombre_campo_bd' => 'cod_fob',
            'id_campo_bd' => 'idfob',

            'value_selected' => $value_selected,

            'pricampo_name' => 'Seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' disabled  aria-describedby="codOrigenHelp" ',
            'autosel_1registro' => 'S'

        ];

        // construye campo
        echo campo_select($consulta, $parametros_array);

    }

    ?>
			<small id="codOrigenHelp"   class="form-text text-muted">Referencte al codigo del Proveedor FOB.</small>
		
		</div>
<?php } ?>

</div>

<?php if ($preferencias_configuraciones_alternativas == "S") {?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Regimen Sanitario</h2>
	<hr>
	<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">RS </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="rs" id="rs" value="<?php  if (isset($_POST['rs'])) {
			    echo antixss($_POST['rs']);
			} else {
			    echo antixss($rsconecta->fields['rs']);
			}?>" placeholder="rs" class="form-control"  />
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">RSPA </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="rspa" id="rspa" value="<?php  if (isset($_POST['rspa'])) {
			    echo antixss($_POST['rspa']);
			} else {
			    echo antixss($rsconecta->fields['rspa']);
			}?>" placeholder="rspa" class="form-control"  />
			</div>
		</div>
</div>
<?php } ?>


<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Grupos  y Categorias</h2>
	<hr>

	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo Stock *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            // consulta
            $consulta = "
			SELECT idgrupoinsu, nombre
			FROM grupo_insumos
			where
			estado = 1
			order by nombre asc
			";

// valor seleccionado
if (isset($_POST['idgrupoinsu'])) {
    $value_selected = htmlentities($_POST['idgrupoinsu']);
} else {
    $value_selected = htmlentities($rsconecta->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idgrupoinsu',
    'id_campo' => 'idgrupoinsu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idgrupoinsu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => ''

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>


	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_categoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Categoria * </label>
		<div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
			<?php
require_once("cate_new.php");

?>
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Subcategoria *</label>
		<div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
			<?php
require_once("subcate_new.php");

?>
		</div>
	</div>


	<?php if ($sub_categoria_secundaria == "S") { ?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria_sec();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Sub Categoria Secundaria</label>
			<div class="col-md-9 col-sm-9 col-xs-12" id="subcatesecbox">
				<?php
    require_once("subcate_sec_new.php");
	    ?>
			</div>
		</div>
	<?php } ?>


</div>


<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Costos</h2>
	<hr>
	<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Costo *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="costo" id="costo" value="<?php  if (isset($_POST['costo'])) {
			    echo floatval($_POST['costo']);
			} else {
			    echo floatval($rs->fields['costo']);
			}?>" placeholder="Costo" class="form-control" required />
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            // consulta
            $consulta = "
			SELECT idtipoiva, iva_porc, iva_describe
			FROM tipo_iva
			where
			estado = 1
			and hab_compra = 'S'
			order by iva_porc desc
			";
$acciones = ' required="required" ';
if ($preferencias_usa_iva_variable = "S") {
    $acciones = ' ';
}


// valor seleccionado
if (isset($_POST['idtipoiva_compra'])) {
    $value_selected = htmlentities($_POST['idtipoiva_compra']);
} else {
    $value_selected = $rsconecta->fields['idtipoiva_compra'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva_compra',
    'id_campo' => 'idtipoiva_compra',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$acciones.'  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

</div>





<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Configuraciones</h2>
	<hr>



	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita Compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<select name="hab_compra" id="hab_compra" class="form-control"  title="Habilita Compra" required >
		<option value="" selected="selected">Seleccionar</option>
		<option value="1" <?php if ($rsconecta->fields['hab_compra'] == 1) {?> selected="selected" <?php } ?>>SI</option>
		<option value="0" <?php if ($rsconecta->fields['hab_compra'] != 1) {?> selected="selected" <?php } ?>>NO</option>
       </select>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita Inventario *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select name="hab_invent" id="hab_invent" class="form-control" title="Habilita Inventario" required="required">
       <option value="" selected="selected">Seleccionar</option>
       <option value="1" <?php if ($rsconecta->fields['hab_invent'] == 1) {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if ($rsconecta->fields['hab_invent'] != 1) {?> selected="selected" <?php } ?>>NO</option>
     </select>
	</div>
</div>




<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Solo Conversion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<select name="solo_conversion" id="solo_conversion" class="form-control" title="Solo Conversion" required="required">
       <option value="" selected="selected">Seleccionar</option>
       <option value="1" <?php if (intval($rsconecta->fields['solo_conversion']) == 1) {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if (intval($rsconecta->fields['solo_conversion']) == 0) {?> selected="selected" <?php } ?>>NO</option>
     </select>
	</div>
</div>

<?php if ($preferencias_configuraciones_alternativas == "S") {?>

<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita Descuento </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="hab_desc" id="hab_desc"  title="Habilita Descuento" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['hab_desc'] == 'S' || $rs->fields['hab_desc'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['hab_desc'] == 'N' || $rs->fields['hab_desc'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Modifica Precio</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="modifica_precio" id="modifica_precio"  title="Modifica Precio" class="form-control">
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['modifica_precio'] == 'S' || $rs->fields['modifica_precio'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['modifica_precio'] == 'N' || $rs->fields['modifica_precio'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Maneja Lote</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="maneja_lote" id="maneja_lote"  title="Maneja Lote" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="1" <?php if ($_POST['maneja_lote'] == '1' || $rs->fields['maneja_lote'] == '1') {?> selected="selected" <?php } ?>>SI</option>
					<option value="0" <?php if ($_POST['maneja_lote'] == '0' || $rs->fields['maneja_lote'] == '0') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Regimen turismo</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="regimen_turismo" id="regimen_turismo"  title="Regimen turismo" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['regimen_turismo'] == 'S' || $rs->fields['regimen_turismo'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['regimen_turismo'] == 'N' || $rs->fields['regimen_turismo'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Maneja Codigo Alternativo</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<select name="maneja_cod_alt" id="maneja_cod_alt" onchange="habilitar_codigo_alternativo(this.value)"  title="Maneja Codigo Alternativo" class="form-control" >
					   <option value="" >Seleccionar</option>
					<option value="S" <?php if ($_POST['maneja_cod_alt'] == 'S' || $rs->fields['maneja_cod_alt'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
					<option value="N" <?php if ($_POST['maneja_cod_alt'] == 'N' || $rs->fields['maneja_cod_alt'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
				   </select>
			</div>
		</div>

		<div class="col-md-6 col-xs-6 form-group" id="box_cod_alternativo" <?php if ($rs->fields['maneja_cod_alt'] == 'N') { ?> style="display:none;" <?php }?>>
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Alternativo</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<div class="" style="display:flex;">
					<div class="dropdown " id="lista_cod_alternativo">
						<select onclick="myFunction2(event)"  class="form-control " id="idcod_alt" name="idcod_alt">
						<?php if (intval($rs->fields['idcod_alt']) > 0) { ?>
							<option value="<?php echo intval($rs->fields['idcod_alt']) ?>" selected><?php echo $rs->fields['cod_alt_nombre'] ?></option>
						<?php } ?>
					</select>
						<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Articulo" id="myInput2" onkeyup="filterFunction2(event)" >
						<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
							<?php echo $resultados_insumos_lista ?>
						</div>
					</div>
				</div>
			</div>
		</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Respeta precio sugerido *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<select name="respeta_precio_sugerido" id="respeta_precio_sugerido" class="form-control" title="Respeta precio sugerido" required="required">
       <option value="" selected="selected">Seleccionar</option>
       <option value="S" <?php if (trim($rsconecta->fields['respeta_precio_sugerido']) == 'S') {?> selected="selected" <?php } ?>>SI</option>
       <option value="N" <?php if (trim($rsconecta->fields['respeta_precio_sugerido']) == 'N') {?> selected="selected" <?php } ?>>NO</option>
     </select>
	</div>
</div>

<?php } ?>
<?php if ($master_franq == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Aplica Regalia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<select name="aplica_regalia" id="aplica_regalia" class="form-control" title="Aplica Regalia" required="required">
       <option value="" selected="selected">Seleccionar</option>
       <option value="S" <?php if ($rsconecta->fields['aplica_regalia'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
       <option value="N" <?php if ($rsconecta->fields['aplica_regalia'] != 'S') {?> selected="selected" <?php } ?>>NO</option>
     </select>
	</div>
</div>
<?php } ?>

<div class= "row" style="margin:0px;">
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Acepta Devolucion *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
	<select name="acepta_devolucion" id="acepta_devolucion" class="form-control" title="Acepta devolucion">
		   <option value="" selected="selected">Seleccionar...</option>
		   <option value="S" <?php if ($rsconecta->fields['acepta_devolucion'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
		   <option value="N" <?php if ($rsconecta->fields['acepta_devolucion'] == 'N') {?> selected="selected" <?php } ?>>NO</option>
		 </select>
		</div>
	</div>
	
	
	
</div>


<?php if ($usa_concepto == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

$onchangeAdd = "";
    if ($preferencias_usa_iva_variable = "S") {
        $onchangeAdd = ' onchange="verificar_concepto()" ';
    }

    // consulta
    $consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and borrable = 'S'
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['idconcepto'])) {
        $value_selected = htmlentities($_POST['idconcepto']);
    } else {
        $value_selected = htmlentities($rsconecta->fields['idconcepto']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idconcepto',
        'id_campo' => 'idconcepto',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idconcepto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => ' class="form-control" ',
        'acciones' => ' '.$onchangeAdd.' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>
<?php } ?>

<?php
    $contabilidad = intval($rsco->fields['contabilidad']);
if ($contabilidad == 1) {
    ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable (Compra) *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
SELECT cuenta, descripcion
FROM cn_plancuentas_detalles
where 
estado<>6 
and asentable='S' 
order by descripcion asc
 ";

    //Buscamos si el insumo existe para auto select
    /*$buscar="Select idsercuenta,trim(cuenta)as cuentacont from cn_articulos_vinculados
    inner join cn_plancuentas_detalles on cn_plancuentas_detalles.idserieun=cn_articulos_vinculados.idsercuenta
    where cn_articulos_vinculados.idinsumo=$idinsu ";
    $rscon1=$conexion->Execute($buscar) or die (errorpg($conexion,$buscar));*/
    //echo $buscar;


    // valor seleccionado
    if (isset($_POST['cuentacont'])) {
        $value_selected = htmlentities($_POST['cuentacont']);
    } else {
        $value_selected = htmlentities($rsconecta->fields['idplancuentadet']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'cuentacont',
        'id_campo' => 'cuentacont',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'cuenta',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>
<?php } ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Paquete </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="paquete" id="paquete" class="form-control" value="<?php if (isset($_POST['paquete'])) {
	    echo htmlentities($_POST['paquete']);
	} else {
	    echo $rsconecta->fields['paquete'];
	} ?>" placeholder="paquete"  />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad por Paquete </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cant_paquete" id="cant_paquete" class="form-control" value="<?php if (isset($_POST['cant_paquete'])) {
	    echo htmlentities($_POST['cant_paquete']);
	} else {
	    echo floatval($rsconecta->fields['cant_paquete']);
	} ?>" placeholder="cant_paquete"  />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Centro Produccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
Select idcentroprod,  descripcion
from produccion_centros 
where 
estado <> 6 
order by descripcion asc
";

// valor seleccionado
if (isset($_POST['cpr'])) {
    $value_selected = htmlentities($_POST['cpr']);
} else {
    $value_selected = htmlentities($rsconecta->fields['idcentroprod']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cpr',
    'id_campo' => 'cpr',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idcentroprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">% Rendimiento *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="rendimiento_porc" id="rendimiento_porc" value="<?php  if (isset($_POST['rendimiento_porc'])) {
	    echo floatval($_POST['rendimiento_porc']);
	} else {
	    echo floatval($rs->fields['rendimiento_porc']);
	} ?>" placeholder="Rendimiento %" class="form-control" required />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion Produccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
Select idagrupacionprod,  agrupacion_prod
from produccion_agrupacion 
where 
estado <> 6 
order by agrupacion_prod asc
";

// valor seleccionado
if (isset($_POST['idagrupacionprod'])) {
    $value_selected = htmlentities($_POST['idagrupacionprod']);
} else {
    $value_selected = htmlentities($rs->fields['idagrupacionprod']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idagrupacionprod',
    'id_campo' => 'idagrupacionprod',

    'nombre_campo_bd' => 'agrupacion_prod',
    'id_campo_bd' => 'idagrupacionprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>


</div>



<br />
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br /><br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
