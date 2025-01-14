 <?php
/*---------------------------------------------

01/08/2021

--------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
//
$valor = antisqlinyeccion($_POST['valor'], 'text');

//print_r($_POST);
if (($valor != null)) {
    $valor = str_replace("'", "", $valor);
    //$buscar="Select descripcion,idprod_serial from productos where descripcion like ('%$valor%')";
    //echo $buscar;
    //$rv=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    if ($_POST['texto'] != '') {
        $texto = antisqlinyeccion($_POST['texto'], 'texto');
        $texto = str_replace("'", "", $texto);
        $add = " and descripcion like('%$texto%') ";
    }
    if (intval($idcategoria) > 0) {
        $add .= " and  idcategoria=$idcategoria ";
    } else {
        //POST
        if (intval($_POST['idcategoria']) > 0) {
            $idcategoria = intval($_POST['idcategoria']);
            $add .= " and idcategoria=$idcategoria ";
        }
    }
    //echo $idcategoria;exit;
    if ($idsubcategoria > 0) {
        $add .= " and idsubcate=$idsubcategoria ";
    } else {
        //POST
        if (intval($_POST['idsubcate']) > 0) {
            $idsubcategoria = intval($_POST['idsubcate']);
            $add .= " and idsubcate=$idsubcategoria ";
        }
    }

    if ($add == '') {
        $limite = " limit 100";
    }


    $buscar = "
        Select * , productos_sucursales.precio as p1
        from productos 
        inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
        where 
        productos.borrado = 'N' 
                    and productos_sucursales.idsucursal = $idsucursal 
                    and productos_sucursales.idempresa = $idempresa
                    and productos_sucursales.activo_suc = 1
        $add 
        order by productos.descripcion asc 
        $limite";
    $rsproducto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    $treg = $rsproducto->RecordCount();
    if ($treg > 0) {
        echo "hay reg";

        ?>
<div id="errorhtml" style="display:none;">
        <div class="alert alert-danger alert-dismissible " role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
            <span id="errortxt"></span>
        </div>
</div>
</div>
    <table class="table table">
        <thead>
            <tr>
                <th>Id </th>
                <th>Descripci&oacute;n / Producto</th>
                <th>Cantidad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php while (!$rsproducto->EOF) {
            $idtipoproducto = intval($rsproducto->fields['idtipoproducto']);
            if ($idtipoproducto == 11) {

            } else {
                $enla = "";
                if ($rsproducto->fields['idtipoproducto'] == 1) { // producto
                    $enla = "onClick=\"apretarauto('".$rsproducto->fields['idprod_serial']."',0,0,$idmesa);\" ";
                } elseif ($rsproducto->fields['idtipoproducto'] == 2) {  // combo
                    $enla = "onClick=\"apretar_combo('".$rsproducto->fields['idprod_serial']."');\" ";
                } elseif ($rsproducto->fields['idtipoproducto'] == 3) {  // combinado simple NO USAR MAS
                    $enla = "onClick=\"apretar_combinado('".$rsproducto->fields['idprod_serial']."',$idmesa);\" ";
                } elseif ($rsproducto->fields['idtipoproducto'] == 4) {  // combinado extendido
                    $enla = "onClick=\"apretar_combinado('".$rsproducto->fields['idprod_serial']."',$idmesa);\" ";
                } else { // por defecto producto
                    $enla = "onClick=\"apretarauto('".$rsproducto->fields['idprod_serial']."',0,0,$idmesa);\" ";
                }
                $idtemporal = $rsproducto->fields['idprod_serial'];

            }


            ?>
            
                <tr>
                    <th><?php echo $rsproducto->fields['idprod_serial'] ?></th>
                    <th><?php echo $rsproducto->fields['descripcion'] ?></th>
                    <th><input type="text" value="" id="canti_<?php echo $rsproducto->fields['idprod_serial'] ?>" name="canti_<?php echo $rsproducto->fields['idprod_serial'] ?>" onkeyup="verificar(<?php echo $rsproducto->fields['idprod_serial']; ?>,event);" /></th>
                    <th>
                        <?php if ($idtipoproducto != 11) { ?>
                            <a href="javascript:void(0);" id="enla_<?php echo $rsproducto->fields['idprod_serial']; ?>" <?php echo $enla; ?>><span class="fa fa-edit"></span>&nbsp;[Seleccionar]</a>
                        <?php } else { ?>
                            <a id="" href="javascript:void(0);" onClick="verificacantidad(<?php echo $rsproducto->fields['idprod_serial'] ?>);"><span class="fa fa-gear"></span>&nbsp;[ARMAR]</a>        
                            
                        <?php }  ?>
                    </th>
                </tr>
            
            

            <?php $rsproducto->MoveNext();
        } ?>
            
            
            </tbody>
        </table>
                
<?php }
    } ?>
