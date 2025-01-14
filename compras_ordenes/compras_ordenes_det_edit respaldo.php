<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");

require_once("../insumos/preferencias_insumos_listas.php");
require_once("./preferencias_compras_ordenes.php");


$ocseria = intval($_POST['id']);
if ($ocseria == 0) {
    echo "No se recibio el id.";
    exit;
}
$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);
// consulta a la tabla
$consulta = "
select compras_ordenes_detalles.*,
cotizaciones.cotizacion as cotizacion,insumos_lista.idmedida as idmedida1, insumos_lista.idmedida2, insumos_lista.idmedida3,
insumos_lista.cant_medida2, insumos_lista.cant_medida3, insumos_lista.cant_caja_edi,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida) as medida,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida2) as medida2,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida3) as medida3
from compras_ordenes_detalles
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
inner join insumos_lista on insumos_lista.idinsumo = compras_ordenes_detalles.idprod
left JOIN cotizaciones 
on compras_ordenes.idcot = cotizaciones.idcot  
where 
compras_ordenes_detalles.ocseria = $ocseria
and compras_ordenes.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocseria = intval($rs->fields['ocseria']);
$ocnum = intval($rs->fields['ocnum']);
$codprod = intval($rs->fields['idprod']);
$cantidad_original = floatval($rs->fields['cantidad']);
$precio_compra_total = floatval($rs->fields['precio_compra_total']);
$cotizacion = floatval($rs->fields['cotizacion']);
$medida = $rs->fields['medida'];
$medida2 = $rs->fields['medida2'];
$medida3 = $rs->fields['medida3'];
$idmedida1 = $rs->fields['idmedida1'];
$idmedida2 = $rs->fields['idmedida2'];
$idmedida3 = $rs->fields['idmedida3'];
$cant_caja_edi = floatval($rs->fields['cant_caja_edi']);
$cant_medida2 = floatval($rs->fields['cant_medida2']);
$cant_medida3 = floatval($rs->fields['cant_medida3']);
$idmedida = floatval($rs->fields['idmedida']);
$cantidad_medida_inicial = $cantidad_original;
$cantidad_medida2_inicial = 0;
$cantidad_medida3_inicial = 0;
$cantidad_medida_edi_inicial = 0;

$costo_input = $precio_compra / $cantidad_medida_inicial;
if ($idmedida != $idmedida1 && $idmedida != $idmedida2 && $idmedida != $idmedida3) {
    $cantidad_medida_edi_inicial = ($cantidad_medida_inicial / $cant_caja_edi);
}
if ($idmedida != $idmedida1 && $idmedida != $idmedida2 && $idmedida == $idmedida3) {
    $cantidad_medida3_inicial = $cantidad_medida_inicial / ($cant_medida3 * $cant_medida2);
    $cantidad_medida2_inicial = $cantidad_medida_inicial / ($cant_medida2);
    $costo_input_unit = $precio_compra_total / $cantidad_medida3_inicial;
}
if ($idmedida != $idmedida1 && $idmedida == $idmedida2 && $idmedida != $idmedida3) {
    $cantidad_medida3_inicial = number_format($cantidad_medida_inicial / ($cant_medida3 * $cant_medida2), 2);
    $cantidad_medida2_inicial = $cantidad_medida_inicial / ($cant_medida2);
    $costo_input_unit = sprintf("%.2f", $precio_compra_total / $cantidad_medida_inicial);
    $costo_input_medida2 = sprintf("%.2f", $precio_compra_total / $cantidad_medida2_inicial);
}
//echo $idmedida, "---", $idmedida1, "---", $costo_input, "---",$precio_compra,"---" ,$precio_compra_total, "---", $cantidad_medida_inicial; exit;
//echo $cotizacion; exit;
if ($ocseria == 0) {
    echo "Articulo ya no existe en esta orden.";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    //$idprod=antisqlinyeccion($_POST['idprod'],"text");
    $cantidad = antisqlinyeccion($_POST['cantidad'], "float");
    $precio_compra = antisqlinyeccion($_POST['precio_compra'], "float");
    $cant_ref = antisqlinyeccion($_POST['cant_ref'], "float");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
    $tipoUnidad = antisqlinyeccion($_POST['tipoUnidad'], "int");
    $almacenar = antisqlinyeccion($_POST['almacenar'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");

    $precio_total = null;
    $precio_total = $cant_ref * $precio_compra;
    $precio = $precio_total / $cantidad;
    echo $precio_compra,"---",$precio_caja;
    exit;
    if (floatval($_POST['cantidad']) <= 0) {
        $valido = "N";
        $errores .= " - El campo cantidad no puede ser cero o negativo.".$saltolinea;
    }
    if (floatval($_POST['precio_compra']) <= 0) {
        $valido = "N";
        $errores .= " - El campo precio_compra no puede ser cero o negativo.".$saltolinea;
    }
    // $consulta_productos_comprados = "
    // SELECT compras_detalles.codprod, SUM(compras_detalles.cantidad) AS total_comprado
    // FROM compras_detalles
    // INNER JOIN compras ON compras.idcompra = compras_detalles.idcompra AND compras.ocnum = $ocnum
    // where compras_detalles.codprod = $codprod
    // GROUP BY compras_detalles.codprod;
    // ";
    // $rs_comptot = $conexion->Execute($consulta_productos_comprados) or die(errorpg($conexion,$consulta_productos_comprados));
    // $compra_total = floatval($rs_comptot -> fields['total_comprado']);
    // // TODO: modo debug alguna preferencia para mensajes
    // if ( $compra_total > 0 && $compra_total > $cantidad){
    // 	$valido="N";
    // 	$errores.=" - El campo cantidad no puede ser menor a la cantidad ya comprada( $compra_total ), ocnum = ".$ocnum." codprod = ". $codprod ." .".$saltolinea;
    // }



    // si todo es correcto actualiza
    if ($valido == "S") {

        // if($almacenar=="'S'"){
        // 	$idoc_dif = select_max_id_suma_uno("compras_ordenes_diferencias","idoc_dif")["idoc_dif"];
        // 	$diferencia = $cantidad_original - $cantidad;
        // 	$consulta="
        // 	insert into compras_ordenes_diferencias
        // 	(idoc_dif, cant_original, diferencia, estado, registrado_el, ocnum, registrado_por, idprod)
        // 	values
        // 	($idoc_dif, $cantidad_original, $diferencia, 1, $registrado_el, $ocnum, $registrado_por, $codprod)
        // 	";
        // 	$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        // }



        // $cant_transito = $transito - $cantidad;

        // // update de carga transito y verificar si la orden esta completa
        // $consulta = "SELECT cmp_dt.ocseria,cmp_dt.idprod, (cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante
        // FROM compras_ordenes_detalles AS cmp_dt
        // INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
        // LEFT JOIN (
        // 	SELECT cmp_det.codprod, SUM(cmp_det.cantidad) AS total_comprado
        // 	FROM compras_detalles AS cmp_det
        // 	INNER JOIN compras AS cmp ON cmp.idcompra = cmp_det.idcompra AND cmp.ocnum = $ocnum
        // 	GROUP BY cmp_det.codprod
        // ) AS cmp_comprado ON cmp_comprado.codprod = cmp_dt.idprod
        // WHERE cmp_dt.ocnum = $ocnum";


        // $orden_items_faltantes = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        // $carga_completa = "'S'";
        // while (!$orden_items_faltantes->EOF){
        // 	$idprod = intval($orden_items_faltantes->fields['idprod']);
        // 	if($idprod > 0){
        // 		$faltante = floatval($orden_items_faltantes->fields['cantidad_faltante']);
        // 		$ocseria = intval($orden_items_faltantes->fields['ocseria']);
        // 		if($faltante != 0){
        // 			$carga_completa = "'N'";
        // 		}
        // 	}
        // }

        // $consulta="
        // update compras_ordenes
        // set
        // 	carga_completa=$carga_completa
        // where
        // 	ocnum=$ocnum
        // ";
        // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        $consulta = "
		update compras_ordenes_detalles
		set
			cantidad=$cantidad,
			cant_transito=$cantidad,
			precio_compra=$precio,
			precio_compra_total=$precio_total,
			idmedida=$idmedida
		where
			ocseria=$ocseria
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




        if ($proveedores_importacion == "S") {
            $consulta = "
			SELECT SUM(compras_ordenes_detalles.precio_compra_total) as costo_total
			FROM `compras_ordenes`
			INNER JOIN compras_ordenes_detalles on compras_ordenes_detalles.ocnum = compras_ordenes.ocnum
			WHERE compras_ordenes.ocnum= $ocnum
			";
            $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $cotizacion_referencia = $cotizacion;
            $precio_total = floatval($rs2->fields['costo_total']);
            if ($cotizacion_referencia > 0) {
                $precio_total = ($precio_total * $cotizacion_referencia);
                $buscar = "UPDATE compras_ordenes 
				set 
				costo_ref = $precio_total
				where ocnum=$ocnum";
                $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            }
        }
        //fin actualizacion



    }

    $arr = [
    'valido' => $valido,
    'errores' => $errores
    ];

    //print_r($arr);

    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;
    exit;

}
//echo $cant_medida2, "---", $cant_medida3;exit;

?>
<script>
	function cargarMedidaEdit(){
    $('#form1Edit #bulto').val(0);
    $('#form1Edit #bulto_edi').val(0);
    $('#form1Edit #pallet').val(0);
    var cant_unidad = $("#cantidad").val();

    var cant_medida2 = <?php echo $cant_medida2 ? $cant_medida2 : 1;  ?>;
    var cant_medida3 = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;

    if (cant_medida2 != undefined && cant_unidad % cant_medida2 == 0 ){
        $("#form1Edit #bulto").val(cant_unidad / cant_medida2);
    }

    if (cant_medida3 != undefined && (cant_unidad / cant_medida2) % cant_medida3 == 0 ){
        $("#form1Edit #pallet").val((cant_unidad / cant_medida2) / cant_medida3);
    }
}

	function cargarMedida2Edit(value,limpiar){
		var medida2_input = document.querySelector('#form1Edit #bulto');
		var cant_medida = <?php echo $cant_medida2 ? $cant_medida2 : 1;  ?>;
		$("#cantidad").val(value*cant_medida)
		if(limpiar == true){
			$('#form1Edit #pallet').val(0);
			$('#form1Edit #bulto_edi').val(0);
		}

		var medida3_input = $("#form1Edit #pallet");
		var cant_medida3 = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;

	
		if(  cant_medida3 != undefined && value%cant_medida3 == 0 ){
			medida3_input.val(value/cant_medida3);
		}
	}
	function cargarMedida3Edit(value){
		var medida2_input = document.querySelector('#form1Edit #pallet');
		var cant_medida = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;
		$("#form1Edit #bulto").val(value*cant_medida);
		$("#form1Edit #bulto_edi").val(0);
		cargarMedida2Edit(value*cant_medida,false);
	}
	function cargarMedidaEdiEdit(value){
		var medida2_input = document.querySelector('#form1Edit #bulto_edi');
		var cant_medida = <?php echo $cant_caja_edi ? $cant_caja_edi : 1; ?>;
		$('#form1Edit #bulto').val(0);
		$('#form1Edit #pallet').val(0);
		$("#form1Edit #cantidad").val(value*cant_medida)
	}
	// function guardar_diferencia(valor){
	// 	var cantidad = <?php echo $cantidad_original; ?>;
	// 	if ( valor != ""  && valor != 0 && (parseInt(valor) < cantidad)){
	// 		$("#almacenar_box").css("display", "block");
	// 	}else{
	// 		$("#almacenar_box").css("display", "none");
	// 	}
	// }
	function cerrar_alert(event){
		event.preventDefault();
		$("#form1Edit #error_box").css('display','none');
	}
</script>
<style>
	
</style>
<form id="form1Edit" name="form1Edit" method="post" action="">
	<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box" style="display:none;">
	<button type="button" class="close" onclick="cerrar_alert(event)"><span aria-hidden="true">×</span></button>
	<strong>Errores:</strong><br /><span id="error_box_msg"><?php echo $errores; ?></span>
	</div>

<div class="form-group col-md-6 col-xs-12">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" disabled  />                    
	</div>
</div>
<div class="form-group col-md-6 col-xs-12">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text"  onchange="cargarMedidaEdit(this.value)" name="cantidad" id="cantidad" value="<?php  if (isset($_POST['cantidad_modif"'])) {
		    echo floatval($_POST['cantidad_modif"']);
		} else {
		    echo floatval($rs->fields['cantidad']);
		}?>" placeholder="Cantidad" class="form-control" required />                    
		<!-- onkeyup="guardar_diferencia(this.value)" se comenta la opcion de guardar diferencias  -->
	</div>
</div>

<?php if ($preferencias_medidas_referenciales == "S") { ?>

								
<!-- MEDIDAS 2  -->
<div class="form-group col-md-6 col-xs-12" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">
		<a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
			<span class="fa fa-plus"></span>
		</a>
		<?php if ($medida2) { ?>
			<div id="medida2"><?php echo $medida2; ?>:</div>
		<?php } else { ?>
			<div id="medida2">Medida2:</div>
			<?php } ?>
	</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php if ($cant_medida2 > 0) { ?>
		<input  class="form-control" onchange="cargarMedida2Edit(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="<?php echo $cantidad_medida2_inicial;?>" size="10" />	
		<?php } else { ?>
			<input disabled class="form-control" onchange="cargarMedida2Edit(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
	<?php } ?>
		<small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
	</div>
</div>


<!-- MEDIDAS INICIO 3 -->
<div class="form-group col-md-6 col-xs-12">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">
        <a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
            <span class="fa fa-plus"></span>
        </a>
        <?php if ($medida3) { ?>
            <div id="medida3"><?php echo $medida3; ?>:</div>
        <?php } else { ?>
            <div id="medida3">Medida3:</div>
        <?php } ?>
    </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php if ($cant_medida3 > 0) { ?>
        <input aria-describedby="palletHelp" onchange="cargarMedida3Edit(this.value)" type="text" class="form-control" name="pallet" id="pallet" value="<?php echo $cantidad_medida3_inicial;?>" size="10" disabled />
    <?php } else { ?>
        <input disabled aria-describedby="palletHelp" onchange="cargarMedida3Edit(this.value)" type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
    <?php } ?>
        <span style="color: red; font-weight: bold; font-family: 'Arial', sans-serif;">Valor Referencial</span>
        <small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong>  asignadas, favor agregar en insumos.</small>
    </div>
</div>

<!-- MEDIDAS EDI  -->
<div class="form-group col-md-6 col-xs-12" style="display:none;">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">
		<a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
			<span class="fa fa-plus"></span>
		</a>
		<div id="medida4">Cajas EDI:</div>
	</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php if ($cant_caja_edi > 0) { ?>
		<input  class="form-control" onkeyup="cargarMedidaEdiEdit(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="<?php echo $cantidad_medida_edi_inicial;?>" size="10" />	
	<?php } else { ?>
		<input disabled class="form-control" onkeyup="cargarMedidaEdiEdit(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
	<?php } ?>
		<small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
	</div>
</div>
<!-- FIN DE MEDIDAS -->
<?php } ?>


<div class="form-group col-md-6 col-xs-12">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Compra *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="precio_compra" id="precio_compra" value="<?php if (isset($_POST['precio_compra'])) {
            echo floatval($_POST['precio_compra']);
        } else {
            echo floatval($costo_input_unit);
        }?>" placeholder="Precio compra unitario" class="form-control" required />
        <span style="color: red; font-weight: bold; font-family: 'Arial', sans-serif;">Precio de compra por unidad</span>
    </div>
</div>

<div class="form-group col-md-6 col-xs-12">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio por <?php echo $medida2; ?></label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="precio_caja" id="precio_caja" value="<?php echo isset($_POST['precio_caja']) ? htmlspecialchars($_POST['precio_caja']) : htmlspecialchars($costo_input_medida2); ?>" placeholder="Precio compra por <?php echo $medida2; ?>" class="form-control" />
    </div>
</div>

<div class="form-group col-md-6 col-xs-12" style="display:none;" id="almacenar_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Almacenar </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php

            // valor seleccionado
            if (isset($_POST['almacenar'])) {
                $value_selected = htmlentities($_POST['almacenar']);
            } else {
                $value_selected = 'S';
            }
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'almacenar',
    'id_campo' => 'almacenar',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" aria-describedby="cantidadHelp" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
		
		<small id="cantidadHelp"  class="form-text text-muted"><p>Se ha detectado una cantidad menor a la cantidad inicial. ¿Desea almacenar la cantidad faltante para una orden futura? </br></p> </small>
	</div>
</div>

<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
							<!-- //////////////////////////////////////////////////////////////////////// -->
							<!-- //////////radio medida comienza////////////////////// -->

							<div class="col-md-12">
								<div class="form-group col-md-6 col-sm-6 col-xs-12">
									<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida de Compra</label>
									<div class="col-md-8 col-sm-8 col-xs-12">
									<div class="row radios_box" style="display: flex;flex-direction: column;">
										<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_unidad">
												<input checked class="form-check-input" <?php if ($idmedida == $idmedida1) { ?>checked<?php } ?> `data-hidden-value=<?php echo $idmedida1; ?> value="1" type="radio" name="radio_medida_form1" id="radio_unidad_form1" >
												<label class="form-check-label" id="label_unidad" for="radio_unidad_form1">
													UNIDAD
												</label>
											</div>
											<?php if ($cant_medida2 > 0) { ?>
												<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_bulto" >
													<input class="form-check-input" <?php if ($idmedida == $idmedida2) { ?> checked<?php } ?> data-hidden-value=<?php echo $idmedida2; ?> value="1" type="radio" name="radio_medida_form1" id="radio_bulto_form1" >
													<label class="form-check-label" id="label_bulto" for="radio_bulto_form1">
														CAJA
													</label>
												</div>
											<?php } ?>
								
											<?php if ($cant_medida3 > 0) { ?>
												<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_pallet" >
													<input class="form-check-input" <?php if ($idmedida == $idmedida3) { ?>checked<?php }  ?> data-hidden-value=<?php echo $idmedida3; ?> value="3" type="radio" name="radio_medida_form1" id="radio_pallet_form1" >
													<label class="form-check-label" id="label_pallet" for="radio_pallet_form1">
														PALLET
													</label>
												</div>
											<?php } ?>
											<?php if ($cant_caja_edi > 0) { ?>
											<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_edi" >
												<input class="form-check-input" data-hidden-value=<?php echo $id_cajas_edi; ?> value="4" type="radio" name="radio_medida_form1" id="radio_edi_form1" >
												<label class="form-check-label"  id="label_EDI" for="radio_edi_form1">
													CAJA EDI
												</label>
											</div>
											<?php } ?>
										</div>
									</div>
								</div>
								
							</div>
							<!-- ////////////////////////////////////////////////////////////////////////// -->
							<!-- ////////////////////////////radio medida fin ///////////////////////////// -->
							<?php } ?>





<div class="clearfix"></div>
<br />

    <div class="form-group ">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="button" class="btn btn-success" onMouseUp="editar_oc_reg(<?php echo intval($ocseria); ?>);" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="$('#modal_ventana').modal('hide');"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


