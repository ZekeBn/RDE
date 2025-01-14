 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");



?>

<div align="center">
<br />
  <span class="resaltaditomenor">Aplicar Descuento - Pedido: <?php echo intval($_POST['pedi']);?></span><br />
  <table width="337" height="295" border="0" class="tablaconborde">
          <tr>
            <td width="107" height="44" align="right" bgcolor="#E1ECE9"><strong>Cajero</strong></td>

            <td width="207" align="left" style="font-size:14px; font-weight:bold"><span class="resaltarojomini"><?php echo $cajero; ?></span></td>
      </tr>
        <tr>
            <td height="49" align="right" bgcolor="#E1ECE9"><strong>Total Venta Gs</strong></td>

            <td align="left" style="font-size:14px; font-weight:bold"><input type="hidden" name="tvdes" id="tvdes" value="<?php echo floatval($_POST['tv']); ?>"><?php echo formatomoneda(floatval($_POST['tv']))?></td>
      </tr>
        <tr>
          <td height="50" align="right" bgcolor="#E1ECE9"  ><strong>% a Descontar</strong></td>
          <td align="left" valign="middle"><input name="descontar_porc" type="text" id="descontar_porc" style="width:20%; height:40px;" onKeyUp="most_porc(this.value)" value="" size="5" maxlength="3" /> 
            <strong>%</strong></td>
        </tr>
        <tr>
            <td height="50" align="right" bgcolor="#E1ECE9"  ><strong>Monto Descontar</strong></td>

            <td align="left"><input type="text" name="descontar" value="" id="descontar" style="width:99%; height:40px;" onKeyUp="most(this.value)" /> </td>
        </tr>
        <tr>
             <td height="44" align="right" bgcolor="#E1ECE9"><strong><strong>Motivo descuento</strong></strong></td>

            <td align="left"><textarea id="motiv" name="motiv" rows="6" style="width:100%"></textarea></td>
        </tr>
        <tr>
             <td height="44" colspan="2" align="center"  bgcolor="#E1ECE9">
          <input type="button" name="rfg" id="rfg" value="Registrar Descuento" style="height:40px;" onClick="registrardes()"/>
          </td>
             
    </tr>


    </table><br /><br />
</div>
