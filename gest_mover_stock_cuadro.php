 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "106";
require_once("includes/rsusuario.php");



//Buscamos tanda activa
$buscar = "select * from gest_transferencias where idempresa=$idempresa and estado=1 and generado_por=$idusu";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idtanda = intval($rstanda->fields['idtanda']);
$estado = intval($rstanda->fields['estado']);
$origen = intval($rstanda->fields['origen']);
$idpo = $origen;
$destino = intval($rstanda->fields['destino']);
if ($rstanda->fields['fecha_transferencia'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fecha_transferencia']));
}

//Post busqueda :Debe estar aca por el origen y destino ctivos
if ((isset($_POST['codigop']) && ($_POST['codigop']) != '') or (isset($_POST['codigoprod']) && ($_POST['codigoprod']) != '') or (isset($_POST['barcode']) && ($_POST['barcode']) != '') or (isset($_POST['grupo']) && ($_POST['grupo']) != '')) {


    // si busca por nombre
    $codpro = antisqlinyeccion($_POST['codigop'], 'text');
    $codigosolo = str_replace("'", "", $codpro);
    if ($codpro != 'NULL') {
        $add = " and descripcion like '%$codigosolo%'";
        $len = strlen($codigosolo);
        $orden = "
        order by 
        CASE WHEN
            substring(descripcion from 1 for $len) = '$codigosolo'
        THEN
            0
        ELSE
            1
        END asc, 
        descripcion asc
        Limit 100
        ";

    }

    // si busca por codigo
    if (isset($_POST['codigoprod']) && ($_POST['codigoprod']) != '') {
        $codinsumo = intval($_POST['codigoprod']);
        $add = " and idinsumo = $codinsumo";
        $orden = " order by  descripcion asc Limit 1";
    }

    // si busca por grupo
    if (isset($_POST['grupo']) && ($_POST['grupo']) != '') {
        $grupo = intval($_POST['grupo']);
        $add = " and idgrupoinsu = $grupo ";
        $orden = " order by  descripcion asc Limit 100000";
    }
    // si busca por barcode
    $codpro = antisqlinyeccion($_POST['barcode'], 'text');
    $codigosolo = str_replace("'", "", $codpro);
    if ($codpro != 'NULL') {
        $add = " and idproducto=(select idprod_serial from productos where barcode=$codpro)";
        $len = strlen($codigosolo);
        $orden = "
        order by
        descripcion asc
        Limit 100
        ";

    }


    //Debemos mostrar los productos existentes en dicho deposito (origen) para permitir moverlos
    $buscar = "
    select *, 
                (
                Select sum(disponible) as disponible 
                from gest_depositos_stock_gral
                 where 
                iddeposito=$idpo 
                and gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
                and idempresa=$idempresa
                ) as disponible,
                (select nombre from medidas where id_medida = insumos_lista.idmedida
                ) as medida
    from insumos_lista 
    where 
    idinsumo is not null
    and idinsumo not in (select idproducto from tmp_transfer where idtanda = $idtanda)
    and insumos_lista.hab_invent = 1
    and insumos_lista.estado = 'A'
    $add
    $orden
    ";
    //echo $buscar;
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tprod = $rsf->RecordCount();


} else {
    echo "<br />No se encontraron resultados para la busqueda.<br /><br />";
    exit;
}





?><div align="center"><form id="tras_insumo" enctype="application/json">
            <table width="700" class="tablalinda2">
                <tr>
                    <td width="50" height="26" align="center" bgcolor="#FFD7AC"><strong>Codigo</strong></td>
                    <td width="139" align="center" bgcolor="#FFD7AC"><strong>Producto</strong></td>
                    <td width="139" align="center" bgcolor="#FFD7AC"><strong>Medida</strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong>Disponible Tanda</strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong>Cantidad Mover</strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong>Lote</strong></td>
                    <td width="150" align="center" bgcolor="#FFD7AC"><strong>Vencimiento</strong></td>
              </tr>
                <?php
                    $c = 0;
while (!$rsf->EOF) {
    $c++;
    $dp = $rsf->fields['disponible'];
    ?>
                <tr id="ag_<?php $rsf->fields['idinsumo']; ?>">    
                     <td height="26" align="center"><?php echo $rsf->fields['idinsumo']?></td>
                      <td align="left"><?php echo capitalizar(antixss($rsf->fields['descripcion']));?></td>
                    <td align="left"><?php echo capitalizar(antixss($rsf->fields['medida']));?></td>
                      <td align="center"><?php echo formatomoneda($rsf->fields['disponible'], 4, 'N')?></td>
                     <td align="center"><input type="text" id="cantimov_<?php echo $rsf->fields['idinsumo']?>" name="cantimov_<?php echo $rsf->fields['idinsumo']?>" style="width:60px;" /></td>
                  <td width="60" align="center" bgcolor="#FFD7AC"><strong><input type="text" id="lotemov_<?php echo $rsf->fields['idinsumo']?>" name="lotemov_<?php echo $rsf->fields['idinsumo']?>" style="width:60px;" /></strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong><input type="date" id="vtomov_<?php echo $rsf->fields['idinsumo']?>" name="vtomov_<?php echo $rsf->fields['idinsumo']?>" style="width:150px;" /></strong></td>
                </tr>
               
              <?php $rsf->MOveNext();
} ?>
              
              
            </table><input type="hidden" name="tp" id="tp" value="1"><input type="button" value="Agregar" onMouseUp="addtmp_todo();">
            </form>
    </div>
