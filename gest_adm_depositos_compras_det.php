<?php
/*-----------------------------------------
18/04/2023


------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
require_once("includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("includes/rsusuario.php");

$idcompra = intval($_GET['id']);

// funciones para stock
require_once("includes/funciones_stock.php");


$idcompra = intval($_GET['idcompra']);
if ($idcompra == 0) {
    header("location: gest_adm_depositos_compras.php");
    exit;
}

$buscar = "
Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
where 
revisado_por=0 
and compras.estado <> 6
and compras.idcompra = $idcompra
order by gest_depositos_compras.fecha_compra desc 
limit 1
";
//echo $buscar;
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcompra = intval($rs->fields['idcompra']);
if ($idcompra == 0) {
    header("location: gest_adm_depositos_compras.php");
    exit;
}

$resultado = relacionar_gastos($resultado);
echo $resultado;
exit;



if (isset($_POST['occompra']) && ($_POST['occompra'] > 0)) {

    $buscar = "Select * from preferencias_compras limit 1";
    $rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    $deposito = intval($_POST['iddeposito']);
    $iddeposito = $deposito;
    $idcompra = intval($_POST['occompra']);
    $idproveedor = intval($_GET['proveedor']);
    //Depositos
    // se muestran los prodctos del deposito
    $buscar = "
	Select 
	iddeposito,descripcion 
	from gest_depositos 
	where 
	iddeposito=$deposito 
	and idempresa = $idempresa
	and estado = 1
	and tiposala <> 3
	";
    $rsptos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tiposala = intval($rsptos->fields['tiposala']);
    $iddeposito = intval($rsptos->fields['iddeposito']);
    //obtiene facturas de proveedores
    $consulta = "
	select 
	id_factura, fecha_compra, id_proveedor, factura_numero, fecha_compra, idcompra
	from facturas_proveedores 
	where 
	idcompra = $idcompra
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_factura = $rsfac->fields['id_factura'];
    $fecha_compra = $rsfac->fields['fecha_compra'];
    $idprov = $rsfac->fields['id_proveedor'];
    $factura_numero = $rsfac->fields['factura_numero'];
    $fecha_compra = $rsfac->fields['fecha_compra'];
    $idcompra = $rsfac->fields['idcompra'];
    if (intval($id_factura) == 0) {
        echo "Factura inexistente!";
        exit;
    }

    if (intval($iddeposito) == 0) {
        echo "Deposito inexistente!";
        exit;
    }

    /* //Paso2 Actualizar ubicacion en costo_productos (SE HACE ABAJO EN EL WHLE)
    $update="Update costo_productos set ubicacion=$deposito where idcompra=$idcompra";
    $conexion->Execute($update) or die(errorpg($conexion,$update));
    */

    if ($valido == 'S') {
        $parametros_array = [
            'id_factura' => $id_factura,
            'fecha_compra' => $fecha_compra,
            'id_proveedor' => $idprov,
            'factura_numero' => $factura_numero,
            'fecha_compra' => $fecha_compra,
            'idcompra' => $idcompra,
            "usar_depositos_asignados" => $usar_depositos_asignados,
            "deposito" => $deposito,
            "iddeposito" => $iddeposito,
            "tiposala" => $tiposala,
            "idempresa" => $idempresa,
            "idsucursal" => $idsucursal,
            "idusu" => $idusu
        ];
        verificar_compra($parametros_array);



        //header("location: gest_adm_depositos_compras.php".$uriadd);
        header("location: compra_verificada.php?id=".$idcompra);
        exit;


    } // if($valido == 'S'){

}


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Verificar compra / Ingreso al stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<p>
	<a href="javascript:history.back();void(0);" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a>
</p>
<hr />  
<strong>Compras finalizadas (los productos aun  no ingresaron al stock)</strong><br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Transacci&oacute;n</th>
            <th align="center">Id Compra</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condici&oacute;n</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php

while (!$rs->EOF) { ?>
		<tr>

			<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
            <td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<?php
// consulta a la tabla
$consulta = "
select * , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, 
(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
(select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto
from compras_detalles 
inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
where 
idcompra = $idcompra
order by insumos_lista.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);



?>
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">C&oacute;digo</th>
            <th align="center">Producto</th>
			<?php if ($depodefecto == 'S') { ?>
			<th align="center">Depo&sacute;ito asignado</th>
			<?php } ?>
            <th align="center">Concepto</th>
			<th align="center">Cantidad</th>
			<th align="center">Costo</th>
			<th align="center">Subtotal</th>
			<th align="center">Iva %</th>
			<th align="center">Lote</th>
			<th align="center">Vencimiento</th>
			
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


			<td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<?php if ($depodefecto == 'S') { ?>
			<td align="center"><?php echo antixss($rs->fields['deposito_por_defecto']); ?></td>
			<?php } ?>
			<td align="center"><?php echo antixss($rs->fields['concepto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['iva']); ?>%</td>
			<td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
			<td align="center"><?php if ($rs->fields['vencimiento'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vencimiento']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select gest_depositos.descripcion as deposito, facturas_proveedores.iddeposito, sucursales.nombre as sucursal,
facturas_proveedores.fecha_valida, facturas_proveedores.validado_por,
(select usuario from usuarios where idusu = facturas_proveedores.validado_por) as usu_validado_por
from facturas_proveedores 
inner join gest_depositos on gest_depositos.iddeposito = facturas_proveedores.iddeposito
inner join sucursales on sucursales.idsucu = gest_depositos.idsucursal
where 
idcompra = $idcompra
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="warning">
<strong>Estado del Stock:</strong> 
<?php if ($rs->fields['iddeposito'] > 0) { ?>
Ingresado<br />
<strong>Deposito:</strong>  <?php echo $rs->fields['deposito']; ?> [<?php echo $rs->fields['iddeposito']; ?>]<br />
<strong>Local:</strong>  <?php echo $rs->fields['sucursal']; ?><br />
<strong>Fecha Validado:</strong>  <?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_valida'])); ?><br />
<strong>Usuario Validador:</strong>  <?php echo $rs->fields['usu_validado_por']; ?><br />
<?php } else { ?>
Pendiente de ingreso
<?php } ?>
</div>
<div class="clearfix"></div>
<hr />
<form id="form1" name="form1" method="post" action="">
<?php if ($depodefecto == 'S') { ?>
<div class="col-md-12">
<div class="alert alert-danger" role="alert">
Atencion: El ingreso de los art&iacute;culos a dep&oacute;sitos asignados, se encuentra activo.Si desea dar ingreso a un dep&oacute;sito diferente,<br />
 seleccione de la lista desplegable , de esta forma,los art&iacute;culos ser&aacute;n ingresados al dep&oacute;sito seleccionado, y no a los establecios previamente.
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['iddeposito'])) {
        $value_selected = htmlentities($_POST['iddeposito']);
    } else {
        $value_selected = htmlentities($rs->fields['iddeposito']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'iddeposito',
        'id_campo' => 'iddeposito',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'iddeposito',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
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
<div class="col-md-6">


</div>







</div>
<?php } else { ?>
 
<strong>Dep&oacute;sito de Ingreso</strong> 





<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['iddeposito'])) {
        $value_selected = htmlentities($_POST['iddeposito']);
    } else {
        $value_selected = htmlentities($rs->fields['iddeposito']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'iddeposito',
        'id_campo' => 'iddeposito',

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

    ?>
	</div>
</div>

<?php } ?>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_adm_depositos_compras.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
    
	<input type="hidden" name="occompra" id="occompra" value="<?php echo $idcompra?>" />
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
