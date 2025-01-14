 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "75";
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

if (intval($_GET['categorial']) > 0) {
    $cate = intval($_GET['categorial']);
    $addcate = " and productos.idcategoria=$cate" ;
}
if (intval($_GET['subcategoria']) > 0) {
    $subcate = intval($_GET['subcategoria']);
    $addsubcate = " and productos.idsubcate=$subcate" ;
}
if (($_GET['cj']) > 0) {
    $idcaja = intval($_GET['cj']);
    $filtrocaja = " and ventas.idcaja = $idcaja";

}
if (intval($_GET['idapp']) > 0) {
    $idapp = intval($_GET['idapp']);
    $whereadd .= " and ventas.idapp=$idapp " ;
}
if (intval($_GET['idproveedor']) > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $join_add = " 
    
    inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
    ";
    $whereadd .= " and insumos_lista.idproveedor = $idproveedor";
}
if (intval($_GET['idsalon']) > 0) {
    $idsalon = intval($_GET['idsalon']);
    $whereadd .= " and (select idsalon from mesas where idmesa = ventas.idmesa limit 1)  = $idsalon ";
}
if (intval($_GET['idvendedor']) > 0) {
    $idvendedor = intval($_GET['idvendedor']);
    $whereadd .= " and ventas.vendedor  = $idvendedor ";
}
if (intval($_GET['idcanal']) > 0) {
    $idcanal = intval($_GET['idcanal']);
    $whereadd .= " and ventas.idcanal  = $idcanal ";
}



if (intval($_GET['idcanalventa']) > 0) {
    $idcanalventa = intval($_GET['idcanalventa']);
    $whereadd .= " and ventas.idcanalventa  = $idcanalventa ";
}


if (($_GET['suc']) > 0) {
    $idsuc = intval($_GET['suc']);
    $filtrocaja = " and ventas.sucursal = $idsuc";
}

if (intval($_GET['idproducto']) > 0) {
    $idproducto = intval($_GET['idproducto']);
    $whereaddprod = " and productos.idprod  = $idproducto ";
    $whereaddcomboycombinados = " and p.idprod_serial = $idproducto ";
    //$joinaddcomboycombinados = "inner join ventas_detalles on ventas_detalles.idventa = ventas.idventa";
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
if (($_GET['idlistaprecio']) > 0) {
    $idlistaprecio = intval($_GET['idlistaprecio']);
    if ($idlistaprecio > 1) {
        $whereadd_mix .= " and ventas_detalles.idlistaprecio = $idlistaprecio ";
    } elseif ($idlistaprecio == 1) {
        $whereadd_mix .= " and (ventas_detalles.idlistaprecio is null or ventas_detalles.idlistaprecio = 1) ";
    }


}
if (intval($_GET['idtipotran']) > 0) {
    $idtipotran = intval($_GET['idtipotran']);
    $whereadd .= " and ventas.idtipotran  = $idtipotran ";
}


$consulta = "
select productos.idprod_serial, productos.descripcion, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as total,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
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
$whereaddprod
$whereadd_mix

group by productos.descripcion, productos.idprod_serial
order by sum(subtotal) desc
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if ($cate == 0) {
    $consulta = "
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
    $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
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


//catego
$buscar = "Select * from categorias where idempresa=$idempresa and estado=1 order by nombre asc";
$rscat = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$parametros_url = parametros_url();
/*
para encontrar la diferencia
diferencias.php
*/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<script src="js/sweetalert.min.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<?php require("includes/head.php"); ?>
 <link rel="stylesheet" type="text/css" href="css/tablesorter.css">
<script src="js/jquery.tablesorter.min.js" type="text/javascript"></script> 
<script>
/*$(document).ready(function(){ 
        $("#myTable").tablesorter(); 
});  */   
function filtrar(){
    
    var cate=document.getElementById('categorial').value;
    
    var parametros = {
         "categoria" : cate
     };
     $.ajax({
                data:  parametros,
                url:   'mini_subcate.php',
                type:  'post',
                beforeSend: function () {
                        
                },
                success:  function (response) {
                    $("#minisub").html(response);
                }
        });
    
}
   $(document).ready(function () {
        jQuery.tablesorter.addParser({
            id: "fancyNumber",
            is: function (s) {
                return /^[0-9]?[0-9,\.]*$/.test(s);
            },
            format: function (s) {
                 var s = parseFloat(s.replace(/[.]/g, '').replace(',', '.'));
                return (isNaN(s)) ? 0 : s;
            },
            type: "numeric"
        });

        $(".tablesorter").tablesorter({
            headers: { 
            0: { sorter: false },
            1: { sorter: false },
            2: { sorter: false },
            3: { sorter: 'fancyNumber'},
            4: { sorter: 'fancyNumber'},
            },
            widgets: ['zebra']
        });
        var table = $("#mitabla");
    
        table.bind("sortEnd",function() { 
            var i = 1;
            table.find("tr:gt(0)").each(function(){
                $(this).find("td:eq(0)").text(i);
                i++;
            });
        });
    }); 
</script>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
     <div align="center" >
         <?php require_once("includes/menuarriba.php");?>
    </div>
    <div class="colcompleto" id="contenedor">
        <!-- SECCION DONDE COMIENZA TODO -->
        <br /><br />
      <div class="divstd">
            <span class="resaltaditomenor">
                Informe de Ventas<br />
</span>
         </div>

  <br />
<?php require_once("includes/menu_top_informes.php"); ?>
        <br />

<hr class="hr" /><br />
<form id="form1" name="form1" method="get" action="">
<table width="898" border="0">
  <tbody>
    <tr>
      <td width="130" height="40" align="left"><strong>Fecha Desde:</strong></td>
      <td width="331"><input type="date" name="desde" id="desde" value="<?php echo $desde; ?>" style="height: 30px;" /></td>
      <td width="143" height="40" align="left"><strong>Fecha Hasta:</strong></td>
      <td width="276"><input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" style="height: 30px;"/></td>
      
    </tr>
    <tr>
      <td height="40" align="left"><strong>Hora Desde:</strong></td>
      <td ><input type="time" name="hdesde" id="hdesde" value="<?php echo $hdesde; ?>" /></td>
      <td align="left"><strong>Hora Hasta:</strong></td>
      <td id="minisub3"><input type="time" name="hhasta" id="hhasta" value="<?php echo $hhasta; ?>" /></td>
      
    </tr>
    <tr>
      <td height="40"  align="left"><strong>Categor&iacute;a: </strong></td>
      <td >
          <select name="categorial" id="categorial"  style="height: 30px; width: 90%;" onChange="filtrar()">
              <option value="0" selected="selected">TODOS</option>
               <?php while (!$rscat->EOF) {?>
              <option value="<?php echo $rscat->fields['id_categoria']?>"<?php if ($cate == $rscat->fields['id_categoria']) {?> selected="selected" <?php }?>><?php echo $rscat->fields['nombre']?></option>
              
              <?php $rscat->MoveNext();
               }?>
          </select  style="height: 30px; width: 90%;">
      </td>
      <td height="40"  align="left"><strong>Sub-Categor&iacute;a:</strong></td>
      <td id="minisub">
      <?php require_once('mini_subcate.php');?>
      </td>
     
    </tr>
    <tr>
      <td height="40"  align="left"><strong>Sucursal:</strong></td>
      <td ><?php


//lista de sucursales
$buscar = "select * from sucursales where idempresa=$idempresa and estado = 1 order by nombre asc";
$rsfd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?><select name="suc" id="suc" style="height: 30px; width: 90%;">
                                <option value="0" <?php if (intval($_GET['suc']) == 0) {?>selected="selected"<?php }?>>TODOS</option>
                                <option value="ss" <?php if ($_GET['suc'] == 'ss') {?>selected="selected"<?php }?> >SOLO SUCURSALES</option>
                               <?php while (!$rsfd->EOF) {?>
                                <option value="<?php echo $rsfd->fields['idsucu']?>" <?php if ($rsfd->fields['idsucu'] == intval($_GET['suc'])) {?>selected="selected"<?php }?>><?php echo $rsfd->fields['nombre']?></option>
                            
                                 <?php $rsfd->MoveNext();
                               }?>
                            </select></td>
      <td height="40" align="left"><strong>Proveedor: </strong></td>
      <td >
      

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
if (isset($_GET['idproveedor'])) {
    $value_selected = htmlentities($_GET['idproveedor']);
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

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
      </td>
      
    </tr>
    <tr>
      <td height="40" align="left"><strong>Lista Precio:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idlistaprecio, lista_precio
FROM lista_precios_venta
where
estado = 1
order by lista_precio asc
 ";

// valor seleccionado
if (isset($_GET['idlistaprecio'])) {
    $value_selected = htmlentities($_GET['idlistaprecio']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idlistaprecio',
    'id_campo' => 'idlistaprecio',

    'nombre_campo_bd' => 'lista_precio',
    'id_campo_bd' => 'idlistaprecio',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
      <td height="40" align="left"><strong>Salon:</strong></td>
      <td ><?php
    // consulta
    $consulta = "
    SELECT idsalon, CONCAT(salon.nombre,' [',sucursales.nombre,']') as nombre
    FROM salon
    inner join sucursales on sucursales.idsucu = salon.idsucursal
    where
       salon.estado_salon = 1
      and sucursales.estado = 1
    order by salon.nombre asc
     ";

// valor seleccionado
if (isset($_GET['idsalon'])) {
    $value_selected = htmlentities($_GET['idsalon']);
} else {
    // $value_selected=htmlentities($cidsalon_usu);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsalon',
    'id_campo' => 'idsalon',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsalon',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
      
    </tr>
    <tr>
      <td height="40" align="left"><strong>App:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idapp, app
FROM app
where
estado = 1
order by app asc
 ";

// valor seleccionado
if (isset($_GET['idapp'])) {
    $value_selected = htmlentities($_GET['idapp']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idapp',
    'id_campo' => 'idapp',

    'nombre_campo_bd' => 'app',
    'id_campo_bd' => 'idapp',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
      <td height="40" align="left"><strong>Vendedor:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idvendedor, concat(nombres,apellidos) as vendedor
FROM vendedor
where
estado = 1
order by concat(nombres,apellidos) asc
 ";

// valor seleccionado
if (isset($_GET['idvendedor'])) {
    $value_selected = htmlentities($_GET['idvendedor']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvendedor',
    'id_campo' => 'idvendedor',

    'nombre_campo_bd' => 'vendedor',
    'id_campo_bd' => 'idvendedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
      
    </tr>
    <tr>
      <td height="40" align="left"><strong>Canal:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idcanal, canal
FROM canal
where
estado_canal = 1
order by canal asc
 ";

// valor seleccionado
if (isset($_GET['idcanal'])) {
    $value_selected = htmlentities($_GET['idcanal']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcanal',
    'id_campo' => 'idcanal',

    'nombre_campo_bd' => 'canal',
    'id_campo_bd' => 'idcanal',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
      <td height="40" align="left"><strong>Canal Venta:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idcanalventa, canal_venta
FROM canal_venta
where
estado = 1
order by canal_venta asc
 ";

// valor seleccionado
if (isset($_GET['idcanalventa'])) {
    $value_selected = htmlentities($_GET['idcanalventa']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcanalventa',
    'id_campo' => 'idcanalventa',
    'nombre_campo_bd' => 'canal_venta',
    'id_campo_bd' => 'idcanalventa',
    'value_selected' => $value_selected,
    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];




// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
    </tr>
    <tr>
      <td height="40" align="left"><strong>Tipo Trans:</strong></td>
      <td >
<?php
// valor seleccionado
if (isset($_GET['idtipotran'])) {
    $value_selected = htmlentities($_GET['idtipotran']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'VENTAS' => '1',
    'CONSUMOS' => '2'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'idtipotran',
    'id_campo' => 'idtipotran',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?></td>




<td height="40" align="left"><strong>Producto:</strong></td>
      <td ><?php
// consulta
$consulta = "
SELECT idprod as idproducto, descripcion
FROM productos
where
borrado = 'N'
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['idproducto'])) {
    $value_selected = htmlentities($_GET['idproducto']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproducto',
    'id_campo' => 'idproducto',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idproducto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"  style="height: 30px; width: 90%;"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];




// construye campo
echo campo_select($consulta, $parametros_array);
?></td>




      <td height="33" align="right">&nbsp;</td>
      <td >&nbsp;</td>
    </tr>
    <tr>
      <td colspan="5" align="center">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="5" align="center"><input type="submit" name="submit" id="submit" value="Generar Reporte" /></td>
      </tr>
  </tbody>
</table>
</form>
<br /><hr class="hr" /><br />
      <p align="center"><strong><span style="font-size:12px;">Mix de Venta</span></strong></p>
        <p align="center"><a href="informe_venta_csv.php<?php echo $parametros_url; ?>"><span style="font-size:12px;">[Descargar]</span></a></p>
      <p>&nbsp;</p>
        
        <table width="700" border="1" class="tablesorter" style="margin:0px auto;" id="mitabla">
          <thead>
            <tr>
              <th align="center" bgcolor="#F8FFCC"><strong>N&deg;</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Codigo</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Productos</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Total</strong></th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rs->EOF) {
               $aa = $aa + 1;
               $totalacum += $rs->fields['total'];
               $cantidad_acum += $rs->fields['cantidad'];
               ?>
            <tr>
              <td id="nn"><?php echo $aa; ?></td>
              <td align="left"><?php echo $rs->fields['idinsumo']; ?></td>
              <td align="left"><?php echo $rs->fields['descripcion']; ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], 2, 'N'); ?></td>
              <td align="right"><?php echo formatomoneda($rs->fields['total']); ?></td>
            </tr>
           <?php  $rs->MoveNext();
           }  ?> 
          </tbody>
            <tr>
              <td ></td>
              <td align="left">Total</td>
              <td align="left"></td>
              <td align="center"><?php echo formatomoneda($cantidad_acum, 0); ?></td>
              <td align="right"><?php echo formatomoneda($totalacum); ?></td>
            </tr>
      </table>
      
      
      
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <?php if ($cate == 0) {?>
        <table width="700" border="1" class="tablesorter" style="margin:0px auto;">
          <thead>
            <tr>
              <th align="center" bgcolor="#F8FFCC"><strong>Agregados</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Total</strong></th>
            </tr>
           <thead>
           <tbody>
           <?php  while (!$rs2->EOF) {
               $totalacum2 += $rs2->fields['total'];

               ?>
            <tr>
              <td align="left"><?php if (trim($rs2->fields['alias']) != '') {
                  echo $rs2->fields['alias'];
              } else {
                  echo $rs2->fields['alias2'].'*';
              } ?></td>
              <td align="center"><?php echo formatomoneda($rs2->fields['cantidad'], 0); ?></td>
              <td align="right"><?php echo formatomoneda($rs2->fields['total']); ?></td>
            </tr>
           <?php  $rs2->MoveNext();
           }  ?> 
          </tbody>
      </table>
       <?php } ?>
  <br />   <br />  
<p align="center"><strong>Mix de Combinados:</strong></p>
<p align="center"><a href="informe_venta_mix_combinados_csv.php<?php echo $parametros_url; ?>"><span style="font-size:12px;">[Descargar]</span></a></p>
        <br /><br />
<?php
$consulta = "
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
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
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
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
select productos.idprod_serial, productos.descripcion, sum(tmp_combinado_listas.cant_global) as cantidad, 
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from ventas 
inner join tmp_combinado_listas on ventas.idventa = tmp_combinado_listas.idventa
inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
inner join productos p on p.idprod_serial = tmp_combinado_listas.idproducto_principal
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
$whereaddcomboycombinados
group by productos.descripcion, productos.idprod_serial
order by sum(tmp_combinado_listas.cant_global) desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
?>
       
        <table width="700" border="1" class="tablesorter" style="margin:0px auto;" id="mitabla">
          <thead>
            <tr>
              <th align="center" bgcolor="#F8FFCC"><strong>N&deg;</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Codigo</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Productos</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rs->EOF) {
               $aa = $aa + 1;
               $totalacum += $rs->fields['total'];
               $cantidad_acum += $rs->fields['cantidad'];
               ?>
            <tr>
              <td id="nn"><?php echo $aa; ?></td>
              <td align="left"><?php echo $rs->fields['idinsumo']; ?></td>
              <td align="left"><?php echo $rs->fields['descripcion']; ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], 2, 'N'); ?></td>
            </tr>
           <?php  $rs->MoveNext();
           }  ?> 
          </tbody>
            <tr>
              <td ></td>
              <td align="left">Total</td>
              <td align="left"></td>
              <td align="center"><?php echo formatomoneda($cantidad_acum, 0); ?></td>

            </tr>
      </table>
     
<?php
$consulta = "
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
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select productos.idprod_serial, productos.descripcion, count(tmp_combos_listas.idlistacombo_tmp) as cantidad, 
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from ventas 
inner join tmp_combos_listas on ventas.idventa = tmp_combos_listas.idventa
inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
inner join ventas_detalles on ventas_detalles.idventatmp = tmp_combos_listas.idventatmp
inner join productos p on p.idprod_serial = ventas_detalles.idprod
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
$whereaddcomboycombinados
group by productos.descripcion, productos.idprod_serial
order by count(tmp_combos_listas.idlistacombo_tmp) desc
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
?>
  <br />   <br />  
<p align="center"><strong>Mix de Combos:</strong></p>
-<p align="center"><a href="informe_venta_mix_combo_csv.php<?php echo $parametros_url; ?>"><span style="font-size:12px;">[Descargar]</span></a></p>
        <br /><br />
       
        <table width="700" border="1" class="tablesorter" style="margin:0px auto;" id="mitabla">
          <thead>
            <tr>
              <th align="center" bgcolor="#F8FFCC"><strong>N&deg;</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Codigo</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Productos</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rs->EOF) {
               $aa = $aa + 1;
               $totalacum += $rs->fields['total'];
               $cantidad_acum += $rs->fields['cantidad'];
               ?>
            <tr>
              <td id="nn"><?php echo $aa; ?></td>
              <td align="left"><?php echo $rs->fields['idinsumo']; ?></td>
              <td align="left"><?php echo $rs->fields['descripcion']; ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], 2, 'N'); ?></td>
            </tr>
           <?php  $rs->MoveNext();
           }  ?> 
          </tbody>
            <tr>
              <td ></td>
              <td align="left">Total</td>
              <td align="left"></td>
              <td align="center"><?php echo formatomoneda($cantidad_acum, 0); ?></td>

            </tr>
      </table>
       
        <p>&nbsp;</p>
        <p align="center"><br />
        </p>
        <table width="700" border="1">
          <tbody>
            <tr>
              <td><strong>Total Productos<?php if ($cate > 0) {?> Seleccionados:<?php }?> </strong></td>
              <td align="right"><?php echo formatomoneda($totalacum); ?></td>
            </tr>
            <?php if ($cate == 0) {?>
            <?php /* ?><tr>
              <td><strong>Total Agregados:</strong></td>
              <td align="right"><?php echo formatomoneda($totalacum2); ?></td>
            </tr>
            <tr>
              <td><strong>Total Productos + Agregados:</strong></td>
              <td align="right"><?php echo formatomoneda($totalacum+$totalacum2); ?></td>
            </tr><?php */ ?>
            <!--<tr>
              <td><strong>Delivery's:</strong></td>
              <td align="right"><?php echo formatomoneda($rs3->fields['monto_delivery']); ?></td>
            </tr>
            <tr>
              <td><strong>Descuentos:</strong></td>
              <td align="right">-<?php echo formatomoneda($rs3->fields['monto_descuento']); ?></td>
            </tr>
            <tr bgcolor="#F8FFCC" style="font-size:16px;">
              <td><strong>Total Global:</strong></td>
              <td align="right"><?php echo formatomoneda($totalacum + $rs3->fields['monto_delivery'] - $rs3->fields['monto_descuento']); ?></td>
            </tr>-->
            <?php }?>
          </tbody>
        </table>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p align="center">&nbsp;</p>
        <p>&nbsp;</p>
        
    <br /><br /><br /><br />        
  </div> <!-- contenedor --> 
  <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
