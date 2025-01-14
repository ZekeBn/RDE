 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "134";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");

$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
}
//$fecha_inicio=date("Y-m-d");
$fecha_inicio = date("Y-m-d H:i:s", strtotime($rs->fields['inicio_registrado_el']));
$consulta = "
select *,
(SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) as grupo,
(SELECT nombre FROM medidas where id_medida = insumos_lista.idmedida) as medida,
(SELECT sum(disponible) FROM gest_depositos_stock_gral where idproducto = insumos_lista.idinsumo and iddeposito = $iddeposito and idempresa = $idempresa) as stock,
/*(
select sum(venta_receta.cantidad) as venta
from venta_receta 
inner join ventas on ventas.idventa = venta_receta.idventa
where 
venta_receta.idinsumo = insumos_lista.idinsumo  
and ventas.fecha >= '$fecha_inicio'
and (select iddeposito from gest_depositos where idsucursal = ventas.sucursal  and tiposala = 2) = $iddeposito
and ventas.estado <> 6
) as venta,*/
(select p1 from productos where idprod_serial = insumos_lista.idproducto and productos.borrado = 'N' and productos.idempresa = $idempresa) as pventa,
(select cantidad_contada from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as cantidad_contada
from insumos_lista 
where 
insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
and insumos_lista.idempresa = $idempresa
and insumos_lista.estado = 'A'
order by (SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) asc, descripcion asc
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['accion']) && ($_POST['accion'] >= 1 && $_POST['accion'] <= 3)) {
    $accion = intval($_POST['accion']);
    $sumavent = strtoupper(substr(trim($_POST['sumavent']), 0, 1));
    //echo "accion:".$accion."<br />";

    $consulta = "
    select * from conteo_detalles where idconteo = $idconteo
    ";
    $rs3 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //echo $consulta;
    //exit;
    while (!$rs3->EOF) {

        //foreach($_POST as $key => $value){
        //$idinsumo=intval(str_replace("cont_","",$key));
        $idinsumo = intval($rs3->fields['idinsumo']);
        //$cantidad=$value;
        $cantidad = floatval($rs3->fields['cantidad_contada']);
        $cantidad_contada = $cantidad;

        //echo $accion;
        //exit;

        if (trim($cantidad) != '' && $idinsumo > 0) {

            // por si hay texto en el campo cantidad
            $cantidad = floatval($cantidad);
            $cantidad_contada = $cantidad;

            // busca que exista el insumo
            $insumo = antisqlinyeccion($idinsumo, 'text');
            $buscar = "Select descripcion from insumos_lista where idinsumo=$idinsumo and idempresa = $idempresa";
            $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $descripcion = antisqlinyeccion($rsin->fields['descripcion'], 'text');

            // stock disponible
            $consulta = "
            select sum(disponible) as total_stock,
            /*(
            select sum(venta_receta.cantidad) as venta
            from venta_receta 
            inner join ventas on ventas.idventa = venta_receta.idventa
            where 
            venta_receta.idinsumo = gest_depositos_stock_gral.idproducto  
            and ventas.fecha >= '$fecha_inicio'
            and (select iddeposito from gest_depositos where idsucursal = ventas.sucursal and tiposala = 2) = $iddeposito
            and ventas.estado <> 6
            ) as venta,*/
            (
            select productos_sucursales.precio
                from productos 
            inner join insumos_lista on insumos_lista.idproducto  = productos.idprod_serial
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where 
            gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
            and productos.idprod_serial = insumos_lista.idproducto
            and productos.borrado = 'N' 
            and productos_sucursales.idsucursal = gest_depositos.idsucursal
            ) as pventa,
            (select costo from insumos_lista where insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto) as pcosto
            from gest_depositos_stock_gral 
            inner join gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito
            where 
            gest_depositos_stock_gral.idproducto = $insumo
            and gest_depositos_stock_gral.iddeposito = $iddeposito
            and gest_depositos_stock_gral.idempresa = $idempresa
            ";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $disponible = floatval($rsdisp->fields['total_stock']);
            $pventa = floatval($rsdisp->fields['pventa']);
            $pcosto = floatval($rsdisp->fields['pcosto']);

            $cantidad_sistema = $disponible;

            // busca si existe ese insumo en detalle para este conteo
            $consulta = "
            select * 
            from conteo_detalles 
            where 
            idconteo = $idconteo
            and idinsumo = $idinsumo
            and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo and idempresa = $idempresa)
            ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //calculos
            $venta = floatval($rsdisp->fields['venta']);
            $cantidad_contada = $cantidad;
            $cantidad_teorica = floatval($disponible);
            $cantidad_teorica_cv = $cantidad_teorica + $venta;
            $diferencia = $cantidad_contada - $cantidad_teorica;
            $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
            $cantidad_venta = "0";
            if ($sumavent == 'S') {
                $diferencia = $diferencia_cv;
                $cantidad_venta = $venta;
            }
            $precio_venta = $pventa;
            $precio_costo = $pcosto;
            $diferencia_pv = $diferencia * $precio_venta;
            $diferencia_pc = $diferencia * $precio_costo;
            //echo $diferencia;
            //if($diferencia > 0){
            //    $cantidad_aumentar=$diferencia;
            //    echo "<br />Aum:".$cantidad_aumentar;
            //}
            //if($diferencia < 0){
            //    $cantidad_descontar=$diferencia*-1;
            //    echo "<br />Desc:".$cantidad_descontar;
            //}

            //exit;

            //echo $diferencia_pc;exit;

            // si no existe inserta
            if (intval($rsex->fields['idinsumo']) == 0) {
                $consulta = "
                insert into conteo_detalles
                (idconteo, idinsumo, descripcion, cantidad_contada, idusu, ubicacion, lote, vto, fechahora, cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc)
                values
                ($idconteo, $idinsumo, $descripcion, $cantidad_contada, $idusu, $iddeposito, NULL, NULL, '$ahora', $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            } else {
                // si existe actualiza
                $consulta = "
                update conteo_detalles
                set
                    descripcion=$descripcion,
                    cantidad_contada=$cantidad_contada,
                    cantidad_sistema=$cantidad_sistema,
                    fechahora='$ahora',
                    precio_venta=$precio_venta, 
                    precio_costo=$precio_costo,
                    diferencia=$diferencia, 
                    diferencia_pv=$diferencia_pv,
                    diferencia_pc=$diferencia_pc
                where
                    idinsumo=$idinsumo
                    and idconteo=$idconteo
                    and    ubicacion=$iddeposito
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            // Guardar
            if ($accion == 1) {

                // estado guardado
                $consulta = "
                update conteo 
                set 
                estado = 2,
                ult_modif = '$ahora',
                sumoventa = '$sumavent'
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }

            // Finalizar sin afectar stock
            if ($accion == 2) {

                // estado finalizado
                $consulta = "
                update conteo 
                set 
                estado = 3,
                ult_modif = '$ahora',
                fecha_final = '$ahora',
                final_registrado_el = '$ahora',
                finalizado_por = $idusu
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                and fecha_final is null
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }




            // Finalizar afectando stock
            if ($accion == 3) {

                // busca el costo del insumo
                $consulta = "select * from insumos_lista where idinsumo = $idinsumo and idempresa = $idempresa";
                $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ult_costo = floatval($rsnom->fields['costo']);

                // descontar si es menor a cero, si es igual a 0 no debe hacer nada
                if ($diferencia < 0) {
                    $cantidad_descontar = $diferencia * -1;
                    descontar_stock_general($idinsumo, $cantidad_descontar, $iddeposito);
                    descuenta_stock_inv($idinsumo, $cantidad_descontar, $iddeposito);
                    movimientos_stock($idinsumo, $cantidad_descontar, $iddeposito, 6, '-', $idconteo, $fecha_inicio);
                }
                // agregar si es mayor a cero, si es igual a 0 no debe hacer nada
                if ($diferencia > 0) {
                    $cantidad_aumentar = $diferencia;
                    aumentar_stock_general($idinsumo, $cantidad_aumentar, $iddeposito);
                    aumentar_stock($idinsumo, $cantidad_aumentar, $ult_costo, $iddeposito);
                    movimientos_stock($idinsumo, $cantidad_aumentar, $iddeposito, 6, '+', $idconteo, $fecha_inicio);
                }

                // actualizar costos DEL CONTEO
                $consulta = "
                update conteo_detalles
                set 
                precio_costo = COALESCE((select insumos_lista.costo from insumos_lista where insumos_lista.idinsumo = conteo_detalles.idinsumo),0)
                WHERE
                idconteo = $idconteo
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // estado finalizado
                $consulta = "
                update conteo 
                set 
                estado = 3,
                fecha_final = '$ahora',
                final_registrado_el = '$ahora',
                finalizado_por = $idusu,
                afecta_stock = 'S'
                where
                idconteo = $idconteo
                and idempresa = $idempresa
                and fecha_final is null
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            }
        }
        //}
        $rs3->MoveNext();
    }

    // Finalizar sin afectar stock
    if ($accion == 2) {

        // actualizar costos DEL CONTEO
        $consulta = "
        update conteo_detalles
        set 
        precio_costo = COALESCE((select insumos_lista.costo from insumos_lista where insumos_lista.idinsumo = conteo_detalles.idinsumo),0)
        WHERE
        idconteo = $idconteo
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // estado finalizado
        $consulta = "
        update conteo 
        set 
        estado = 3,
        ult_modif = '$ahora',
        fecha_final = '$ahora',
        final_registrado_el = '$ahora',
        finalizado_por = $idusu
        where
        idconteo = $idconteo
        and idempresa = $idempresa
        and fecha_final is null
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    // redireccionar
    header("location: conteo_stock.php");
    exit;

}



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function calcular_dif(id){
    conteo_guarda_tmp(id);
}
function accionbtn(cod){
    $("#accion").val(cod);    
    $("#form1").submit();
    $("#submit_1").hide();
    $("#submit_2").hide();
    $("#submit_3").hide();
}
function conteo_guarda_tmp(id){
    var campo_cant = "cont_"+id;
    var campo_name = $('#'+campo_cant).attr('name');
    var idprod_s = campo_name.split("_");
    var idprod = idprod_s[1];
    //alert(idprod);
    
    var cantidad = $("#"+campo_cant).val();
        var parametros = {
                    "accion" : 1,
                    "cant" : cantidad,
                    "idprod" : idprod,
                    "id" : <?php echo $idconteo; ?>
                    
           };
          $.ajax({
                    data:  parametros,
                    url:   'conteo_guarda_tmp.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#dif_"+id).html('Guardando...');
                    },
                    success:  function (response) {
                        $("#dif_"+id).html(response);
                    }
            });    

}
function mantiene_session(){
    var f=new Date();
    cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
    var parametros = {
                "ses" : cad,
       };
      $.ajax({
                data:  parametros,
                url:   'mantiene_session.php',
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                    //alert(response);
                }
        });    
}
function buscar_producto_codbar(e){
    
    // que tecla presiono
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==13){
        var codbar = $("#codbar").val();
        var direccionurl='conteo_stock_filtra.php';        
        var parametros = {
          "codbar"   : codbar,
          "id"       : <?php echo $idconteo; ?>
        };
        $.ajax({          
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            beforeSend: function () {
                $("#filtroprod").html('Cargando...');                
            },
            success:  function (response) {
                $("#filtroprod").html(response);
                if (tecla==13){
                    $("#cont_1").focus();
                }
            }
        });
    }
}
</script>
<style>
.mt-1{
    margin-top: 20px; !important;
}
</style>
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
                    <h2>Conteo #<?php echo $rs->fields['idconteo']; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="conteo_importar.php?id=<?php echo $idconteo;  ?>" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
</p>
<hr />

<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
    <tr>
      <th><strong># Conteo</strong></th>
      <th><strong>Deposito</strong></th>
      <th><strong>Iniciado Por</strong></th>
      <th><strong>Estado</strong></th>
      </tr>
       </thead>
        </tbody>
    <tr>
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['usuario']; ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
      </tr>
  </tbody>
</table>
</div>

<div id="filtroprod">
<?php require_once("conteo_stock_filtra.php"); ?>
</div>

<div class="clearfix"></div>
<br />

<div class="form-group">
    <div class="col-md-12 col-sm-12 col-xs-12 text-center">
    
        <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit" id="submit1" class="btn btn-default" onmouseup="accionbtn(1);"><span class="fa fa-clock-o"></span> Continuar mas tarde</button>
       </div>
       
       <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit2" id="submit2" class="btn btn-success" onmouseup="accionbtn(2);"><span class="fa fa-ban"></span> Finalizar sin afectar stock</button>
       </div>
       
       <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit3" id="submit3" class="btn btn-success" onmouseup="accionbtn(3);"><span class="fa fa-check-square-o"></span> Finalizar y afectar stock</button>
       </div>
       
    </div>
</div>


<div class="clearfix"></div>
<br />
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
