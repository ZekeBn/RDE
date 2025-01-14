 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "21";
$submodulo = "412";
require_once("includes/rsusuario.php");

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (trim($_GET['hdesde']) == '' or trim($_GET['hhasta']) == '') {
    $hdesde = "00:00";
    $hhasta = "23:59";
} else {
    $hdesde = date("H:i", strtotime($_GET['hdesde']));
    $hhasta = date("H:i", strtotime($_GET['hhasta']));
}
$desde_completo = $desde." ".$hdesde.':00';
$hasta_completo = $hasta." ".$hhasta.':59';

if ($_GET['codigo_vrapida'] > 0) {
    $codigo_vrapida = antisqlinyeccion($_GET['codigo_vrapida'], "text");
    $consulta = "
    select idcliente from cliente where ruc = $codigo_vrapida and estado <> 6 limit 1
    ";
    $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rsvrap->fields['idcliente']);
    if ($idcliente == 0) {
        $consulta = "
        select idcliente from cliente where documento = $codigo_vrapida  and estado <> 6 limit 1
        ";
        $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rsvrap->fields['idcliente']);
    }
}

if (trim($_GET['MM_print']) != '') {

    $consulta = "";

    $consulta = "
    select tmp_ventares_cab.*, canal.canal,
    (select nombre from sucursales where idsucu = tmp_ventares_cab.idsucursal ) as sucursal,
    (select usuario from usuarios where idusu = tmp_ventares_cab.idusu) as registrado_por
    from tmp_ventares_cab
    inner join canal on canal.idcanal =  tmp_ventares_cab.idcanal
    where 
    tmp_ventares_cab.estado <> 6
    and (tmp_ventares_cab.idcanal = 1 or tmp_ventares_cab.idcanal = 3) 
    and tmp_ventares_cab.finalizado = 'S'
    and tmp_ventares_cab.registrado = 'N'
    and date(fechahora) = '$desde'
    and tmp_ventares_cab.idcanal = 1
    and tmp_ventares_cab.idclienteped = $idcliente
    order by date(tmp_ventares_cab.fechahora) desc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //$idcliente=intval($rs->fields['idcliente']);
    $idtmpventares_cab = intval($rs->fields['idtmpventares_cab']);
    $totreg = $rs->RecordCount();
    //echo $totreg;exit;
    if ($idtmpventares_cab > 0) {
        if ($totreg == 1) {



            /*
            $parametros_post=array(
                'pedido'           => $idtmpventares_cab,
                'idzona'           => 0, // zona costo delivery
                'idadherente'      => 0,
                'idservcom'        => 0, // servicio comida
                'banco'            => 0,
                'adicional'        => '', // numero de cheque, tarjeta, etc
                'condventa'        => 2, // credito o contado
                'mediopago'        => 7, // forma de pago  7 credito
                'fac_suc'          => '',
                'fac_pexp'         => '',
                'fac_nro'          => '',
                'domicilio'        => '',
                'llevapos'         => '',
                'cambiode'         => '',
                'observadelivery'  => '',
                'observacion'      => '',
                'mesa'             => 0,
                'canal'            => 1,
                'fin'              => 3,
                'idcliente'        => $idcliente,
                'monto_recibido'   => $rs->fields['monto'],
                'descuento'        => 0,
                'motivo_descuento' => '',
                'chapa'            => '',
                'montocheque'      => '',
                'idvendedor'       => '',
                'iddeposito'       => '',
                'idmotorista'      => '',
                'json'             => 'S'

            );


            $parametros_array=array(
                'url' => "http://".$rsco->fields['url_local']."/".'registrar_venta.php',
                'postdata' => $parametros_post
            );

            $res=abrir_url($parametros_array);
            $respuesta=json_decode($res['respuesta'],true);
            */

            //print_r($parametros_array);
            //print_r($respuesta);
            //exit;

            if ($idcliente > 0) {

                if (trim($respuesta['error']) == '') {
                    $idventa = intval($respuesta['idventa']);
                    //header("location: registrar_venta_ajx.php?vta=".$idventa."&tk=1");
                    header("location: registrar_venta_ajx.php?id=".$idtmpventares_cab."&tk=1&idcliente=$idcliente");
                    exit;
                } else {
                    $valido = "N";
                    $errores .= nl2br($respuesta['error']);
                }

            } else {
                $valido = "N";
                $errores .= "Cliente inexistente!";
            }



        } else {
            // no hacer nada, para que muestre abajo los registros
        }
    } else {
        $valido = "N";
        $errores .= "- No se encontraron pedidos para hoy con los datos suministrados.<br />";
    }



}



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function cambia_campo(campo){
    var ruc = $("#ruc").val();
    var doc = $("#documento").val();
    //alert($("#ruc").val().length);
    if(campo == 'ruc'){
        if(ruc.length > 1){
            $("#documento").val('');
        }
    }
    if(campo == 'documento'){
        if(doc.length > 1){
            $("#ruc").val('');
        }
    }
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Cobro Abreviado de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    Se cobran las cuentas a credito.
                

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
Reimprime la ultima venta del cliente indicado de la fecha actual.
<div class="clearfix"></div>
<hr />

<form id="form1" name="form1" method="get" action="">



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="codigo_vrapida" id="codigo_vrapida" value="<?php  if (isset($_GET['codigo_vrapida'])) {
        echo htmlentities($_GET['codigo_vrapida']);
    } ?>" placeholder="Codigo" class="form-control" autofocus   />                    
    </div>
</div>




<div class="clearfix"></div>
<br />


    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-print"></span> Imprimir</button>

        </div>
    </div>
    
    <input type="hidden" name="MM_print" value="<?php echo date("YmdHis").rand(1, 100); ?>" />


<br />
</form>        
<div class="clearfix"></div>
<br />
<?php if (trim($_GET['MM_print']) != '') { ?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Pedido N#</th>
            <th align="center">Fechahora</th>
            <th align="center">Canal</th>
            <th align="center">Razon social</th>
            <th align="center">Ruc</th>
            <th align="center">Cliente Carry Out/Delivery</th>
            <th align="center">Observacion</th>
            <th align="center">Monto</th>

            <th align="center">Sucursal</th>


            <th align="center">Registrado Por</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $idtmpventares_cab = intval($rs->fields['idtmpventares_cab']);
    $idclienteped = intval($rs->fields['idclienteped']);
    ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="registrar_venta_ajx.php?id=<?php echo $idtmpventares_cab; ?>&tk=1&idcliente=<?php echo $idclienteped ?>" class="btn btn-sm btn-default" title="Cobrar" data-toggle="tooltip" data-placement="right"  data-original-title="Cobrar"><span class="fa fa-money"></span></a>

                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idtmpventares_cab']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>

            <td align="center"><?php
            $idcanal = $rs->fields['idcanal'];
    if ($idcanal == 1) { // carry out
        echo antixss($rs->fields['chapa']);
    }
    if ($idcanal == 3) { // delivery
        echo antixss($rs->fields['nombre_deliv']).' '.antixss($rs->fields['apellido_deliv']);
    }
    ?></td>


            <td align="center"><?php echo antixss($rs->fields['observacion']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['monto']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>


            



        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
 <?php } ?>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
