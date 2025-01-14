 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

function redondeo_descuento($precio, $ceros = 1, $direccion = 'N')
{
    // temporal, ideal reemplazar a denominacion minima 500 por ejemplo
    if ($ceros == 3) {
        if (substr($precio, -3, 1) >= 5) {
            $ceros_ajustado = $ceros - 1;
            $precio = substr_replace($precio, 5, -3, 1);
            $precio = floor($precio / 10 ** $ceros_ajustado) * (10 ** $ceros_ajustado);
            $ceros = $ceros_ajustado;
        }
    }
    // direccion  A: hacia arriba // B: hacia abajo N: Normal
    if ($direccion == 'A') {
        $precio_redondeado = ceil($precio / 10 ** $ceros) * (10 ** $ceros);
    } elseif ($direccion == 'B') {
        $precio_redondeado = floor($precio / 10 ** $ceros) * (10 ** $ceros);
    } else {
        $precio_redondeado = round($precio / 10 ** $ceros) * (10 ** $ceros);
    }
    return $precio_redondeado;
}

//Traemos las preferencias para la empresa
$buscar = "Select usa_descuento from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_descuento = $rspref->fields['usa_descuento'];


$buscar = "Select obliga_motivos,usar_pin as obligapin from preferencias_caja limit 1";
$rsprefca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$obligar_motivos = trim($rsprefca->fields['obliga_motivos']);
$obligapin = trim($rsprefca->fields['obligapin']);


if ($usa_descuento != 'S') {
    echo "Tu usuario no tiene permitido realizar descuentos.";
    exit;
}

//Traemos las preferencias para la empresa
$buscar = "Select permite_desc_productos, desc_redondeo_ceros, desc_redondeo_dir  from preferencias_caja limit 1 ";
$rsprefcaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$permite_desc_productos = $rsprefcaj->fields['permite_desc_productos'];
$desc_redondeo_ceros = intval($rsprefcaj->fields['desc_redondeo_ceros']);
$desc_redondeo_dir = $rsprefcaj->fields['desc_redondeo_dir'];
if ($permite_desc_productos != 'S') {
    echo "Tu usuario no tiene permitido realizar descuentos sobre productos.";
    exit;
}
if ($desc_redondeo_dir == 'A') {
    $dir_txt = 'hacia ARRIBA';
} elseif ($desc_redondeo_dir == 'B') {
    $dir_txt = 'hacia ABAJO';
} else {
    $dir_txt = 'NORMAL (0,5 hacia arriba)';
}



$consulta = "
select tmp_ventares.idventatmp, productos.descripcion, tmp_ventares.cantidad, tmp_ventares.subtotal, tmp_ventares.precio,
tmp_ventares.descuento
from tmp_ventares 
inner join productos on productos.idprod_serial = tmp_ventares.idproducto
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros

    //$descuento=antisqlinyeccion($_POST['descuento'],"float");
    //$descuento=antisqlinyeccion($_POST['descuento_porc'],"float");


    // validaciones basicas
    $valido = "S";
    $errores = "";

    while (!$rs->EOF) {

        $idventatmp = intval($rs->fields['idventatmp']);

        if (floatval($_POST['porc_desc_'.$idventatmp]) > 100) {
            $valido = "N";
            $errores .= " - El campo descuento no puede ser mayor a 100%.<br />";
        }

        $rs->MoveNext();
    }
    $rs->MoveFirst();


    //Verificamos si el codigo fue recibido
    ///pin_acciones

    $codigo = md5(trim($_POST['cod_autorizacion']));



    $buscar = "Select obliga_motivos,usar_pin as obligapin from preferencias_caja limit 1";
    $rsprefca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $obligar_motivos = trim($rsprefca->fields['obliga_motivos']);
    $obligapin = trim($rsprefca->fields['obligapin']);

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

    // si todo es correcto actualiza
    if ($valido == "S") {
        //echo "VALIDO";exit;
        while (!$rs->EOF) {

            $idventatmp = $rs->fields['idventatmp'];
            $subtotal_sindesc = $rs->fields['precio'] * $rs->fields['cantidad'];
            //echo $subtotal_sindesc;exit;
            $descuento_porc = floatval($_POST['porc_desc_'.$idventatmp]) / 100;
            //echo $descuento_porc;exit;
            //$descuento_monto=$subtotal_sindesc*$descuento_porc;
            $descuento_monto = floatval($_POST['monto_desc_'.$idventatmp]);

            if ($desc_redondeo_ceros > 0) {
                $descuento_monto = redondeo_descuento($descuento_monto, $desc_redondeo_ceros, $desc_redondeo_dir);
            }

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

            $rs->MoveNext();
        }


        //$idventatmp=0;
        $idcab = 0;

        if ($obligapin == 'S') {
            $insertar = "Insert into mesas_acciones_log (fechahora,id_usuario,id_usu_pin,accion,clase,idatc,idmesa,porcen,monto,idtmpventares,idtmpventares_cab)
                values
                ('$ahora',$idusu,$usu_borra_cod,'DESC PROD - IDTMP:$idventatmp ',' DES',0,0,$descuento_porc,$descuento_monto,$idventatmp,0)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }



        header("location: carrito_descuento.php?ok=s");
        exit;

    } else {


    }

}


$consulta = "
select tmp_ventares.idventatmp, productos.descripcion, tmp_ventares.cantidad, tmp_ventares.subtotal, tmp_ventares.precio, tmp_ventares.descuento
from tmp_ventares 
inner join productos on productos.idprod_serial = tmp_ventares.idproducto
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$subtotal_sindesc = $rs->fields['precio'] * $rs->fields['cantidad'];
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function calcula_subtotal(descuento,id){
    var precio = $("#precio_unitario_"+id).val();
    var cantidad = $("#cantidad_"+id).val();
    var subtotal_sindesc = precio*cantidad;
    var subtotal = subtotal_sindesc-descuento;
    $("#subtotal_box_"+id).html(subtotal);
}
function calcula_desc(desc_porc,id){
    var precio = $("#precio_unitario_"+id).val();
    var cantidad = $("#cantidad_"+id).val();
    var subtotal_sindesc = precio*cantidad;
    var desc_porc_100 = desc_porc/100;
    var descuento = subtotal_sindesc*desc_porc_100;
    $("#monto_desc_"+id).val(descuento);
    calcula_subtotal(descuento,id);
}
function calcula_desc_mont(descuento_monto,id){
    var precio = $("#precio_unitario_"+id).val();
    var cantidad = $("#cantidad_"+id).val();
    var subtotal_sindesc = precio*cantidad;
    var desc_porc_100 = desc_porc/100;
    var desc_porc = descuento_monto/subtotal_sindesc*100;
    var descuento = descuento_monto;
    $("#porc_desc_"+id).val(desc_porc);
    calcula_subtotal(descuento,id);
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
                    <h2>Descuento sobre producto</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<?php if (trim($errores) == "" && $_GET['ok'] == 's') { ?>
<div class="alert alert-success alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Registrado Exitosamente!</strong><br />
</div>
<?php } ?>

<?php if (intval($desc_redondeo_ceros) > 0) { ?>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
</button>
<strong>AVISO:</strong><br />El redondeo del descuento esta activado, se redondeara a <?php echo $desc_redondeo_ceros; ?> ceros con direccion <?php echo  $dir_txt; ?> .
</div>
<?php } ?>


<p><a href="gest_ventas_resto_caja.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a></p>
<hr />

<form id="form1" name="form1" method="post" action="">

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Producto</th>

            <th align="center">Cantidad</th>
            <th align="center">Precio</th>

            <th align="center">Subtotal sin Descuento</th>
            <th align="center">% Desc</th>
            <th align="center">Monto Desc</th>
            <th align="center">Subtotal</th>

        </tr>
      </thead>
      <tbody>
<?php

$i = 1;
while (!$rs->EOF) {

    $idventatmp = $rs->fields['idventatmp'];
    $subtotal_sindesc = $rs->fields['cantidad'] * $rs->fields['precio'];
    $subtotal = $rs->fields['subtotal'];
    if (isset($_POST['monto_desc_'.$idventatmp])) {

        $monto_desc = floatval($_POST['monto_desc_'.$idventatmp]);
        $porc_desc = floatval($_POST['porc_desc_'.$idventatmp]);
    } else {

        $monto_desc = floatval($rs->fields['descuento']);
        $porc_desc = round((($monto_desc / $subtotal_sindesc) * 100), 0);
    }
    ?>
        <tr>

            <td align="left"><?php echo antixss($rs->fields['descripcion']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['cantidad']);  ?><input name="cantidad_<?php echo $idventatmp; ?>" id="cantidad_<?php echo $idventatmp; ?>" type="hidden" value="<?php echo $rs->fields['cantidad']; ?>"></td>
            <td align="right"><?php echo formatomoneda($rs->fields['precio']);  ?><input name="precio_unitario_<?php echo $idventatmp; ?>" id="precio_unitario_<?php echo $idventatmp; ?>" type="hidden" value="<?php echo $rs->fields['precio']; ?>"></td>
            <td align="right"><?php echo formatomoneda($subtotal_sindesc);  ?></td>
            <td align="right"><input name="porc_desc_<?php echo $idventatmp; ?>" id="porc_desc_<?php echo $idventatmp; ?>" type="text" value="<?php echo $porc_desc; ?>" style="text-align:right;" onKeyUp="calcula_desc(this.value,<?php echo $idventatmp; ?>);" onChange="calcula_desc(this.value,<?php echo $idventatmp; ?>);"  class="form-control"></td>
            <td align="right"><input name="monto_desc_<?php echo $idventatmp; ?>" id="monto_desc_<?php echo $idventatmp; ?>" type="text" value="<?php echo $monto_desc; ?>" style="text-align:right;" onKeyUp="calcula_desc_mont(this.value,<?php echo $idventatmp; ?>);" onChange="calcula_desc_mont(this.value,<?php echo $idventatmp; ?>);"  class="form-control"></td>
            <td align="right" id="subtotal_box_<?php echo $idventatmp; ?>"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
        </tr>
<?php

    $i++;
    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<input name="totitems" type="hidden" value="<?php echo $i; ?>">
<div class="clearfix"></div>
<br />
<?php if ($obligapin == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pin/ Cod. acciones * </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="password" name="cod_autorizacion" id="cod_autorizacion" value="" placeholder="Indicar PIN" class="form-control" required="required" >                    
    </div>
</div>


<?php } ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_ventas_resto_caja.php'"><span class="fa fa-ban"></span> Cancelar</button>               
    </div>
</div>
  
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

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
