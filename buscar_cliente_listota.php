 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";

require_once("includes/rsusuario.php");

$ruc = antisqlinyeccion($_POST['ru'], 'text');
$rz = antisqlinyeccion($_POST['rz'], 'text');
$tipo = intval($_POST['tipo']);


if ($tipo == 1) {
    $dd = str_replace("'", "", $rz);
    $add = " and razon_social like('%$dd%') order by razon_social asc";
}
if ($tipo == 2) {
    $dd = str_replace("'", "", $ruc);
    $add = " and ruc like('$dd%') order by ruc asc";
}


//Lista de clientes registrados
$buscar = "Select * from cliente where idempresa=$idempresa and borrable='S' $add ";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$total = $rs->RecordCount();
//echo $buscar;

$buscar = "Select * from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usaportal = trim($rspref->fields['habilita_portal']);
?>


<?php if ($total > 0) {?>
  <table width="802" border="1" style="padding:2px;margin:2px;">
  <tr>
    <td height="25" colspan="6" align="center" bgcolor="#ECECEC"><strong>Total Registros <?php echo $total ?></strong></td>
    </tr>
  <tr>
    <td width="166" height="25" align="center" bgcolor="#ECECEC"><strong>Razon Social</strong></td>
    <td width="97" align="center" bgcolor="#ECECEC"><strong>Ruc</strong></td>
    <td width="137" align="center" bgcolor="#ECECEC"><strong>CI</strong></td>
    <td width="137" align="center" bgcolor="#ECECEC"><strong>Direcci&oacute;n</strong></td>
    <td width="101" align="center" bgcolor="#ECECEC"><strong>Celular / Otros</strong></td>
    <td width="124" align="center" bgcolor="#ECECEC"><strong>Acciones</strong></td>
  </tr>
  <?php while (!$rs->EOF) {

      $idcli = $rs->fields['idcliente'];
      $tipocliente = intval($rs->fields['tipocliente']);
      ?>
  <tr>
    <td>
        <?php
    //    if ($rs->fields['tipocliente']==1){
    //
    //    } else {

         //}
         if (trim($rs->fields['razon_social']) == '') {
             echo(trim($rs->fields['nombre']).' '.trim($rs->fields['apellido']));
         } else {
             echo(trim($rs->fields['razon_social']));

         }
      ?></td>
    <td align="center"><?php echo($rs->fields['ruc']); ?></td>
    <td><?php echo formatomoneda($rs->fields['documento'])?></td>
    <td><?php echo capitalizar(trim($rs->fields['direccion'])); ?></td>
    <td align="center" style="margin-left:2px"><?php echo trim($rs->fields['celular']); ?></td>
    <td align="center"><a href="gest_editar_clientesv2.php?idc=<?php echo $idcli?>" ><img src="img/1445735221_file.png" width="20" height="20" title="Editar" /></a><img src="img/dropdb.png" width="22" height="22" title="Eliminar" onclick="eliminar(<?php echo $idcli ?>)" />
     <?php if ($usaportal == 'S' && $tipocliente == 2) {?>
     <a href="gest_administrar_acceso_portal.php?idc=<?php echo $idcli?>"><img src="img/inet.png" width="22" height="22" title="Administrar Portal"/></a>
     <?php }?>
     <a href="venta_credito_habilita.php?bus_rz=<?php echo  (trim($rs->fields['razon_social']))?>" title="Linea de Credito"><img src="img/pagos.png" width="22" height="22" alt=""/></a>      </td>
  </tr>
   <?php
   $rs->MoveNext();
  }
    ?>
</table>
<?php } else {
    echo 'No encontramos registros.';
} ?>
