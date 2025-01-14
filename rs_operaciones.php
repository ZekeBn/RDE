 <?php
    require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

$buscar = "Select * from gest_billetes order by idbillete asc";
$bille = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "Select * from tipo_moneda where estado=1 order by descripcion asc";
$moneda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "Select * from proveedores where estado=1 and idempresa=$idempresa order by nombre asc";
$rspr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="div-izq300">
      <strong>Entregar Valores</strong><br />
      (Sale dinero de la caja) <br />
      <a href="caja_retiros_cajero.php" target="_blank" title="Ver registros cargados"><img src="img/1444615142_opened_folder.png" width="32" height="32" alt=""/></a><br />
     
      <?php if ($errorautoriza != '') {?>
            <div align="center"><span class="resaltarojomini"><?php echo $errorautoriza?></span></div>
      <?php }?>
      <br />
       <form id="entregaval" name="entregaval" action="gest_administrar_caja.php" method="post">
                
              <input type="hidden" name="ocidcaja" id="ocidcaja" value="<?php echo $idcaja?>" />
              <input type="hidden" name="cual" id="cual" value="1" />
              <input type="hidden" name="md" id="md" value="<?php echo $dispo?>" />
              <table width="200" height="106">
                  <tr>
                     <td width="104" height="34" align="right" bgcolor="#D9FF2D"><strong>Monto entregar</strong></td>
                     <td ><input type="text" name="montoentrega" id="montoentrega" value="0" style="height: 30px;"/></td>
                  </tr>
                  <tr>
                     <td align="right" bgcolor="#D9FF2D"><strong>C&oacute;digo Autorizaci&oacute;n</strong></td>
                     <td><input type="password" name="codigoau" id="codigoau" style="height: 30px;" /></td>
                  </tr>
                 <tr>
                     <td height="36" align="right" bgcolor="#D9FF2D"><strong>Observaciones</strong></td>
                     <td><input type="text" name="obs" id="obs"  style="height: 30px;" /></td>
                  </tr>
                  <tr>
                     <td colspan="2" align="center"> <?php if ($estadocaja == 1) {?><input type="button" name="r" id="sacavalcaj" value="Registrar Salida Valor" onclick="sacarvalor();" /><?php } ?></td>
                  </tr>
                </table>
     </form>
</div>

 <!----------------------REPOSICION----------------------------------------->
                <div class="div-izq250">
                     <strong>Recibir Valores</strong><br />
                      (Entra dinero a la caja)  <br />
                      <a href="caja_retiros_cajero.php" target="_blank" title="Ver registros cargados"><img src="img/1444615142_opened_folder.png" width="32" height="32" alt=""/></a><br />
                     <?php if ($errorautorizav != '') {?>
                        <div align="center"><span class="resaltarojomini"><?php echo $errorautorizav?></span></div>
                     <?php }?>
                    
                  <form id="recibeval" name="recibeval" action="gest_administrar_caja.php" method="post">
                     <input type="hidden" name="ocidcaja" id="ocidcaja" value="<?php echo $idcaja?>" />
                      <input type="hidden" name="cual" id="cual" value="2" />
                      <input type="hidden" name="md" id="md" value="<?php echo $dispo?>" />
                    <table width="200" height="106">
                        <tr>
                            <td width="104" height="34" align="right" bgcolor="#08155E" style="color:#FFFFFF"><strong>Monto recibir</strong></td>
                          <td width="84"><input type="text" name="montorecibe" id="montorecibe" value="0" style="width:130px;height: 30px;"/></td>
                        </tr>
                        <tr>
                            <td align="right" bgcolor="#08155E" style="color:#FFFFFF"><strong>C&oacute;digo Autorizaci&oacute;n</strong></td>
                          <td><input type="password" name="codigoaure" id="codigoaure" style="width:130px;height: 30px;"/></td>
                        </tr>
                        <tr>
                            <td height="36" align="right" bgcolor="#08155E" style="color:#FFFFFF"><strong>Observaciones</strong></td>
                          <td><input type="text" name="obs2" id="obs2" style="width:130px;height: 30px;"/></td>
                      </tr>
                      <tr>
                          <td colspan="2" align="center"> <?php if ($estadocaja == 1) { ?><input type="button" name="r" id="recibevalcaj" value="Registrar reposici&oacute;n de valor" onclick="recibirvalor();" /><?php }?></td>
                      </tr>
                    </table>
                  </form>
                  
                 </div>
                 <!------------------PAGO SIMPLE------------------------>
                <div class="div-izq250">
                    <strong>Pago por Caja</strong><br /><a href="inf_pagosxcaja.php" target="_blank" title="Ver registros cargados"><img src="img/1444615142_opened_folder.png" width="32" height="32" alt=""/></a><br />
                     <?php if ($errorautorizav != '') {?>
                        <div align="center"><span class="resaltarojomini"><?php echo $errorautorizav?></span></div>
                     <?php }?>
                     <br />
                   <?php   if ($rspref->fields['pagoxcaja_rec'] == 'S' or $rspref->fields['pagoxcaja_chic'] == 'S') { ?>
                  <form id="pagochi" name="pagochi" action="gest_administrar_caja.php" method="post">
                     <input type="hidden" name="ocidcajac" id="ocidcajac" value="<?php echo $idcaja?>" />
                      <input type="hidden" name="cual" id="cual" value="5" />
                      <input type="hidden" name="mdc" id="mdc" value="<?php echo $dispo?>" />
                    <table width="200" height="106">
                       <tr>
                            <td width="104" height="34" align="right" bgcolor="#A34B54" style="color:#FFFFFF"><strong>Proveedor(opcional ver parametrizar)</strong></td>
                          <td width="84"><select name="minip" id="minip" style="width: 98%;height: 30px;">
                              <option value="0" selected="Selected">Seleccionar</option>
                                  <?php while (!$rspr->EOF) {?>
                                   <option value="<?php echo $rspr->fields['idproveedor']?>"><?php echo $rspr->fields['nombre']?></option>
                                  
                                  
                                  <?php $rspr->MoveNext();
                                  }?>
                              </select>
                          </td>
                      </tr>
                        <tr>
                            <td width="104" height="34" align="right" bgcolor="#A34B54" style="color:#FFFFFF"><strong>Monto a Pagar</strong></td>
                          <td width="84"><input type="text" name="montopagoc" id="montopagoc" value="0" style="width:98%;height: 30px;" /></td>
                      </tr>
                        <tr>
                            <td align="right" bgcolor="#A34B54" style="color:#FFFFFF"><strong>Factura / Num</strong></td>
                          <td><input type="text" name="nfactu" id="nfactu" style="width:98%;height: 30px;"/></td>
                      </tr>
                        <tr>
                            <td height="36" align="right" bgcolor="#A34B54" style="color:#FFFFFF"><strong>Observaciones / Motivo</strong></td>
                          <td><input type="text" name="obspago" id="obspago" style="width:98%; height: 30px;"/></td>
                      </tr>
                        <tr>
                          <td height="36" align="right" bgcolor="#A34B54" style="color:#FFFFFF">Caja</td>
                          <td>
                            <select name="tipocajapag" id="tipocajapag" required style="height: 30px;">
                              <?php if ($rspref->fields['pagoxcaja_chic'] == 'S') { ?>
                              <option value="C">Caja Chica</option>
                              <?php } ?>
                              <?php if ($rspref->fields['pagoxcaja_rec'] == 'S') { ?>
                              <option value="R">Caja Recaudacion</option>
                              <?php } ?>
                          </select></td>
                        </tr>
                      <tr>
                          <td colspan="2" align="center"> <?php if ($estadocaja == 1) { ?><input type="button" name="r" id="registrapagocaj" value="Registrar Pago x Caja" onclick="pagarmini();" /><?php }?></td>
                      </tr>
                    </table>
                  </form>
                  <?php } else { ?>
                  <br /><br /><br />No permitido.<br /><br /><br /><br /><br />
                  <?php } ?>
                 </div>     
 <hr />
 <div class="clear"></div>
 <br />
<div align="center">
  <br /> <br />
   <span class="numb"> Billetes -Vouchers - Monedas Ex - Otros</span>
    <br />
    <div class="div-izq300" style="width:400px;">
        <form id="tv" action="" method="post">
    <table width="380">
          <tr>
              <td width="142" height="27" align="center" bgcolor="#E5E5E5"><strong>Total Vouchers</strong></td>
              <td width="80" align="center" bgcolor="#E5E5E5"><strong>Accion</strong></td>
          
       </tr>
        <tr>
              <td height="13"><input type="text" name="tvouchers" id="tvouchers" style="height: 40px; width: 98%;" required="required" />
         
           </td>
              <td><input type="submit" value="Registrar Total " /></td>
          
       </tr>
    </table>
    </form>        
    </div>
     <div class="div-izq300" style="width:150px; height:60px;">
    <strong>Deliverys no Rendidos</strong><br />
    <input type="button" value="ver" onMouseUp="document.location.href='delivery_norendidos.php'">
  </div>
    <div class="div-izq300">
        
<table width="294">
          <tr>
              <td width="142" height="27" align="center" bgcolor="#E5E5E5"><strong>Billete</strong></td>
           <td width="80" align="center" bgcolor="#E5E5E5"><strong>Cantidad</strong></td>
           <td width="50" align="center" bgcolor="#E5E5E5"></td>  
       </tr>
       <tr>
              <td height="13">
           <select name="billeton" id="billeton" style="width:98%">
            <option value="0" selected="selected">Seleccionar</option>
           <?php while (!$bille->EOF) {?>
           <option value="<?php echo $bille->fields['idbillete'] ?>"><?php echo formatomoneda($bille->fields['valor']) ?></option>
           <?php $bille->MoveNext();
           }?>
           </select>
           </td>
           <td><input type="text" name="ofg" id="ofg" style="width:80px;" /></td>    
           <td align="center"><a href="javascript:void(0);" onClick="agregabb();"><img src="img/1444616400_plus.png" width="32" height="32" alt=""/></a></td>   
       </tr>
    
    
    
   </table>
    </div>
    <div class="clear"></div>
    <br /><br />
    <div class="div-izq300" style="width:400px;">
    <table width="380">
          <tr>
              <td width="142" height="27" align="center" bgcolor="#E5E5E5"><strong>Tipo Moneda</strong></td>
              
           <td width="80" align="center" bgcolor="#E5E5E5"><strong>Cantidad</strong></td>
           <td width="80" align="center" bgcolor="#E5E5E5"><strong>Cotizacion</strong></td>
           <td width="50" align="center" bgcolor="#E5E5E5"></td>  
       </tr>
        <tr>
              <td height="13">
           <select name="moneda" id="moneda" style="width:98%" onchange="carga_cotizacion(this.value);">
            <option value="0" selected="selected">Seleccionar</option>
           <?php while (!$moneda->EOF) {?>
           <option value="<?php echo $moneda->fields['idtipo'] ?>"><?php echo $moneda->fields['descripcion'] ?></option>
           <?php $moneda->MoveNext();
           }?>
           </select>
           </td>

           <td><input type="text" name="cantimoneda" id="cantimoneda" style="width:80px;" /></td>    
              <td><input type="text" name="coti" id="coti" style="width:80px; background-color:#CCC;" readonly="readonly" /></td>
           <td align="center"><a href="javascript:void(0);" onClick="agregabbm();"><img src="img/1444616400_plus.png" width="32" height="32" alt=""/></a></td>   
       </tr>
    </table>
    </div>
   
    <div class="div-izq300" style="width:300px; height:60px;">
  <table width="290">
      <tr>
        <td width="80" height="27" align="center" bgcolor="#E5E5E5"><strong>Monto Cierre Caja Chica</strong></td>
      </tr>
      <tr>
        <td align="center">Gs. 
          <input type="text" name="caja_chica_cierre_tmp" id="caja_chica_cierre_tmp" style="width:80px;" value="0" /></td>
      </tr>
  </table>
  </div>
     
    
 
</div>

 <div class="clear"> </div><br />

 <br />
  <div class="clear"> </div>
<hr />
 <div class="div-izq400" id="billetitos">
    <?php require_once('ar_billetes.php'); ?>
    
 </div>
  
