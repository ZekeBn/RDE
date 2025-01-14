<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
require_once("../includes/funciones_carrito.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";

$ver_pedido = intval($_POST['ver_pedido']);

if (!isset($_SESSION)) {
    session_start();
}


$idatc = intval($_SESSION['idatc']);
$parametros_array = [
    "id_atc" => $idatc
];
$rs_mesas_atc = verificar_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
if ($idatc == 0) {
    $data = ["success" => false,"error" => "Error: la mesa no esta activa.","logout" => true ];
    echo json_encode($data);
    exit;

}
/*
// // Ejemplo de uso
$id_mesa = $_SESSION['id_mesa'];
if($id_mesa ==0 ){
    header("Location: ./mesas_qr.php");
}

$parametros_array=array(
  "id_mesa" => $id_mesa
);
$rs_mesas_atc = buscar_mesa_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
if($idatc ==0 ){
  header("Location: ./mesas_qr.php");exit;
}
*/
//echo $idatc;exit;



?>


<?php if ($idatc > 0) {?>
<style>
    h2 {
        margin-bottom: 0px;
        margin-top: 25px;
        text-align: center;
        font-weight: 200;
        font-size: 19px;
        font-size: 1.2rem;
    }


    


    .thin {
        font-weight: 400;
    }
    .small {
        font-size: 12px;
        font-size: 0.8rem;
    }


    .window {
        background: #fff;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        box-shadow: 0px 15px 50px 10px rgba(0, 0, 0, 0.2);
        border-radius: 30px;
        z-index: 10;

        width: 100%;
            height: 100%;
            display: block;
    }
    .order-info {
        padding-left: 25px;
        padding-right: 25px;
        box-sizing: border-box;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-pack: center;
        -webkit-justify-content: center;
        -ms-flex-pack: center;
        justify-content: center;
        position: relative;
        
        width: 100%;
            height: auto;
            padding-bottom: 100px;
    }
    .price {
        bottom: 0px;
        position: absolute;
        right: 0px;
        color: #48d;
    }
    .order-table td:first-of-type {
        width: 25%;
    }
    .order-table {
        position: relative;
        width: 100%;
    }
    .line {
        height: 1px;
        width: 100%;
        margin-top: 10px;
        margin-bottom: 10px;
        background: #ddd;
    }
    .order-table td:last-of-type {
        vertical-align: top;
        padding-left: 25px;
    }
    .order-info-content {
        table-layout: fixed;
        width: 100%;
    }
    .full-width {
        width: 100%;
    }
    
    .total {
        margin-top: 25px;
        font-size: 20px;
        font-size: 1.3rem;
        position: absolute;
        bottom: 30px;
        right: 27px;
        left: 35px;
    }
    .dense {
        line-height: 1.2em;
        font-size: 16px;
        font-size: 1rem;
    }






    @media (max-width: 600px) {
        
        .order-info {
            width: 100%;
            height: auto;
            padding-bottom: 100px;
            border-radius: 0px;
        }
        
        
    }
    
</style>





<div id="pedidos" onclick="<?php if ($ver_pedido != 1) {
    echo "agregarSombra(2)";
} ?>" class="<?php if ($ver_pedido == 0) {
    echo "black_text boton-body";
} ?> ">

<?php if ($ver_pedido == 1) { ?>

<div class='window' >
   <div class='order-info'>
      <div class='order-info-content'>
         <h2 style="font-weight:bold;font-size:14px;">Productos cargados a la mesa</h2>

         <div class='line'></div>
<p><a href="#" onclick="<?php if ($ver_pedido == 1) {
    echo "agregarSombra(5)";
} else {
    echo "agregarSombra(2)";
}?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
            <?php

           /* $parametros_array=array(
                "idatc" => $idatc
            );
            $res = buscar_tmp_venta_cab($parametros_array);
            $idpedido = $res["idpedido"];*/
            // echo $idpedido;exit;
            $parametros_array = [
              "estado_pedido" => 'R',
              //"idpedido" => $idpedido,
              "idatc" => $idatc
            ];//idatc
    $carrito_detalles = carrito_muestra_mesa($parametros_array);


    foreach ($carrito_detalles as $carrito_detalle) {
        if ($carrito_detalle['idtipoproducto'] != 5) {
            ?>
                <table class='order-table'>
                    <tbody>
                        <tr>  
                            <td>
                                <br> <span class='thin'>


                                <?php

                                        echo  '<strong>'.$carrito_detalle['descripcion'].'</strong>';
            if (trim($carrito_detalle['observacion']) != '') {
                echo  "<br />&nbsp;&nbsp;( ! ) OBS: ".$carrito_detalle['observacion'];
            }
            //print_r($carrito_detalle['agregados']);



            ?>




                                </span>
                                <br>
                                <div  >
                                    <div style="display:inline" style="color:#808080;">
                                        <small class="fa fa-shopping-cart"></small>
                                        <span style="color:#808080;">
                                        Cant: <?php echo  formatomoneda($carrito_detalle['cantidad'], 4, 'N'); ?><?php  if ($carrito_detalle['idmedida'] != 4 && $carrito_detalle['idtipoproducto'] == 1) {?></a><?php } ?>
				
                                     &nbsp;-&nbsp;P. Unit: Gs.
                                        <?php echo  formatomoneda($carrito_detalle['precio_unitario_con_extras'], 2, 'N'); ?>
										</span>
                                     </div>
                                </div>
								<!--<span class='thin small'>-->
								<span class='thin'>
                                <?php

                // combinado extendido
                $combinados = $carrito_detalle['combinado'];
            $ic = 1;
            foreach ($combinados as $combinado) {
                echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado['descripcion']);
                $ic++;
            }



            // combo
            $combos = $carrito_detalle['combo'];
            $tam = count($combos);
            $ic = 1;
            if ($tam > 0) {
                echo "<br>Detalles";
            }
            foreach ($combos as $combo) {
                echo "<br /> ".formatomoneda($combo['cantidad'])."  ".Capitalizar($combo['descripcion']);
                $ic++;
            }
            // combinado viejo
            $combinado_vs = $carrito_detalle['combinado_v'];
            $ic = 1;
            foreach ($combinado_vs as $combinado_v) {
                echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado_v['descripcion']);
                $ic++;
            }


            ?>
                                

                                        <?php
                        // agregados
                        $carrito_agregados = $carrito_detalle['agregados'];
            $iag = 1;
            foreach ($carrito_agregados as $carrito_agregado) {
                echo "<div style='
                                                '><div style='display:inline'><small class='fa fa-plus'></small> ".formatomoneda($carrito_agregado['cantidad']).'  '.trim($carrito_agregado['alias'], 36)."</div> ";
                echo "<div style='display:inline;'>( Gs. ".formatomoneda($carrito_agregado['precio_adicional'])." ) </div></div>";
                $iag++;
            }
            // sacados
            $carrito_sacados = $carrito_detalle['sacados'];
            $iag = 1;
            foreach ($carrito_sacados as $carrito_sacado) {
                echo "<br />&nbsp;&nbsp;&nbsp;<small class='fa fa-minus'></small> SIN ".trim($carrito_sacado['alias'], 36)."";
                $iag++;
            }
            $totalacum += $carrito_detalle['subtotal_con_extras'];
            $estilo_entrada = "";
            $estilo_fondo = "";
            if ($carrito_detalle['tipo_plato'] == 'E') {
                $estilo_entrada = ' style="background-color:#82E9FF;" ';
            }
            if ($carrito_detalle['tipo_plato'] == 'F') {
                $estilo_fondo = ' style="background-color:#82E9FF;" ';
            }
            if ($carrito_detalle['idventatmp'] > 0) {
                $tipo_borra = 'onClick="borrar_item('.$carrito_detalle['idventatmp'].','.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $carrito_detalle['descripcion'])).'\');"';
                $tipo_personaliza = 'editareceta.php?idvt='.$carrito_detalle['idventatmp'];
                $accion_entrada = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'E\');"';
                $accion_fondo = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'F\');"';
            } else {
                $tipo_borra = 'onClick="borrar('.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $des)).'\');"';
                $tipo_personaliza = 'editareceta.php?id='.$carrito_detalle['idproducto'];
                $accion_entrada = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'E\');"';
                $accion_fondo = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'F\');"';
            }
            ?>
                                </span>
                                <br> 
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class='price' style="color:#2e2a2acf;"><strong>Gs. <?php echo  formatomoneda($carrito_detalle['subtotal_con_extras'], 2, 'N'); ?>.</strong></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class='line'></div>

            <?php } // if($carrito_detalle['idtipoproducto'] != 5){?>
            <?php } ?>

         <div class='total'>
            <span style='float:left; font-size:14px;'>
               <strong>TOTAL</strong>
            </span>
            <span style='float:right; text-align:right;color:#70AE6E; font-size:14px;'>
               <strong>Gs. <?php echo formatomoneda($totalacum, 0); ?></strong>
            </span>
         </div>
      </div>
   </div>
</div>
<br />
<br />
    
    
<?php } else { ?>
    <i class="fas fa-cutlery "></i>
    <h3 class="">Ver Consumo</h3>
    <p class="">
    Visualizar los productos cargados a tu mesa.
    </p>
<?php } ?>
</div>

<?php } ?>

