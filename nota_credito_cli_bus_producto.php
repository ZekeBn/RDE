<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
require_once("includes/rsusuario.php");


$factura = antisqlinyeccion($_POST['factura'], "text");
$idventa = antisqlinyeccion($_POST['idventa'], "int");
$clase = intval($_POST['clase']); // 1 articulo 2 monto global
$idnotacred = intval($_POST['idnotacred']);



$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 nota_credito_cabeza.estado = 1 
 and nota_credito_cabeza.idnotacred = $idnotacred
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$motivo = antixss($rs->fields['motivo']);
if ($idnotacred == 0) {
    echo "- Nota de credito inexistente.<br />";
    exit;
}
/*if($_POST['factura'] != ''){
    $whereadd=" and factura = $factura ";
}
if($_POST['idventa'] > 0){
    $whereadd=" and idventa = $idventa ";
}
if(intval($_POST['idventa']) == 0  && trim($_POST['factura']) == ''){
    echo "- No se indico la factura ni el idventa, debe especificar al menos 1 de los 2.<br />";
    exit;
}*/
if (intval($_POST['clase']) == 0) {
    echo "- No especifico la forma de aplicar.<br />";
    exit;
}


?><hr />
<strong>Producto:</strong><br />


<?php
// articulo
if ($clase == 1) {

    $producto = antisqlinyeccion($_POST['producto'], "text-notnull");
    $codbar = antisqlinyeccion($_POST['codbar'], "text-notnull");
    if (trim($_POST['producto']) != '') {
        $whereadd = "and productos.descripcion like '%$producto%'";
    } else {
        $whereadd = "and barcode  = '$codbar'";
    }

    $consulta = "
select productos.idprod_serial as idprod, 
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
productos.descripcion as producto
from productos
where 
productos.borrado = 'N'
$whereadd
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Cantidad</th>
            <th align="center">Precio Unitario</th>
			<?php if ($_POST['clase'] == 1) {?><th align="center">Deposito</th><?php } ?>
			<th align="center">Codigo</th>
			<th align="center">Codigo Barras</th>
			<th align="center">Articulo</th>


			
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0);" onMouseUp="cargar_articulo_sinfactura(<?php echo $rs->fields['idprod']; ?>,<?php echo $clase; ?>);" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
				</div>

			</td>
			<td align="center"><input name="idprod_cant_<?php echo intval($rs->fields['idprod']); ?>" id="idprod_cant_<?php echo intval($rs->fields['idprod']); ?>" type="text" value="" class="form-control"></td>
			<td align="center"><input name="idprod_monto_<?php echo intval($rs->fields['idprod']); ?>" id="idprod_monto_<?php echo intval($rs->fields['idprod']); ?>" type="text" value="" class="form-control"></td>
			<?php if ($_POST['clase'] == 1) {?>
            <td align="center"><?php

    $idprod = intval($rs->fields['idprod']);

			    // consulta
			    $consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

			    // valor seleccionado
			    if (isset($_POST['iddeposito_'.$idprod])) {
			        $value_selected = htmlentities($_POST['iddeposito_'.$idprod]);
			    } else {
			        $value_selected = htmlentities($rs->fields['iddeposito']);
			    }

			    // parametros
			    $parametros_array = [
			        'nombre_campo' => 'iddeposito_'.$idprod,
			        'id_campo' => 'iddeposito_'.$idprod,

			        'nombre_campo_bd' => 'descripcion',
			        'id_campo_bd' => 'iddeposito',

			        'value_selected' => $value_selected,

			        'pricampo_name' => 'Seleccionar...',
			        'pricampo_value' => '',
			        'style_input' => 'class="form-control"',
			        'acciones' => ' required="required" ',
			        'autosel_1registro' => 'S'

			    ];

			    // construye campo
			    echo campo_select($consulta, $parametros_array);

			    ?></td>
<?php } ?>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['barcode']);  ?></td>
            <td align="left"><?php echo antixss($rs->fields['producto']);  ?></td>





		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>

<?php } ?>
<?php
// monto global
if ($clase == 2) {

    echo "Monto global no se puede realizar sin aplicar a una factura.";
    exit;
    ?>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Total Factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="totalcobrar" id="totalcobrar" value="<?php  if (isset($_POST['totalcobrar'])) {
	    echo intval($_POST['totalcobrar']);
	} else {
	    echo intval($sugiere_nota);
	}?>" placeholder="Totalcobrar" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="concepto" id="concepto" value="<?php  if (isset($_POST['concepto'])) {
	    echo intval($_POST['concepto']);
	} else {
	    echo $motivo;
	}?>" placeholder="Ej: descuento concedido por xxx" class="form-control" required="required" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="button" class="btn btn-default" onMouseUp="cargar_monto_factura(<?php echo $idventa; ?>);" ><span class="fa fa-plus"></span> Agregar</button>
        </div>
    </div>

<?php } ?>
<?php
// monto articulo
if ($clase == 3) {

    $producto = antisqlinyeccion($_POST['producto'], "text-notnull");
    $codbar = antisqlinyeccion($_POST['codbar'], "text-notnull");
    if (trim($_POST['producto']) != '') {
        $whereadd = "and productos.descripcion like '%$producto%'";
    } else {
        $whereadd = "and barcode  = '$codbar'";
    }

    $consulta = "
select productos.idprod_serial as idprod, 
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
productos.descripcion as producto
from productos 
where 
productos.borrado = 'N'
$whereadd
order by productos.descripcion asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<strong>Detalle de la Factura:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Monto Aplicar</th>
			<th align="center">Codigo</th>
			<th align="center">Codigo Barras</th>
			<th align="center">Articulo</th>


			
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0);" onMouseUp="cargar_monto_articulo_sinfactura(<?php echo $rs->fields['idprod']; ?>,<?php echo $clase; ?>);" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
				</div>

			</td>
			<td align="center"><input name="idprod_<?php echo intval($rs->fields['idprod']); ?>" id="idprod_<?php echo intval($rs->fields['idprod']); ?>" type="text" value="" class="form-control"></td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['barcode']);  ?></td>
            <td align="left"><?php echo antixss($rs->fields['producto']);  ?></td>





		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>

<?php } ?>
<div class="clearfix"></div>
