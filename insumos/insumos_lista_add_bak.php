<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$usa_concepto = $rsco->fields['usa_concepto'];
$idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
$idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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


    // recibe parametros
    $idproducto = antisqlinyeccion('', "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $idconcepto = antisqlinyeccion($_POST['idconcepto'], "int");
    $idcategoria = antisqlinyeccion('', "int");
    $idsubcate = antisqlinyeccion('', "int");
    $idmarcaprod = antisqlinyeccion('', "int");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
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
    /*if(floatval($_POST['costo']) <= 0){
        $valido="N";
        $errores.=" - El campo costo no puede ser cero o negativo.<br />";
    }*/
    if (trim($_POST['idtipoiva_compra']) == '') {
        $valido = "N";
        $errores .= " - El campo iva compra no puede estar vacio.<br />";
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
    if ($rsexpr->fields['idprod_serial'] > 0) {
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

    //print_r($_POST);exit;

    // si todo es correcto inserta
    if ($valido == "S") {

        $buscar = "select max(idinsumo) as mayor from insumos_lista";
        $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idinsumo = intval($rsmayor->fields['mayor']) + 1;

        $consulta = "
		insert into insumos_lista
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo,  idtipoiva, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el)
		values
		($idinsumo, $idproducto, $descripcion, $idconcepto, $idcategoria, $idsubcate, $idmarcaprod, $idmedida, $produccion, $costo, $idtipoiva_compra, $tipoiva_compra, $mueve_stock, $paquete, $cant_paquete, $estado, $idempresa, $idgrupoinsu, $ajuste, $fechahora, $registrado_por_usu, $hab_compra, $hab_invent, $idproveedor, $aplica_regalia, $solo_conversion, $respeta_precio_sugerido, $idprodexterno, $restaurado_por, $restaurado_el)
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
        header("location: insumos_lista_add.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Agregar Articulo</h2>
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

Esta seccion es para crear articulos que <strong style="color:#F00">NO SE VENDERAN</strong>, si desea crear un producto para vender hacerlo en:  <a href="gest_listado_productos.php"  class="btn btn-sm btn-default"><span class="fa fa-external-link"></span> productos</a>.
<hr />
<form id="form1" name="form1" method="post" action="">



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" required autofocus />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT id_medida, nombre
FROM medidas
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idmedida'])) {
    $value_selected = htmlentities($_POST['idmedida']);
} else {
    $value_selected = htmlentities($rs->fields['idmedida']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmedida',
    'id_campo' => 'idmedida',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_medida',

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

// valor seleccionado
if (isset($_POST['idtipoiva_compra'])) {
    $value_selected = htmlentities($_POST['idtipoiva_compra']);
} else {
    $value_selected = $idtipoiva_compra_pred;
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
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>



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
    $value_selected = htmlentities($rs->fields['idgrupoinsu']);
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
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor </label>
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
    $value_selected = htmlentities($rs->fields['idproveedor']);
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<select name="hab_compra" id="hab_compra"  title="Habilita Compra" class="form-control" required>
       <option value="">Seleccionar</option>
       <option value="1" <?php if ($_POST['hab_compra'] == '1') {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if ($_POST['hab_compra'] == '0') {?> selected="selected" <?php } ?>>NO</option>
       </select>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Habilita inventario *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<select name="hab_invent" id="hab_invent"  title="Habilita Compra" class="form-control" required>
       <option value="" >Seleccionar</option>
       <option value="1" <?php if ($_POST['hab_invent'] == '1') {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if ($_POST['hab_invent'] == '0') {?> selected="selected" <?php } ?>>NO</option>
       </select>
	</div>
</div>




<?php if ($usa_concepto == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
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
        $value_selected = htmlentities($rs->fields['idconcepto']);
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
<?php
$contabilidad = intval($rsco->fields['contabilidad']);
if ($contabilidad == 1) {
    ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Art Contable *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
SELECT cuenta, descripcion
FROM cn_plancuentas_detalles
where 
estado<>6 
and asentable='S' 
order by idserieun asc
 ";

    // valor seleccionado
    if (isset($_POST['cuentacont'])) {
        $value_selected = htmlentities($_POST['cuentacont']);
    } else {
        $value_selected = htmlentities($rs->fields['cuentacont']);
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
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
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
            
<?php
    $consulta = "
select *,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
(select usuario from usuarios where idusu = insumos_lista.registrado_por_usu ) as registrado_por
from insumos_lista 
where 
 estado = 'A' 
order by fechahora desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos 10 Agregados</h2>
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
			<th></th>
			<th align="center">Codigo Articulo</th>
			<th align="center">Codigo Producto</th>
			<th align="center">Articulo</th>
			<th align="center">Grupo Stock</th>
			<th align="center">Medida</th>
			<th align="center">Ult. Costo</th>
			<th align="center">IVA %</th>
			<th align="center">Habilita compra</th>
			<th align="center">Habilita inventario</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="insumos_lista_edit.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <?php if (intval($rs->fields['idproducto']) == 0) { ?>
					<a href="insumos_lista_del.php?id=<?php echo $rs->fields['idinsumo']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['grupo_stock']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoiva']); ?>%</td>
			<td align="center"><?php if ($rs->fields['hab_compra'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
			<td align="center"><?php if ($rs->fields['hab_invent'] == 1) {
			    echo "SI";
			} else {
			    echo "NO";
			} ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if (trim($rs->fields['fechahora']) != '') {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
