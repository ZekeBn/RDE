 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");

$palabra = antisqlinyeccion($_POST['busca'], 'text');

$bb = str_replace("'", '', $palabra);
if ($palabra == 'NULL') {
    $consulta = "
    select * 
    from cliente
    where 
    idempresa = $idempresa
    and estado = 1
    order by razon_social asc
    limit 100
    ";
} else {
    $consulta = "
    select * 
    from cliente
    where 
    idempresa = $idempresa and razon_social like '%$bb%'
    and estado = 1
    order by razon_social asc
    limit 100
    ";
}

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?>




<table width="900" border="1" style="border-collapse:collapse;">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Idcliente</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Ruc</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Razon Social</strong></td>
      <?php if ($rsco->fields['usa_adherente'] == 'S') { ?><td align="center" bgcolor="#F8FFCC"><strong>[Adherentes]</strong></td><?php } ?>
      <td align="center" bgcolor="#F8FFCC"><strong>[Consumo]</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>[Linea]</strong></td>
     <!-- <td align="center" bgcolor="#F8FFCC">Linea Sobregiro</td>
      <td align="center" bgcolor="#F8FFCC">Maximo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Consumo Actual</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Linea</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Cant Adherentes.</td>-->
      <td align="center" bgcolor="#F8FFCC"><strong>[Editar]</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>[Borrar]</strong></td>
    </tr>
<?php while (!$rs->EOF) {?>
    <tr>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['ruc'];?></td>
      <td align="center"><?php echo $rs->fields['razon_social'];?></td>
      <?php if ($rsco->fields['usa_adherente'] == 'S') { ?><td align="center"><a href="adherentes_credito.php?id=<?php echo $rs->fields['idcliente'];?>">[Adherentes]</a></td><?php } ?>
      <td align="center"><a href="venta_credito_consumo.php?id=<?php echo $rs->fields['idcliente'];?>">[Consumo]</a></td>
      <td align="center"><a href="cliente_edita_cred_linea.php?id=<?php echo $rs->fields['idcliente'];?>">[Linea]</a></td>
      <!--<td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>-->
      <td align="center"><a href="cliente_edita_cred.php?id=<?php echo $rs->fields['idcliente'];?>">[Editar]</a></td>
      <td align="center"><a href="cliente_borra_cred.php?id=<?php echo $rs->fields['idcliente'];?>">[Borrar]</a></td>
    </tr>
<?php $rs->MoveNext();
}?>
  </tbody>
</table>
