<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");
$continua = 'S';
$fp = antisqlinyeccion($_POST['idc'], 'text');
if ($fp != 'NULL') {
    $vfp = str_replace("'", "", $fp);
    $len = strlen($vfp);
    //Buscamos como insumo
    $buscar = "
	Select * 
	from insumos_lista 
	where 
	descripcion like '%$vfp%' 
	and idempresa = $idempresa 
	and estado = 'A'
	and hab_compra = 1
	order by 
	CASE WHEN
		substring(descripcion from 1 for $len) = '$vfp'
	THEN
		0
	ELSE
		1
	END asc, 
	descripcion asc
	Limit 100
	";
    $rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $descripcion = trim($rsprod->fields['descripcion']);
    if ($descripcion == '') {
        $continua = 'N';
    }

} else {

    $buscar = "Select * from insumos_lista where idempresa = $idempresa and estado = 'A' and hab_compra = 1 order by descripcion asc ";
    $rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

}
?> 
<br />
<select size="8" style="width:95%" onChange="este(this.value)">
    	<?php while (!$rsprod->EOF) {?>
    		<option value="<?php echo $rsprod->fields['idinsumo']?>"><?php echo $rsprod->fields['descripcion']?></option>
   		 <?php $rsprod->MoveNext();
    	}?>
</select>