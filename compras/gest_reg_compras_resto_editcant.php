<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_iva.php");

$idregcc = intval($_POST['id']);
if ($idregcc == 0) {
    echo "Registro inexistente!";
    exit;
}

// consulta a la tabla
$consulta = "
select tmpcompradeta.*, insumos_lista.descripcion
from tmpcompradeta 
inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
where 
idregcc = $idregcc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idregcc = intval($rs->fields['idregcc']);
$idtransaccion = intval($rs->fields['idt']);
$idtipoiva = intval($rs->fields['idtipoiva']);
if ($idregcc == 0) {
    echo "Registro inexistente!";
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";



    // recibe parametros
    $cantidad = antisqlinyeccion($_POST['cantidad'], "float");
    $costo = antisqlinyeccion($_POST['costo'], "float");

    $subtotal = $cantidad * $costo;
    $p1 = $costo;
    $parametros_array = [
        "idinsumo" => intval($rs->fields['idprod']),
        "cantidad" => $cantidad,
        "costo_unitario" => $costo,
        "idtransaccion" => intval($rs->fields['idt']),
        "idregcc" => $idregcc,
        "idtipoiva" => $idtipoiva
    ];
    $res = validar_carrito_compra($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    }

    // si todo es correcto actualiza
    if ($valido == "S" && $res['valido'] = "S") {
        editar_carrito_compra($parametros_array);
    }

}



?><?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<br /><br />
<form id="form1" name="form1" method="post" action="">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>
  
  	<tr>
		<td align="center">Articulo</td>
		<td width="130" align="left"><?php  echo htmlentities($rs->fields['descripcion']); ?></td>
	</tr>


	<tr>
		<td align="center">Cantidad</td>
		<td width="130" align="left"><input type="text" name="cantidad_modif"" id="cantidad_modif" value="<?php  if (isset($_POST['cantidad_modif"'])) {
		    echo floatval($_POST['cantidad_modif"']);
		} else {
		    echo floatval($rs->fields['cantidad']);
		}?>" placeholder="cantidad"  /></td>
	</tr>


	<tr>
		<td align="center">Precio Unitario</td>
		<td width="130" align="left"><input type="text" name="costo_modif"" id="costo_modif" value="<?php  if (isset($_POST['costo_modif"'])) {
		    echo floatval($_POST['costo_modif"']);
		} else {
		    echo floatval($rs->fields['costo']);
		}?>" placeholder="precio unitario"  /></td>
	</tr>


  </tbody>
</table>
<br />
<p align="center">
  <input type="button" name="button" id="button" value="Registrar" onMouseUp="registrar_cambio_cant(<?php echo $idregcc; ?>);" />
  <input type="hidden" name="MM_update" value="form1" />
</p>
<br />
</form><br />