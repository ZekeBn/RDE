 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";

require_once("includes/rsusuario.php");

$link_descarga_xls = "gest_deposito_admin_mov_entrada_xls.php".parametros_url();

$idpo = intval($_GET['idpo']);
$idgrupoinsu = intval($_GET['g']);
//if ($idpo==0){
//    header("Location:gest_adm_depositos.php");
//    exit;
//}
$iddeposito = $idpo;

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

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


$whereaddconmovimiento = " and 
(select sum(cantidad) from stock_movimientos 
where stock_movimientos.idinsumo = insumos_lista.idinsumo 
and stock_movimientos.iddeposito =$iddeposito 
and (stock_movimientos.tipomov=1 
or stock_movimientos.tipomov=4) 
and stock_movimientos.fecha_comprobante >='$desde' 
and stock_movimientos.fecha_comprobante<= '$hasta')>0
 ";
//$whereaddconmovimiento ="";
//if($idsucursalinv > 0){
$consulta = "select insumos_lista.*, 
(
select grupo_insumos.nombre from grupo_insumos 
where 
grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu 
and grupo_insumos.idempresa = $idempresa
) as nombre,
 medidas.nombre as unidadmedida,
costo as ultimocosto,
(
select disponible 
from gest_depositos_stock_gral
where
idproducto = insumos_lista.idinsumo
and iddeposito =$iddeposito 
and idempresa = $idempresa 
) as stock_teorico,
(
SELECT precio 
FROM productos_sucursales 
inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
where  
productos_sucursales.idproducto = insumos_lista.idproducto
and productos_sucursales.idempresa =$idempresa 
and gest_depositos.idempresa = $idempresa 
limit 1
) as precio_venta,
CASE WHEN 
    insumos_lista.idproducto > 0
THEN
    (
        select sum(subcosto)  
        from 
        ( select recetas_detalles.idprod, 
            (
                select isl.costo 
                from insumos_lista isl 
                inner join ingredientes on ingredientes.idinsumo = isl.idinsumo 
                where 
                ingredientes.idingrediente = recetas_detalles.ingrediente
            )*cantidad as subcosto 
            from recetas_detalles 
        ) as tt
        where
        tt.idprod=insumos_lista.idproducto
    ) 
ELSE
    insumos_lista.costo
END as costo_receta,
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,
(select sum(cantidad) from stock_movimientos where stock_movimientos.idinsumo = insumos_lista.idinsumo and stock_movimientos.iddeposito =$iddeposito and stock_movimientos.tipomov=1 and stock_movimientos.fecha_comprobante >='$desde' and stock_movimientos.fecha_comprobante<= '$hasta') as compra,
(select sum(cantidad) from stock_movimientos where stock_movimientos.idinsumo = insumos_lista.idinsumo and stock_movimientos.iddeposito =$iddeposito and stock_movimientos.tipomov=4 and stock_movimientos.fecha_comprobante >='$desde' and stock_movimientos.fecha_comprobante<= '$hasta') as tentrada
from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
inner join gest_depositos_stock_gral on gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
where mueve_stock = 'S' 
and insumos_lista.idempresa = $idempresa 
and insumos_lista.estado = 'A' 
and gest_depositos_stock_gral.iddeposito =$iddeposito 
and gest_depositos_stock_gral.idempresa = $idempresa 
and gest_depositos_stock_gral.disponible>0
$whereadd
$whereaddconmovimiento
order by     
descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// valor seleccionado
if (isset($_POST['idmedida'])) {
    $value_selected = htmlentities($_POST['idmedida']);
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
    'nombre_campo' => 'idmedida',
    'id_campo' => 'idmedida',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
//echo campo_select_sinbd($parametros_array);



// construye campo
//echo campo_select_sinbd($parametros_array);
//echo $consulta;
//}
/*
order by         (
select grupo_insumos.nombre from grupo_insumos
where
grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu
and grupo_insumos.idempresa = $idempresa
) asc,

*/

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
                    <h2>Deposito: <?php echo antixss($rsf->fields['descripcion']).' - ENTRADAS ';?> </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="<?php echo $link_descarga_xls;?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Entradas</a>

</p>
<hr />
<?php //echo $link_descarga;?>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Desde </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php if (isset($_POST['desde'])) {
        echo htmlentities($_POST['desde']);
    } else {
        echo $desde;
    } ?>" placeholder="Fecha Desde" class="form-control"  />                    
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Hasta </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php  if (isset($_POST['hasta'])) {
        echo htmlentities($_POST['hasta']);
    } else {
        echo $hasta;
    }  ?>" placeholder="Fecha Hasta" class="form-control"  />                    
    <input type="hidden" name="idpo" id="idpo" value="<?php  if (isset($_POST['idpo'])) {
        echo htmlentities($_POST['idpo']);
    } else {
        echo $idpo;
    }  ?>" placeholder="iddeposito" class="form-control"  />      
</div>
</div>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>
       
        </div>
    </div>

  
<br />
</form>
<div class="clearfix"></div>
<br /><br />

<?php //if($iddeposito > 0){
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where 
usuarios.idempresa=$idempresa 
and gest_depositos.idempresa=$idempresa 
order by descripcion ASC ";
$rsdep = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "
SELECT idgrupoinsu, nombre 
FROM grupo_insumos
where
idempresa = 1
and estado = 1
 ";
$rsgru = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>





<div class="clearfix"></div>
<hr />
            
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                  <th align="center" >N&deg;</th>
                  <th>Cod</th>
                  <th>Articulo</th>
                  <th>P. Costo Compra</th>
                  <th>Tras.Entrante</th>
                  <th>Compras</th>
                  <th>Tot.Entradas</th>
                  <th>Valorizado PC</th>
                </tr>
               </thead>
              <tbody>
<?php
$pcount = 0;
$stock_teorico_acum += 0;
$ultimo_costo_acum += 0;
$valorizado_acum += 0;
$tottranentrada += 0;
$tottransalida += 0;
$totnc += 0;
$totcompra += 0;

while (!$rs->EOF) {

    // grupo de insumos
    $grupo = $rs->fields['nombre'];

    // buscar datos en sus respectivas tablas y completar las variables para ese insumo

    $idinsumo = $rs->fields['idinsumo'];

    $ultimo_costo = $rs->fields['ultimocosto'];
    $precio_venta = $rs->fields['precio_venta'];
    $costo_receta = $rs->fields['costo_receta'];

    $tranentrada = $rs->fields['tentrada'];
    $nc = $rs->fields['ncredito'];
    $compra = $rs->fields['compra'];
    $totentradas = $nc + $compra;
    $tottranentrada += $tranentrada;
    $totnc += $nc;
    $totcompra += $compra;


    $valorizado_pc = $totentradas * $ultimo_costo;
    //cerar montos negativos
    $valorizado_pc_acum += $valorizado_pc;
    //$costo_promedio_verificar
    // busca por idprodducto > 0 si tiene receta, si tiene calcula costo de cada ingrediente segun insumos lista y acumula
    $aa = $aa + 1;

    ?>

                <tr height="30">
                  <td id="nn"><?php echo $aa; ?></td>
                  <td align="center"><?php echo intval($rs->fields['idinsumo']);  ?></td>
                  <td align="left"><?php if ($produccion == 2) { ?>(*)<?php } ?><?php echo capitalizar(antixss($rs->fields['descripcion'])); ?><?php //echo " - ".$idinsumo?></td>
                  <td align="center"><?php echo formatomoneda($costo_receta, 2, 'N'); ?></td>
                <td align="center" bgcolor="#DBFFBE"><?php echo formatomoneda($tranentrada, 4, 'N');  ?></td>
                   <td align="center" bgcolor="#DBFFBE"><?php echo formatomoneda($compra, 4, 'N'); ?></td>
                    <td align="center" bgcolor="#bad9a0"><?php echo formatomoneda($totentradas, 4, 'N');  ?></td>
                   <td align="center"><?php echo formatomoneda($valorizado_pc, 4, 'N');  ?></td>
                </tr>
<?php $grupoant = $grupo;
    $rs->MoveNext();
} ?>
            <tfoot>
                <tr style="font-weight:bold; background-color:#CCC;">
                  <td colspan="4" align="left" ><strong>Totales</strong></td>
                  <td align="center" ><?php echo  formatomoneda($tottranentrada, 4, 'N'); ?></td>
                  <td align="center" ><?php echo formatomoneda($totcompra, 4, 'N'); ?></td>
                  <td align="center" ><?php echo formatomoneda($tottranentrada + $totcompra, 4, 'N'); ?></td>
                  <td align="center" ><?php echo formatomoneda($valorizado_pc_acum, 4, 'N'); ?></td>
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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
