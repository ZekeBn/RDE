<?php
require_once("../includes/conexion.php");
//require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";

require_once("../includes/rsusuario.php");



if (isset($_POST['proc']) && ($_POST['proc'] != 0)) {
    //procesamos la lista completa, si no camio, hule ya
    $buscar = "Select idseriepkcos,productos.descripcion,
			gest_depositos.descripcion as nombrede,
			tiposala,costo_productos.ubicacion,id_producto,lote,vencimiento,
			costo_productos.disponible,costo_productos.numinterno,costo_productos.numfactura,
			costo_productos.subprod,costo_productos.produccion,costo_productos.precio_costo
		from costo_productos 
		inner join productos on productos.idprod=costo_productos.id_producto
		inner join gest_depositos on gest_depositos.iddeposito=costo_productos.ubicacion
		where costo_productos.ubicacion > 0 and costo_productos.disponible > 0 
		and id_producto NOT IN(select idproducto from gest_depositos_stock_gral) 
		order by productos.descripcion asc";
    //recorremos la lista y registramos en el stock, segun corresponda

    $rslista = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rslista->EOF) {
        $idserial = intval($rslista->fields['idseriepkcos']);
        $idp = antisqlinyeccion($rslista->fields['id_producto'], 'text');
        $iddeposito = intval($rslista->fields['ubicacion']);
        $cantidad = floatval($rslista->fields['disponible']);
        $tiposala = floatval($rslista->fields['tiposala']);
        $pchar = antisqlinyeccion($rslista->fields['descripcion'], 'text');
        $lote = antisqlinyeccion($rslista->fields['lote'], 'text');
        $vto = antisqlinyeccion($rslista->fields['vencimiento'], 'text');
        $subprod = intval($rslista->fields['subprod']);
        $produccion = intval($rslista->fields['produccion']);
        $notanum = antisqlinyeccion($rslista->fields['notanum'], 'text');
        $factura = antisqlinyeccion($rslista->fields['numfactura'], 'text');
        $preciocosto = floatval($rslista->fields['precio_costo']);
        //Paso1: Registrar en el stock general si no existe
        $buscar = "select * from gest_depositos_stock_gral where idproducto=$idp";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        if ($rsb->fields['descripcion'] != '') {
            //ya existe en el stock gra, damos update nomas
            $update = "Update gest_depositos_stock_gral set disponible=disponible+$cantidad where idproducto=$idp";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
        } else {
            //no existe, insert
            $insertar = "Insert into gest_depositos_stock_gral
				(iddeposito,idproducto,disponible,tipodeposito,last_transfer,estado,descripcion)
				values
				($iddeposito,$idp,$cantidad,$tiposala,current_timestamp,1,$pchar)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }

        //Paso 2: de acuero al tipo de sala (deposito) almacenamos en donde corresponda

        if ($tiposala == 1) {
            //va a deposito
            $insertar = " insert into gest_depositos_stock
				(idproducto,idseriecostos,disponible,cantidad,iddeposito,
				 subproducto,produccion,lote,vencimiento,recibido_el,autorizado_por,
				 verificado_por,verificado_el,facturanum,notanum,descripcion,costogs)
				values
				($idp,$idserial,$cantidad,$cantidad,$iddeposito,$subprod,$produccion,$lote,$vto,current_timestamp,
				$idusu,$idusu,current_timestamp,$factura,$notanum,$pchar,$preciocosto)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        } else {
            //salon de ventas
            $insertar = " insert into gest_depositos_ventas 	
				(idproducto,idseriecostos,disponible,cantidad,iddeposito,subproducto,
				produccion,lote,vencimiento,recibido_el,autorizado_por,descripcion,costogs)
				values
				($idp,$idserial,$cantidad,$cantidad,$iddeposito,$subprod,$produccion,$lote,$vto,current_timestamp,$idusu,$pchar,$preciocosto)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        }

        $rslista->MoveNext();
    }

}
$idserie = intval($_GET['ids']);
if ($idserie == 0) {
    //vemos por post
    $idserie = intval($_POST['ids']);
    if ($idserie == 0) {
        //Elementos pendientes de asignacion
        $buscar = "Select idseriepkcos,productos.descripcion,gest_depositos.descripcion as nombrede,costo_productos.ubicacion,id_producto,lote,vencimiento,costo_productos.disponible
		from costo_productos 
		inner join productos on productos.idprod=costo_productos.id_producto
		inner join gest_depositos on gest_depositos.iddeposito=costo_productos.ubicacion
		where costo_productos.ubicacion > 0 and costo_productos.disponible > 0 
		and id_producto NOT IN(select idproducto from gest_depositos_stock_gral) 
		order by productos.descripcion asc";

        $rspen = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tpen = $rspen->RecordCount();

        $nobusca = 1;
    } else {
        //Hubo por post, vemos si hay cambio de cosas

        if (isset($_POST['idsv']) && ($_POST['idsv'] > 0)) {

            //es para registrar unacambio antes de procesar
            $lote = antisqlinyeccion($_POST['lote'], 'text');
            $vto = antisqlinyeccion($_POST['vto'], 'date');
            $deposito = intval($_POST['deposito']);

            $buscar = "Select * from costo_productos where idseriepkcos=$idserie";
            $rscv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //actualizamos los cambios

            $update = "Update costo_productos set vencimiento=$vto,lote=$lote,ubicacion=$deposito where idseriepkcos=$idserie";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            $buscar = "Select idseriepkcos,productos.descripcion,gest_depositos.descripcion as nombrede,costo_productos.ubicacion,id_producto,lote,vencimiento,costo_productos.disponible,asentado
		from costo_productos 
		inner join productos on productos.idprod=costo_productos.id_producto
		inner join gest_depositos on gest_depositos.iddeposito=costo_productos.ubicacion
		where costo_productos.idseriepkcos=$idserie";
            $rsedita = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));




        } else {
            $buscar = "Select idseriepkcos,productos.descripcion,gest_depositos.descripcion as nombrede,costo_productos.ubicacion,id_producto,lote,vencimiento,costo_productos.disponible,asentado
			from costo_productos 
			inner join productos on productos.idprod=costo_productos.id_producto
			inner join gest_depositos on gest_depositos.iddeposito=costo_productos.ubicacion
			where costo_productos.idseriepkcos=$idserie";
            $rsedita = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $asentado = intval($rsedita->fields['asentado']);
            if ($asentado > 0) {
                $errorasentado = "El producto ya fue asentado. No se permite el cambio."	;

            }
            $nobusca = 0;
        }
    }
} else {
    //editar
    $buscar = "Select idseriepkcos,productos.descripcion,gest_depositos.descripcion as nombrede,costo_productos.ubicacion,id_producto,lote,vencimiento,costo_productos.disponible,asentado
		from costo_productos 
		inner join productos on productos.idprod=costo_productos.id_producto
		inner join gest_depositos on gest_depositos.iddeposito=costo_productos.ubicacion
		where costo_productos.idseriepkcos=$idserie";
    $rsedita = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $asentado = intval($rsedita->fields['asentado']);
    if ($asentado > 0) {
        $errorasentado = "El producto ya fue asentado. No se permite el cambio."	;

    }
    $nobusca = 0;



}
//Traemos depositos
$buscar = "Select * from gest_depositos where estado=1  order by descripcion asc";
$rsdpto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("../includes/head.php"); ?>
<script>
function enviar(cual){
	if (cual==1){
		document.getElementById('nuevo').submit();
	}
	if (cual==2){
		document.getElementById('registarcambios').submit();
	}
}

</script>
</head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Asignar a los salones la primera carga. Proviene de su previo-registro de stock, en el cual ha indicado la ubicaci&oacute;n f&iacute;sica del producto.</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


				  

	  <div class="colcompleto" id="contenedor">
 		<!-- SECCION DONDE COMIENZA TODO -->
    
    		<div class="divstd">
				<a href="gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
			  </span>
          	</div>
            <br />
    		<div class="resumenmini">
                
    		</div>
   			 <br />
             <?php if ($nobusca == 1) {?>
		  <?php if ($tpen > 0) {?>	
          <form id="nuevo" name="nuevo" action="gest_adm_depositos_asignar.php" method="post">
          <input type="hidden" name="proc" id="proc" value="1" />
                <table width="700" class="tablalinda2" >
                          <tr>
                              <td width="76" height="32" align="center" bgcolor="#E7EF6C"><strong>C&oacute;digo</strong></td>
                              <td width="219" align="center" bgcolor="#E7EF6C"><strong>Producto</strong></td>
                              <td width="59" align="center" bgcolor="#E7EF6C"><strong>Cantidad</strong></td>
                              <td width="78" align="center" bgcolor="#E7EF6C"><strong>Lote</strong></td>
                              <td width="82" align="center" bgcolor="#E7EF6C"><strong>Vencimiento</strong></td>
                              <td width="115" align="center" bgcolor="#E7EF6C"><strong>Dep&oacute;sito Asignado</strong></td>
                              <td width="39" align="center" bgcolor="#E7EF6C">&nbsp;</td>
                          </tr>	
                          <?php while (!$rspen->EOF) {?>
                              <tr>
                                  <td height="40" align="left"><?php echo $rspen->fields['id_producto']?></td>
                                  <td><?php echo $rspen->fields['descripcion']?></td>
                                  <td align="right"><?php echo $rspen->fields['disponible']?></td>
                                  <td align="right"><?php echo $rspen->fields['lote']?></td>
                                  <td align="right"><?php echo $rspen->fields['vencimiento']?></td>
                                  <td align="center"><?php echo $rspen->fields['nombrede']?></td>
                                  <td align="center"><a href="gest_adm_depositos_asignar.php?ids=<?php echo $rspen->fields['idseriepkcos']?>" ><img src="img/1445735221_file.png" width="20" height="20" alt=""/></a></td>
                              </tr>	
                            
                          <?php $rspen->MoveNext();
                          }?>
                          <tr>
                                <td height="52" colspan="7" align="center"><input type="button" value="Registrar / Asentar" onclick="enviar(1)" />
                                </td>
                  </tr>
        </table>
        </form>
                
                  <?php } else {?>
        <div align="center">
                      <span class="resaltarojomini">No existen productos asignados a un dep&oacute;sito que no hayan sido transferidos.</span>
                </div>
                  <?php } ?>
            <?php } else { ?>
            	<!-----------------------------------EDICION--------------------------------->
                <?php if ($errorasentado == '') {?>
                <form id="registarcambios" method="post" action="gest_adm_depositos_asignar.php" >
                <input type="hidden" name="ids" id="ids" value="<?php echo $idserie ?>" />
                <input type="hidden" name="idsv" id="idsv" value="<?php echo $idserie ?>" />
		<table width="666">
        					<tr>
                            	<td height="35" colspan="5" bgcolor="#FFF8A0"><strong>Seleccionado <?php echo $errorasentado?></strong></td>
                           	 
          </tr>
                        	<tr>
                        		<td width="156" height="31" align="center" bgcolor="#CCCCCC"><strong>Producto</strong></td>
                            	<td width="130" align="center" bgcolor="#CCCCCC"><strong>C&oacute;digo</strong></td>
                                <td width="153" align="center" bgcolor="#CCCCCC"><strong>Dep&oacute;sito Seleccionado</strong></td>
                        		<td width="118" align="center" bgcolor="#CCCCCC"><strong>Lote</strong></td>
                                <td width="83" align="center" bgcolor="#CCCCCC"><strong>Vencimiento</strong></td>
                               
                       	  </tr>
                         <tr>
                         	<td height="24"><?php echo $rsedita->fields['descripcion']?></td>
                           <td><?php echo $rsedita->fields['id_producto']?></td>
                            <td align="center"><?php echo $rsedita->fields['nombrede']?></td>
                            <td align="center"><?php echo $rsedita->fields['lote']?></td>
                           <td align="center"><?php echo $rsedita->fields['vencimiento']?></td>
                          
                         </tr>
                         <tr>
                         	<td height="35" colspan="5" bgcolor="#C8FFEB">
                            <strong>Cambiar por</strong>
                            </td>
                         
                         </tr>	
                         <tr>
                         	<td height="32" colspan="2" align="center" bgcolor="#D7D7D7"><strong>Dep&oacute;sito </strong></td>
                            
							<td width="153" align="center" bgcolor="#D7D7D7"><strong>Lote</strong></td>
 							<td colspan="2" align="center" bgcolor="#D7D7D7"><strong>Vencimiento</strong></td>
                          </tr>
                          <tr>
                          	<td height="35" colspan="2" align="center">
                          		<select name="deposito" id="deposito" style="width:80%">
                            		<option value="0" selected="selected">Seleccionar</option>
                               		 	<?php while (!$rsdpto->EOF) {?>
                            			<option value="<?php echo $rsdpto->fields['iddeposito']?>">
										<?php echo $rsdpto->fields['descripcion']?>
                                        </option>
                                		<?php $rsdpto->MoveNext();
                               		 	}?>
                            	</select>
                          
                          	</td>
                            <td><input type="text" name="lote" id="lote" value="<?php echo $rsedita->fields['lote']?>" /></td>
                            <td colspan="2" align="center"><input type="date" name="vto" id="vto" value="<?php echo $rsedita->fields['vencimiento']?>" /></td>
                            
                          </tr>
                          <tr>
                          	<td height="39" colspan="5" align="center">
                            <input type="button" name="rf" value="Registrar Cambios" onclick="enviar(2)"/>
                           </td>
                          
          </tr>
                          
                        </table>
            		</form>
            	<?php } else {?>
                	<div align="center">
                	<span class="resaltarojomini"><?php echo $errorasentado ?></span>
                	</div>
                <?php }?>
            <?php } ?>
		</div> 
<!-- contenedor -->
 	 





</div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>

