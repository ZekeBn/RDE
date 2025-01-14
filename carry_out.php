 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


$consulta = "
SELECT razon_social, ruc FROM cliente where borrable = 'N' and estado = 1 order by idcliente asc limit 1
";
$rsclipred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = $rsclipred->fields['razon_social'];
$ruc_pred = $rsclipred->fields['ruc'];

//Eliminar
$chau = intval($_POST['chau']);
if ($chau > 0) {
    $update = "Update tmp_ventares_cab set estado=6,anulado_el='$ahora',anulado_por=$idusu where idtmpventares_cab=$chau and idempresa = $idempresa and idsucursal = $idsucursal"    ;
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}


$buscar = "
Select * 
from tmp_ventares_cab 
where 
finalizado='S' 
and registrado='N' 
and estado=1 
and (idmesa=0 or idmesa is null) 
and idcanal <> 3  
and idmesa_tmp is null 
and idempresa = $idempresa 
and idsucursal = $idsucursal
 order by fechahora asc
 limit 500
 ";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rspedicu->RecordCount();


?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> RUC * </label>
    <div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
        <input type="text" name="ruc_carry" id="ruc_carry" value="<?php echo $ruc_pred; ?>" placeholder="RUC" class="form-control" style="width:80%;" autofocus="autofocus" />
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onMouseUp="carga_ruc_carry(<?php echo intval($_POST['idpedido']);?>);" title="Buscar en la SET" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar en la SET"><span class="fa fa-search"></span></button>
        </div>        
    </div>
</div>

   
<div class="col-md-6 col-sm-6 form-group" id="rz1_box">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
    <div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
    <input name="razon_social_carry" type="text" required id="razon_social_carry" placeholder="Razon Social" style="text-transform: uppercase; width:80%;" value="<?php  echo $razon_social_pred;  ?>" class="form-control"   >  
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onMouseUp="buscar_rz_carry(<?php echo intval($_POST['idpedido']);?>);" title="Buscar Cliente" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></button>
        </div>                   
    </div>
</div>   

<div class="col-md-6 col-sm-6 form-group" >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input name="chapa_carry" id="chapa_carry" type="text" required  placeholder="Nombre" style="text-transform: uppercase; width:99%;" value="" class="form-control"  >                   
    </div>
</div> 

<div class="col-md-6 col-sm-6 form-group" >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input name="telefono_carry" id="telefono_carry" type="text"   placeholder="Telefono" style="text-transform: uppercase; width:99%;" value="" class="form-control"  >                   
    </div>
</div> 

<div class="col-md-6 col-sm-6 form-group" >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Observacion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input  name="observacion_carry" id="observacion_carry" type="text"  placeholder="Observacion" style="text-transform: uppercase; width:99%;" value="" class="form-control"  >                   
    </div>
</div> 
        

    <div class="clearfix"></div>
<br />

    <div class="form-group" id="regpedidobox">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-success"  name="regpedido" id="regpedido" onMouseUp="registrar_pedido(1);"><span class="fa fa-check-square-o"></span> Registrar</button>

        </div>
    </div>
    
 
    <div id="reimprimebox"></div>
    
   <div class="clearfix"></div>
<hr />

      
       <strong>Pendientes de Retiro: </strong>
    <br /> 
        <div style="overflow:auto;  min-height:368px; border:1px auto;">
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
          <thead>
            <tr>
              <th align="center" bgcolor="#F8FFCC"><strong>N&ordm;</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Nombre/Tel</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Razon Social/RUC</strong></th>
              <th align="center" bgcolor="#F8FFCC"><strong>Monto</strong></th>
              
              <th align="center" bgcolor="#F8FFCC"><strong>Registrado</strong></th>
              <th  align="center" bgcolor="#F8FFCC"></th>
            </tr>
            </thead>
            <tbody>
<?php while (!$rspedicu->EOF) {
    $totalventa = intval($rspedicu->fields['monto']);
    $delivery = intval($rspedicu->fields['delivery_costo']);
    $montoglobal = $totalventa + $delivery;
    ?>
            <tr style="background-color:#FFF;">
              <td align="center"><?php echo $rspedicu->fields['idtmpventares_cab'] ?>
              </td>
              
              <td align="center"><?php echo $rspedicu->fields['chapa'] ?><?php if ($rspedicu->fields['telefono'] > 0) { ?><br />
              0<?php echo $rspedicu->fields['telefono']; ?><?php } ?></td>
              <td align="center"><?php echo $rspedicu->fields['razon_social'] ?><br /><?php echo $rspedicu->fields['ruc'] ?></td>
              <td align="right"><?php echo formatomoneda($montoglobal); ?></td>
              
              <td align="center"><?php echo date("d/m/Y", strtotime($rspedicu->fields['fechahora'])); ?><br /><?php echo date("H:i", strtotime($rspedicu->fields['fechahora'])); ?></td>


            
            <td>
                
                <div class="btn-group">
                    <a href="javascript:void(0);" onMouseUp="cobrar_pedido('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>',<?php echo $montoglobal; ?>);" class="btn btn-sm btn-default" title="Cobrar" data-toggle="tooltip" data-placement="right"  data-original-title="Cobrar"><span class="fa fa-money"></span></a>
                    <a href="javascript:void(0);" onMouseUp="reimpimir_comp('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>');" class="btn btn-sm btn-default" title="Imprimir" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir"><span class="fa fa-print"></span></a>
                    <a href="javascript:void(0);" onMouseUp="transfer_mesa('<?php echo $rspedicu->fields['idtmpventares_cab'] ?>');" class="btn btn-sm btn-default" title="Transferir a Mesa" data-toggle="tooltip" data-placement="right"  data-original-title="Transferir a Mesa"><span class="fa fa-cutlery"></span></a>
                    <a href="cambiar_canal_pedido_carry_to_delivery.php?id=<?php echo $rspedicu->fields['idtmpventares_cab'] ?>" class="btn btn-sm btn-default" title="Transferir a Delivery" data-toggle="tooltip" data-placement="right"  data-original-title="Transferir a Delivery"><span class="fa fa-motorcycle"></span></a>
                    <a href="tmp_ventares_cab_del_vcaja.php?id=<?php echo $rspedicu->fields['idtmpventares_cab']; ?>"  class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            </tr>
<?php $rspedicu->MoveNext();
}  ?>
          </tbody>
        </table>
        </div>
      
      <div class="clearfix"></div>
<br />   <br />  


