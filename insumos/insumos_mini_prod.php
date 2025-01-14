<?php //Mostramos lista de insumos registrados
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "17";
$submodulo = "91";
$dirsup = "S";
require_once("../includes/rsusuario.php");
$add = intval($_POST['ad']);
if ($add > 0) {
    $describe = antisqlinyeccion($_POST['des'], 'text');
    $idmedida = intval($_POST['medi']);
    $grupo = intval($_POST['grupo']);

    $idcategoria = 0;
    $idsubcate = 0;
    $produ = 1;
    $tipoiva = intval($_POST['iva']);



    $buscar = "Select * from insumos_lista where descripcion=$describe and idempresa = $idempresa";

    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsb->fields['idinsumo'] == 0) {
        $buscar = "select max(idinsumo) as mayor from insumos_lista";
        $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsmayor->fields['mayor']) + 1;

        $insertar = "Insert into insumos_lista
		(idinsumo,descripcion,idcategoria,idsubcate,idmedida,produccion,tipoiva,idempresa,idgrupoinsu)
		values
		($mayor,$describe,$idcategoria,$idsubcate,$idmedida,$produ,$tipoiva,$idempresa,$grupo)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        //Insertar
        $insertar = "Insert into ingredientes (idinsumo,estado,idempresa) values ($mayor,1,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


    }

}
//Categorias
$buscar = "Select * from categorias where estado = 1 order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas where estado = 1 order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//grupos
$buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$buscar = "Select idinsumo,insumos_lista.descripcion,medidas.nombre as medida,produccion,tipoiva,(select nombre from grupo_insumos where idgrupoinsu=insumos_lista.idgrupoinsu) as des
from insumos_lista
inner join medidas on medidas.id_medida=insumos_lista.idmedida

where
insumos_lista.idempresa = $idempresa
 order by insumos_lista.descripcion asc";
$rslista = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tlista = $rslista->RecordCount();




?>
 
 <div align="center">
  <table width="279" height="260">
    <tr>
      <td height="48" colspan="3" align="center" bgcolor="#D0D0D0"><strong>Nuevo Insumo</strong></td>
    </tr>
    <tr>
      <td width="91" height="48" align="right" bgcolor="#D0D0D0"><strong>Nombre Insumo</strong></td>
      <td width="10"></td>
      <td width="185"><input name="descripcion" type="text" required="required" id="descripcion" style="width:90%; height:40px;" /></td>
    </tr>
    <tr>
      <td align="right" bgcolor="#D0D0D0"><strong>Medida</strong></td>
      <td></td>
      <td><select name="medidal" required id="medidal" style="width:90%; height:40px;">
        <option value="0" selected="selected">Seleccionar</option>
        <?php while (!$rsmed->EOF) {?>
        <option value="<?php echo $rsmed->fields['id_medida']?>"><?php echo trim($rsmed->fields['nombre']) ?></option>
        <?php $rsmed->MoveNext();
        }?>
      </select></td>
    </tr>
    <tr>
      <td height="42" align="right" bgcolor="#D0D0D0"><strong>Grupo</strong></td>
      <td></td>
      <td><select name="grupo" required id="grupo" style="width:90%; height:40px;">
        <option value="0" selected="selected">Seleccionar</option>
        <?php while (!$gr->EOF) {?>
        <option value="<?php echo $gr->fields['idgrupoinsu']?>"><?php echo trim($gr->fields['nombre']) ?></option>
        <?php $gr->MoveNext();
        }?>
      </select></td>
    </tr>
    <tr>
      <td height="38" align="right" bgcolor="#D0D0D0"><strong>IVA Compra:</strong></td>
      <td></td>
      <td><select name="tipoiva" required id="tipoiva" readonly="readonly" >
        <option value="" selected="selected">Seleccionar</option>
        <option value="0">Excenta</option>
        <option value="5">5%</option>
        <option value="10" selected="selected">10%</option>
      </select></td>
    </tr>
    <tr>
      <td colspan="3" align="center"><input type="button" value="Registrar Insumo" onclick="registra(1)" style="height: 40px;"  /></td>
    </tr>
   </table>

</div>