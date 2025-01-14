<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "61";
require_once("includes/rsusuario.php");

//Post de busqueda

if (isset($_POST['desde']) && ($_POST['desde'] != '')) {

    $desde = antisqlinyeccion($_POST['desde'], 'date');
    $hasta = antisqlinyeccion($_POST['hasta'], 'date');


    $buscar = "Select idpedido,fecha,usuario,descripcion from salon_ventas_reposiciones 
	inner join usuarios on usuarios.idusu=salon_ventas_reposiciones.generado_por
	inner join gest_depositos on gest_depositos.iddeposito=salon_ventas_reposiciones.idsalon
	where date(fecha) between $desde and $hasta order by idpedido asc";
    $rsvta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tpr = $rsvta->RecordCount();

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
	function env(){
		//controlamos cantidad a mover
		document.getElementById('b1').submit();	
	}
	function enviar(){
		//controlamos cantidad a mover
		document.getElementById('fhp').submit();	
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
    <div align="center">
		<a href="gest_solicitar_productos.php">
        	<img src="img/homeblue.png" width="64" height="64" title="Regresar" style="cursor:pointer" />		
    </a></div>
    <div class="divstd">
    	
    	<div class="resumenmini">
    
       				<strong>Buscar Pedidos efectuados</strong><span class="resaltarojomini"></span>
    				<form id="b1"  name="b1" action="gest_buscar_productos.php" method="post">
                    <table width="397" height="42">
                    
                    	<tr>
                        	<td width="38"><strong>Desde</strong></td>
                          <td width="142"><input type="date" name="desde" id="desde" required="required" /></td>
                            <td width="35"><strong>Hasta</strong></td>
                          <td width="162"><input type="date" name="hasta" id="hasta" required="required" /></td>
                        
                        </tr>
                       
                    </table>
    				</form>
    				<a href="javascript:void(0);" onclick="env();" >
    					<img src="img/buscar16.png" width="16" height="16" alt=""/> 
                    </a>   
       </div>
   		<br />
        <div align="center">
        	<span class="resaltarojomini"><?php echo $errores?></span>
        
        
        </div>
        
        <hr />
        <div align="center">
        	<?php if ($tpr > 0) {?>
            	<form action="gest_solicitar_productos.php" method="post" id="fhp">
        		<table width="591" height="79" class="tablalinda2">
                	<tr>
                    	<td width="59" height="26" align="center" bgcolor="#A5F89C"><strong>Pedido</strong></td>
                        <td width="101" align="center" bgcolor="#A5F89C"><strong>Fecha</strong></td>
                        <td width="89" align="center" bgcolor="#A5F89C"><strong>Solicitado por</strong></td>
                        <td width="133" align="center" bgcolor="#A5F89C"><strong>Solicitado para</strong></td>
                        <td width="93" align="center" bgcolor="#64FFE9"><strong>Procesado por</strong></td>
                        <td width="149" align="center" bgcolor="#64FFE9"><strong>Procesado el</strong></td>
                    </tr>
             		 <?php

                     while (!$rsvta->EOF) {
                         $idpedido = intval($rsvta->fields['idpedido']);
                         $fecha = date("d-m-Y H:i:s", strtotime($rsvta->fields['fecha']));
                         $quien = ($rsvta->fields['usuario']);
                         $donde = $rsvta->fields['descripcion'];
                         $vp = intval($rsvta->fields['procesado_por']);
                         if ($vp > 0) {
                             $buscar = "select usuario from usuarios where idusu=$vp";
                             $rsju = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                             $fgh = $rsju->fields['usuario'];
                         }
                         ?>
                		<tr>
                    		<td height="26" align="right"><?php echo $idpedido ?></td>
                        	<td align="center"><?php echo $fecha ?></td>
                        	<td align="center"><?php echo $quien  ?></td>
                        	<td align="center"><?php echo $donde ?></td>
                            <td align="center"><?php echo $fgh ?></td>
                            <td align="center"><?php echo $rsvta->fields['procesado_el']?></td>
                        	
                   		 </tr>
                         <tr>
                         	<?php
                                 //buscamos
                                 $buscar = "Select productos.descripcion,idproducto,cantidad_solicitada,gest_depositos.descripcion as depo from salon_ventas_reposiciones_detalles inner join productos on productos.idprod=salon_ventas_reposiciones_detalles.idproducto inner join gest_depositos on gest_depositos.iddeposito=salon_ventas_reposiciones_detalles.pedido_para where idpedido=$idpedido
							";
                         //	echo $buscar;
                         $pedicuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                         $tcur = $pedicuerpo->RecordCount();
                         ?>
                         	<?php if ($tcur > 0) {?>
                            	<table width="500" class="tablalinda3">
                            		<tr>    
                            			<td align="center" bgcolor="#F89CE6" style="height:40px;"><strong>C&oacute;digo</strong></td>
                                        <td align="center" bgcolor="#F89CE6"><strong>Producto</strong></td>
                                        <td align="center" bgcolor="#F89CE6"><strong>Cantidad Solicitada</strong></td>
                                        <td align="center" bgcolor="#F89CE6"><strong>Solicitado a</strong></td>
                                    </tr>
                                    <?php while (!$pedicuerpo->EOF) {?>
                                    <tr>
                                   	 	<td height="26"><?php echo $pedicuerpo->fields['idproducto']?></td>
										<td><?php echo $pedicuerpo->fields['descripcion']?></td>
                                    	<td align="center"><?php echo $pedicuerpo->fields['cantidad_solicitada']?></td>
                                    	<td><?php echo $pedicuerpo->fields['depo']?></td>
                                    </tr>
									<?php $pedicuerpo->MoveNext();
                                    }?>
                            	</table>
                            <?php }?>
                         </tr>
                	<?php $rsvta->MoveNext();
                     }?>
                    
                </table>
                </form>
        	<?php } ?>
        </div>
  	</div> 
</div> 
<!-- contenedor -->
 	 
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>


