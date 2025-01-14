 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");


// actualizar saldos
$consulta = "
update cliente set 
saldo_sobregiro = linea_sobregiro-COALESCE((
                    select sum(cuentas_clientes.saldo_activo) as saldoactivo 
                    from cuentas_clientes 
                    where 
                    cuentas_clientes.idcliente = cliente.idcliente 
                    and cuentas_clientes.idempresa = cliente.idempresa
                  ),0)
where 
cliente.idempresa = $idempresa
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

$mesactu = date("m");
$anoactu = date("Y");

$consulta = "
SELECT ventas.idcliente, sum(totalcobrar) as total, count(*) as cantidad,
    (select nombre from cliente where idcliente = ventas.idcliente) as funcionario_nombre,
    (select apellido from cliente where idcliente = ventas.idcliente) as funcionario_apellido,
    (select saldo_sobregiro from cliente where idcliente = ventas.idcliente) as disponible,
    (select linea_sobregiro from cliente where idcliente = ventas.idcliente) as linea_sobregiro,
    (select max_mensual from cliente where idcliente = ventas.idcliente) as max_mensual,
    (
    select sum(cuentas_clientes.saldo_activo) as saldoactivo 
    from cuentas_clientes 
    where 
    cuentas_clientes.idcliente = ventas.idcliente 
    and cuentas_clientes.idempresa = ventas.idempresa
    ) saldoactivo,
    (
    select sum(totalcobrar) 
    from ventas as vent
    where 
    MONTH(vent.fecha) = $mesactu 
    and YEAR(vent.fecha) = $anoactu 
    and vent.tipo_venta = 2 
    and vent.idcliente = ventas.idcliente
    and vent.idempresa = ventas.idempresa
    ) as consumomes
FROM ventas 
where 
tipo_venta = 2
and idempresa = $idempresa
and date(ventas.fecha) >= '$desde'
and date(ventas.fecha) <= '$hasta'
and estado <> 6
and (
    select sum(cuentas_clientes.saldo_activo) as saldoactivo 
    from cuentas_clientes 
    where 
    cuentas_clientes.idcliente = ventas.idcliente 
    and cuentas_clientes.idempresa = ventas.idempresa
    ) > 0
group by idcliente
ORDER BY sum(totalcobrar) desc, (
    select sum(cuentas_clientes.saldo_activo) as saldoactivo 
    from cuentas_clientes 
    where 
    cuentas_clientes.idcliente = ventas.idcliente 
    and cuentas_clientes.idempresa = ventas.idempresa
    ) desc
limit 1000
";
$rscon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    $consulta = "
    select * 
    from cuentas_clientes 
    where 
    idempresa = $idempresa
    and date(cuentas_clientes.registrado_el) >= '$desde'
    and date(cuentas_clientes.registrado_el) <= '$hasta'
    and saldo_activo > 0
    ";
    $rscuen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recorrer cada cuenta e insertar en pagos
    while (!$rscuen->EOF) {
        $idcliente = $rscuen->fields['idcliente'];
        $idcta = $rscuen->fields['idcta'];
        $saldo_activo = $rscuen->fields['saldo_activo'];
        ;

        // insertar en pagos
        $consulta = "
        insert into cuentas_clientes_pagos
        (fecha_pago, idcuenta, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, banco, chequenum, estado, 
        sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idcliente)
        values
        ('$ahora', $idcta, $saldo_activo, $idusu, $idempresa, $saldo_activo, NULL, NULL, NULL, 1, 
        $idsucursal, 0, $saldo_activo, 0, NULL, $idcliente)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualizar cada cuenta en cuentas clientes
        $consulta = " 
        update cuentas_clientes
         set 
         saldo_activo = 0 
        where 
        idempresa = $idempresa 
        and idcliente = $idcliente 
        and idcta = $idcta    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $rscuen->MoveNext();
    }

    // actualizar saldos
    $consulta = "
    update cliente set 
    saldo_sobregiro = linea_sobregiro-(
                        select sum(cuentas_clientes.saldo_activo) as saldoactivo 
                        from cuentas_clientes 
                        where 
                        cuentas_clientes.idcliente = cliente.idcliente 
                        and cuentas_clientes.idempresa = cliente.idempresa
                      ) 
    where 
    cliente.idempresa = $idempresa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    header("location: reporte_pagos.php");
    exit;

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<script>
function preguntar_cobro(){
    if(window.confirm('Se eliminaran todas las cuentas sin dejar registro, esta accion no se puede deshacer. esta seguro?')){
        document.getElementById('form1').submit();    
    }
}
</script>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">
          <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="venta_credito.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>

                 <div class="divstd">
                    <span class="resaltaditomenor">Pago Masivo Venta a Credito</span></div>
    <hr />            <p>&nbsp;</p>
              <form id="form2" name="form2" method="get" action="">
                <table width="500" border="0">
                  <tbody>
                    <tr>
                      <td><strong>Desde:</strong></td>
                      <td><input type="date" name="desde" id="desde" value="<?php echo $desde; ?>" /></td>
                      <td><strong>Hasta:</strong></td>
                      <td><input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" /></td>
                      <td align="center"><input type="submit" name="submit" id="submit" value="Generar Reporte" /></td>
                    </tr>
                  </tbody>
                </table>
                <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
              </form>
<br />
    <p>&nbsp;</p>
              <p>&nbsp;</p>
              <table width="980" border="1">
                <tbody>
                  <tr>
                    <td align="center" bgcolor="#F8FFCC">Cliente</td>
                    <td align="center" bgcolor="#F8FFCC">Total<br />Tickets</td>
                    <td align="center" bgcolor="#F8FFCC">Consumo<br />Rango Fecha</td>
                  </tr>
                  <?php while (!$rscon->EOF) {    ?>
                  <tr>
                    <td align="left"><?php echo $rscon->fields['funcionario_nombre']; ?> <?php echo $rscon->fields['funcionario_apellido']; ?></td>
                    <td align="center"><?php echo formatomoneda($rscon->fields['cantidad']); ?></td>
                    <td align="center"><?php echo formatomoneda($rscon->fields['total']); ?></td>
                  </tr>
                  <?php
            $tk_tot += $rscon->fields['cantidad'];
                      $consumo_tot += $rscon->fields['total'];
                      $consumomes_tot += $rscon->fields['consumomes'];
                      $consumonoabo_tot += $rscon->fields['saldoactivo'];

                      $rscon->MoveNext();
                  } ?>
                  <tr>
                    <td align="left" bgcolor="#CCCCCC">Total:</td>
                    <td align="center" bgcolor="#CCCCCC"><?php echo formatomoneda($tk_tot); ?></td>
                    <td align="center" bgcolor="#CCCCCC"><?php echo formatomoneda($consumo_tot); ?></td>
                  </tr>
                </tbody>
              </table>
              <p>&nbsp;</p><br /><br /><hr /><br /><br />
              <form id="form1" name="form1" method="post" action="venta_credito_pagomasivo.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>">
                <H1 align="center" style="color:#FF0000; font-weight:bold;">CUIDADO!!!! ESTA ACCION NO SE PUEDE DESHACER O ANULAR!!!!</H1>
                <br />
   <table width="300" border="1">
  <tbody>
    <tr>
      <td colspan="2" align="center" bgcolor="#F8FFCC">Se marcara como pagado todas las cuentas entre:</td>
      </tr>
    <tr>
      <td width="50%" align="center"><?php echo date("d/m/Y", strtotime($desde)); ?></td>
      <td align="center"><?php echo date("d/m/Y", strtotime($hasta)); ?></td>
    </tr>
  </tbody>
</table>
<br />

                <p align="center">
                  <input type="button" name="button" id="button" value="Marcar todo como Pagado" onmouseup="preguntar_cobro();" />
                  <input type="hidden" name="MM_update" id="MM_update" value="form1" />
                </p>
              </form>
              <p>&nbsp;</p>
              <p></p>
          </div> 
          <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
