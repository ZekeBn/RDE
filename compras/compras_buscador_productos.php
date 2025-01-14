<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../deposito/preferencias_deposito.php");


$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);



$agregar_insumo_lprod = intval($_POST['agregar_insumo_lprod']);
if ($agregar_insumo_lprod == 1) {

    $valido = "S";
    $errores = "";

    // recibe parametros
    $idproducto = antisqlinyeccion('', "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $idconcepto = antisqlinyeccion($_POST['idconcepto'], "int");
    //$idcategoria=antisqlinyeccion('',"int");
    //$idsubcate=antisqlinyeccion('',"int");
    $idmarcaprod = antisqlinyeccion('', "int");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
    $cant_medida2 = antisqlinyeccion($_POST['cant_medida2'], "int");
    $cant_medida3 = antisqlinyeccion($_POST['cant_medida3'], "int");
    $idmedida2 = antisqlinyeccion($_POST['idmedida2'], "int");
    $idmedida3 = antisqlinyeccion($_POST['idmedida3'], "int");
    $idsubcate_sec = antisqlinyeccion($_POST['idsubcate_sec'], "int");
    $idpais = antisqlinyeccion($_POST['idpais'], "int");
    $dias_utiles = antisqlinyeccion($_POST['dias_utiles'], "float");
    $dias_stock = antisqlinyeccion($_POST['dias_stock'], "float");
    $bar_code = antisqlinyeccion($_POST['bar_code'], "float");



    if (intval($cant_medida2) > 0) {
        $idmedida2 = $idcaja;
    }
    if (intval($cant_medida3) > 0) {
        $idmedida3 = $idpallet;
    }
    $produccion = antisqlinyeccion('1', "int");
    $costo = antisqlinyeccion(floatval($_POST['costo']), "float");
    $idtipoiva_compra = antisqlinyeccion($_POST['idtipoiva_compra'], "int");
    $mueve_stock = antisqlinyeccion('S', "text");
    $paquete = antisqlinyeccion('', "text");
    $cant_paquete = antisqlinyeccion('', "float");
    $estado = antisqlinyeccion('A', "text");
    $idempresa = antisqlinyeccion(1, "int");
    $idgrupoinsu = antisqlinyeccion($_POST['idgrupoinsu'], "int");
    $ajuste = antisqlinyeccion('N', "text");
    $fechahora = antisqlinyeccion($ahora, "text");
    $registrado_por_usu = antisqlinyeccion($idusu, "int");
    $hab_compra = antisqlinyeccion($_POST['hab_compra'], "int");
    $hab_invent = antisqlinyeccion($_POST['hab_invent'], "int");
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $aplica_regalia = antisqlinyeccion('S', "text");
    $solo_conversion = antisqlinyeccion('', "int");
    $respeta_precio_sugerido = antisqlinyeccion('N', "text");
    $idprodexterno = antisqlinyeccion('', "int");
    $restaurado_por = antisqlinyeccion('', "int");
    $restaurado_el = antisqlinyeccion('', "text");
    $idcategoria = antisqlinyeccion($_POST['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($_POST['idsubcate'], "int");
    $cuentacontable = antisqlinyeccion($_POST['cuentacont'], "int");
    $centroprod = intval($_POST['cpr']);
    $idagrupacionprod = antisqlinyeccion($_POST['idagrupacionprod'], "int");
    $rendimiento_porc = antisqlinyeccion($_POST['rendimiento_porc'], "float");

    //opcionales
    // TODO: poner preferencias
    $cant_caja_edi = antisqlinyeccion($_POST['cant_caja_edi'], "float");
    $largo = antisqlinyeccion($_POST['largo'], "float");
    $ancho = antisqlinyeccion($_POST['ancho'], "float");
    $alto = antisqlinyeccion($_POST['alto'], "float");
    $peso = antisqlinyeccion($_POST['peso'], "float");
    $cod_fob = antisqlinyeccion($_POST['cod_fob'], "text");
    $rs = antisqlinyeccion($_POST['rs'], "text");
    $rspa = antisqlinyeccion($_POST['rspa'], "text");
    $hab_desc = antisqlinyeccion($_POST['hab_desc'], "text");
    $modifica_precio = antisqlinyeccion($_POST['modifica_precio'], "text");
    $maneja_lote = antisqlinyeccion($_POST['maneja_lote'], "text");
    $regimen_turismo = antisqlinyeccion($_POST['regimen_turismo'], "text");
    $maneja_cod_alt = antisqlinyeccion($_POST['maneja_cod_alt'], "text");
    $idcod_alt = antisqlinyeccion($_POST['idcod_alt'], "int");


    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }

    if ($usa_concepto == 'S') {
        if (intval($_POST['idconcepto']) == 0) {
            $valido = "N";
            $errores .= " - El campo concepto no puede ser cero o nulo.<br />";
        }
    }

    if (intval($_POST['idmedida']) == 0) {
        $valido = "N";
        $errores .= " - El campo medida no puede ser cero o nulo.<br />";
    }


    if ($idconcepto_despacho != $idconcepto && $idconcepto != $idconcepto_flete) {
        if (trim($_POST['idtipoiva_compra']) == '') {
            $valido = "N";
            $errores .= " - El campo iva compra no puede estar vacio.<br />";
        }
    } else {
        $idtipoiva_compra = 0;
        $tipoiva_compra = 0;
    }

    if (intval($_POST['idgrupoinsu']) == 0) {
        $valido = "N";
        $errores .= " - El campo grupo stock no puede estar vacio.<br />";
    }

    if (trim($_POST['hab_compra']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita compra debe completarse.<br />";
    }

    if (trim($_POST['hab_invent']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita inventario debe completarse.<br />";
    }
    if ($_POST['hab_compra'] > 0) {
        if (intval($_POST['solo_conversion']) == 0) {
            if (intval($_POST['hab_invent']) == 0) {
                $valido = "N";
                $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
            }
        }
    }
    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $descripcion and borrado = 'N' limit 1
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si existe producto
    $idprod_serial = $rsexpr->fields['idprod_serial'] ? $rsexpr->fields['idprod_serial'] : "";
    if ($idprod_serial > 0) {
        $errores .= "- Ya existe un producto con el mismo nombre.<br />";
        $valido = 'N';
    }
    // validar que no hay insumo con el mismo nombre
    $buscar = "Select * from insumos_lista where descripcion=$descripcion and estado = 'A' limit 1";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsb->fields['idinsumo'] > 0) {
        $errores .= "* Ya existe un articulo con el mismo nombre.<br />";
        $valido = 'N';
    }


    /////////////////

    if ($idconcepto_despacho != $idconcepto && $idconcepto != $idconcepto_flete) {
        // iva compra
        $consulta = "
		select * 
		from tipo_iva
		where 
		idtipoiva = $idtipoiva_compra
		";
        $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tipoiva_compra = $rsiva->fields['iva_porc'];
        $idtipoiva_compra = $rsiva->fields['idtipoiva'];

        $contabilidad = intval($rsco->fields['contabilidad']);
        if ($contabilidad == 1) {
            if (trim($_POST['hab_compra']) == '1') {
                if (intval($_POST['cuentacont']) == 0) {
                    $valido = "N";
                    $errores .= "- Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
                }
            }
        }
    }
    /////////////////////
    if (floatval($_POST['rendimiento_porc']) <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['rendimiento_porc']) > 100) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser mayor a 100.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $buscar = "select max(idinsumo) as mayor from insumos_lista";
        $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idinsumo = intval($rsmayor->fields['mayor']) + 1;

        $consulta = "
		insert into insumos_lista
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida,idmedida2,idmedida3, cant_medida2, cant_medida3, produccion, costo,  idtipoiva, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el,
		idplancuentadet, idcentroprod, idagrupacionprod, rendimiento_porc,cant_caja_edi,largo,ancho,alto,peso,cod_fob,rs,rspa,hab_desc,modifica_precio,maneja_lote,regimen_turismo,maneja_cod_alt,idcod_alt, idpais, dias_utiles, dias_stock,bar_code, idsubcate_sec
		)
		values
		($idinsumo, $idproducto, $descripcion, $idconcepto, $idcategoria, $idsubcate, $idmarcaprod, $idmedida, $idmedida2, $idmedida3, $cant_medida2, $cant_medida3, $produccion, $costo, $idtipoiva_compra, $tipoiva_compra, $mueve_stock, $paquete, $cant_paquete, $estado, $idempresa, $idgrupoinsu, $ajuste, $fechahora, $registrado_por_usu, $hab_compra, $hab_invent, $idproveedor, $aplica_regalia, $solo_conversion, $respeta_precio_sugerido, $idprodexterno, $restaurado_por, $restaurado_el,
		$cuentacontable, $centroprod, $idagrupacionprod, $rendimiento_porc,$cant_caja_edi,$largo,$ancho,$alto,$peso,$cod_fob,$rs,$rspa,$hab_desc,$modifica_precio,$maneja_lote,$regimen_turismo,$maneja_cod_alt,$idcod_alt, $idpais, $dias_utiles, $dias_stock, $bar_code, $idsubcate_sec
		)
		";

        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $insertar = "Insert into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $codarticulocontable = intval($_POST['cuentacont']);
        if ($codarticulocontable > 0) {
            //traemos los datos del plan de cuentas activo
            $buscar = "Select * from cn_plancuentas_detalles where cuenta=$codarticulocontable and estado <> 6";
            $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idplan = intval($rsvv->fields['idplan']);
            $idsercuenta = intval($rsvv->fields['idserieun']);

            $insertar = "Insert into cn_articulos_vinculados
			(idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
			values 
			($idinsumo,$idplan,$idsercuenta,current_timestamp,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        }





    } else {
        $res = [
            "success" => false,
            "errores" => $errores
        ];
        echo json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        exit;
    }


}




function isNullAddChar($palabra)
{
    if ($palabra == "NULL") {
        return "'NULL'";
    } else {
        return $palabra;
    }
}


$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);


//Traemos los insumos de insumos_lista
$buscar = "Select idinsumo,idconcepto,descripcion,maneja_lote,(select nombre from medidas where id_medida=insumos_lista.idmedida and estado=1) as medida, (select nombre from categorias where id_categoria=insumos_lista.idcategoria and estado=1) as categoria,
cant_caja_edi,cant_medida2,cant_medida3,idmedida2,idmedida3,idmedida,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida2) as medida2,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida3) as medida3,
(select descripcion from sub_categorias where sub_categorias.idsubcate = insumos_lista.idsubcate) as subcate,
( SELECT proveedores_fob.codigo_articulo from proveedores_fob WHERE proveedores_fob.idfob = insumos_lista.cod_fob ) as codigo_origen,
(select descripcion from sub_categorias_secundaria where sub_categorias_secundaria.idsubcate_sec = insumos_lista.idsubcate_sec) as subcate_sec
 from insumos_lista where
 UPPER(insumos_lista.descripcion)  not like \"%DESCUENTO%\" 
 and  UPPER(insumos_lista.descripcion)  not like \"%AJUSTE%\"
 and estado='A' 
 and hab_compra=1 order by descripcion asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$i = 1;
while (!$rsd->EOF) {
    $descripcion = isNullAddChar(trim(antisqlinyeccion($rsd->fields['descripcion'], "text")));
    $medida = isNullAddChar(trim(antisqlinyeccion($rsd->fields['medida'], "text")));
    $idinsumo = intval($rsd->fields['idinsumo']);
    $idmedida = intval($rsd->fields['idmedida']);
    $idmedida2 = intval($rsd->fields['idmedida2']);
    $idmedida3 = intval($rsd->fields['idmedida3']);
    $maneja_lote = intval($rsd->fields['maneja_lote']);
    $idconcepto = intval($rsd->fields['idconcepto']);
    $mostrar_iva = isNullAddChar(trim(antisqlinyeccion("false", "text")));
    if ($idconcepto == $idconcepto_despacho || $idconcepto == $idconcepto_flete) {
        $mostrar_iva = isNullAddChar(trim(antisqlinyeccion("true", "text")));
    }

    $categoria = isNullAddChar(antisqlinyeccion($rsd->fields['categoria'], "text"));
    $subcate = isNullAddChar(antisqlinyeccion($rsd->fields['subcate'], "text"));
    $subcate_sec = isNullAddChar(antisqlinyeccion($rsd->fields['subcate_sec'], "text"));
    $medida2 = isNullAddChar(antisqlinyeccion($rsd->fields['medida2'], "text"));
    $medida3 = isNullAddChar(antisqlinyeccion($rsd->fields['medida3'], "text"));
    $codigo_origen = isNullAddChar(antisqlinyeccion($rsd->fields['codigo_origen'], "text"));

    $cant_caja_edi = (floatval($rsd->fields['cant_caja_edi']));
    $cant_medida2 = (floatval($rsd->fields['cant_medida2']));
    $cant_medida3 = (floatval($rsd->fields['cant_medida3']));
    $clase = "";
    if ($i % 2 == 1) {
        $clase = "class='even'";
    }
    $resultados .= "
	<a href='javascript:void(0);' data-hidden-value=$categoria data-hidden-codorigen=$codigo_origen onclick=\"este_producto({idinsumo: $idinsumo,maneja_lote: $maneja_lote, descripcion: $descripcion, medida:$medida, medida2: $medida2, medida3:$medida3, cant_medida2: $cant_medida2, cant_medida3: $cant_medida3, id_cajas_edi: $id_cajas_edi, idmedida2: $idmedida2, idmedida3: $idmedida3, idmedida: $idmedida, cant_caja_edi: $cant_caja_edi});\">[$idinsumo]-$descripcion ($medida)</a>
	";
    $resultados2 .= "
	<option href='javascript:void(0);' $clase data-hidden-value=$categoria data-hidden-codorigen=$codigo_origen onclick=\"este_producto({ mostrar_iva: $mostrar_iva, idinsumo: $idinsumo,maneja_lote: $maneja_lote, descripcion: $descripcion, medida:$medida, medida2: $medida2, medida3:$medida3, cant_medida2: $cant_medida2, cant_medida3: $cant_medida3, id_cajas_edi: $id_cajas_edi, idmedida2: $idmedida2, idmedida3: $idmedida3, idmedida: $idmedida, cant_caja_edi: $cant_caja_edi});\">[$idinsumo]-$descripcion ($medida)</option>
	";
    $i++;
    $rsd->MoveNext();
}
?>
<style>
	input:focus, select:focus,#radio1:focus,#radio2:focus {
		border: #add8e6 solid 3px !important; /* Este es un tono de azul pastel */
	}
	
	/* Cambiar color del texto al hacer clic en la etiqueta */
	input,select{
		border-radius: 3px !important;
	}
	.radios_box{
		border-radius: 8px;
		border:1px solid #c2c2c2;
		width: 30%;

	}

	.radio_div{
		background-color: #fff;
		padding: 5px;
		margin: 2px;
		border-radius: 8px;
		color: #6789A9;
		cursor: pointer;

		/* border:1px solid #c2c2c2; */
		
	}
	
	.radio_div:hover{
		background-color: #cecece;
		/* border:1px solid #c2c2c2; */
		color: black !important;
		opacity: 0.8;
		/* color: #486b7a !important; */
		

	}
	.radio_div input{
		width: 20%;
		cursor: pointer;
	}
	.even{
		background: #F7F7F7 !important;
	}

	.radio_div label{
		cursor: pointer;
	}
	.radio_div input:focus{
		border:1px solid hsl(210, 50%, 70%);
		cursor: pointer;
	}
	#lprod option{
		padding: 1.4vh;
		position: relative;
		cursor: pointer;
		/* border-bottom: 1px solid #c2c2c2; */
	}
	#lprod option:hover{
		background: #cecece; 
		/* #4BA0E2 */
		font-weight: bold;
		color: black ;
		opacity: 0.7;
		box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);

	}
	#lprod option + option:after{

		content: "";
		background: #c2c2c2;
		position: absolute;
		bottom: 100%;
		left: 2%;
		height: 1px;
		width: 96%;
	}
	#lprod{
		border: 0.5px solid lightgray;
		border-radius: 8px;
		box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
	}
	
	.btn_insumo_select{
		color: #6789A9 !important;
		background: white;
		border: #6789A9 solid 1px;
	}
	.btn_insumo_select:hover{
		/* color: #fff !important;
		background: #6D8EAE; */
		color: #6789A9;
		background: #E9EDF1;
	}
	.btn_agregar_insumo{
		background: #71B48D;
    	border: 1px solid #0EAD69;
	}
	.btn_agregar_insumo:hover{
		background: #0EAD69;
		border: 1px solid #0EAD69;
	}
</style>
<div class="col-md-6">
	<div class="col-md-8 col-xs-12" style="display:none;">
		<div class="col-md-9 col-sm-9 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
			<div class="dropdown" id="insumos_dropdown">
			  <button onclick="myFunction()" class="btn btn-rimary btn_insumo_select" id="abrecierra">Buscar Insumo por nombre</button>
			  <input type="text" placeholder="Nombre Insumo" id="myInput" onkeyup="filterFunction()" style="position: absolute;top: 37px;left: 0;z-index: 99999;display:none;" >
			  <div id="myDropdown" class="dropdown-content" style="position: absolute;top: 90px;left: 0;z-index: 99999;width: 261px;max-width: 300px;max-height: 200px;overflow: auto;">
				<?php echo $resultados ?>
			  </div>
			</div>
		</div>
	</div>

	<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Insumo<small class="btn btn-default btn-sm fa fa-plus" onclick="agregar_insumo_modal()" ></small></label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="insumo_text" id="insumo_text" value="" onkeyup=" return insumo_onchange(event)"  class="form-control"  />                    
		</div>
	</div>
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Codigo Insumo</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="insumo" id="insumo" value="" onclick="insumo_onchange_click(event)" onchange="idinsumo_onchange()"  class="form-control"  />                    
		</div>
	</div>
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Cod. Barra</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="codbar" id="codbar" value="" placeholder="" class="form-control" onkeypress="buscar_codbar(event);" />                    
		</div>
	</div>

	<select name="lprod" size="4" id="lprod" style="width:100%;" onkeyup="return select_enter(event)" >
		<?php echo $resultados2 ?>
	</select>

</div>
<div class="col-md-6" id="formulario_compras_add">
	<div class="col-md-12 col-xs-12" id="" style="margin-bottom:.8rem !important;padding:0;">
		<div class="alert alert-danger alert-dismissible " role="alert" style="display:none;" id="erroresjs">
		<button type="button" class="close"  aria-label="Close" onclick="cerrar_errorestxt()"><span aria-hidden="true" >×</span></button>

			<span id="errorestxt"></span>

		</div>

		<label class="control-label col-md-3 col-sm-3 col-xs-12">Seleccionado</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="seleccionado" id="seleccionado" class="form-control" disabled/>                      
		</div>

		
	</div>

	<div class="col-md-12 col-xs-12 " style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            $deposito_por_defecto = "";
if ($preferencia_autosel_compras == "S") {
    $select = "select iddeposito from gest_depositos where autosel_compras = 'S'";
    $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
    $deposito_por_defecto = $rs_activo->fields['iddeposito'];
}
// consulta
$consulta = "
			SELECT iddeposito, descripcion
			FROM gest_depositos
			where
			estado = 1
			and compras = 0
			and tiposala <> 3
			order by descripcion asc
			";

// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = $deposito_por_defecto;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Automatico',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>
	
	
	<div  class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-5 col-sm-5 col-xs-12">
			Lote
			<a href="javascript:void(0);" id="lote_search" onclick="lote_recomendado()" class="btn btn-sm btn-default" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Detalles" style="display:none;"><span class="fa fa-search"></span></a>
		</label>
		<div class="col-md-7 col-sm-7 col-xs-12">
			<input disabled type="text" name="lote" id="lote" value="" placeholder="" class="form-control" />                    
		</div>
		<!-- <div class="col-md-9 col-sm-9 col-xs-12">
		</div>
		<div class="clearfix"></div> -->

	</div>

	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Vto</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input disabled type="date" name="vencimiento" onBlur="validar_fecha_vencimiento(this.value);"id="vencimiento" value="" placeholder="" class="form-control" />                    
		</div>
	</div>

	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12 ">Cantidad </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input onchange="cargarMedida(this.value)" type="text" name="cantidad" id="cantidad" class="form-control" />
			<span id="medidanombre" style="color: red;"></span>
		</div>
	</div>
	<!-- ///////////////// -->
	<!-- MEDIDAS 2  -->
	<?php if ($preferencias_medidas_referenciales == "S") { ?>
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			<a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
				<span class="fa fa-plus"></span>
			</a>
			<div id="medida2">Medida2:</div>
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input disabled class="form-control" onchange="cargarMedida2(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
			<small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
		</div>
	</div>

	
	<!-- MEDIDAS INICIO 3 -->
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			<a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
				<span class="fa fa-plus"></span>
			</a>	
			<div id="medida3">Medida3:</div>
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input disabled aria-describedby="palletHelp" onchange="cargarMedida3(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
			<small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong>  asignadas,favor agregar en insumos.</small>
		
		</div>
	</div>
	<?php } ?>
	<?php if ($preferencias_medidas_edi == "S") { ?>

	<!-- MEDIDAS EDI  -->
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;display:none;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			<a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
				<span class="fa fa-plus"></span>
			</a>
			<div id="medida2">Cajas EDI:</div>
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input disabled class="form-control" onkeyup="cargarMedidaEDI(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
			<small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
		</div>
	</div>
	<?php } ?>

	<!-- FIN DE MEDIDAS -->

	
		<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio con IVA</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="precio_compra" id="precio_compra" value="" placeholder="" class="form-control" />
			</div>
		</div>
		<div id="box_iva_articulo" class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;display:none;">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">IVA</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="iva_articulo" id="iva_articulo" value="" placeholder="" class="form-control" />
			</div>
		</div>
		<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="precio_compra" id="precio_compra" value="" placeholder="" class="form-control hide" />
			</div>
		</div>
	<!-- /////////////////////////////////// -->
	
	<div class="row">
		<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
			<!-- //////////////////////////////////////////////////////////////////////// -->
			<!-- //////////radio medida comienza////////////////////// -->
			<div id="radio1" class="col-md-12 col-xs-12 " style="margin-bottom:.8rem !important; ">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Medida</label>
				<div  class="row radios_box" style="display: flex;flex-direction: column;">
					<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_unidad">
						<input class="form-check-input" value="1" type="radio" name="radio_medida" id="radio_unidad" checked>
						<label class="form-check-label" id="label_unidad" for="radio_unidad">
							UNIDAD
						</label>
					</div>
					<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_bulto" style="display:none;">
						<input class="form-check-input" value="2" type="radio" name="radio_medida" id="radio_bulto" >
						<label class="form-check-label" id="label_bulto" for="radio_bulto">
							CAJA
						</label>
					</div>
					<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_pallet" style="display:none;">
						<input class="form-check-input" value="3" type="radio" name="radio_medida" id="radio_pallet" >
						<label class="form-check-label" id="label_pallet" for="radio_pallet">
							PALLET
						</label>
					</div>
					<!-- <div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_edi" style="display:none;">
						<input class="form-check-input" value="4" type="radio" name="radio_medida" id="radio_edi" >
						<label class="form-check-label" id="label_EDI" for="radio_edi">
							CAJA EDI
						</label>
					</div> -->
				</div>
			</div>
			<!-- ////////////////////////////////////////////////////////////////////////// -->
			<!-- ////////////////////////////radio medida fin ///////////////////////////// -->
			<?php } ?>
		
		
		
		<!-- verificando tipo de moneda para el precio/////////////////////////////////// -->
		<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && (intval($idmoneda_select) != 0 && intval($idmoneda_select) != $id_moneda_nacional)) { ?>
		<div id="radio2" class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Moneda </label>
			<div class="col radios_box" style="display: flex;flex-direction: column;">
				<div class="form-check radio_div" id="box_radio_moneda_extranjera" style="display:inline-block;">
					<input  class="form-check-input" data-hidden-nacional="false" value="<?php echo $idmoneda_select; ?>" type="radio" name="radio_moneda" id="radio_moneda_extranjera" >
					<label class="form-check-label" id="label_moneda_extranjera" for="radio_moneda_extranjera">
						<?php echo $moneda_nombre; ?>
					</label>
				</div>
				<div class="form-check radio_div" id="box_radio_moneda_nacional" style="display:inline-block;">
					<input class="form-check-input" data-hidden-nacional="true" value="<?php echo $id_moneda_nacional; ?>" type="radio" name="radio_moneda" id="radio_moneda_nacional" >
					<label class="form-check-label" id="label_moneda_nacional" for="radio_moneda_nacional">
					<?php echo $nombre_moneda_nacional; ?>
					</label>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
	<!-- /////////////////////////////////////////////////// -->

	
	<div class="clearfix"></div>

	<div class="col-md-12 col-xs-12"  style="text-align:right;">
		<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />
		<button  class="btn btn-success btn_agregar_insumo" id="btn_agregar" onclick="agregar_insumo_carrito();"><span class="fa fa-plus"></span>&nbsp;Agregar</button>
	</div>
</div>





<script>
	function agregar_insumo_modal(){
		
		var parametros = {
                "agregar"   : 1,
        };
		$.ajax({
			data:  parametros,
			url:   'agregar_insumo_modal.php',
			type:  'post',
			beforeSend: function () {
				//   $("#carritocompras").html('Cargando...');  
			},
			success:  function (response) {
				alerta_modal("Agregar insumo",response)
			}
		});
	}
	function click_radio(div) {
		var input = div.querySelector('input');
		input.click();
	}
	function alerta_modal(titulo,mensaje){
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html(titulo);
      $("#modal_cuerpo").html(mensaje);
    }
	function lote_recomendado(){
		var idinsumo = $("#ocinsumo").val();
		var parametros = {
                "idinsumo"   : idinsumo,
        };
		$.ajax({
			data:  parametros,
			url:   'lotes_guardados.php',
			type:  'post',
			beforeSend: function () {
				//   $("#carritocompras").html('Cargando...');  
			},
			success:  function (response) {
				alerta_modal("Detalle de Lote en Depositos",response)
			}
		});
	}
	function cerrar_errorestxt(event){
		$("#erroresjs").hide();
	}
	//Funciones nuevas
	function determinarUnidadCompra(){
		var tipos_carga ={"unidad": 1,"bulto":2,"pallet":3, "bulto_edi":4}
		var tipo = tipos_carga["unidad"];
		var medida = document.getElementById('cantidad');
		var id_tipo = medida.getAttribute('data-hidden-id');
		var medida2 =document.getElementById('bulto');
		var medida3 = document.getElementById('pallet');
		var medida_edi = document.getElementById('bulto_edi');
		var cantidad_medida = 0;
		if(medida){

			cantidad_medida = parseFloat(medida?.value);
		}
		var cantidad_medida2 = 0;
		if(medida2){

			cantidad_medida2 = parseFloat(medida2?.value);
		}

		var cantidad_medida3 = 0;
		if(medida3){

			cantidad_medida3 = parseFloat(medida3?.value);
		}
		var cantidad_edi = 0;
		if(medida_edi){

			cantidad_edi = parseFloat(medida_edi?.value);
		} 
		var cantidad_cargada=0;
		var cantidad_ref=0;
		// console.log(" asd" ,cantidad_medida3 , cantidad_medida2);
		if(cantidad_medida3 != 0){
			tipo = tipos_carga["pallet"];
			id_tipo = medida3.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_medida3;
		}
		if(cantidad_medida2 != 0 && cantidad_medida3 == 0){
			tipo = tipos_carga["bulto"];
			id_tipo = medida2.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_medida2;
		}
		if(cantidad_edi != 0){
			tipo = tipos_carga["bulto_edi"];
			id_tipo = medida_edi?.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_edi;
		}
		if(cantidad_medida != 0 && cantidad_medida2 == 0 && cantidad_medida3 == 0){
				tipo = tipos_carga["unidad"];
				id_tipo = medida?.getAttribute('data-hidden-id');
				cantidad_cargada = cantidad_medida;
			}
		// console.log(tipo);
		return {"tipo": tipo, "id_tipo": id_tipo};
	}

	function agregar_insumo_carrito(){
		var resp = determinarUnidadCompra();
		var id_moneda_elegida = $('input[name="radio_moneda"]').filter(':checked').val();
		var nacional = $('input[name="radio_moneda"]').filter(':checked').attr('data-hidden-nacional');
		////////////////////////////////////////////agregar block de preferencias 

		<?php //if ( $multimoneda_local == "N"){?>
			if(id_moneda_elegida == undefined){
				id_moneda_elegida=0;
				nacional = "true";
			}
		<?php //}?>
		// console.log(resp['id_tipo'],resp['tipo'],id_moneda_elegida,nacional,<?php echo "'$idtipo_origen','$id_tipo_origen_importacion'"; ?>);
		
		agregar_insumo(resp['id_tipo'],resp['tipo'],id_moneda_elegida,nacional);
		$("#insumo_text").focus();
	}
	document.addEventListener("DOMContentLoaded", function() {

		$("#formulario_compras_add  input").keydown(function(event) {
		// Verifica si la tecla presionada es "Enter"
		if (event.keyCode === 13) {
			
			// Cancela el comportamiento predeterminado del formulario
			event.preventDefault();
			// Envía el formulario
			$("#formulario_compras_add #btn_agregar").click();
		}});
		$("#insumo_text").focus();
		$('#boxErroresCompras').on('closed.bs.alert', function () {
			$('#boxErroresCompras').removeClass('show');
			$('#boxErroresCompras').addClass('hide');
		});
		});
		function cerrar_errores_compras(){
			$('#boxErroresCompras').removeClass('show');
			$('#boxErroresCompras').addClass('hide');
		}
	function idinsumo_onchange() {

		// Acciones a realizar cuando el valor del input cambie
		var parametros = {
					"idinsumo"   :  $('#insumo').val(),
					"idempresa"  : <?php echo $idempresa;?>
				};
		$.ajax({
				data:  parametros,
				url:   'buscar_insumo.php',
				type:  'post',
				beforeSend: function () {
					//   $("#carritocompras").html('Cargando...');  
				},
				success:  function (response) {
					if (JSON.parse(response)["success"] == true) {
						var medida=JSON.parse(response)["medida"];
						var medida2=JSON.parse(response)["medida2"];
						var medida3=JSON.parse(response)["medida3"];
						var descripcion=JSON.parse(response)["descripcion"];
						var idinsumo=JSON.parse(response)["idinsumo"];
						var idmedida=JSON.parse(response)["idmedida"];
						var idmedida2=JSON.parse(response)["idmedida2"];
						var idmedida3=JSON.parse(response)["idmedida3"];
						var id_cajas_edi=JSON.parse(response)["id_cajas_edi"];
						var cant_medida2=JSON.parse(response)["cant_medida2"];
						var cant_medida3=JSON.parse(response)["cant_medida3"];
						var cant_caja_edi=JSON.parse(response)["cant_caja_edi"];
						var maneja_lote=JSON.parse(response)["usa_lote"];
						
						// console.log(JSON.parse(response));
						este_producto({idinsumo: idinsumo, descripcion: descripcion, medida: medida, medida2: medida2, medida3: medida3, cant_medida2: cant_medida2, cant_medida3: cant_medida3, id_cajas_edi: id_cajas_edi, idmedida2: idmedida2, idmedida3: idmedida3, idmedida: idmedida, cant_caja_edi: cant_caja_edi,maneja_lote: maneja_lote})
						$("#abrecierra").click();
					} else {
						$('#boxErroresCompras').removeClass('hide');
						$("#erroresCompras").html(JSON.parse(response)["errores"]);
						$('#boxErroresCompras').addClass('show');
					}
					
				}
		});
	
	}
	function select_enter(event){
		var target = $(event.target);
		var select = document.getElementById("lprod");

		var optionIndexes = [];

		for (var i = 0; i < select.options.length; i++) {
		var option = select.options[i];
		if (getComputedStyle(option).display !== "none") {
			optionIndexes.push(i);
		}
		}

		if(event.keyCode == 38 && select.selectedIndex === optionIndexes[0]){
			$("#insumo_text").focus();
			return false;  
		}
		var select = document.getElementById("lprod");

		select.addEventListener("keydown", function(event) {
		if (event.key === "Enter") {
			var selectedOption = select.options[select.selectedIndex];
			selectedOption.click();
			// Realiza aquí la acción que deseas al presionar Enter
		}
		});
	}

	function insumo_onchange(e){
		e.preventDefault();
		if (e.keyCode === 40) { // Verificar si se presionó la tecla Tab (código 9)
			// Ejecutar tu función aquí
			$("#lprod").focus();
			return false;  
		}
		

		var input, filter, ul, li, a, i;
	input = document.getElementById("insumo_text");
	filter = input.value.toUpperCase();
	div = document.getElementById("lprod");
	a = div.getElementsByTagName("option");
	for (i = 0; i < a.length; i++) {
		txtValue = a[i].textContent || a[i].innerText;
		categoriaValue = a[i].getAttribute('data-hidden-value');
		codigoOrigen = a[i].getAttribute('data-hidden-codorigen');
		if (txtValue.toUpperCase().indexOf(filter) > -1 || categoriaValue.toUpperCase().indexOf(filter) > -1 || codigoOrigen.toUpperCase().indexOf(filter) > -1) {
		a[i].style.display = "";
		} else {
		a[i].style.display = "none";
		}
	}

	}

	function buscar_codbar(e){
		var codbar = $("#codbar").val();
		tecla = (document.all) ? e.keyCode : e.which;
		// tecla enter
		if (tecla==13){
			// selecciona
			este(0,codbar);
		}
	}
	function este(valor,cbar=''){
			
			var parametros = {
					"insu"   : valor,
					"cbar"   : cbar,
					"p"      : 2
			};
			$.ajax({
					data:  parametros,
					url:   'codbar_insumo.php',
					type:  'post',
					beforeSend: function () {
						$("#selecompra").html('Cargando...');  
					},
					success:  function (response) {
						if (JSON.parse(response)["success"] == true) {
						var medida=JSON.parse(response)["medida"];
						var descripcion=JSON.parse(response)["descripcion"];
						var idinsumo=JSON.parse(response)["idinsumo"];
						
						
						$("#ocinsumo").val(idinsumo);
						$("#medidanombre").html(medida);
						
						$("#myInput").val(descripcion);
						$("#seleccionado").val(idinsumo+'-'+descripcion);
						$("#cantidad").focus();
						//   $("#carritocompras").html(response);
					} else {
						$('#boxErroresCompras').removeClass('hide');
						$("#erroresCompras").html(JSON.parse(response)["errores"]);
						$('#boxErroresCompras').addClass('show');
					}
					}
			});
			setTimeout(function(){ controlar(); }, 200);
	}
	function myFunction() {
		
		document.getElementById("myInput").classList.toggle("show");
		document.getElementById("myDropdown").classList.toggle("show");
		div = document.getElementById("myDropdown");
		$("#myInput").focus();

		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput');
			var myDropdown = $('#myDropdown');
			var div = $("#insumos_dropdown");
			var button = $("#abrecierra");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			$("#myInput").keydown(function(event) {
				if (event.which === 9) {
					$("#myDropdown").children()[0];
				}
			});
		});
	}


	function filterFunction() {
	var input, filter, ul, li, a, i;
	input = document.getElementById("myInput");
	filter = input.value.toUpperCase();
	div = document.getElementById("myDropdown");
	a = div.getElementsByTagName("a");
	for (i = 0; i < a.length; i++) {
		txtValue = a[i].textContent || a[i].innerText;
		categoriaValue = a[i].getAttribute('data-hidden-value');
		codigoOrigen = a[i].getAttribute('data-hidden-codorigen');
		if (txtValue.toUpperCase().indexOf(filter) > -1 || categoriaValue.toUpperCase().indexOf(filter) > -1 || codigoOrigen.toUpperCase().indexOf(filter) > -1) {
		a[i].style.display = "";
		} else {
		a[i].style.display = "none";
		}
	}
	}
	function este_producto(parametros){
		$("#errorestxt").html("");
		$("#erroresjs").hide();
		$("#ocinsumo").val(parametros.idinsumo);
		$("#medidanombre").html(parametros.medida);
		$("#abrecierra").click();
		$("#myInput").val(parametros.descripcion);
		$("#seleccionado").val(parametros.idinsumo+'-'+parametros.descripcion);
		$("#cantidad").focus();

		$('#cantidad').attr('data-hidden-id', parametros.idmedida);
		$('input[name="radio_medida"]').filter(':checked').prop('checked', false);
		$('#lote').attr('data-hidden-lote', parametros.maneja_lote);
		$('#lote').val('');
		if(parametros.mostrar_iva=="TRUE"){
			$('#box_iva_articulo').css("display","block");
			$('#iva_articulo').attr('data-hidden-iva', "true");
		}else{
			$('#box_iva_articulo').css("display","none");
			$('#iva_articulo').attr('data-hidden-iva', "false");
		}

		if (parametros.maneja_lote==1) {
			$('#lote_search').css('display', 'inline');
			$('#lote').prop('disabled', false);
			$('#vencimiento').prop('disabled', false);
			
		}else{
			$('#lote_search').css('display', 'none');
			$('#lote').prop('disabled', true);
			$('#vencimiento').prop('disabled', true);
		}
		$('#vencimiento').val(''); 
		$('#cantidad').val(''); 
		$('#bulto').val(''); 
		$('#bulto_edi').val(''); 
		$('#pallet').val(''); 
		$('#precio_compra').val(''); 
		if(parametros.id_cajas_edi == 0  ){
			$('#boxErroresCompras').removeClass('hide');
			$("#erroresCompras").html("- Medida Cajas EDI no fue creado.<br>");
			$('#boxErroresCompras').addClass('show'); 
		}


		if( (parametros.medida2) == "NULL" || (parametros.medida2) == "" ){
			$('#bulto').prop('disabled', true);
			$('#cajaHelp').css('display', 'inline');
			$('#bulto').val(0);
			$('#box_radio_bulto').css('display', 'none');
		}else{
			$("#medida2").html(parametros.medida2);
			$('#bulto').attr('data-hidden-id', parametros.idmedida2);
			$('#caja_plus').css('display', 'none');
			$('#bulto').prop('disabled', false);
			$('#bulto').attr('data-hidden-cant',parametros.cant_medida2 );
			$('#cajaHelp').css('display', 'none');
			$('#box_radio_bulto').css('display', 'block');
		}
		if( parametros.cant_caja_edi == 0 ){
			$('#bulto_edi').prop('disabled', true);
			$('#cajaEdiHelp').css('display', 'inline');
			$('#bulto_edi').val(0);
			$('#box_radio_edi').css('display', 'none');
		}else{
			$('#bulto_edi').attr('data-hidden-id', parametros.idcajas_edi);
			$('#bulto_edi').prop('disabled', false);
			$('#bulto_edi').attr('data-hidden-cant',parametros.cant_caja_edi );
			$('#cajaEdiHelp').css('display', 'none');
			$('#box_radio_edi').css('display', 'block');
		}
		if( parametros.medida3 == "" || parametros.medida3 == "NULL"  ){
			$('#pallet').prop('disabled', true);
			$('#palletHelp').css('display', 'inline');
			$('#pallet').val(0);
			$('#box_radio_pallet').css('display', 'none');
		}else{
			$('#pallet').attr('data-hidden-id', parametros.idmedida3);
			$("#medida3").html(parametros.medida3);
			$('#pallet_plus').css('display', 'none');
			$('#pallet').prop('disabled', false);
			$('#pallet').attr('data-hidden-cant',parametros.cant_medida3 );
			$('#palletHelp').css('display', 'none');
			$('#box_radio_pallet').css('display', 'block');
		}
		
		
	}

	function cargarMedida(){
		$('#bulto').val(0);
		$('#bulto_edi').val(0);
		$('#pallet').val(0);
		var cant_unidad = $("#cant").val();


		var medida2_input = document.getElementById('bulto');
		var cant_medida2 = medida2_input.getAttribute('data-hidden-cant');

		var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

		if(  cant_medida2 != undefined && cant_unidad%cant_medida2 == 0 ){
			medida2_input.value = (cant_unidad/cant_medida2);
		}

		if(  cant_medida3 != undefined && (cant_unidad/cant_medida2)%cant_medida3 == 0 ){
			medida3_input.value = ((cant_unidad/cant_medida2)/cant_medida3);
		}
	}
	function cargarMedidaEDI(value){
		var medida2_input = document.getElementById('bulto_edi');
		var cant_medida = medida2_input.getAttribute('data-hidden-cant');
		$('#bulto').val(0);
		$('#pallet').val(0);
		$("#cantidad").val(value*cant_medida)
	}
	function cargarMedida2(value,limpiar){
		var medida2_input = document.getElementById('bulto');
		var cant_medida = medida2_input.getAttribute('data-hidden-cant');
		$("#cantidad").val(value*cant_medida)
		if(limpiar == true){
			$('#pallet').val(0);
			$('#bulto_edi').val(0);
		}
		var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

	
		if(  cant_medida3 != undefined && value%cant_medida3 == 0 ){
			medida3_input.value = (value/cant_medida3);
		}
	}
	function cargarMedida3(value){
		var medida2_input = document.getElementById('pallet');
		var cant_medida = medida2_input.getAttribute('data-hidden-cant');
		$("#bulto").val(value*cant_medida);
		$("#bulto_edi").val(0);
		cargarMedida2(value*cant_medida,false);
	}
	//Final funciones nuevas
</script>
<div class="clearfix"></div>
</script>

