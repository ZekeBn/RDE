 <?php
require_once("conexion.php");
require_once("funciones.php");

$idcli = intval($_POST['idcli']);
if ($idcli > 0) {
    $buscar = "Select * from cliente where idcliente=$idcli"    ;

    $rsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $razon = trim($rsc->fields['razon_social']);
    $ruc = trim($rsc->fields['ruc']);
    $direccion = trim($rsc->fields['direccion']);
    $telfos = trim($rsc->fields['telefono']).'/'.trim($rsc->fields['celular']);



} else {
    //Vemos si no existe un cliente de pedido
    if ($idclientepedido > 0) {
        $idcli = $idclientepedido;
        $buscar = "Select * from cliente where idcliente=$idclientepedido"    ;

        $rsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $razon = trim($rsc->fields['razon_social']);
        $ruc = trim($rsc->fields['ruc']);
        $direccion = trim($rsc->fields['direccion']);
        $telfos = trim($rsc->fields['telefono']).'/'.trim($rsc->fields['celular']);

    } else {
        $razon = '';
        $ruc = '';
        $direccion = '';
        $telfos = '';
    }

}

?>


<table width="100%" border="1">
  <tbody>
  <tr><td><input type="hidden" name="idclioc" id="idclioc" value="<?php echo $idcli?>"></td></tr>
    <tr>
      <td width="96" height="31" align="left" valign="middle"><label for="razon"><strong>Raz&oacute;n Social :</strong></label><input type="text" id="razon" name="razon"  placeholder="Ingrese Razon social del comprador" onKeyUp="buscliente(this.value)" value="<?php echo $razon; ?>"/></td>
    </tr>
    <tr>
      <td height="31" align="left" valign="middle"><label for="ruc"><strong>RUC: </strong></label><input type="text" id="ruc" name="ruc" value="<?php echo $ruc; ?>" placeholder="Ingrese RUC del comprador" />
    <a href="javascript:void(0);" onClick="generico()"><img src="img/notas.gif" width="16" height="16" Title="RUC Generico"/></a></td>
    </tr>
    <tr>
      <td height="31" align="left" valign="middle"><label for="direccion"><strong>Direcci&oacute;n: </strong></label><input type="text" id="direccion" name="direccion" value="<?php echo $direccion; ?>" placeholder="Ingrese direccion del comprador" /></td>
    </tr>
    <tr>
      <td align="left" valign="middle"><label for="conta"><strong>Telfo: </strong></label><input type="text" id="conta" name="conta" value="<?php echo $telfos; ?>" placeholder="Numeros de contacto" /></td>
    </tr>
  </tbody>
</table>
