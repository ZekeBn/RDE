 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";

require_once("includes/rsusuario.php");

$link_descarga_xls = "gest_deposito_admin_resu_xls.php".parametros_url();

$idpo = intval($_GET['idpo']);
$idgrupoinsu = intval($_GET['g']);
//if ($idpo==0){
//    header("Location:gest_adm_depositos.php");
//    exit;
//}
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
if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


if ($_GET['viendo'] == 's') {
    $whereadd .= "
    and (select cantidad from stock_movimientos 
    where idinsumo = insumos_lista.idinsumo 
    and iddeposito=$iddeposito 
    and fecha_comprobante='$desde' and tipomov = 5 
    order by fechahora asc limit 1 ) > 0
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
$consulta = "select insumos_lista.idinsumo,insumos_lista.descripcion as producto,
(select descripcion from gest_depositos where iddeposito=$iddeposito) as nombredeposito,
 medidas.nombre as unidadmedida,(select barcode from productos where idprod = insumos_lista.idproducto) as codigo_barras, 
(select cantidad from stock_movimientos where idinsumo = insumos_lista.idinsumo and iddeposito=$iddeposito and fecha_comprobante='$desde' and tipomov = 5 order by fechahora desc limit 1 ) as stock,insumos_lista.idgrupoinsu,grupo_insumos.nombre as grupo,CASE WHEN 
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
END as costo_receta,insumos_lista.idempresa
from insumos_lista inner join medidas on medidas.id_medida = insumos_lista.idmedida
left join grupo_insumos on grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu
where insumos_lista.mueve_stock = 'S'
and insumos_lista.idempresa = $idempresa 
and insumos_lista.estado = 'A' 
and insumos_lista.idgrupoinsu = 1
and insumos_lista.hab_invent =1
$whereadd
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
                    <h2>Deposito: <?php echo antixss($rsf->fields['descripcion']).' - INVENTARIO ';?> </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="<?php echo $link_descarga_xls;?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Inventario</a>

</p>
<hr />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Inventario:</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php if (isset($_POST['desde'])) {
        echo htmlentities($_POST['desde']);
    } else {
        echo $desde;
    } ?>" placeholder="Fecha Desde" class="form-control"  />                    
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="hidden" name="hasta" id="hasta" value="<?php  if (isset($_POST['hasta'])) {
        echo htmlentities($_POST['hasta']);
    } else {
        echo $hasta;
    }  ?>" placeholder="Fecha Hasta" class="form-control"  />                    
    <input type="hidden" name="idpo" id="idpo" value="<?php  if (isset($_POST['idpo'])) {
        echo htmlentities($_POST['idpo']);
    } else {
        echo $idpo;
    }  ?>" placeholder="iddeposito" class="form-control"  />      
    <input type="hidden" name="viendo" id="viendo" value="s" placeholder="viendo" class="form-control"  />  
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
                  <th>Codigo Barra</th>
                  <th>Articulo</th>
                  <th>U.Medida</th>
                  <th>Pre.Costo Compra</th>
                  <th>Stock</th>
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

while ((!$rs->EOF)) {

    // grupo de insumos
    $grupo = $rs->fields['nombre'];

    // buscar datos en sus respectivas tablas y completar las variables para ese insumo

    $idinsumo = $rs->fields['idinsumo'];
    $stock_teorico = $rs->fields['stock'];
    $ultimo_costo = $rs->fields['costo_receta'];
    $stock_teorico_acum += $stock_teorico;




    $valorizado_pc = $stock_teorico * $ultimo_costo;
    $ultimo_costo_acum += $ultimo_costo;
    //cerar montos negativos
    if ($valorizado_pc < 0) {
        $valorizado_pc = 0;
    }

    $valorizado_pc_acum += $valorizado_pc;

    //$costo_promedio_verificar
    // busca por idprodducto > 0 si tiene receta, si tiene calcula costo de cada ingrediente segun insumos lista y acumula
    $aa = $aa + 1;

    ?>
                <tr height="30">
                  <td id="nn"><?php echo $aa; ?></td>
                  <td align="center"><?php echo intval($rs->fields['idinsumo']);  ?></td>
                  <td align="left"><?php echo antixss($rs->fields['codigo_barras']);  ?></td>
                  <td align="left"><?php if ($produccion == 2) { ?>(*)<?php } ?><?php echo capitalizar(antixss($rs->fields['producto'])); ?><?php //echo " - ".$idinsumo?></td>
                  <td align="left"><?php echo antixss($rs->fields['unidadmedida']);  ?></td>
                  <td align="center"><?php echo formatomoneda($ultimo_costo, 2, 'N'); ?></td>
                 <td align="center" bgcolor="#F8FFCC"><?php echo formatomoneda($stock_teorico, 6, 'N');  ?></td>
                 <td align="center"><?php echo formatomoneda($valorizado_pc, 4, 'N');  ?></td>
                </tr>
<?php $grupoant = $grupo;
    $rs->MoveNext();
} ?>
            <tfoot>
                <tr style="font-weight:bold; background-color:#CCC;">
                  <td colspan="6" align="left" ><strong>Totales</strong></td>
                  <td align="center" ><?php echo  formatomoneda($stock_teorico_acum, 4, 'N'); ?></td>
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
