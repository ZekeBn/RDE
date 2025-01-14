 <?php
/*-----------------------------------------
01/11/2023
Se contempla la bandera de descuento_aplicado
 en mesas_atc
07/02/2024:
Se agrega LOG de pin de acciones (mesas_acciones_log)

------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
/*
$idventatmp=intval($_POST['idtmp']);
$consulta="
select * from tmp_ventares where idventatmp = $idventatmp
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idventatmp=intval($rs->fields['idventatmp']);
$idtmpventares_cab=intval($rs->fields['idtmpventares_cab']);
$precio=floatval($rs->fields['precio']);
$cantidad=floatval($rs->fields['cantidad']);
if($idventatmp == 0){
    echo "Pedido inexistente!";
    exit;
}*/

//$idtmp=intval($_POST['idtmp']);
//$idcab=intval($_POST['idcab']);
$idmesa = intval($_POST['idmesa']);
$idatc = intval($_POST['idatc']);
$codigo = md5(trim($_POST['cod_autorizacion']));



$buscar = "Select obliga_motivos,usar_pin as obligapin from preferencias_caja limit 1";
$rsprefca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$obligar_motivos = trim($rsprefca->fields['obliga_motivos']);
$obligapin = trim($rsprefca->fields['obligapin']);
$consulta = "
select sum(tmp_ventares.precio) as precio, sum(tmp_ventares.cantidad) as cantidad
from tmp_ventares 
inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
where
tmp_ventares_cab.idatc = $idatc
and tmp_ventares.borrado = 'N'
and tmp_ventares.borrado_mozo = 'N'
and tmp_ventares_cab.estado <> 6
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$subtotal_sindesc = $rs->fields['precio'] * $rs->fields['cantidad'];

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
    $usu_borra_cod = 0;
    // recibe parametros

    //$descuento=antisqlinyeccion($_POST['descuento'],"float");
    $descuento = antisqlinyeccion($_POST['descuento_porc'], "float");


    // validaciones basicas
    $valido = "S";
    $errores = "";

    if (floatval($_POST['descuento_porc']) < 0) {
        $valido = "N";
        $errores .= " - El campo descuento no puede ser negativo.<br />";
    }
    if (floatval($_POST['descuento_porc']) > 100) {
        $valido = "N";
        $errores .= " - El campo descuento no puede ser mayor a 100%.<br />";
    }
    if ($obligapin == 'S') {
        if ($codigo == '') {
            $valido = "N";
            $errores .= "Debe indicar el codigo de acciones para continuar (obligatorio).".$saltolinea;
        } else {
            //validar el codigo
            $consulta = "
            select *
            from codigos_borraped 
            where 
            codigo = '$codigo'
            and estado = 1
            and registrado_por in (select idusu from usuarios where estado = 1)
            limit 1
            ";
            $rscod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if (intval($rscod->fields['idusuario']) == 0) {
                $valido = 'N';
                $errores .= '- Codigo de autorizacion Incorrecto.'.$saltolinea;
                $cod_accion_error = 2;
            } else {
                $usu_borra_cod = intval($rscod->fields['idusuario']);
                $codigo_maestro_ok = trim($rscod->fields['super']);//o es si o no,si es no
            }

        }


    }

    $consulta = "
    select tmp_ventares.precio, tmp_ventares.cantidad, tmp_ventares.idventatmp
    from tmp_ventares 
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
    where
    tmp_ventares_cab.idatc = $idatc
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.borrado_mozo = 'N'
    and tmp_ventares_cab.estado <> 6
    and cortesia is null
    and tmp_ventares.idtmpventares_cab is not null
    ";
    //echo $consulta;exit;
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





    // validar que el descuento no supere el total de la venta pero puede ser igual si es 100% descuento
    /*$subtotal_sindesc=$rs->fields['precio']*$rs->fields['cantidad'];
    if(floatval($_POST['descuento']) > $subtotal_sindesc){
        $valido="N";
        $errores.=" - El campo descuento no puede ser superior al precio*cantidad de este producto.<br />";
    }*/


    // si todo es correcto actualiza
    if ($valido == "S") {

        while (!$rsdet->EOF) {

            $idventatmp = $rsdet->fields['idventatmp'];
            $subtotal_sindesc = $rsdet->fields['precio'] * $rsdet->fields['cantidad'];
            //echo $subtotal_sindesc;exit;
            $descuento_porc = floatval($_POST['descuento_porc']) / 100;
            //echo $descuento_porc;exit;
            $descuento_monto = round($subtotal_sindesc * $descuento_porc, 0);

            $consulta = "
            update tmp_ventares
            set
                descuento=$descuento_monto
            where
                idventatmp = $idventatmp
            ";
            //echo $consulta;exit;
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
            update tmp_ventares
            set
                subtotal=((cantidad*precio)-descuento)
            where
                idventatmp = $idventatmp
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsdet->MoveNext();
        }

        // actualiza cabecera
        $consulta = "
        update tmp_ventares_cab 
        set 
        monto = (
                    COALESCE
                    (
                        (
                            select sum(subtotal) as total_monto
                            from tmp_ventares
                            where
                            tmp_ventares.idempresa = $idempresa
                            and tmp_ventares.idsucursal = $idsucursal
                            and tmp_ventares.borrado = 'N'
                            and tmp_ventares.borrado_mozo = 'N'
                            and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                        )
                    ,0)
                    +
                    COALESCE
                    (
                        (
                            SELECT sum(precio_adicional) as montototalagregados
                            FROM 
                            tmp_ventares_agregado
                            where
                            idventatmp in 
                            (
                                select idventatmp
                                from tmp_ventares
                                where
                                tmp_ventares.idempresa = $idempresa
                                and tmp_ventares.idsucursal = $idsucursal
                                and tmp_ventares.borrado = 'N'
                                and tmp_ventares.borrado_mozo = 'N'
                                and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                            )
                        )
                    ,0)
                )
        WHERE
        tmp_ventares_cab.idatc = $idatc    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //porultimo el ATC
        if ($descuento > 0) {
            $aplicado = 'S';
        } else {
            $aplicado = 'N';
        }
        $update = "update mesas_atc set descuento_aplicado='$aplicado' where idatc=$idatc ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));


        //Registrar LOG

        $insertar = "Insert into mesas_acciones_log (fechahora,id_usuario,id_usu_pin,accion,clase,idatc,idmesa,porcen,monto) values ('$ahora',$idusu,$usu_borra_cod,'DESC PROD ALL',' DES',$idatc,$idmesa,$descuento_porc,$descuento_monto)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));




        echo '
        <div class="alert alert-info alert-dismissible fade in" role="alert">
        
        <strong>Descuento aplicado correctamente en todo el carrito!
        </div>
        ';
        exit;

    } else {

        echo '
        <div class="alert alert-danger alert-dismissible fade in" role="alert">
        
        <strong>Errores:</strong><br />'.$errores.'
        </div>
        ';
        exit;

    }

}
$buscar = "Select * from mesas_atc where idatc=$idatc";
$gt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$aplicado_dip = trim($gt->fields['diplomatico']);
$aplicado_desc = trim($gt->fields['descuento_aplicado']);
if ($aplicado_desc == 'S') {
    //hay que ver si un descuento no esta aplicado para permitir o bloquear
    $ena = " disabled='disabled' style='background-color:grey;' ";
    $ena2 = "";
    $mensaje = "<span style='color:red;'>Descuento(s) activo(s) para la mesa. </span>";

} else {
    $ena = "";
    $ena2 = " disabled='disabled' style='background-color:grey;' ";
    $mensaje = "<span style='color:red;'>Descuento(s) NO activo(s) para la mesa.</span>";
}

?>
<strong>Aplicar Descuento %</strong><br />
<div class="alert alert-warning alert-dismissible fade in" role="alert" style="text-align:center;">
<strong>Nota:</strong>Aplica el descuento (%) a todos los articulos del carrito en curso. Para revertir,colocar CERO(0) y aplicar.<br />Si la mesa es para un diplomatico, primero debe aplicar diplomatico y por ultimo, el presente descuento.
    Estado actual: &nbsp; <?php echo $mensaje ?>
</div>
<?php if (trim($errores) != "") { ?>
    <div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="">
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tbody>

    <tr>
      <td align="center">% descuento</td>
      <td align="left"><input type="text" name="descuento_porcn" id="descuento_porcn" value="" placeholder="% Desc"   /></td>
      </tr>
    <!--<tr>
      <td align="center">Subtotal</td>
      <td align="left"><input type="text" name="subtotal" id="subtotal" value="<?php if (isset($_POST['subtotal'])) {
          echo round($_POST['subtotal']);
      } else {
          echo round($rs->fields['subtotal']);
      } ?>" readonly="readonly"  style="background-color:#CCC; border-color:#CCC;" /></td>
      </tr>-->

  </tbody>
</table>
</div>

<div class="clearfix"></div>
<input name="precio_d" id="precio_d" type="hidden" value="<?php echo $precio; ?>" />
<input name="cantidad_d" id="cantidad_d" type="hidden" value="<?php echo $cantidad; ?>" />
<div class="col-md-6 col-sm-6 form-group">
    Indique su código de  autorización
    <input type="password" id="cod_autoriza" placeholder="Codigo Autorizacion" class="form-control">
    
</div>
    <div class="form-group">
        <div class="col-md-4 col-sm-4 col-xs-12 col-md-offset-4">
       <button type="button" class="btn btn-primary" onMouseUp="registra_descuento_atc(<?php echo $idmesa ?>,<?php echo $idatc ?>,'<?php echo $obligapin; ?>');" ><span class="fa fa-check-square-o"></span> Registrar Descuento</button>

        </div>
    </div>
<br />
</form>
<br /><br />
