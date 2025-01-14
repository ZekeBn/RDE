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
$tanda = $idtanda;
$estado = intval($rstanda->fields['estado']);
$origen = intval($rstanda->fields['origen']);
$destino = intval($rstanda->fields['destino']);
if ($rstanda->fields['fecha_transferencia'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fecha_transferencia']));
}

// recibe parametros
$tp = intval($_POST['tp']);
//$cantidad=floatval($_POST['ca']);
//$tanda=intval($_POST['idta']);
//print_r($_POST);exit;
// Array ( [cantimov_230] => 23 [cantimov_231] => 5 [cantimov_148] => 4 [cantimov_232] => [cantimov_436] => [cantimov_563] => [cantimov_505] => [tp] => 1 )

//Array ( [cantimov_84] => 1 [lotemov_84] => 8 [vtomov_84] => 2020-01-01 [tp] => 1 )

// agregar
if ($tp == 1) {

    // valida que la tanda este abierta
    $consulta = "SELECT * FROM gest_transferencias where idtanda = $tanda and idempresa = $idempresa and estado = 1";
    $rst = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = $rst->fields['origen'];
    if (intval($rst->fields['idtanda']) == 0) {
        echo "<br /><br /><span style=\"color:#F00;\"> Error! Tanda inexistente o ya cerrada.</span>";
        exit;
    }
    // busca en preferencias si quiere validar o no el disponible de stock
    $consulta = "
    SELECT     traslado_nostock FROM preferencias where idempresa = $idempresa
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rspref->fields['traslado_nostock'] == 2) {
        $valida_stock = "S";
    } else {
        $valida_stock = "N";
    }

    // recorre y mete en un array
    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 8) == 'cantimov') {
            $idinsumo = intval(str_replace("cantimov_", "", $key));
            if ($idinsumo > 0) {
                $insumos[$idinsumo]['idinsumo'] = $idinsumo;
                $insumos[$idinsumo]['cantidad'] = $value;
            }
        }
        if (substr($key, 0, 7) == 'lotemov') {
            $idinsumo = intval(str_replace("lotemov_", "", $key));
            if ($idinsumo > 0) {
                $insumos[$idinsumo]['idinsumo'] = $idinsumo;
                $insumos[$idinsumo]['lote'] = $value;
            }
        }
        if (substr($key, 0, 6) == 'vtomov') {
            $idinsumo = intval(str_replace("vtomov_", "", $key));
            if ($idinsumo > 0) {
                $insumos[$idinsumo]['idinsumo'] = $idinsumo;
                $insumos[$idinsumo]['vto'] = $value;
            }
        }

    }

    //print_r($insumos);
    //exit;
    // recorre los datos recibidos
    $paso = 0;
    foreach ($insumos as $insumo) {
        if ($paso == 4) {
            $paso = 0;
        }

        // obtiene idinsumo y cantidad
        /*$idinsumo=intval(str_replace("cantimov_","",$key));
        $cantidad=$value;
        $lote=intval(str_replace("cantimov_","",$key));
        $vto=intval(str_replace("cantimov_","",$key));
        */
        //Array ( [idinsumo] => 82 [cantidad] => 1 [lote] => 2 [vto] => 3 )
        //print_r($insumo);
        //exit;
        $idinsumo = intval($insumo['idinsumo']);
        $cantidad = floatval($insumo['cantidad']);
        $lote = antisqlinyeccion($insumo['lote'], "int");
        if (trim($insumo['vto']) != '') {
            $vto = antisqlinyeccion(date("Y-m-d", strtotime($insumo['vto'])), "text");
            if ($insumo['vto'] != date("Y-m-d", strtotime($insumo['vto']))) {
                echo "Fecha de vencimiento en formato no valido.";
                exit;
            }
        } else {
            $vto = antisqlinyeccion("", "text");
        }



        //echo $key[1];exit;
        /*if ($paso==1){
            $lote=intval($value);
            $update="Update tmp_transfer set lote=$lote where idproducto = $insumo and idtanda = $tanda ";
            $conexion->Execute($update) or die(errorpg($conexion,$update));
        }
        if ($paso==2){
            $vto=antisqlinyeccion($value,'date');
            $update="Update tmp_transfer set vto=$vto where idproducto = $insumo and idtanda = $tanda ";
            $conexion->Execute($update) or die(errorpg($conexion,$update));
        }*/

        // si la cantidad es mayor a 0 y es un codigo de insumo lo que viene del form
        if ($cantidad > 0 && $idinsumo > 0) {
            // busca que exista el insumo
            $insumo = antisqlinyeccion($idinsumo, 'text');
            $buscar = "Select descripcion from insumos_lista where idinsumo=$idinsumo and idempresa = $idempresa";
            $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $describe = antisqlinyeccion($rsin->fields['descripcion'], 'text');


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

            // valida si es unitario
            $consulta = "
            select idmedida
            from insumos_lista 
            where 
            idinsumo = $insumo
            ";
            $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idmedida = intval($rsins->fields['idmedida']);
            if ($idmedida == 4) {
                if (floatval($cantidad) != intval($cantidad)) {
                    echo "<br /><br /><span style=\"color:#F00;\"> Error! el articulo: ".$rsin->fields['descripcion']." es unitario no puede tener decimales.</span>";
                    exit;
                }
            }


            // valida que ya no exista en la tabla temporal para evitar dupolicidad que pueda saltar la validacion de disponibilidad de stock aqui y en el otro script
            $consulta = "select * from tmp_transfer where idproducto = $insumo and idtanda = $tanda ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si ya existe
            if (intval($rsex->fields['idproducto']) > 0) {
                echo "<br /><br /><span style=\"color:#F00;\"> Error! ya ingresaste este insumo, borralo antes de volver a ingresar.</span>";
                exit;
                // si no existe inserta
            } else {
                $insertar = "Insert into tmp_transfer
                (idtanda,idproducto,descripcion,cantidad,lote,vto)
                values
                ($tanda,$insumo,$describe,$cantidad,$lote,$vto)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            }

        } // if($cantidad > 0){
        $paso = $paso + 1;
    } //foreach($_POST as $key => $value){

} //if ($tp==1){

// eliminar
if ($tp == 3) {
    //chau
    $cual = intval($_POST['cual']);
    $delete = "delete from tmp_transfer where unicaser=$cual";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

}

//Mostramos lista nueva
$buscar = "Select tmp_transfer.* ,
    (select nombre from medidas where id_medida = insumos_lista.idmedida
                ) as medida
    from tmp_transfer
    inner join insumos_lista on insumos_lista.idinsumo =tmp_transfer.idproducto
    where 
    tmp_transfer.idtanda=$tanda 
    order by tmp_transfer.descripcion asc";
//echo $buscar;
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tdata = $rsl->RecordCount();
?>
<?php if ($tdata > 0) {?>
<table width="464" height="104">
    <tr>
      <td height="33" colspan="5" align="center" bgcolor="#EFEFEF"><strong>Productos a ser Transferidos - Tanda <?php echo formatomoneda($tanda);?></strong></td>
  </tr>
    <tr>
        <td width="70" align="center" bgcolor="#EFEFEF"><strong>Id Insumo</strong></td>
        <td width="211" align="center" bgcolor="#EFEFEF"><strong>Producto</strong></td>
        <td width="88" align="center" bgcolor="#EFEFEF"><strong>Cantidad Transferir</strong></td>
        <td width="211" align="center" bgcolor="#EFEFEF"><strong>Medida</strong></td>
        <td width="50" bgcolor="#EFEFEF"></td>
        
    </tr>
    <?php while (!$rsl->EOF) {
        $cantacum += $rsl->fields['cantidad'];
        ?>
   <tr>
        <td><?php echo $rsl->fields['idproducto']?></td>
        <td><?php echo $rsl->fields['descripcion']?></td>
        <td align="center"><?php echo formatomoneda($rsl->fields['cantidad'], 4, 'N'); ?></td>
       <td><?php echo $rsl->fields['medida']?></td>
        <td align="center"><a href="javascript:void(0);" onClick="chau(<?php echo $rsl->fields['unicaser']?>)">[X]</a></td>
    </tr>
    <?php $rsl->MoveNext();
    }?>
   <tr>
        <td bgcolor="#F8FFCC"><strong>Total</strong></td>
        <td bgcolor="#F8FFCC"></td>
        <td align="center" bgcolor="#F8FFCC"><?php echo  formatomoneda($cantacum, 4, 'N'); ?></td>
       <td bgcolor="#F8FFCC"></td>
     <td align="center" bgcolor="#F8FFCC"></td>
  </tr>
</table>
<?php } ?>
