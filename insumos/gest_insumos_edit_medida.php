<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../insumos/preferencias_insumos_listas.php");

//Categorias
$buscar = "Select * from categorias where idempresa = $idempresa order by nombre ASC";
$rscate2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));




$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
medidas.nombre LIKE \"%cajas\" order by nombre asc";
$rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = $rs_cajas->fields['id_medida'];
$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 
AND medidas.nombre LIKE \"%pall%\" order by nombre asc ";
$rs_pallets = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpallet = $rs_pallets->fields['id_medida'];



$idinsu = intval($_GET['id']);
if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}
$buscar = "select *, (select descripcion from productos where idprod_serial = insumos_lista.idproducto) as producto,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida) as medida
 from insumos_lista where idinsumo=$idinsu and idempresa = $idempresa";
$rsconecta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idinsu = intval($rsconecta->fields['idinsumo']);
if ($idinsu == 0) {
    header("location: insumos_lista.php");
    exit;
}


$buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $idinsumo = antisqlinyeccion($idinsu, "int");
    $idmedida = antisqlinyeccion($_POST['id_medida'], "text");
    $idmedida2 = antisqlinyeccion($_POST['id_medida2'], "text");
    $idmedida3 = antisqlinyeccion($_POST['id_medida3'], "text");
    $cant_medida2 = antisqlinyeccion($_POST['cant_medida2'], "text");
    $cant_medida3 = antisqlinyeccion($_POST['cant_medida3'], "text");


    // validaciones basicas
    $valido = "S";
    $errores = "";

    if (intval($idinsu) == 0) {
        $valido = "N";
        $errores .= " - El campo idinsumo no puede ser cero o nulo.<br />";
    }




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
			idmedida=$idmedida,
			idmedida2=$idmedida2,
			idmedida3=$idmedida3,
			cant_medida2=$cant_medida2,
			cant_medida3=$cant_medida3
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
		'$ahora',$idusu, acepta_devolucion,idplancuentadet,idcentroprod,idagrupacionprod,
		rendimiento_porc
		from insumos_lista
		where 
		idinsumo = $idinsu
		limit 1;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update productos 
		set 
		idmedida = $idmedida
		where
		idprod_serial in (select idproducto from insumos_lista where idinsumo = $idinsu and idproducto is not null)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update recetas_produccion 
		set 
		medida = COALESCE(( 
				select insumos_lista.idmedida 
				from prod_lista_objetivos 
				inner join insumos_lista on insumos_lista.idinsumo = prod_lista_objetivos.idinsumo 
				where 
				prod_lista_objetivos.unicopkss = recetas_produccion.idobjetivo 
				and prod_lista_objetivos.estado = 1 
				),0)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: gest_insumos_edit.php?id=".$idinsu);
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
                    <h2>Editar Medida</h2>
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

<strong>IMPORTANTE:</strong> Modificar la medida no realizara conversiones en las cantidades de movimientos ya realizados, si tiene en inventario 10 Gramos y convierte la medida a KG o Unidades la cantidad luego del cambio sera 10 KG o Unidades. HAGALO BAJO SU PROPIO RIESGO.
<hr />
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="descripcion" id="descripcion" value="<?php   echo htmlentities($rsconecta->fields['descripcion']); ?>" placeholder="Descripcion" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
        // consulta
        $consulta = "select * from medidas  where estado = 1";

// valor seleccionado
if (isset($_POST['id_medida'])) {
    $value_selected = htmlentities($_POST['id_medida']);
} else {
    $value_selected = htmlentities($rsconecta->fields['idmedida']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'id_medida',
    'id_campo' => 'id_medida',

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
<?php if ($preferencias_medidas_referenciales == "S") { ?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida 2 </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
        // consulta
        $consulta = "select * from medidas  where estado = 1";

    // valor seleccionado
    if (isset($_POST['id_medida'])) {
        $value_selected = htmlentities($_POST['id_medida']);
    }
    if ($idcaja) {
        $value_selected = htmlentities($idcaja);
    } else {
        $value_selected = htmlentities($rsconecta->fields['idmedida2']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'id_medida2',
        'id_campo' => 'id_medida2',

        'nombre_campo_bd' => 'nombre',
        'id_campo_bd' => 'id_medida',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' aria-describedby="medida2Help" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
		<small id="medida2Help"   class="form-text text-muted">Designar Medida2 que contiene Medida.</small>

		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 2</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" aria-describedby="cant_medida2Help"  name="cant_medida2" id="cant_medida2" value="<?php  if (isset($_POST['cant_medida2'])) {
			    echo floatval($_POST['cant_medida2']);
			} else {
			    echo floatval($rsconecta->fields['cant_medida2']);
			}?>" placeholder="cant_medida2" class="form-control"  />
			<small id="cant_medida2Help"   class="form-text text-muted">Cuantas veces Medida es contenido en Medidas2.</small>
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida 3 </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            // consulta
            $consulta = "select * from medidas  where estado = 1";

    // valor seleccionado
    if (isset($_POST['id_medida'])) {
        $value_selected = htmlentities($_POST['id_medida']);
    }
    if ($idpallet) {
        $value_selected = htmlentities($idpallet);
    } else {
        $value_selected = htmlentities($rsconecta->fields['idmedida3']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'id_medida3',
        'id_campo' => 'id_medida3',

        'nombre_campo_bd' => 'nombre',
        'id_campo_bd' => 'id_medida',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => 'aria-describedby="medida3Help" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
		<small id="medida3Help"   class="form-text text-muted">Designar Medida3 que contiene Medida2.</small>

		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Medida 3</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" aria-describedby="cant_medida3Help"  name="cant_medida3" id="cant_medida3" value="<?php  if (isset($_POST['cant_medida3'])) {
		    echo floatval($_POST['cant_medida3']);
		} else {
		    echo floatval($rsconecta->fields['cant_medida3']);
		}?>" placeholder="cant_medida3" class="form-control"  />
		<small id="cant_medida3Help"   class="form-text text-muted">Cuantas veces Medida2 es contenido en Medidas3.</small>
		</div>
	</div>
<?php } ?>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_insumos_edit.php?id=<?php echo $idinsu ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
<input type="hidden" name="idproducto" value="<?php echo $rsconecta->fields['idproducto']; ?>" />
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
