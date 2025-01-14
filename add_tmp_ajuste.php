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
//print_r($_REQUEST);

//Buscamos tanda activa
$buscar = "select * 
    from gest_depositos_ajustes_stock
    where 
    estado = 'A'
    and idempresa = $idempresa
    and registrado_por = $idusu
    ";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$idajuste=intval($rstanda->fields['idajuste']);
$estado = trim($rstanda->fields['estado']);
if ($rstanda->fields['fechaajuste'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fechaajuste']));
}

// recibe parametros
$tp = intval($_POST['tp']);
//$cantidad=floatval($_POST['ca']);
//$tanda=intval($_POST['idta']);
//print_r($_POST);
// Array ( [cantimov_230] => 23 [cantimov_231] => 5 [cantimov_148] => 4 [cantimov_232] => [cantimov_436] => [cantimov_563] => [cantimov_505] => [tp] => 1 )

// agregar
if ($tp == 1) {

    // valida que la tanda este abierta
    $consulta = "SELECT * FROM gest_depositos_ajustes_stock where idajuste = $idajuste and idempresa = $idempresa and estado = 'A'";
    $rst = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = $rst->fields['iddeposito'];
    if (intval($rst->fields['idajuste']) == 0) {
        echo "<br /><br /><span style=\"color:#F00;\"> Error! Tanda inexistente o ya cerrada.</span>";
        exit;
    }
    // busca en preferencias si quiere validar o no el disponible de stock
    /*$consulta="
    SELECT     traslado_nostock FROM preferencias where idempresa = $idempresa
    ";
    $rspref=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if($rspref->fields['traslado_nostock'] == 2){
        $valida_stock="S";
    }else{
        $valida_stock="N";
    }*/
    $valida_stock = "N";


    // recorre los datos recibidos
    foreach ($_POST as $key => $value) {
        //echo 'nombre:'.$key.'valor:'.$value."<br />";

        // obtiene idinsumo y cantidad
        $idinsumo = intval(str_replace("cantimov_", "", $key));
        $cantidad = $value;

        // si la cantidad es mayor a 0 y es un codigo de insumo lo que viene del form
        if ($cantidad != '' && $idinsumo > 0) {
            // busca que exista el insumo
            $insumo = antisqlinyeccion($idinsumo, 'text');
            $buscar = "Select descripcion from insumos_lista where idinsumo=$idinsumo and idempresa = $idempresa";
            $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $describe = antisqlinyeccion($rsin->fields['descripcion'], 'text');
            if ($cantidad > 0) {
                $tipoajuste = "+";
            } else {
                $tipoajuste = "-";
                $cantidad = $cantidad * -1;
            }


            // busca en stock general
            if ($valida_stock == 'S') {
                $consulta = "
                select sum(disponible) as total_stock 
                from gest_depositos_stock_gral 
                where 
                idproducto = $insumo
                and iddeposito = $iddeposito
                and idempresa = $idempresa
                ";
                $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $disponible = $rsdisp->fields['total_stock'];
                //echo $disponible;
                if ($disponible < $cantidad) {
                    echo "<br /><br /><span style=\"color:#F00;\"> Error! el disponible es menor a la cantidad que quiere transferir de: ".$rsin->fields['descripcion'].".</span><br /><input type=\"button\" value=\"OK\" onmouseup=\"recarga_tmp();\">";
                    exit;
                }
                if ($disponible == 0) {
                    echo "<br /><br /><span style=\"color:#F00;\"> Error! no queda disponible de este insumo: ".$rsin->fields['descripcion'].".</span>";
                    exit;
                }

            }


            // valida que ya no exista en la tabla temporal para evitar dupolicidad que pueda saltar la validacion de disponibilidad de stock aqui y en el otro script
            $consulta = "select * from tmp_ajuste where idinsumo = $insumo and idajuste = $idajuste ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si ya existe
            if (intval($rsex->fields['idinsumo']) > 0) {
                echo "<br /><br /><span style=\"color:#F00;\"> Error! ya ingresaste este insumo, borralo antes de volver a ingresar.</span>";
                exit;
                // si no existe inserta
            } else {
                $insertar = "Insert into tmp_ajuste
                (idajuste,idinsumo,cantidad,tipoajuste)
                values
                ($idajuste,$insumo,$cantidad,'$tipoajuste')";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            }

        } // if($cantidad > 0){

    } //foreach($_POST as $key => $value){

} //if ($tp==1){

// eliminar
if ($tp == 3) {
    //chau
    $cual = intval($_POST['cual']);
    $delete = "delete from tmp_ajuste where unicaser=$cual";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

}

//Mostramos lista nueva
$buscar = "Select tmp_ajuste.*, insumos_lista.descripcion
    from tmp_ajuste 
    inner join insumos_lista on insumos_lista.idinsumo = tmp_ajuste.idinsumo
    where 
    idajuste=$idajuste
    and insumos_lista.idempresa = $idempresa
    ";
//echo $buscar;
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tdata = $rsl->RecordCount();
?>
<?php if ($tdata > 0) {?>
<table width="464" height="104">
    <tr>
      <td height="33" colspan="4" align="center" bgcolor="#EFEFEF"><strong>Productos a ser Ajustados - Tanda <?php echo formatomoneda($idajuste);?></strong></td>
  </tr>
    <tr>
        <td width="70" align="center" bgcolor="#EFEFEF"><strong>Id Insumo</strong></td>
        <td width="211" align="center" bgcolor="#EFEFEF"><strong>Producto</strong></td>
        <td width="88" align="center" bgcolor="#EFEFEF"><strong>Cantidad Ajustar</strong></td>
        <td width="50" bgcolor="#EFEFEF"></td>
        
    </tr>
    <?php while (!$rsl->EOF) {
        $cantacum += $rsl->fields['cantidad'];
        ?>
   <tr>
        <td><?php echo $rsl->fields['idinsumo']?></td>
        <td><?php echo $rsl->fields['descripcion']?></td>
        <td align="center"><?php if ($rsl->fields['tipoajuste'] == '-') {
            echo "-";
        } ?><?php echo formatomoneda($rsl->fields['cantidad'], 4, 'N'); ?></td>
        <td align="center"><a href="javascript:void(0);" onClick="chau(<?php echo $rsl->fields['unicaser']?>)">[X]</a></td>
    </tr>
    <?php $rsl->MoveNext();
    }?>
   <tr>
        <td bgcolor="#F8FFCC"><strong>Total</strong></td>
        <td bgcolor="#F8FFCC"></td>
        <td align="center" bgcolor="#F8FFCC"><?php echo  formatomoneda($cantacum, 0, 'N'); ?></td>
     <td align="center" bgcolor="#F8FFCC"></td>
  </tr>
</table>
<?php } ?>
