<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

$idinsumo = intval($_POST['insu']);
$codbar = antisqlinyeccion(trim($_POST['cbar']), "text");
//print_r($_POST);


if (trim($_POST['cbar']) != '') {
    // busca dando prioridad a solo conversion
    $buscar = "
	Select insumos_lista.descripcion, medidas.nombre , insumos_lista.idinsumo
	from insumos_lista 
	inner join medidas on insumos_lista.idmedida = medidas.id_medida
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	where 
	productos.barcode = $codbar
	and insumos_lista.estado = 'A'
	and insumos_lista.hab_compra = 1
	and insumos_lista.idempresa = $idempresa
	order by solo_conversion desc
	";
    $rscbar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idinsumo = intval($rscbar->fields['idinsumo']);
    //echo $buscar;
    //exit;
}

if ($idinsumo > 0) {
    $buscar = "
	Select insumos_lista.descripcion, medidas.nombre from insumos_lista 
	inner join medidas on insumos_lista.idmedida = medidas.id_medida
	where 
	idinsumo=$idinsumo
	and insumos_lista.estado = 'A'
	and hab_compra = 1
	and idempresa = $idempresa";
    $des = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    $producto = htmlentities(trim($des->fields['descripcion']));
    $medida = trim($des->fields['nombre']);

    $buscar = "Select * from insumos_lista where idempresa = $idempresa and insumos_lista.estado = 'A' and hab_compra = 1 order by descripcion asc limit 100 ";
    $rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $listo = 1;
    //exit;

} else {
    $buscar = "Select * from insumos_lista where idempresa=$idempresa and estado = 'A' and hab_compra = 1  order by descripcion asc ";
    $rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
}


?>

 <div align="center">
					
				<span class="resaltaditomenor">Selecci&oacute;n de Productos</span>

			</div><br />
<?php if (trim($errores) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
<div>
	<div style="border:1px solid #000000; float:left; width:600px;">
    	<table width="600" border="1">
			<tr >
					<td width="150" height="36" align="right"><strong>Nombre Producto</strong></td>
					<td><input type="text" style="width:48%; height:40px; " placeholder="Nombre del producto" name="nombre" id="nombre" onKeyUp="listar(this.value);" value="<?php echo $producto?>" />
				    <input type="text" style="width:48%; height:40px; " placeholder="Codigo" name="codigo" id="codigo"  value="<?php if ($idinsumo > 0) {
				        echo $idinsumo;
				    } ?>" onChange="este(this.value)"  /></td>
			<tr>
			  <td height="34" align="right" valign="middle"><strong>Cod Barra:</strong></td>
			  <td><label for="textfield"></label>
		      <input type="text" name="codbar" id="codbar" style="width:90%; height:40px;" onkeypress="buscar_codbar(event);" /></td>
		  </tr>
<?php if ($idinsumo > 0) { ?>	
			<tr>
					<td height="34" align="right" valign="middle"><strong>Cantidad Compra</strong>:</td>
				  <td><input type="text" placeholder="Ingrese cantidad adquirida producto" style="width:100px;" name="cantidad" id="cantidad"/>&nbsp;&nbsp;<strong><?php echo $medida; ?></strong></td>
			</tr>	
            <tr>
              <td height="37" align="right" valign="middle"><strong>Precio Compra:
                <br />
              </strong></td>
              <td height="37" align="left"><strong>
                <input type="text" placeholder="Ingrese costo del producto" name="costobase" id="costobase" style="width:100px;"/>
              &nbsp;x <?php echo $medida; ?></strong></td>
            </tr>
<?php } ?>
            
            
            <tr>
            	<td height="37" colspan="3" align="center">
                <input type="button" id="agp" onclick="agregatmp()" value="Agregar Producto"  />
                <input type="hidden" name="insuag" id="insuag" value="<?php echo $idinsumo ?>" />
                </td>
            
          </tr>
       </table>
	
    
    </div>
    <div style="border:1px solid #000000; float:left; width:420px; margin-left:10px;" id="listaprodudiv">
   <select size="8" style="width:98%" onChange="este(this.value)">
    	<?php while (!$rsprod->EOF) {?>
    		<option value="<?php echo $rsprod->fields['idinsumo']?>"><?php echo $rsprod->fields['descripcion']?></option>
   		 <?php $rsprod->MoveNext();
    	}?>
</select>
<input type="hidden" name="existep" id="existep" value="<?php echo $listo ?>" />
    </div>


</div>