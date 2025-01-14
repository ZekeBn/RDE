 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";

require_once("includes/rsusuario.php");

$idpo = intval($_GET['idpo']);
$idgrupoinsu = intval($_GET['g']);
if ($idpo == 0) {
    header("Location:gest_adm_depositos.php");
    exit;
}
$iddeposito = $idpo;

$url_add = '&idpo='.$iddeposito;




//Lista de depositos
$buscar = "Select * from gest_depositos where iddeposito=$idpo and idempresa = $idempresa";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sucursal_deposito = intval($rsf->fields['idsucursal']);
$tiposala = intval($rsf->fields['tiposala']);

if ($idgrupoinsu > 0) {
    $whereadd .= "
        and insumos_lista.idgrupoinsu = $idgrupoinsu
        ";
    $url_add .= '&g='.$idgrupoinsu;
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
    $url_add .= '&viendo='.antixss($_GET['viendo']);
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
    $url_add .= '&hab_venta='.antixss($_GET['hab_venta']);
}

if ($_GET['idproveedor'] > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= " and insumos_lista.idproveedor = $idproveedor ";
    $url_add .= '&idproveedor='.antixss($_GET['idproveedor']);
}
if ($_GET['idmarca'] > 0) {
    $idmarca = intval($_GET['idmarca']);
    $whereadd .= " and (select idmarca from productos where idprod_serial = insumos_lista.idproducto limit 1) = $idmarca ";
    $url_add .= '&idmarca='.antixss($_GET['idmarca']);
}
if (trim($_GET['descripcion']) != '') {
    $descripcion = antisqlinyeccion($_GET['descripcion'], "like");
    $whereadd .= " and insumos_lista.descripcion like '%$descripcion%' ";
    $url_add .= '&descripcion='.antixss($_GET['descripcion']);
}


// sobre escribe lo de arriba
if (trim($_GET['barcode']) != '') {
    $barcode = antisqlinyeccion($_GET['barcode'], "text");
    $whereadd = " and (select barcode from productos where productos.idprod_serial = insumos_lista.idproducto) = $barcode ";
    $url_add .= '&barcode='.antixss($_GET['barcode']);
}




// debe ser igual la consulta de abajo el where
$consulta = "
    select count(idinsumo) as total

    from insumos_lista
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    
    where 
    mueve_stock = 'S'
    and insumos_lista.idempresa = $idempresa
    and insumos_lista.estado = 'A'
    $whereadd
    ";
$rstotal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$num_total_registros = $rstotal->fields['total'];

/******** PAGINACION **********/
$nropagina = intval($_GET["pagina"]);
//Limito la busqueda
$TAMANO_PAGINA = 1000;
//examino la página a mostrar y el inicio del registro a mostrar
if ($nropagina == 0) {
    $inicio = 0;
    $nropagina = 1;
} else {
    $inicio = ($nropagina - 1) * $TAMANO_PAGINA;
}
//calculo el total de páginas
$total_paginas = ceil($num_total_registros / $TAMANO_PAGINA);
// volver a consultar pero agregar los limites
$limite = " 
    Limit $inicio, $TAMANO_PAGINA
    ";


//$nombreboton ="Ver Entrada/Salida:" . <?php echo antixss($rsf->fields['descripcion']);?';
//if($idsucursalinv > 0){
$consulta = "
select insumos_lista.*, 
(
select grupo_insumos.nombre from grupo_insumos 
where 
grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu 
) as nombre,
 medidas.nombre as unidadmedida,
costo as ultimocosto,
(
select disponible 
from gest_depositos_stock_gral
where
idproducto = insumos_lista.idinsumo
and iddeposito = $iddeposito
) as stock_teorico,
(
SELECT precio 
FROM productos_sucursales 
inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
where  
productos_sucursales.idproducto = insumos_lista.idproducto
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
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar


from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
mueve_stock = 'S'
and insumos_lista.estado = 'A'
$whereadd

order by     
descripcion asc
$limite
";
/*if($idusu == 3){
    echo $consulta;
}*/
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



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

// igual al de arriba el where dentro de la consulta from pero sin limite, en el select de stock_teorico hay diferencias para las sumatorias
$consulta = "
select sum(stock_teorico) as stock_teorico, sum(stock_teorico_sin_negativos*ultimocosto) as valorizado_pc,  sum(stock_teorico_sin_negativos*precio_venta) as valorizado_pv
from 
(

        select insumos_lista.*, 
        (
        select grupo_insumos.nombre from grupo_insumos 
        where 
        grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu 
        ) as nombre,
         medidas.nombre as unidadmedida,
        costo as ultimocosto,
        (
            select disponible 
            from gest_depositos_stock_gral
            where
            idproducto = insumos_lista.idinsumo
            and iddeposito = $iddeposito
        ) as stock_teorico,
        CASE WHEN 
            (
            select disponible 
            from gest_depositos_stock_gral
            where
            idproducto = insumos_lista.idinsumo
            and iddeposito = $iddeposito
            ) > 0
        THEN
            (
            select disponible 
            from gest_depositos_stock_gral
            where
            idproducto = insumos_lista.idinsumo
            and iddeposito = $iddeposito
            )
        ELSE
            0
        END as stock_teorico_sin_negativos,
        
        (
        SELECT precio 
        FROM productos_sucursales 
        inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
        where  
        productos_sucursales.idproducto = insumos_lista.idproducto
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
        (select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar


        from insumos_lista
        inner join medidas on medidas.id_medida = insumos_lista.idmedida
        where
        mueve_stock = 'S'
        and insumos_lista.estado = 'A'
        $whereadd


) datos

";
/*if($idusu == 3){
    echo $consulta;
}*/
$rstotales = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



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

if (intval($rs->fields['idinsumo']) > 0) {
    $invsel = "S";
}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function aplicar_filtros(){
    $("#form1").submit();
}
</script>
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
                    <h2>Deposito: <?php echo antixss($rsf->fields['descripcion']);?> </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Depositos</a>
<a href="gest_deposito_admin_agrupa.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Agrupado</a>
<a href="gest_deposito_admin_xls.php?idpo=<?php echo intval($_GET['idpo']).'&g='.intval($_GET['g']).'&idproveedor='.intval($_GET['idproveedor']).'&hab_venta='.htmlentities($_GET['hab_venta']).'&viendo='.htmlentities($_GET['viendo']);?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Stock</a>
<a href="gest_deposito_admin_ven_xls.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Habilitados para Ventas</a>
<a href="gest_deposito_admin_mov_inventario_a_fecha.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Stock a Fecha</a>
<a href="gest_deposito_admin_mov_inventario.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Inventario</a>
<a href="gest_deposito_admin_mov_entrada.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Entradas</a>
<a href="gest_deposito_admin_mov_salida.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Salidas</a>
<a href="gest_deposito_admin_mov.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Entradas/Salidas</a>
<a href="gest_deposito_admin_mov_perdidas.php?idpo=<?php echo intval($_GET['idpo']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver Perdidas</a>
</p>
<hr />
<form id="form1" name="form1" method="get" action="gest_deposito_admin.php">
<input type="hidden" name="idpo" id="idpo" value="<?php echo intval($_GET['idpo']); ?>"  />

<?php
//if($iddeposito > 0){
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

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo Stock </label>
    <div class="col-md-9 col-sm-9 col-xs-12">

    <select name="g" id="g" class="form-control" onchange="aplicar_filtros();">
    <option value="">TODOS</option>
    <?php while (!$rsgru->EOF) {?>
        <option value="<?php echo $rsgru->fields['idgrupoinsu']?>" <?php if ($rsgru->fields['idgrupoinsu'] == $idgrupoinsu) { ?> selected="selected" <?php } ?>><?php echo $rsgru->fields['nombre']?></option>
     <?php $rsgru->MoveNext();
    } ?>
    </select>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Viendo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


$acciones = ' onchange="aplicar_filtros();" ';

// valor seleccionado
if (isset($_GET['viendo'])) {
    $value_selected = htmlentities($_GET['viendo']);
} else {
    $value_selected = 't';
}
// opciones
$opciones = [
    'SOLO ARTICULOS CON STOCK' => 's'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'viendo',
    'id_campo' => 'viendo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => 't',
    'style_input' => 'class="form-control"',
    'acciones' => " $acciones ",
    'autosel_1registro' => 'N',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

$acciones = ' onchange="aplicar_filtros();" ';

// consulta
$consulta = "
SELECT idproveedor, nombre
FROM proveedores
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['idproveedor'])) {
    $value_selected = htmlentities($_GET['idproveedor']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$acciones.' ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Habilitados Venta </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


$acciones = ' onchange="aplicar_filtros();" ';

// valor seleccionado
if (isset($_GET['hab_venta'])) {
    $value_selected = htmlentities($_GET['hab_venta']);
} else {
    $value_selected = '';
}
// opciones
$opciones = [
    'Habilitados venta' => 's',
];
// parametros
$parametros_array = [
    'nombre_campo' => 'hab_venta',
    'id_campo' => 'hab_venta',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => 't',
    'style_input' => 'class="form-control"',
    'acciones' => " $acciones ",
    'autosel_1registro' => 'N',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Marca *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

$acciones = ' onchange="aplicar_filtros();" ';

// consulta
$consulta = "
SELECT idmarca, marca
FROM marca
where
idestado = 1
order by marca asc
 ";

// valor seleccionado
if (isset($_GET['idmarca'])) {
    $value_selected = htmlentities($_GET['idmarca']);
} else {
    $value_selected = 0;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmarca',
    'id_campo' => 'idmarca',

    'nombre_campo_bd' => 'marca',
    'id_campo_bd' => 'idmarca',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$acciones.' ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_GET['descripcion'])) {
        echo antixss($_GET['descripcion']);
    }?>" placeholder="Descripcion" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Barras </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="barcode" id="barcode" value="<?php  if (isset($_GET['barcode'])) {
        echo antixss($_GET['barcode']);
    }?>" placeholder="barcode" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>
        </div>
    </div>

</form>


<hr />


                  

    <ul class="pagination">
<?php
//$rs_prod->MoveFirst();

if ($url_add != '') {
    $url = "gest_deposito_admin.php?sf=n".$url_add;
} else {
    $url = "gest_deposito_admin.php?sf=s";
}
if ($total_paginas > 1) {
    //echo '<a href="'.$url.'?pagina='.($pagina-1).'"><img src="images/izq.gif" border="0"></a>';
    if ($nropagina != 1) {
        echo '<li><a href="'.$url.'&pagina='.($nropagina - 1).'">&laquo;</a></li>';
    }
    for ($i = 1;$i <= $total_paginas;$i++) {
        if ($nropagina == $i) {
            //si muestro el índice de la página actual, no coloco enlace
            //echo $pagina;
            echo '<li class="active" style="background-color:#FE980F; color:#FFF;"><a href="'.$url.'">'.$nropagina.'</a></li>';
        } else {
            //si el índice no corresponde con la página mostrada actualmente,
            //coloco el enlace para ir a esa página
            // echo '  <a href="'.$url.'?pagina='.$i.'">'.$i.'</a>  ';
            echo '<li><a href="'.$url.'&pagina='.$i.'">'.$i.'</a></li>';
        }

    }
    if ($nropagina != $total_paginas) {
        //echo '<a href="'.$url.'?pagina='.($pagina+1).'"><img src="images/der.gif" border="0"></a>';
        echo '<li><a href="'.$url.'&pagina='.($nropagina + 1).'">&raquo;</a></li>';
    }
}
?>
    </ul>
    <div class="clearfix"></div>
    <br />


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                  <th>Stock Teorico</th>
                  <th>Valorizado PC</th>
                  <th>Valorizado PV</th>
                </tr>
               </thead>
              <tbody>


                <tr >
                  <td align="center" ><?php echo  formatomoneda($rstotales->fields['stock_teorico'], 4, 'N'); ?></td>
                  <td align="center" ><?php echo  formatomoneda($rstotales->fields['valorizado_pc'], 4, 'N'); ?></td>
                  <td align="center" ><?php echo formatomoneda($rstotales->fields['valorizado_pv'], 4, 'N'); ?></td>
                </tr>

              </tbody>
            </table>
</div>
<br />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                  <th align="center" >N&deg;</th>
                  <th>Cod</th>
                  <th>Cod Barras</th>
                  <th>Articulo</th>
                  <th>Unidad de Medida</th>
                  <th>P. Costo Compra</th>
                  <th>P. Costo Receta Venta</th>
                  <th>P. Venta</th>
                  <th>Stock Teorico</th>
                  <th>Valorizado PC</th>
                  <th>Valorizado PV</th>
                </tr>
               </thead>
              <tbody>
<?php
$pcount = 0;
$stock_teorico_acum = 0;
$ultimo_costo_acum = 0;
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
                  <td align="center"><?php echo intval($rs->fields['idinsumo']);  ?></td>
                  <td align="left"><?php echo antixss($rs->fields['codbar']);  ?></td>
                  <td align="left"><?php if ($produccion == 2) { ?>(*)<?php } ?><?php echo capitalizar(antixss($rs->fields['descripcion'])); ?><?php //echo " - ".$idinsumo?></td>
                  <td align="center"><?php echo capitalizar(antixss($rs->fields['unidadmedida']));  ?></td>
                  <td align="center"><?php echo formatomoneda($ultimo_costo, 2, 'N'); ?></td>
                  <td align="center"><?php echo formatomoneda($costo_receta, 2, 'N'); ?></td>
                  <td align="center"><?php echo formatomoneda($precio_venta, 2, 'N'); ?></td>
                  
                   <td align="center" bgcolor="#F8FFCC"><?php echo formatomoneda($stock_teorico, 4, 'N');  ?></td>
                   <td align="center"><?php echo formatomoneda($valorizado_pc, 4, 'N');  ?></td>
                   <td align="center"><?php echo formatomoneda($valorizado_pv, 4, 'N'); ?></td>

                </tr>
<?php $grupoant = $grupo;
    $rs->MoveNext();
} ?>
            <tfoot>
                <tr style="font-weight:bold; background-color:#CCC;">
                  <td colspan="8" align="left" ><strong>Totales</strong></td>
                  <td align="center" ><?php echo  formatomoneda($stock_teorico_acum, 4, 'N'); ?></td>
                  <td align="center" ><?php echo  formatomoneda($valorizado_pc_acum, 4, 'N'); ?></td>
                  <td align="center" ><?php echo formatomoneda($valorizado_pv_acum, 4, 'N'); ?></td>
                </tr>
            </tfoot>
              </tbody>
            </table>
</div>
<br />
<strong>Obs:</strong> Los totales al final de esta tabla solamente reflejan de esta pagina, si desea ver el total completo teniendo en cuenta todas las paginas debe verlo en la tabla de arriba.

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
