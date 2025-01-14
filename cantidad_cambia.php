 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "58";
require_once("includes/rsusuario.php");

$idprod = intval($_GET['id']);
$total = 0;
if ($idprod > 0) {
    $consulta = "
    select tmp_ventares.*, productos.descripcion, tmp_ventares.cantidad, tmp_ventares.precio as p1
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idproducto = $idprod
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total = $rs->RecordCount();
    //$precio = $rs->fields['p1'];
    $idvt = intval($rs->fields['idventatmp']);
    if ($total == 1) {
        header("location: cantidad_cambia.php?idvt=$idvt");
        exit;
    }
}
$idvt = intval($_GET['idvt']);
if ($idvt > 0) {
    $consulta = "
    select tmp_ventares.*, productos.descripcion, tmp_ventares.cantidad, tmp_ventares.precio as p1
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idventatmp = $idvt
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idprod = $rs->fields['idproducto'];
    $precio = $rs->fields['p1'];
}
if (intval($idprod) == 0 && intval($idvt) == 0) {
    header("location: index.php");
    exit;
}

// para saber si exise mas de 1
$consulta = "
    select tmp_ventares.*, productos.descripcion, cantidad
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idproducto = $idprod
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
//echo $consulta;
$rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalex = $rsex->RecordCount();

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
    // recibe cantidad
    $cantidad_new = floatval($_POST['cantidad_new']);
    //echo $precio;
    $subtotal_new = floatval($precio * $cantidad_new);
    // calcular precio nuevo


    // actualiza cantidad, precio, subtotal
    $consulta = "
    update tmp_ventares
    set 
    cantidad = $cantidad_new,
    subtotal = $subtotal_new
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idventatmp = $idvt
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: index.php");
    exit;



}
?><!doctype html>
<html>
<head>
<title>Toma de Pedidos</title>
<?php require_once("includes/head.php"); ?>
</head>

<body>
<?php require_once("includes/cabeza.php"); ?>
<div class="cuerpo">
<?php

if ($total > 1) {
    $seleccionaing = "S";
    ?>
<p align="center" onMouseUp="document.location.href='index.php'"><strong><img src="gfx/iconos/atras.png" width="50"  alt="Favorito"/><br />
Volver</strong></p>
<h1 align="center">Cambiar Cantidad:</h1>
<p align="center">Existe mas de 1 pedido del mismo producto, seleccione el producto a Editar:</p>
<br />
<table width="98%" border="1" class="tablalinda">
  <tbody>
    <tr>
      <td bgcolor="#CCCCCC"><strong>Producto</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Cantidad</strong></td>
      <td align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
    </tr>
<?php while (!$rs->EOF) {
    $total = $rs->fields['precio'] * $rs->fields['total'];
    $totalacum += $total;

    $idvt = $rs->fields['idventatmp'];
    $consulta = "
select tmp_ventares_agregado.*
from tmp_ventares_agregado
where 
idventatmp = $idvt
order by alias desc
";
    $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
select tmp_ventares_sacado.*
from tmp_ventares_sacado
where 
idventatmp = $idvt
order by alias desc
";
    $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
    <tr>
      <td height="50"><?php echo Capitalizar($rs->fields['descripcion']); ?><?php
    if ($rs->fields['combinado'] == 'S') {

        $prod_1 = $rs->fields['idprod_mitad1'];
        $prod_2 = $rs->fields['idprod_mitad2'];
        $consulta = "
select *
from productos
where 
(idprod_serial = $prod_1 or idprod_serial = $prod_2)
order by descripcion asc
";
        $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rspcom->EOF) {

            ?><br /><span style="font-style:italic;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Mitad <?php echo Capitalizar($rspcom->fields['descripcion']); ?></span>
      <?php $rspcom->MoveNext();
        }
    } ?></td>
      <td align="center"><?php echo floatval($rs->fields['cantidad']);  ?></td>
      <td align="center"><input type="button" name="button2" id="button9" value="Seleccionar" class="boton" onClick="document.location.href='cantidad_cambia.php?idvt=<?php echo $rs->fields['idventatmp']; ?>'"></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<?php } else { ?>
<br />
<form id="form1" name="form1" method="post">
<table class="tablalinda" style="margin:0px auto; border:2px solid #000;">
  <tbody>
  <tr align="center" style="border:2px solid #000;">
    <td>
        <table width="99%" height="99%" border="0">
              <tbody>
                <tr>
                  <td height="40" align="center"><?php echo Capitalizar($rs->fields['descripcion']); ?></td>
                </tr>              
                <tr>
                  <td  height="40"><input type="text" name="cantidad_new" style="width:250px;" required id="cantidad_new" placeholder="Cantidad" value="<?php if (isset($_POST['cantidad_new'])) {
                      echo floatval($_POST['cantidad_new']);
                  } else {
                      echo floatval($rs->fields['cantidad']);
                  } ?>" ></td>
                </tr>
                
            </tbody>
        </table>
    </td>
    <td rowspan="4" align="center" valign="middle"><input type="submit" value="Guardar Cambio" class="boton" style="height:100px; width:128px;">
        </td>
  </tr>
  </tbody>
</table>
<input type="hidden" name="MM_update" id="MM_update" value="form1">
</form>
<br />
<?php } ?>
  <br />
<div class="clear"></div>

</div>

<div class="clear"></div>
</div>
</body>
</html>
