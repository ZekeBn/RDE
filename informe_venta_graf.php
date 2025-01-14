 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "302";
require_once("includes/rsusuario.php");


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (trim($_GET['hdesde']) == '' or trim($_GET['hhasta']) == '') {
    $hdesde = "00:00";
    $hhasta = "23:59";
} else {
    $hdesde = date("H:i", strtotime($_GET['hdesde']));
    $hhasta = date("H:i", strtotime($_GET['hhasta']));
}
$desde_completo = $desde." ".$hdesde.':00';
$hasta_completo = $hasta." ".$hhasta.':59';

if (intval($_GET['idcategoria']) > 0) {
    $cate = intval($_GET['idcategoria']);
    $addcate = " and productos.idcategoria=$cate" ;
}
if (intval($_GET['idsubcate']) > 0) {
    $subcate = intval($_GET['idsubcate']);
    $addsubcate = " and productos.idsubcate=$subcate" ;
}
if (($_GET['cj']) > 0) {
    $idcaja = intval($_GET['cj']);
    $filtrocaja = " and ventas.idcaja = $idcaja";

}
if (($_GET['suc']) > 0) {
    $idsuc = intval($_GET['suc']);
    $filtrocaja = " and ventas.sucursal = $idsuc";

}
if (trim($_GET['suc']) == 'ss') {

    $consulta = "
    select idsucu 
    from sucursales 
    where 
    estado = 1 
    and matriz = 'N'
    ";
    $rssucufil = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $sucu_or_add = "and ( ".$saltolinea;
    $i = 0;
    while (!$rssucufil->EOF) {
        $i++;
        $idsuc = intval($rssucufil->fields['idsucu']);
        if ($i == 1) {
            $sucu_or_add .= " ventas.sucursal = $idsuc ".$saltolinea;
        } else {
            $sucu_or_add .= " or ventas.sucursal = $idsuc ".$saltolinea;
        }
        $rssucufil->MoveNext();
    }
    $sucu_or_add .= " ) ".$saltolinea;
    $filtrocaja = $sucu_or_add;

}
if (($_GET['operador']) > 0) {
    $operador_pedido = intval($_GET['operador']);
    $filtrocaja = " and ventas.operador_pedido = $operador_pedido ";

}
if (($_GET['cajero']) > 0) {
    $cajero = intval($_GET['cajero']);
    $filtrocaja = " and ventas.registrado_por = $cajero";

}





$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
date(ventas.fecha) >= '$desde' 
and date(ventas.fecha) <= '$hasta' 
and ventas.fecha >= '$desde_completo' 
and ventas.fecha <= '$hasta_completo' 
$addcate
$addsubcate
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idempresa = $idempresa
$filtrocaja
group by productos.descripcion
order by sum(ventas_detalles.subtotal) desc
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// igual al de arriba pero limit 5
$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
date(ventas.fecha) >= '$desde' 
and date(ventas.fecha) <= '$hasta' 
and ventas.fecha >= '$desde_completo' 
and ventas.fecha <= '$hasta_completo' 
$addcate
$addsubcate
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idempresa = $idempresa
$filtrocaja
group by productos.descripcion
order by sum(ventas_detalles.subtotal) desc
limit 5
";
//echo $consulta;
$rschart = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// igual al de arriba pero limit 5
$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
date(ventas.fecha) >= '$desde' 
and date(ventas.fecha) <= '$hasta' 
and ventas.fecha >= '$desde_completo' 
and ventas.fecha <= '$hasta_completo' 
$addcate
$addsubcate
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idempresa = $idempresa
$filtrocaja
group by productos.descripcion
order by sum(ventas_detalles.cantidad) desc
limit 5
";
//echo $consulta;
$rschartcant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select sum(otrosgs) as monto_delivery, sum(descneto) as monto_descuento
from ventas 
where
date(ventas.fecha) >= '$desde' 
and date(ventas.fecha) <= '$hasta' 
and ventas.estado <> 6
and ventas.idempresa = $idempresa
and ventas.excluye_repven = 0
$filtrocaja
";
$rs3 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$leyenda = "true";


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function subcategorias(idcategoria){
    var direccionurl='subcate_inf.php';    
    var parametros = {
      "idcategoria" : idcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#subcatebox").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
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
                    <h2>Mix de Ventas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php
$refemenu = "mix";
require_once("includes/menu_venta_graf.php"); ?>
<div class="clearfix"></div>
<hr />

<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Categoria * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
require_once("cate_inf.php");

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Subcategoria *</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
<?php
require_once("subcate_inf.php");

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Sucursal * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php


//lista de sucursales
$buscar = "select * from sucursales where  estado = 1 order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?><select name="suc" id="suc" class="form-control" >
        <option value="0" <?php if (intval($_GET['suc']) == 0) {?>selected="selected"<?php }?>>TODOS</option>
        <option value="ss" <?php if ($_GET['suc'] == 'ss') {?>selected="selected"<?php }?> >SOLO SUCURSALES</option>
       <?php while (!$rsfd->EOF) {?>
        <option value="<?php echo $rsfd->fields['idsucu']?>" <?php if ($rsfd->fields['idsucu'] == intval($_GET['suc'])) {?>selected="selected"<?php }?>><?php echo $rsfd->fields['nombre']?></option>
    
         <?php $rsfd->MoveNext();
       }?>
    </select>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Operador * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
// consulta
$consulta = "
SELECT idusu, usuario
FROM usuarios
where
estado = 1
order by usuario asc
 ";

// valor seleccionado
if (isset($_GET['operador'])) {
    $value_selected = htmlentities($_GET['operador']);
} else {
    //$value_selected=htmlentities($rs->fields['idusu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'operador',
    'id_campo' => 'operador',

    'nombre_campo_bd' => 'usuario',
    'id_campo_bd' => 'idusu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
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

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Cajero * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
// consulta
$consulta = "
SELECT idusu, usuario
FROM usuarios
where
estado = 1
order by usuario asc
 ";

// valor seleccionado
if (isset($_GET['cajero'])) {
    $value_selected = htmlentities($_GET['cajero']);
} else {
    //$value_selected=htmlentities($rs->fields['idusu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cajero',
    'id_campo' => 'cajero',

    'nombre_campo_bd' => 'usuario',
    'id_campo_bd' => 'idusu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
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


<div class="clearfix"></div>
<br />


    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>

        </div>
    </div>


<br />
</form>        
<div class="clearfix"></div>
  <hr /><br /> 
  <div class="col-md-6">          
                <p class="text-muted font-13 m-b-30">
                      Top 5 mas vendidos en montos
                    </p>  
 <canvas id="pieChart_mixventa"  height="250" width="300" ></canvas>
 </div>  
 
 
  <div class="col-md-6">          
                <p class="text-muted font-13 m-b-30">
                      Top 5 mas vendidos en cantidades
                    </p>  
 <canvas id="pieChart_mixventa2" height="250" width="300" ></canvas>
 </div> 
 
 
 
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Mix de Ventas:</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<strong></strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Producto</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td align="left"><?php echo antixss($rs->fields['producto']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], '4', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['subtotal'], '4', 'N'); ?></td>
        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['subtotal'];
    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td align="left">Totales</td>
            <td align="center"><?php echo formatomoneda($cantidad_acum, '4', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($total_acum, '4', 'N'); ?></td>
        </tr>
      </tfoot>
    </table>
</div>
<br />
<?php /*?>
      <?php if ($cate==0){
$consulta="
select  ventas_agregados.idingrediente, count(ventas_agregados.idingrediente) as cantidad, sum(ventas_agregados.precio_adicional) as total,
(select alias from agregado where ventas_agregados.idingrediente = agregado.idingrediente limit 1) as alias,
    (select insumos_lista.descripcion
    from ingredientes
    inner join insumos_lista on ingredientes.idinsumo = insumos_lista.idinsumo
     where
     ventas_agregados.idingrediente = ingredientes.idingrediente
     limit 1
     ) as alias2
from ventas
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join ventas_agregados on ventas_agregados.idventadet = ventas_detalles.idventadet
where
date(ventas.fecha) >= '$desde'
and date(ventas.fecha) <= '$hasta'
and ventas.estado <> 6
and ventas.idempresa = $idempresa
and ventas.excluye_repven = 0
$filtrocaja
group by ventas_agregados.idingrediente
order by sum(ventas_agregados.precio_adicional) desc
";
//echo $consulta;
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
          <thead>
            <tr>
              <th><strong>Agregados</strong></th>
              <th><strong>Cantidad</strong></th>
              <th><strong>Total</strong></th>
            </tr>
           <thead>
           <tbody>
           <?php  while (!$rs2->EOF){
           $totalacum2+=$rs2->fields['total'];

           ?>
            <tr>
              <td align="left"><?php if(trim($rs2->fields['alias']) != ''){ echo $rs2->fields['alias']; }else{ echo $rs2->fields['alias2'].'*';  } ?></td>
              <td align="center"><?php echo formatomoneda($rs2->fields['cantidad'],0); ?></td>
              <td align="right"><?php echo formatomoneda($rs2->fields['total']); ?></td>
            </tr>
           <?php  $rs2->MoveNext(); }  ?>
          </tbody>
      </table>
</div>
       <?php } ?>
  <br />   <br />
<strong>Mix de Combinados:</strong><br />
<?php
$consulta="
update tmp_combinado_listas
set
cant_global = (1/

                   (
                    select count(idventatmp) as total
                    from (
                            select idventatmp
                            from tmp_combinado_listas tcl
                         )
                     as tt
                     WHERE
                     tt.idventatmp = tmp_combinado_listas.idventatmp
                   )

              )
WHERE
cant_global is null
and
(
select count(idventatmp) as total
from (
    select idventatmp
    from tmp_combinado_listas tcl
 )
as tt
WHERE
tt.idventatmp = tmp_combinado_listas.idventatmp
) > 0
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$consulta="
update tmp_combinado_listas
set idventa = (
    select
    tmp_ventares_cab.idventa
    from tmp_ventares
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
    where
    tmp_ventares_cab.idventa is not null
    and tmp_ventares.idventatmp = tmp_combinado_listas.idventatmp
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.borrado_mozo = 'N'
    UNION ALL
    select
    tmp_ventares_cab.idventa
    from tmp_ventares_bak
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares_bak.idtmpventares_cab
    where
    tmp_ventares_cab.idventa is not null
    and tmp_ventares_bak.idventatmp = tmp_combinado_listas.idventatmp
    and tmp_ventares_bak.borrado = 'N'
    and tmp_ventares_bak.borrado_mozo = 'N'

)
WHERE
tmp_combinado_listas.idventa is null
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


$consulta="
select productos.idprod_serial, productos.descripcion, sum(tmp_combinado_listas.cant_global) as cantidad,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from ventas
inner join tmp_combinado_listas on ventas.idventa = tmp_combinado_listas.idventa
inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
$join_add
where
date(ventas.fecha) >= '$desde'
and date(ventas.fecha) <= '$hasta'
and ventas.fecha >= '$desde_completo'
and ventas.fecha <= '$hasta_completo'
$addcate
$addsubcate
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idempresa = $idempresa
$filtrocaja
$whereadd
group by productos.descripcion, productos.idprod_serial
order by sum(tmp_combinado_listas.cant_global) desc
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$cantidad_acum=0;
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
          <thead>
            <tr>
              <th>N&deg;</strong></th>
              <th>Codigo</strong></th>
              <th>Productos</strong></th>
              <th>Cantidad</strong></th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rs->EOF){
           $aa=$aa+1;
           $totalacum+=$rs->fields['total'];
           $cantidad_acum+=$rs->fields['cantidad'];
           ?>
            <tr>
              <td id="nn"><?php echo $aa; ?></td>
              <td align="left"><?php echo $rs->fields['idinsumo']; ?></td>
              <td align="left"><?php echo $rs->fields['descripcion']; ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad'],2,'N'); ?></td>
            </tr>
           <?php  $rs->MoveNext(); }  ?>
          </tbody>
            <tr>
              <td ></td>
              <td align="left">Total</td>
              <td align="left"></td>
              <td align="center"><?php echo formatomoneda($cantidad_acum,0); ?></td>

            </tr>
      </table>
</div>

<?php
$consulta="
update tmp_combos_listas
set idventa = (
    select
    tmp_ventares_cab.idventa
    from tmp_ventares
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
    where
    tmp_ventares_cab.idventa is not null
    and tmp_ventares.idventatmp = tmp_combos_listas.idventatmp
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.borrado_mozo = 'N'
    UNION ALL
    select
    tmp_ventares_cab.idventa
    from tmp_ventares_bak
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares_bak.idtmpventares_cab
    where
    tmp_ventares_cab.idventa is not null
    and tmp_ventares_bak.idventatmp = tmp_combos_listas.idventatmp
    and tmp_ventares_bak.borrado = 'N'
    and tmp_ventares_bak.borrado_mozo = 'N'

)
WHERE
tmp_combos_listas.idventa is null
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));



$consulta="
select productos.idprod_serial, productos.descripcion, count(tmp_combos_listas.idlistacombo_tmp) as cantidad,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from ventas
inner join tmp_combos_listas on ventas.idventa = tmp_combos_listas.idventa
inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
$join_add
where
date(ventas.fecha) >= '$desde'
and date(ventas.fecha) <= '$hasta'
and ventas.fecha >= '$desde_completo'
and ventas.fecha <= '$hasta_completo'
$addcate
$addsubcate
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idempresa = $idempresa
$filtrocaja
$whereadd
group by productos.descripcion, productos.idprod_serial
order by count(tmp_combos_listas.idlistacombo_tmp) desc
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$cantidad_acum=0;
?>
<br />
<strong>Mix de Combos:</strong><br />

        <table width="700" border="1" class="tablesorter" style="margin:0px auto;" id="mitabla">
          <thead>
            <tr>
              <th>N&deg;</strong></th>
              <th>Codigo</strong></th>
              <th>Productos</strong></th>
              <th>Cantidad</strong></th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rs->EOF){
           $aa=$aa+1;
           $totalacum+=$rs->fields['total'];
           $cantidad_acum+=$rs->fields['cantidad'];
           ?>
            <tr>
              <td id="nn"><?php echo $aa; ?></td>
              <td align="left"><?php echo $rs->fields['idinsumo']; ?></td>
              <td align="left"><?php echo $rs->fields['descripcion']; ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad'],2,'N'); ?></td>
            </tr>
           <?php  $rs->MoveNext(); }  ?>
          </tbody>
            <tr>
              <td ></td>
              <td align="left">Total</td>
              <td align="left"></td>
              <td align="center"><?php echo formatomoneda($cantidad_acum,0); ?></td>

            </tr>
      </table>

<?php */?>

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
<?php

$data_cant = "";
$data_prod = "";
while (!$rschart->EOF) {
    $data_prod .= '"'.antixss(str_replace(",", "", trim($rschart->fields['producto']))).'"'.', '.$saltolinea;
    $data_cant .= $rschart->fields['subtotal'].', ';
    $rschart->MoveNext();
} //$rschart->MoveFirst();
$data_prod = substr(trim($data_prod), 0, -1);
$data_cant = substr(trim($data_cant), 0, -1);


?>
<script>
      // Pie chart 1
             if ($('#pieChart_mixventa').length ){
                
                  var ctx = document.getElementById("pieChart_mixventa");
                  var data = {
                    datasets: [{
                      data: [<?php echo $data_cant; ?>],
                      backgroundColor: [
                        "#455C73",
                        "#9B59B6",
                        "#BDC3C7",
                        "#26B99A",
                        "#3498DB",
                        "#F08080",
                        "#FF69B4",
                        "#FFA500",
                        "#EEE8AA",
                        "#DDA0DD",
                        "#7B68EE",
                        "#90EE90",
                        "#AFEEEE",
                        "#D2691E"
                      ],
                      label: 'Top 10 mas vendidos' // for legend
                    }],
                    labels: [
                      <?php echo $data_prod ?>
                    ],
                  };

                  var pieChart = new Chart(ctx, {
                    data: data,
                    type: 'pie',
                    options : {
                        legend: {
                            display: <?php echo $leyenda ?>,
                            position: 'bottom',
                
                        },
                        maintainAspectRatio: true,
                        responsive: true,
                        useRandomColors: true
                    }
                    
                  });
                  
              }
<?php
$data_cant = "";
$data_prod = "";
while (!$rschartcant->EOF) {
    $data_prod .= '"'.antixss(str_replace(",", "", trim($rschartcant->fields['producto']))).'"'.', '.$saltolinea;
    $data_cant .= $rschartcant->fields['cantidad'].', ';
    $rschartcant->MoveNext();
} //$rschartcant->MoveFirst();
$data_prod = substr(trim($data_prod), 0, -1);
$data_cant = substr(trim($data_cant), 0, -1);

?>          
      // Pie chart 2
             if ($('#pieChart_mixventa2').length ){
                
                  var ctx2 = document.getElementById("pieChart_mixventa2");
                  var data2 = {
                    datasets: [{
                      data: [<?php echo $data_cant; ?>],
                      backgroundColor: [
                        "#455C73",
                        "#9B59B6",
                        "#BDC3C7",
                        "#26B99A",
                        "#3498DB",
                        "#F08080",
                        "#FF69B4",
                        "#FFA500",
                        "#EEE8AA",
                        "#DDA0DD",
                        "#7B68EE",
                        "#90EE90",
                        "#AFEEEE",
                        "#D2691E"
                      ],
                      label: 'Top 10 mas vendidos' // for legend
                    }],
                    labels: [
                      <?php echo $data_prod ?>
                    ],
                  };

                  var pieChart2 = new Chart(ctx2, {
                    data: data2,
                    type: 'pie',
                    options : {
                        legend: {
                            display: <?php echo $leyenda ?>,
                            position: 'bottom',
                
                        },
                        maintainAspectRatio: true,
                        responsive: true,
                        useRandomColors: true
                    }
                  });
                  
              }
    
    </script>
  </body>
</html>
