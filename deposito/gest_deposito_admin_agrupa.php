<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idpo = intval($_GET['idpo']);
$idgrupoinsu = intval($_GET['g']);
if ($idpo == 0) {
    header("Location:gest_adm_depositos.php");
    exit;
}
$iddeposito = $idpo;




//Lista de depositos
$buscar = "Select * from gest_depositos where iddeposito=$idpo and idempresa = $idempresa";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sucursal_deposito = intval($rsf->fields['idsucursal']);
$tiposala = intval($rsf->fields['tiposala']);

if ($idgrupoinsu > 0) {
    $whereadd .= "
		and insumos_lista.idgrupoinsu = $idgrupoinsu
		";
}

// busca ultimo inventario
$consulta = "
select * 
from inventario
where
idempresa = $idempresa
and iddeposito = $iddeposito
and inventario.estado = 3
order by fecha_inicio desc
limit 1
";
$rsinv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinventario_desde = $rsinv->fields['idinventario'];
$fec_inicio_inv = $rsinv->fields['fecha_inicio'];
$fec_fin_inv = date("Y-m-d", strtotime($ahora));
$idsucursalinv = $rsinv->fields['idsucursal'];
$existe_inventario = "S";
if (intval($idsucursalinv) == 0) {
    $fec_inicio_inv = '2000-01-01';
    $existe_inventario = "N";
}

$hab_invent_endeposito = intval($rsco->fields['hab_invent_endeposito']);
if ($hab_invent_endeposito == 1) {
    $whereadd .= "
	and insumos_lista.hab_invent = 1
	";
}

if ($_GET['viendo'] == 's') {
    $whereadd .= "
	and (
		select disponible 
		from gest_depositos_stock_gral
		where
		idproducto = insumos_lista.idinsumo
		and iddeposito = $iddeposito
		and idempresa = $idempresa
	) > 0
	";
}
if ($_GET['hab_venta'] == 's') {
    $whereadd .= "
	and 
	(
	select activo_suc 
	from productos_sucursales 
	where 
	idproducto = insumos_lista.idproducto 
	and idsucursal = $sucursal_deposito
	
	) = 1
	";
}

if ($_GET['idproveedor'] > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= " and insumos_lista.idproveedor = $idproveedor ";
}


//if($idsucursalinv > 0){
$consulta = "
select categorias.nombre as categoria, categorias.id_categoria as idcategoria,
(
select sum(disponible) 
from gest_depositos_stock_gral, insumos_lista il
where
il.idinsumo = gest_depositos_stock_gral.idproducto
and il.idcategoria = categorias.id_categoria
and iddeposito = $iddeposito
and il.mueve_stock = 'S'
and il.estado = 'A'
and il.hab_invent = 1
) as stock_teorico,
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar


from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
inner join categorias on categorias.id_categoria = insumos_lista.idcategoria
inner join sub_categorias on sub_categorias.idsubcate = insumos_lista.idsubcate
where
mueve_stock = 'S'
and insumos_lista.estado = 'A'
$whereadd
group by categorias.id_categoria
order by categorias.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;
//}

if (intval($rs->fields['idinsumo']) > 0) {
    $invsel = "S";
}


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
                    <h2>Deposito: <?php echo antixss($rsf->fields['descripcion']);?> (Agrupado)</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Depositos</a>
<a href="gest_deposito_admin.php?idpo=<?php echo $idpo ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Desagrupado</a>

</p>
<hr />

     
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                  <th align="center" >N&deg;</th>
                  <th>Categoria</th>
                  <th>Stock Teorico</th>

                </tr>
               </thead>
              <tbody>
<?php
$pcount = 0;
$stock_teorico_acum += 0;
$ultimo_costo_acum += 0;
$valorizado_acum += 0;
while (!$rs->EOF) {

    // grupo de insumos
    $grupo = $rs->fields['nombre'];

    // buscar datos en sus respectivas tablas y completar las variables para ese insumo

    $idinsumo = $rs->fields['idinsumo'];
    $stock_teorico = $rs->fields['stock_teorico'];
    $ultimo_costo = $rs->fields['ultimocosto'];
    $precio_venta = $rs->fields['precio_venta'];
    $costo_receta = $rs->fields['costo_receta'];
    $valorizado_pc = $stock_teorico * $ultimo_costo;
    $valorizado_pv = $stock_teorico * $precio_venta;
    $stock_teorico_acum += $stock_teorico;
    $ultimo_costo_acum += $ultimo_costo;
    //cerar montos negativos
    if ($valorizado_pc < 0) {
        $valorizado_pc = 0;
    }
    if ($valorizado_pv < 0) {
        $valorizado_pv = 0;
    }
    $valorizado_pc_acum += $valorizado_pc;
    $valorizado_pv_acum += $valorizado_pv;

    // busca por idprodducto > 0 si tiene receta, si tiene calcula costo de cada ingrediente segun insumos lista y acumula
    $aa = $aa + 1;

    ?>
               <?php /*if($grupo != $grupoant){ ?> <tr>
                  <td colspan="19" align="left" style="font-weight:bold; background-color:#CCC;"><?php echo $grupo; ?></td>
                </tr>
                <?php }*/ ?>
                <tr height="30">
                  <td id="nn"><?php echo $aa; ?></td>
                  <td align="left"><?php echo antixss($rs->fields['categoria']);  ?> [<?php echo antixss($rs->fields['idcategoria']);  ?>]</td>
   
                  
                   <td align="right" bgcolor="#F8FFCC"><?php echo formatomoneda($stock_teorico, 4, 'N');  ?></td>


                </tr>
<?php $grupoant = $grupo;
    $rs->MoveNext();
} ?>
			<tfoot>
                <tr style="font-weight:bold; background-color:#CCC;">
                  <td colspan="2" align="left" ><strong>Totales</strong></td>
                  <td align="right" ><?php echo  formatomoneda($stock_teorico_acum, 4, 'N'); ?></td>
                </tr>
            </tfoot>
              </tbody>
            </table>
</div>
<br />

<?php

$consulta = "
select sub_categorias.descripcion as subcategoria, sub_categorias.idsubcate,
(
select sum(disponible) 
from gest_depositos_stock_gral, insumos_lista il
where
il.idinsumo = gest_depositos_stock_gral.idproducto
and iddeposito = $iddeposito
and il.idsubcate = sub_categorias.idsubcate
and il.mueve_stock = 'S'
and il.estado = 'A'
and il.hab_invent = 1
) as stock_teorico,
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar


from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
inner join categorias on categorias.id_categoria = insumos_lista.idcategoria
inner join sub_categorias on sub_categorias.idsubcate = insumos_lista.idsubcate
where
mueve_stock = 'S'
and insumos_lista.estado = 'A'
$whereadd
group by sub_categorias.idsubcate
order by sub_categorias.descripcion asc
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
    
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                  <th align="center" >N&deg;</th>
                  <th>Sub-Categoria</th>
                  <th>Stock Teorico</th>

                </tr>
               </thead>
              <tbody>
<?php
$aa = 0;
$pcount = 0;
$stock_teorico_acum = 0;
$ultimo_costo_acum = 0;
$valorizado_acum = 0;
while (!$rs->EOF) {

    // grupo de insumos
    $grupo = $rs->fields['nombre'];

    // buscar datos en sus respectivas tablas y completar las variables para ese insumo

    $idinsumo = $rs->fields['idinsumo'];
    $stock_teorico = $rs->fields['stock_teorico'];
    $ultimo_costo = $rs->fields['ultimocosto'];
    $precio_venta = $rs->fields['precio_venta'];
    $costo_receta = $rs->fields['costo_receta'];
    $valorizado_pc = $stock_teorico * $ultimo_costo;
    $valorizado_pv = $stock_teorico * $precio_venta;
    $stock_teorico_acum += $stock_teorico;
    $ultimo_costo_acum += $ultimo_costo;
    //cerar montos negativos
    if ($valorizado_pc < 0) {
        $valorizado_pc = 0;
    }
    if ($valorizado_pv < 0) {
        $valorizado_pv = 0;
    }
    $valorizado_pc_acum += $valorizado_pc;
    $valorizado_pv_acum += $valorizado_pv;

    // busca por idprodducto > 0 si tiene receta, si tiene calcula costo de cada ingrediente segun insumos lista y acumula
    $aa = $aa + 1;

    ?>
               <?php /*if($grupo != $grupoant){ ?> <tr>
                  <td colspan="19" align="left" style="font-weight:bold; background-color:#CCC;"><?php echo $grupo; ?></td>
                </tr>
                <?php }*/ ?>
                <tr height="30">
                  <td id="nn"><?php echo $aa; ?></td>
                  <td align="left"><?php echo antixss($rs->fields['subcategoria']);  ?> [<?php echo antixss($rs->fields['idsubcate']);  ?>]</td>
   
                  
                   <td align="right" bgcolor="#F8FFCC"><?php echo formatomoneda($stock_teorico, 4, 'N');  ?></td>


                </tr>
<?php $grupoant = $grupo;
    $rs->MoveNext();
} ?>
			<tfoot>
                <tr style="font-weight:bold; background-color:#CCC;">
                  <td colspan="2" align="left" ><strong>Totales</strong></td>
                  <td align="right" ><?php echo  formatomoneda($stock_teorico_acum, 4, 'N'); ?></td>
                </tr>
            </tfoot>
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
