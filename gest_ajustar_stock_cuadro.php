 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");


if (intval($_POST['idajuste']) > 0) {
    $idajuste = intval($_POST['idajuste']);
}
if ($idajuste == 0) {
    echo "- No se envio la tanda de ajuste.";
    exit;
    exit;
}

//Buscamos tanda activa
$buscar = "select * 
    from gest_depositos_ajustes_stock
    where 
    estado = 'A'
    and idajuste = $idajuste
    ";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//$idajuste=intval($rstanda->fields['idajuste']);
$estado = trim($rstanda->fields['estado']);
$origen = intval($rstanda->fields['iddeposito']);
$idpo = $origen;
if ($idpo == 0) {
    echo 'Deposito de origen no encontrado.';
    exit;
}

//$destino=intval($rstanda->fields['destino']);
if ($rstanda->fields['fechaajuste'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fechaajuste']));
}

//Post busqueda :Debe estar aca por el origen y destino ctivos
if ((isset($_POST['codigop']) && ($_POST['codigop']) != '') or (isset($_POST['codigoprod']) && ($_POST['codigoprod']) != '') or (isset($_POST['grupo']) && ($_POST['grupo']) != '') or (isset($_POST['codigobarra']) && ($_POST['codigobarra']) != '')) {


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
        $orden = " order by  descripcion asc Limit 1000";
    }
    // si busca por codigo de barras
    if (isset($_POST['codigobarra']) && ($_POST['codigobarra']) != '') {
        $codigobarra = intval($_POST['codigobarra']);
        $add = " and (select barcode from productos where productos.idprod_serial = insumos_lista.idproducto) = $codigobarra ";
        $orden = " order by  descripcion asc Limit 10";
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
                ) as disponible
    from insumos_lista 
    where 
    idinsumo is not null
    and insumos_lista.estado = 'A'
    and hab_invent = 1
    and idinsumo not in (select idinsumo from tmp_ajuste where idajuste = $idajuste)
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
            <table width="419" class="tablalinda2">
                <tr>
                    <td width="50" height="26" align="center" bgcolor="#FFD7AC"><strong>Codigo</strong></td>
                    <td width="139" align="center" bgcolor="#FFD7AC"><strong>Producto</strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong>Disponible Tanda</strong></td>
                    <td width="60" align="center" bgcolor="#FFD7AC"><strong>Cantidad Ajustar</strong></td>
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
                      <td align="center"><?php echo formatomoneda($rsf->fields['disponible'], 4, 'N')?></td>
                     <td align="center"><input type="text" id="cantimov_<?php echo $rsf->fields['idinsumo']?>" name="cantimov_<?php echo $rsf->fields['idinsumo']?>" style="width:60px;" <?php if ($c == 1) { ?>autofocus="autofocus" class="insu_focus_1"<?php } ?> /></td>
                </tr>
               
              <?php $rsf->MOveNext();
} ?>
              
              
            </table><input type="hidden" name="tp" id="tp" value="1">
            <input type="hidden" name="idajuste" id="idajuste" value="<?php echo $idajuste; ?>">
    <input type="button" value="Agregar" onMouseUp="addtmp_todo();">
            </form>
    </div>
