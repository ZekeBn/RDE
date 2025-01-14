 <?php
require_once("includes/conexion.php");
//require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "106";
require_once("includes/rsusuario.php");


$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where gest_depositos.idempresa=$idempresa and usuarios.idempresa=$idempresa
order by descripcion ASC";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where gest_depositos.idempresa=$idempresa and usuarios.idempresa=$idempresa
order by descripcion ASC";
$rsd2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$f1 = $_POST['desde'];
$f2 = $_POST['hasta'];

if ($f1 == '') {
    $f1 = date("Y").'-'.date("m").'-'.'01';
}
if ($f2 == '') {
    $f2 = date("Y-m-d", strtotime($ahora));
}




if (isset($_POST['origen'])) {
    $add1 = '';
    $add2 = '';
    $idpo = intval($_POST['origen']);


    $destino = intval($_POST['destino']);
    $desde = antisqlinyeccion($_POST['desde'], 'date');
    $hasta = antisqlinyeccion($_POST['hasta'], 'date');

    if (($idpo > 0) && ($destino > 0) && ($desde != 'NULL') && ($hasta != 'NULL')) {

        $add1 = " and gest_depositos_mov.iddeposito=$idpo  and destino=$destino and date(fechahora) between $desde and $hasta ";
    } else {
        if (($idpo > 0) && ($destino > 0)) {
            $add1 = " and gest_depositos_mov.iddeposito=$idpo and destino=$destino";

        } else {
            if (($idpo > 0) && ($destino == 0)) {
                $add1 = " and gest_depositos_mov.iddeposito=$idpo ";
            } else {
                if ($destino > 0) {
                    $add1 = " and destino=$destino ";
                }
            }
        }
    }
    //fechas
    if (($desde != 'NULL') && ($hasta != 'NULL')) {
        $add2 = " and date(fechahora) between $desde and $hasta ";

    }


    $compuesto = $add1.$add2;

    if ($compuesto == '') {
        //Mostamos la lista y movimientos generados
        $buscar = "Select gest_depositos.descripcion as deposito, productos.descripcion as 
        producto,gest_depositos_stock.iddeposito,fechahora,idtanda,
        cantidad_transferida,cantidad_vendida,gest_depositos_stock.disponible,ser,idserie,destino,usuario
        from gest_depositos_stock
        inner join gest_depositos on gest_depositos.iddeposito=gest_depositos_stock.iddeposito
        inner join productos on productos.idprod=gest_depositos_stock.idproducto
        inner join usuarios on usuarios.idusu=gest_depositos_stock.idusu
        order by fechahora asc
        ";
        //NUevo
        $buscar = "select iddeposito,fechahora,tipomov,deschar,usuario,obs,lote,cantidad,idtanda,
        (select descripcion from gest_depositos where iddeposito=gest_depositos_mov.origen and idempresa=$idempresa) as origen,
        (select descripcion from gest_depositos where iddeposito=gest_depositos_mov.destino and idempresa=$idempresa) as destino,
        (select descripcion from insumos_lista where idinsumo=gest_depositos_mov.idproducto and idempresa=$idempresa) as productoc
        from  gest_depositos_mov 
            inner join usuarios on usuarios.idusu=gest_depositos_mov.idusu 
            where usuarios.idempresa=$idempresa and gest_depositos_mov.idempresa=$idempresa
            order by fechahora asc
        ";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $td = $rsb->RecordCount();

    } else {


        //NUevo
        $buscar = "select iddeposito,fechahora,tipomov,deschar,usuario,obs,lote,cantidad,(select descripcion from gest_depositos where iddeposito=gest_depositos_mov.origen and idempresa=$idempresa) as origen,idtanda,
(select descripcion from gest_depositos where iddeposito=gest_depositos_mov.destino and idempresa=$idempresa) as destino,
(select descripcion from insumos_lista where idinsumo=gest_depositos_mov.idproducto and idempresa=$idempresa) as productoc
from  gest_depositos_mov 
inner join usuarios on usuarios.idusu=gest_depositos_mov.idusu 
where gest_depositos_mov.idempresa=$idempresa and usuarios.idempresa=$idempresa
$compuesto
order by fechahora asc
        ";


        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $td = $rsb->RecordCount();
    }
    //echo $buscar;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("includes/head.php"); ?>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<script>
    
    function buscar(){
        
                document.getElementById('buscarde').submit();    
            
        
    }
    function alertar(titulo,error,tipo,boton){
        swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
    }
</script>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
 <div align="center" >
 <?php require_once("includes/menuarriba.php");?>
</div>

<div class="colcompleto" id="contenedor">
     <!-- SECCION DONDE COMIENZA TODO -->
    
    <div class="divstd">
           <span class="resaltaditomenor"><a href="gest_adm_depositos.php">
               <img src="img/homeblue.png" width="64" height="64" title="Regresar" style="cursor:pointer" /></a>
             <br />
                Movimiento Interno
      </span>
    </div>
    <div class="resumenmini600"><h3><br />
        <strong> Filtros Disponibles</strong>
           </h3>
        <br />
       
      <form id="buscarde" name="buscarde" action="gest_depo_mov.php" method="post">
      <input type="hidden" name="enviapost" id="enviapost" value="1"/>
        <table width="200" >
            <tr>
                <td height="29" align="center" bgcolor="#F1F1F1"><strong>Origen</strong></td>
                <td align="center" bgcolor="#F1F1F1"><strong>Destino</strong></td>
                
            </tr>
            <tr>
                  <td>
                   <select name="origen" id="origen">
                        <option value="0" selected="selected">Seleccionar Origen</option>
                            <?php while (!$rsd->EOF) {?>
                                <option value="<?php echo $rsd->fields['iddeposito']?>"
                                <?php if ($idpo == $rsd->fields['iddeposito']) {?> selected="selected"<?php } ?>>
                                <?php echo $rsd->fields['descripcion']?></option>
                             <?php $rsd->MoveNext();
                            } ?>
                     </select>
                  </td>
                    <td><select name="destino" id="destino">
                      <option value="0" selected="selected">Seleccionar Destino</option>
                      <?php while (!$rsd2->EOF) {?>
                      <option value="<?php echo $rsd2->fields['iddeposito']?>" <?php if ($destino == $rsd2->fields['iddeposito']) {?> selected="selected"<?php } ?>><?php echo $rsd2->fields['descripcion']?></option>
                      <?php $rsd2->MoveNext();
                      } ?>
                  </select></td>
                 
                
            </tr>
            <tr>
                <td height="29" align="center" bgcolor="#F1F1F1"><strong>Desde</strong></td>
                <td align="center" bgcolor="#F1F1F1"><strong>Hasta</strong></td>
            
            
            </tr>
            <tr>
                 <td><input type="date" name="desde" id="desde" required="required" value="<?php echo $f1?>" /></td>
                <td><input type="date" name="hasta" id="hasta"  required="required" value="<?php echo $f2?>"/></td>
            
            </tr>
            <tr>
                <td height="35" colspan="4" align="center">
                <input type="submit" value="Buscar" />
                </td>
            
            </tr>
        </table>
        
      </form>
        <hr/>
    </div>
       <br />
    <?php if ($td > 0) { ?>
    <table width="798" class="tablalinda2">
        <tr style="color:#FFFFFF">
          <td width="61" align="center" bgcolor="#1F46FD">Tanda</td>
            <td width="106" align="center" bgcolor="#1F46FD"><strong>Fecha</strong></td>
            <td width="112" align="center" bgcolor="#1F46FD"><strong>Usuario</strong></td>
            <td width="128" align="center" bgcolor="#1F46FD"><strong>Origen</strong></td>
            <td width="140" align="center" bgcolor="#1F46FD"><strong>Destino</strong></td>
            <td width="95" align="center" bgcolor="#1F46FD"><strong>Producto</strong></td>
            <td width="95" align="center" bgcolor="#1F46FD"><strong>Cantidad Transferida</strong></td>
            <td width="25" align="center" bgcolor="#1F46FD">&nbsp;</td>
        </tr>
        <?php while (!$rsb->EOF) {?>
        <tr>
          <td align="left"><?php echo $rsb->fields['idtanda'] ?></td>
            <td align="left"><?php echo date("d-m-Y H:i:s", strtotime($rsb->fields['fechahora']))?></td>
            <td align="center"><?php echo $rsb->fields['usuario'] ?></td>
            <td align="center"><?php echo $rsb->fields['origen'] ?></td>
            <td align="center"><?php echo ($rsb->fields['destino']) ?></td>
            <td align="center"><?php echo ($rsb->fields['productoc']) ?></td>
            <td align="center"><?php echo formatomoneda($rsb->fields['cantidad'], 2) ?></td>
            <td align="center"><a href="reimpresion_transferencia.php?ta=<?php echo $rsb->fields['idtanda'] ?>"><img src="img/impres.gif" width="17" height="16" title="Reimpresion de trasferencia"/></a></td>
        </tr>
        <?php $rsb->MoveNext();
        }?>
    </table>
    <?php } ?>
</div> 
<!-- contenedor -->
      
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>


