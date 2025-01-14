<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$idinsumoimpcab = intval($_GET['id']);
if ($idinsumoimpcab == 0) {
    header("location: insumos_lista_importar.php");
    exit;
}
$consulta = "
select *,
(select idinsumo from insumos_lista where descripcion = insumos_lista_import.descripcion and estado = 'A' limit  1) as existe,
(select descripcion from cn_conceptos where idconcepto = insumos_lista_import.idconcepto) as concepto,
(select descripcion from produccion_centros where idcentroprod = insumos_lista_import.idcentroprod ) as centro_produccion,
(select agrupacion_prod from produccion_agrupacion where idagrupacionprod =  insumos_lista_import.idagrupacionprod) as agrupacion_produccion,
(select descripcion from cn_plancuentas_detalles where idserieun = insumos_lista_import.idplancuentadet) as cuenta_contable
from insumos_lista_import 
where 
idinsumoimpcab = $idinsumoimpcab
order by idinsumoimp asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinsumoimp = intval($rs->fields['idinsumoimp']);
if ($idinsumoimp == 0) {
    header("location: insumos_lista_importar.php");
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

    // si todo es correcto actualiza
    if ($valido == "S") {


        // si no existe categoria crea
        $consulta = "
		INSERT INTO categorias
		(nombre, ab, orden, textobanner, imgbanner, estado, borrable, especial,
		idsucursal, idempresa, muestra_self, recarga_porc, muestra_ped, muestra_menu, muestra_venta) 
		SELECT 
		categoria, NULL, 0, '','',1,'S', 'N',
		1, 1, 'N', 0, 'N', 'N', 'N'
		FROM insumos_lista_import 
		where 
		categoria not in (select categorias.nombre from categorias where estado = 1) 
		and idinsumoimpcab = $idinsumoimpcab
		group by categoria
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si no exisste subcategoria crea
        $consulta = "
		INSERT INTO sub_categorias
		(idcategoria, 
		descripcion, idempresa, estado, describebanner, orden, muestrafiltro, borrable, recarga_porc) 
		SELECT 
		(select id_categoria from categorias where nombre = insumos_lista_import.categoria) as idcategoria,
		subcategoria, 1, 1, '', 0, 'S', 'S', 0
		FROM insumos_lista_import 
		where 
		subcategoria not in (select sub_categorias.descripcion from sub_categorias where estado = 1) 
		and idinsumoimpcab = $idinsumoimpcab
        group by subcategoria
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // si no existe grupo crea
        $consulta = "
		INSERT INTO grupo_insumos
		(nombre, idempresa, estado, grupo_borrado_por, grupo_borrado_el) 
		SELECT 
		grupo_stock, 1, 1, NULL, NULL
		FROM insumos_lista_import 
		where 
		grupo_stock not in (select grupo_insumos.nombre from grupo_insumos where estado = 1) 
		and idinsumoimpcab = $idinsumoimpcab
        group by grupo_stock
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // inserta en insumos
        $consulta = "
		INSERT INTO insumos_lista
		(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo,  tipoiva, idtipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, borrado_por, idproveedor, acepta_devolucion, aplica_regalia, solo_conversion, borrado_el, idproveedor_vinculado, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el, idcentroprod, idplancuentadet, idagrupacionprod, rendimiento_porc, costo_referencial, idinsumoimp) 

		select 
		(select COALESCE(max(idinsumo),0) from insumos_lista)+ROW_NUMBER() OVER (ORDER BY idinsumoimp asc) AS idinsumo_row,
		NULL,
		descripcion,
		idconcepto,
		(select id_categoria from categorias where nombre = insumos_lista_import.categoria and estado =1 ) as idcategoria,
		(select idsubcate  from sub_categorias where descripcion = insumos_lista_import.subcategoria  and estado =1 limit 1) as idsubcate,
		NULL, 
		(select id_medida  from medidas where nombre = insumos_lista_import.medida and estado =1) as idmedida,
		1, costo_unitario,
		iva_compra_porc,
		(select idtipoiva from tipo_iva where estado = 1 and iva_porc = insumos_lista_import.iva_compra_porc order by idtipoiva asc limit 1) as idtipoiva,
		'S' as mueve_stock, NULL, NULL, 'A' as estado, 1, 
		(select idgrupoinsu  from grupo_insumos where nombre = insumos_lista_import.grupo_stock and estado =1) as idgrupoinsu,
		'N' as ajuste, '$ahora', $idusu, 
		CASE WHEN 
			hab_compra = 'SI'
		THEN
			1
		ELSE
			0
		END AS hab_compra,
		CASE WHEN 
			hab_invent = 'SI'
		THEN
			1
		ELSE
			0
		END AS hab_invent,
		NULL, NULL, NULL as acepta_devolucion, 'S' as aplica_regalia, NULL as solo_conversion, NULL, 
		NULL, 'N' as respeta_precio_sugerido, NULL as idprodexterno, NULL as restaurado_por,  NULL as restaurado_el, 
		idcentroprod, idplancuentadet, idagrupacionprod, rendimiento_porc, NULL as costo_referencial, 
		idinsumoimp
		from insumos_lista_import
		where
		idinsumoimpcab = $idinsumoimpcab
		and (select idinsumo from insumos_lista where descripcion = insumos_lista_import.descripcion and estado = 'A' limit  1) is null
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // insertar en ingredientes
        $consulta = "
		INSERT INTO ingredientes
		(idinsumo, estado, idempresa, fechahora) 
		SELECT idinsumo, 1, 1, '$ahora' 
		FROM insumos_lista 
		where 
		idinsumo not in (select ingredientes.idinsumo from ingredientes)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update insumos_lista_import_cab
		set
		finalizado_por = $idusu,
		finalizado_el = '$ahora',
		estado = 3
		where
		idinsumoimpcab = $idinsumoimpcab
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: insumos_lista.php");
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
                    <h2>Controlar Articulos del Archivo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
			Favor verifique que los datos cargados esten correctos, una vez finalizado ya <strong style="color:#F00">NO SE PODRA DESHACER</strong> esta accion.


<hr />
<p><a href="insumos_lista_importar.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Valido</th>
			<th align="center">Idinsumoimp</th>
			<th align="center">Descripcion</th>
			<th align="center">Categoria</th>
			<th align="center">Subcategoria</th>
			<th align="center">Medida</th>
			<th align="center">Costo unitario</th>
			<th align="center">Iva compra porc</th>
			<th align="center">Grupo stock</th>
			<th align="center">Hab compra</th>
			<th align="center">Hab invent</th>
			<th align="center">Concepto</th>
			<th align="center">Centro Produccion</th>
			<th align="center">Agrupacion produccion</th>
			<th align="center">Cuenta C.</th>
			<th align="center">Rendimiento</th>
			

		</tr>
	  </thead>
	  <tbody>
<?php
$duplicados = 0;
while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php
if (intval($rs->fields['existe']) > 0) {
    echo  '<span style="color:#F00;">DUPLICADO</span>';
    $duplicados++;
} else {
    echo "OK";
}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['idinsumoimp']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['categoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['subcategoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo_unitario']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['iva_compra_porc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['grupo_stock']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['hab_compra']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['hab_invent']); ?></td>
			
			<td align="center"><?php echo antixss($rs->fields['concepto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['centro_produccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['agrupacion_produccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cuenta_contable']); ?></td>
			<td align="center"><?php echo floatval($rs->fields['rendimiento_porc']); ?>%</td>

		</tr>
<?php


$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
Duplicados: <?php echo formatomoneda($duplicados); ?><br />
Estos insumos duplicados no se crearan, se omitiran de la importacion. <br /> 

<form id="form1" name="form1" method="post" action="">
					  

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista_importar.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
	  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
					  <div class="clearfix"></div>
<br />
<br /><br /><br />

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
