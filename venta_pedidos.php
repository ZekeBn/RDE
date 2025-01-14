 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "259";
require_once("includes/rsusuario.php");
require_once("includes/funciones_stock.php");
/*---------------------------MINI FUNCIONES---------------------------*/
require_once('tarjetas/mini_funciones_nuevo.php');
//require_once('mini_funciones.php');
/*---------------------------MINI FUNCIONES---------------------------*/




//Comprobar caja abierta
$array_entrada = [];

$array_entrada['idcajero'] = "$idusu";
$array_entrada['sucursalid'] = "$idsucursal";
$comprobarcaja = comprobar_caja($array_entrada);
if ($comprobarcaja == 0) {
    header("location: gest_administrar_caja.php");
    exit;
}

//Comprobar existencia de deposito de ventas

$consulta = "
SELECT * 
FROM gest_depositos
where
tiposala = 2
and idempresa = $idempresa
and idsucursal = $idsucursal
and estado = 1
";
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
if ($iddeposito == 0) {
    $errores .= "No existe deposito de ventas asignado a la sucursal actual.";
    $valido = "N";
}

/*------------------------PREFERENCIAS Y CONTROL DE FACTURAS-----------------*/
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$script = trim($rspref->fields['script_factura']);
$usa_descuento = $rspref->fields['usa_descuento'];
$bloquear = intval($rspref->fields['autorizar']);
$controlfactura = intval($rspref->fields['controlafactura']);
$autoimpresor = trim($rspref->fields['auto_impresor']);


if ($controlfactura == 1) {

    $ano = date("Y");
    // busca si existe algun registro
    $buscar = "
    Select max(numfac) as mayor 
    from lastcomprobantes 
    where 
    idsuc=$factura_suc 
    and pe=$factura_pexp 
    and idempresa=$idempresa 
    order by ano desc 
    limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    if ($maxnfac <= 1) {

        $consulta = "
                    INSERT INTO lastcomprobantes
                    (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
                    numhoja, hojalevante, idempresa) 
                    VALUES
                    ($factura_suc, 0, $maxnfac, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
                    NULL, 0, '', $idempresa)
                    ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}

$parte1 = intval($factura_suc);
$parte2 = intval($factura_pexp);
if ($parte1 == 0 or $parte2 == 0) {
    $parte1f = '001';
    $parte2f = '001';
} else {
    $parte1f = agregacero($parte1, 3);
    $parte2f = agregacero($parte2, 3);
}




$eliminar = intval($_REQUEST['el']);
if ($eliminar > 0) {



    $delete = "delete from carrito_tarjeta where idtarjeta=$eliminar and idusu=$idusu";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

    //ahora los pagos del carrito
    $delete = "delete from tarjetas_cobros_deta where estadopago=1 and registrado_por=$idusu";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

    header("location: venta_pedidos.php");
    exit;

}
if (isset($_POST['ruc'])) {
    //print_r($_POST);exit;
    $idtransaccion = intval($_POST['ocidtrans']);
    $tipofacturacion = 1;//intval($_POST['ventatip']);//Tradicional o Diplomatico
    $formaventa = 1;//intval($_POST['fventa']);//Credito-Contado
    $textoimpresion = 1;//intval($_POST['timpre']); //Indica si es la lista de productos o consumision agrupada
    //Numeros de la Factura
    $factunum = intval($_POST['factu1']);
    $sucuf = intval($_POST['sucu1']);
    $punf = intval($_POST['pun1']);
    //Datos del Cliente
    //print_r($_POST);exit;
    $idcliente = intval($_POST['ocidcliente']);
    $tipoventa = 1;//intval($_POST['ventatip']);
    if ($tipoventa == 1) {
        //ruc
        $documento = antisqlinyeccion($_POST['ruc'], 'text');
        $documento = str_replace("'", "", $documento);
        $add = " where ruc='$documento' ";
        $diplomatico = 'N';
    } else {
        if ($tipoventa == 2) {
            //carnet
            $documento = antisqlinyeccion($_POST['carnet'], 'text');
            $documento = str_replace("'", "", $documento);
            $add = " where ruc='$documento' ";
            $diplomatico = 'S';
        }
    }
    $razon_social = antisqlinyeccion($_POST['rz'], 'text');
    $razon_social = str_replace("'", "", $razon_social);
    $ex = explode(" ", $razon_social);
    $nc = $ex[0];
    $ap = $ex[1];
    if ($idcliente == 0) {
        //viene vacio o no existe, registramos el cliente

        $buscar = "Select * from cliente where ruc='$documento'";
        $rscon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        if (intval($rscon->fields['idcliente']) == 0) {
            $insertar = "Insert into cliente (razon_social,nombre, apellido,ruc,diplomatico,registrado_por,registrado_el,idempresa) values ('$razon_social','$nc','$ap','$documento','$diplomatico',$idusu,current_timestamp,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }

        //Traemos el id del cliente
        $buscar = "Select * from cliente where registrado_por=$idusu order by idcliente desc limit 1";
        $rsclie = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcliente = intval($rsclie->fields['idcliente']);
    }

    if ($errores == '') {
        $array_entrada['idtransaccion'] = $idtransaccion;
        $array_entrada['idatc'] = "";//ATC en curso
        $array_entrada['idmesa'] = "";//Id de la mesa
        $array_entrada['tipofactura'] = 'S';//Indica si es factura simple o multiple
        $array_entrada['tipofacturacion'] = $tipofacturacion;//Indica si es factura normal o diplomatica
        $array_entrada['formaventa'] = $formaventa;//Indica si es vta cred o contado
        $array_entrada['textoimpresion'] = $textoimpresion;//Indica si se imprimen productos o agrupados
        $array_entrada['idcliente'] = "$idcliente";
        $array_entrada['iddeposito'] = "$iddeposito";
        $array_entrada['idempresa'] = "$idempresa";
        $array_entrada['idsucursal'] = "$idsucursal";
        $array_entrada['cajeroid'] = "$idusu";
        $array_entrada['mozoid'] = "";
        $array_entrada['sucursalfc'] = "$sucuf";
        $array_entrada['pefc'] = "$punf";
        $array_entrada['facturanum'] = "$factunum";

        //print_r($array_entrada);exit;
        $idv = registrarventa($array_entrada);
        //echo $idv;
        //    exit;

    } else {




    }



}



$tb = trim($_REQUEST['tarje']);
if ($tb != '') {
    //Generamos una transaccion para el usuario, si es que no hay

    $buscar = "Select * from tarjetas_transacciones where cajero=$idusu and estado=1";
    $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtransaccion = $rst->fields['idtrans'];
    if ($idtransaccion == 0) {
        $insertar = "Insert into tarjetas_transacciones (cajero,estado) values ($idusu,1)"    ;
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $buscar = "Select * from tarjetas_transacciones where cajero=$idusu and estado=1";
        $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtransaccion = $rst->fields['idtrans'];
    }
    $tt = antisqlinyeccion($tb, 'text');
    $buscar = "Select * from tarjetas_activas where numero=$tb";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idtarjeta = intval($rs->fields['idtarjeta']);

    $buscar = "Select sum(monto) as tabonar from tmp_ventares_cab where idtarjeta=$idtarjeta and registrado='N' and finalizado='S' and idventa is null";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $monto = floatval($rsm->fields['tabonar']);

    //Insertamos en el carrito solo si no esta

    $buscar = "Select * from carrito_tarjeta where idtarjeta=$idtarjeta and monto=$monto ";
    $rscon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if ($rscon->fields['idtarjeta'] == '') {

        $insertar = "insert into carrito_tarjeta (idtarjeta,monto,idcaja,idusu) values ($idtarjeta,$monto,0,$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //marcamos los temporales con el id de transaccion

        $update = "Update tmp_ventares_cab set idtrans=$idtransaccion where idtarjeta=$idtarjeta and registrado='N' and finalizado='S' and idventa is null";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        //Ahora el cuerpo
        $update = "Update tmp_ventares set idtrans=$idtransaccion where idtarjeta=$idtarjeta and registrado='N' and finalizado='S' and borrado='N'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));


        header("location: venta_pedidos.php");
        exit;
    }

}
/*$consulta="

";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/


$buscar = "Select carrito_tarjeta.idtarjeta, numero,monto 
from carrito_tarjeta 
inner join tarjetas_activas on tarjetas_activas.idtarjeta=carrito_tarjeta.idtarjeta 
where
 idusu=$idusu
order by carrito_tarjeta.idtarjeta asc";
$rscarr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcarrito = $rscarr->RecordCount();

$buscar = "
    select * from formas_pago where estado=1
    ";
$rsformas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tot = $rsformas->RecordCount();

$buscar = "Select * from tarjetas_transacciones where cajero=$idusu and estado=1";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtransaccion = $rst->fields['idtrans'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">
      <br /><br />
   <div align="center"></div>
      <div class="divstd">
            <span class="resaltaditomenor">
                Cobrar Pedidos<br />
            </span>
         </div>
<br />
<div class="resumenmini">Para cobrar pedidos activos, escanee la o las tarjetas que desee facturar. Antes de escanear las tarjetas, consulte con el cliente la forma de pago.</div>
<hr /><br />
    <div align="center">
      <p><form id="bf" action="" method="get">
        <input type="text" name="tarje" id="tarje" style="height:40px; width: 50%;"  placeholder="Ingrese tarjeta a cobrar" required="required" />
        <input type="submit" value="Buscar" />
        </form>
      </p>
    
    </div>
    <br />
    <br />
  
    <?php if ($tcarrito > 0) {?>

<div class="div-izq300" style="width:400px;">
    <table width="100%">
        <tr>
            <td width="38%" height="25" align="center" bgcolor="#D1D1D1"><strong>Tarjeta</strong></td>
            <td width="62%" align="center" bgcolor="#D1D1D1"><strong>Monto abonar</strong></td>
            <td width="62%" align="center" bgcolor="#D1D1D1">&nbsp;</td>
        </tr>
        <?php while (!$rscarr->EOF) {?>
        <tr>
             <td height="26"><?php echo $rscarr->fields['numero']?></td>
            <td align="center"><?php echo formatomoneda($rscarr->fields['monto'], 4, 'N');?></td>
            <td align="center"><a href="venta_pedidos.php?el=<?php echo  $rscarr->fields['idtarjeta']?>">[eliminar]</a></td>
        </tr>
    
        <?php $rscarr->MoveNext();
        }?>
    </table>



</div>
<div class="div-izq300" style="width:500px;">
<form id="ggh" action="" method="post">
<input type="hidden" name="ocidtrans" id="ocidtrans" value="<?php echo $idtransaccion?>" />
<table width="100%">
    <tr>
      <td height="26" colspan="3" align="center"><strong>Facturacion</strong></td>
      </tr>
    <tr>
      <td height="35" ><strong>Factura Num</strong></td>
      <td><input type="text" placeholder="SUC" name="sucu1" id="sucu1"  style="height: 40px; width: 20%;" value="<?php echo $parte1f?>" /><input type="text" placeholder="PE" style="height: 40px; width: 20%;" name="pun1" id="pun1" value="<?php echo $parte2f?>"  />
        <input type="text" placeholder="FACTURA" name="factu1" id="factu1"  style="height: 40px; width: 20%;" value="<?php echo $maxnfac?>" /></td>
      <td>&nbsp;</td>
      </tr>
    <tr>
        <td width="21%" ><strong>RUC</strong></td>
        <td width="37%"><input type="text" onkeyup="buscacliente(this.value);" name="ruc" id="ruc" style="height:40px; width:90%;" value="<?php echo $ruc_pred?>"  /></td>
        <td width="42%"><input type="button" value="Generico" onClick="generico(<?php echo $idclipred?>);" /></td>
    
    </tr>
    <tr id="regclie">
        <?php require_once("mregclie.php"); ?>
        
    </tr>
    <tr id="">
    <td ><strong>Forma Pago</strong></td>
        <td><select name="mpago" id="mpago" style="height: 40px; width: 90%;">
                      <?php while (!$rsformas->EOF) {?>
                      <option value="<?php echo $rsformas->fields['idforma'] ?>"><?php echo $rsformas->fields['descripcion']?></option>
                      <?php $rsformas->MoveNext();
                      }?>
                     
                  </select></td>
        <td><input type="text" name="monto" id="monto" style="height:40px; width:50%;" value=""  /><input type="button" name="o" value="Agregar" onclick="registrarpago();" /></td>
    
    </tr>
    <tr>
        <td colspan="3" id="mediospagosc">
        <?php require_once('mini_tmp_pagos.php');?>
       </td> 
    </tr>
</table>
</form>
<hr />


</div>

<?php }?>

<script>
function chaupago(idcobser){
    
    var parametros = {
        "idcobser" : idcobser,
        "eliminar" : 1
    };
    $.ajax({
        data:  parametros,
        url:   'mini_tmp_pagos.php',
        type:  'post',
        beforeSend: function () {
            $("#mediospagosc").html("");
        },
        success:  function (response) {

            $("#mediospagosc").html(response);

        }     
    
     });
    
    
    
}
function generico(idgenerico){
  var gen='<?php echo $ruc_pred ?>';
  var rzgen='<?php echo $razon_social_pred ?>';
  $("#ruc").val(gen);
  $("#cliebasico").val(rzgen);
  $("#idcliente").val(idgenerico);
    //alert(idgenerico);
}
function registrarpago(){
    var monto=$("#monto").val();
    var fpago=$("#mpago").val();
    if (monto!='' && fpago !=''){
         var parametros = {
            "monto" : monto,
            "forma" : fpago

        };
          $.ajax({
                data:  parametros,
                url:   'mini_tmp_pagos.php',
                type:  'post',
                beforeSend: function () {
                    $("#mediospagosc").html("");
                },
                success:  function (response) {
    
                    $("#mediospagosc").html(response);
    
                }     
    
     });
        
        
        
        
        
    } else {
        alert('Debe indicar medio y forma de pago')    ;
    }
}
function buscacliente(valorbuscar){
     var parametros = {
        "buscar" : valorbuscar

    };
      $.ajax({
            data:  parametros,
            url:   'mregclie.php',
            type:  'post',
            beforeSend: function () {
                $("#regclie").html("");
            },
            success:  function (response) {

                $("#regclie").html(response);

            }     

 });
        
        
        
        
        
        
}
function cerrar_venta(){
    $("#ggh").submit();
    
    
}
</script>



          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
