<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$ocnum = intval($_GET['id']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from compras_ordenes 
where 
ocnum = $ocnum
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocnum = intval($rs->fields['ocnum']);
$ocn = $ocnum;
$idproveedor = intval($rs->fields['idproveedor']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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
    $idagrupacioncat = antisqlinyeccion($_POST['idagrupacioncat'], "int");
    $origen_costo = substr($_POST['origen_costo'], 0, 2);




    /**if(intval($_POST['idcategoria']) == 0){
        $valido="N";
        $errores.=" - El campo idcategoria no puede ser cero o nulo.<br />";
    }*/


    // si todo es correcto actualiza
    if ($valido == "S") {

        // por seguridad
        $whereadd = "";

        //Vemos para reservar el numero
        $buscar = "Select * from compras_ordenes where ocnum=$ocn";
        $rsocnum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        if (intval($rsocnum->fields['ocnum']) == 0) {
            $insertar = "Insert into compras_ordenes (ocnum,generado_por) values ($ocn,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }

        // actualiza stock actual
        $ahora_date = date("Y-m-d");
        $consulta = "
		update stock_minimo 
		set stock_actual = 
			COALESCE((
			SELECT sum(disponible)
			FROM gest_depositos_stock_gral 
			where 
			gest_depositos_stock_gral.iddeposito = stock_minimo.iddeposito 
			and gest_depositos_stock_gral.idempresa = $idempresa 
			and gest_depositos_stock_gral.idproducto = stock_minimo.idinsumo 
			),0),
			ult_actualizacion = '$ahora'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update stock_minimo 
		set 
		stock_actual_positivo = stock_actual
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update stock_minimo 
		set 
		stock_actual_positivo = 0
		where 
		stock_actual_positivo < 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // corrige sucursal en stock minimo
        $consulta = "
		update stock_minimo set 
		idsucursal = (select gest_depositos.idsucursal from gest_depositos where iddeposito = stock_minimo.iddeposito)   
		where 
		idsucursal <> (select gest_depositos.idsucursal from gest_depositos where iddeposito = stock_minimo.iddeposito)  
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        if (intval($_POST['idagrupacioncat']) > 0) {
            $idagrupacioncat = intval($_POST['idagrupacioncat']);
            // actualiza categoria y subcategoria en insumos
            $consulta = "
			update insumos_lista 
			set 
			insumos_lista.idcategoria = (select productos.idcategoria from productos where idprod_serial = insumos_lista.idproducto) 
			where 
			idproducto is not null
			and insumos_lista.idcategoria <> (select productos.idcategoria from productos where idprod_serial = insumos_lista.idproducto);
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
			update insumos_lista 
			set 
			insumos_lista.idsubcate = (select productos.idsubcate from productos where idprod_serial = insumos_lista.idproducto) 
			where 
			idproducto is not null
			and insumos_lista.idsubcate <> (select productos.idsubcate from productos where idprod_serial = insumos_lista.idproducto);
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $whereadd .= " and insumos_lista.idcategoria in ( select categoria_agrupacion_det.idcategoria from categoria_agrupacion_det where categoria_agrupacion_det.idagrupacioncat =$idagrupacioncat ) ".$saltolinea;
        }

        // ultima factura
        if ($origen_costo == 'UF') {
            $costo_add = "
			COALESCE(insumos_lista.costo,0) as pcosto,
			";
            $whereadd .= " and insumos_lista.idproveedor = $idproveedor ".$saltolinea;
            // lista precio costo proveedores
        } else {
            $costo_add = "
			COALESCE((
			SELECT precio_costo FROM lista_precios_costo_proveedores 
			where 
			idproveedor = $idproveedor 
			and idinsumo = insumos_lista.idinsumo
			and estado_pc = 1
			),0) as pcosto,
			";
            $whereadd .= " and insumos_lista.idinsumo in (select lista_precios_costo_proveedores.idinsumo from lista_precios_costo_proveedores where idproveedor = $idproveedor and estado_pc = 1) ".$saltolinea;
        }


        // inserta en tanda de orden de compras
        $consulta = "
		INSERT INTO compras_ordenes_detalles
		(ocnum, idprod, cantidad, precio_compra,  descripcion) 
		select * from (
			select 
				$ocn as ocn, stock_minimo.idinsumo, 
				(sum(stock_minimo.stock_ideal)-sum(stock_minimo.stock_actual_positivo)-COALESCE((select sum(disponible) from gest_depositos_stock_gral inner join gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito  where gest_depositos.tiposala = 3 and  gest_depositos_stock_gral.idproducto = stock_minimo.idinsumo and gest_depositos_stock_gral.disponible > 0 ),0)) as cant_reponer, 
				$costo_add
				insumos_lista.descripcion
			from stock_minimo 
			inner join insumos_lista on insumos_lista.idinsumo = stock_minimo.idinsumo
			where 
				stock_minimo.idinsumo not in (select idprod from compras_ordenes_detalles where ocnum = $ocn)
				and insumos_lista.hab_compra = 1
				$whereadd
			Group by insumos_lista.idinsumo, insumos_lista.descripcion
			order by insumos_lista.descripcion asc
		) reponer
		where
		cant_reponer > 0
		";
        //echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //header("location: teso_orden_compras.php");
        header("location: compras_ordenes_det.php?id=$ocn");
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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Generar Detalle Automaticamente</h2>
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
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion de Categorias *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idagrupacioncat, nombre_agrupacion
FROM categoria_agrupacion
where
estado = 1
order by nombre_agrupacion asc
 ";

// valor seleccionado
if (isset($_POST['idagrupacioncat'])) {
    $value_selected = htmlentities($_POST['idagrupacioncat']);
} else {
    $value_selected = htmlentities($rs->fields['idagrupacioncat']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idagrupacioncat',
    'id_campo' => 'idagrupacioncat',

    'nombre_campo_bd' => 'nombre_agrupacion',
    'id_campo_bd' => 'idagrupacioncat',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => 'T',
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Origen Costo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <select name="origen_costo" class="form-control" required="required">
    	<option value="">Seleccionar...</option>
    	<option value="UF">Ultima Factura</option>
        <option value="LP">Lista Precios Proveedores</option>
    </select>
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
         <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Generar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes_det.php?id=<?php echo $ocnum; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br /><br /><br />

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
