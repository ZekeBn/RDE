 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "340";
require_once("includes/rsusuario.php");



$idempresa = 1;

// consulta a la tabla
$consulta = "
select * 
from preferencias 
where 
idempresa = $idempresa
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idempresa = intval($rs->fields['idempresa']);
if ($idempresa == 0) {
    header("location: preferencias.php");
    exit;
}

// consulta a la tabla
$consulta = "
select idprefecaja, usa_motorista, obliga_motorista, idempresa, usa_ventarapida, filtros_reimp, linea_auto_creacliente,
avisar_quedanfac, fantasia_sucursal_fact, leyenda_termico, pie_final_factura, texto_matriz_fact,
permite_desc_factura, permite_desc_productos, desc_redondeo_dir, desc_redondeo_ceros, imprime_ticket_venta, leyenda_credito,
desglosa_impuesto_multiple, usa_canalventa, obliga_cierremesa_caja, obliga_carry_caja, obliga_deliv_caja, reimpresion_muestra_cant,
leyenda_credito_a4, leyenda_contado_a4, mail_cierrecaja, mails_cierre_caja_csv, imp_comanda_borra,     usa_tk_prod, sucursal_tablet,
tipo_a4, vencimiento_credito, agregado_usaprecioprod, monto_endeliverycaja, permite_reimpresion_masiva,
caja_compartida, usar_pin,obliga_motivos,pulsera_vcaja
from preferencias_caja
where 
idempresa = $idempresa
limit 1
";
$rspv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idprefecaja = intval($rspv->fields['idprefecaja']);
$idempresa = 1;
if ($idprefecaja == 0) {

    // si no hay registros crea
    $consulta = "
    INSERT INTO `preferencias_caja` (`idprefecaja`, `permite_desc_factura`, `permite_desc_productos`, `usar_pin`, 
    `permite_borrar_prod`, `permite_rechazo_pedido`, `asignar_venta_vendedor`, `idempresa`, `idsucursal`, 
    `muestra_formapago`, `bloquea_ticket_venta`, `cod_plu_desde`, `cod_plu_cantdigit`, `cant_plu_entero_desde`,
    `cant_plu_entero_cantdigit`, `cant_plu_decimal_desde`, `cant_plu_decimal_cantdigit`, `redondear_subtotal`,
    `redondeo_ceros`, `redondear_direccion`, `usa_motorista`, `obliga_motorista`, `usa_canalventa`,
    `sucursal_tablet`, `avisar_quedanfac`,mail_cierrecaja,usa_tk_prod,usar_pin,obliga_motivos) 
    VALUES
    (1, 'N', 'N', 'N', 'S', 'S', 'S', 1, 1, 'S', 'N', 
    3, 4, 7, 3, 10, 3, 'S', 2, 'B', 'S', 'N', 'S', 'N', 200,'N','N','S','S');
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    header("location: preferencias_ventas.php");
    exit;
}


// consulta a la tabla    preferencias mesas
$consulta = "
select idprefmesa, usar_cod_mozo
from mesas_preferencias
limit 1
";
$rspm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idprefmesa = intval($rspm->fields['idprefmesa']);
if ($idprefmesa == 0) {

    // si no hay registros crea
    $consulta = "
    INSERT INTO `mesas_preferencias` 
    (`idprefmesa`, `idestadopref`, `usar_iconos`, `usar_cod_mozo`, `usar_cod_adm`, `idempresa`, porc_servicio) 
    VALUES
    (1, 1, 'N', 'N', 'S', 1, 0);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    header("location: preferencias_ventas.php");
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $usapin = antisqlinyeccion($_POST['usar_pin_acciones'], "text");
    $obligamotivos = antisqlinyeccion($_POST['obliga_motivos_acciones'], "text");
    $factura_obliga = antisqlinyeccion($_POST['factura_obliga'], "text");
    $idtipoiva_venta_pred = antisqlinyeccion($_POST['idtipoiva_venta_pred'], "int");
    $alerta_ventas = antisqlinyeccion($_POST['alerta_ventas'], "text");

    $ventas_nostock = antisqlinyeccion($_POST['ventas_nostock'], "int");
    $usa_listaprecio = antisqlinyeccion($_POST['usa_listaprecio'], "text");
    $usa_canalventa = antisqlinyeccion($_POST['usa_canalventa'], "text");
    $permite_delivery_sinitem = antisqlinyeccion($_POST['permite_delivery_sinitem'], "text");
    $imprime_ambos = antisqlinyeccion($_POST['imprime_ambos'], "int");
    $obliga_motorista = antisqlinyeccion($_POST['obliga_motorista'], "text");
    $usa_motorista = antisqlinyeccion($_POST['usa_motorista'], "text");
    $usa_ventarapida = antisqlinyeccion($_POST['usa_ventarapida'], "text");
    $filtros_reimp = antisqlinyeccion($_POST['filtros_reimp'], "text");
    $usar_cod_mozo = antisqlinyeccion($_POST['usar_cod_mozo'], "text");
    $borrar_ped = antisqlinyeccion($_POST['borrar_ped'], "text");
    $borrar_ped_cod = antisqlinyeccion($_POST['borrar_ped_cod'], "text");
    $linea_auto_creacliente = antisqlinyeccion(floatval($_POST['linea_auto_creacliente']), "float");
    $muestra_stock_ven = antisqlinyeccion($_POST['muestra_stock_ven'], "text");
    $avisar_quedanfac = antisqlinyeccion($_POST['avisar_quedanfac'], "int");
    $fantasia_sucursal_fact = antisqlinyeccion($_POST['fantasia_sucursal_fact'], "text");
    $muestra_fantasia_fac = antisqlinyeccion($_POST['muestra_fantasia_fac'], "text");
    $muestra_actividad_fac = antisqlinyeccion($_POST['muestra_actividad_fac'], "text");
    $leyenda_termico = antisqlinyeccion($_POST['leyenda_termico'], "text");
    $pie_final_factura = antisqlinyeccion($_POST['pie_final_factura'], "text");
    $texto_matriz_fact = antisqlinyeccion($_POST['texto_matriz_fact'], "text");
    $permite_desc_factura = antisqlinyeccion($_POST['permite_desc_factura'], "text");
    $permite_desc_productos = antisqlinyeccion($_POST['permite_desc_productos'], "text");
    $desc_redondeo_dir = antisqlinyeccion($_POST['desc_redondeo_dir'], "text");
    $desc_redondeo_ceros = antisqlinyeccion($_POST['desc_redondeo_ceros'], "float");
    $imprime_ticket_venta = antisqlinyeccion($_POST['imprime_ticket_venta'], "text");
    $leyenda_credito = antisqlinyeccion($_POST['leyenda_credito'], "text");
    $agrega_delivery_auto = antisqlinyeccion($_POST['agrega_delivery_auto'], "text");
    $desglosa_impuesto_multiple = antisqlinyeccion($_POST['desglosa_impuesto_multiple'], "text");
    $obliga_cierremesa_caja = antisqlinyeccion($_POST['obliga_cierremesa_caja'], "text");
    $obliga_carry_caja = antisqlinyeccion($_POST['obliga_carry_caja'], "text");
    $obliga_deliv_caja = antisqlinyeccion($_POST['obliga_deliv_caja'], "text");
    $reimpresion_muestra_cant = antisqlinyeccion($_POST['reimpresion_muestra_cant'], "int");
    $leyenda_credito_a4 = antisqlinyeccion($_POST['leyenda_credito_a4'], "text");
    $leyenda_contado_a4 = antisqlinyeccion($_POST['leyenda_contado_a4'], "text");
    $mail_cierrecaja = antisqlinyeccion($_POST['mail_cierrecaja'], "text");
    $mails_cierre_caja_csv = antisqlinyeccion(strtolower($_POST['mails_cierre_caja_csv']), "textbox");
    $imp_comanda_borra = antisqlinyeccion($_POST['imp_comanda_borra'], "text");
    $usa_vendedor = antisqlinyeccion($_POST['usa_vendedor'], "text");
    $imp_vales_productos = antisqlinyeccion($_POST['imp_vales'], "text");
    $usa_factura_a4_auto = antisqlinyeccion($_POST['usa_factura_a4_auto'], "text");
    $tipo_a4 = antisqlinyeccion($_POST['tipo_a4'], "text");
    $usa_adherente = antisqlinyeccion($_POST['usa_adherente'], "text");
    $sucursal_tablet = antisqlinyeccion($_POST['sucursal_tablet'], "text");
    $vencimiento_credito = antisqlinyeccion($_POST['vencimiento_credito'], "text");
    $agregado_usaprecioprod = antisqlinyeccion($_POST['agregado_usaprecioprod'], "text");
    $tiempo_seguro_borrado = antisqlinyeccion($_POST['tiempo_seguro'], "int");
    $pdf_en_reimpresion_caja = antisqlinyeccion($_POST['pdf_en_reimpresion_caja'], "text");
    $monto_endeliverycaja = antisqlinyeccion($_POST['monto_endeliverycaja'], "text");
    $permite_reimpresion_masiva = antisqlinyeccion($_POST['permite_reimpresion_masiva'], "text");
    $caja_compartida = antisqlinyeccion($_POST['caja_compartida'], "text");
    $permite_reimpresion_centralped = antisqlinyeccion($_POST['permite_reimpresion_centralped'], "text");
    $pulsera_vcaja = antisqlinyeccion($_POST['pulsera_vcaja'], "text");
    $permite_cambiar_canal = antisqlinyeccion($_POST['permite_cambiar_canal'], "text");
    $permite_editar_pedido = antisqlinyeccion($_POST['permite_editar_pedido'], "text");
    $cambioformapago_muestra_cant = antisqlinyeccion($_POST['cambioformapago_muestra_cant'], "int");
    $barcode_muestrainfo = antisqlinyeccion($_POST['barcode_muestrainfo'], "text");

    if (trim($_POST['mail_cierrecaja']) == 'S') {
        if (trim($_POST['mails_cierre_caja_csv']) == '') {
            $valido = "N";
            $errores .= " - Debe cargar al menos un correo si se activa el mail al cierre de caja.<br />";
        } else {
            // recorre cada email y valida y  tambien elimina espacios al comienzo y final de cada email
            $email_pos = trim($_POST['mails_cierre_caja_csv']);
            $tot_mail = 0;
            if (trim($email_pos) != '') {
                $emails_ar = explode(";", trim($email_pos));
                $emails_new = '';
                foreach ($emails_ar as $email) {
                    $email_se = trim($email);
                    if ($email_se != '') {
                        $emails_new .= $email_se . ';';

                        if (!filter_var($email_se, FILTER_VALIDATE_EMAIL)) {
                            $valido = "N";
                            $errores .= " - El email '" . htmlentities($email_se) . "' no es un correo válido.<br />";
                        } else {
                            $tot_mail++;
                        }

                    }

                }
                // Eliminar el último ';' de $emails_new después de aplicar trim
                $emails_new = trim(substr($emails_new, 0, -1));
                if ($tot_mail > 3) {
                    $valido = "N";
                    $errores .= " - Solo se permite hasta un maximo de 3 emails.<br />";
                }

            }
            $emails = strtolower(antisqlinyeccion(trim($emails_new), "text"));
        }
    }

    if (trim($_POST['factura_obliga']) == '') {
        $valido = "N";
        $errores .= " - El campo acepta ticket no puede estar vacio.<br />";
    }
    if (trim($_POST['idtipoiva_venta_pred']) == '') {
        $valido = "N";
        $errores .= " - El campo tipo iva venta predeterminado no puede estar vacio.<br />";
    }

    if (intval($_POST['ventas_nostock']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar si permite o no vender sin stock.<br />";
    }
    if (intval($_POST['reimpresion_muestra_cant']) < 0) {
        $valido = "N";
        $errores .= " - El campo reimpresion_muestra_cant no puede ser menor a cero.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        // por seguridad
        $consulta = "    update preferencias set idempresa = 1;";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "    update preferencias_caja set idempresa = 1;";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "    update mesas_preferencias set idempresa = 1;";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $consulta_pref = "
        update preferencias
        set
            tiempo_borrado_pedidos=$tiempo_seguro_borrado,
            factura_obliga=$factura_obliga,
            idtipoiva_venta_pred=$idtipoiva_venta_pred,
            alerta_ventas=$alerta_ventas,
            ventas_nostock=$ventas_nostock,
            usa_listaprecio=$usa_listaprecio,
            permite_delivery_sinitem=$permite_delivery_sinitem,
            imprime_ambos=$imprime_ambos,
            borrar_ped=$borrar_ped,
            borrar_ped_cod=$borrar_ped_cod,
            muestra_stock_ven=$muestra_stock_ven,
            muestra_fantasia_fac=$muestra_fantasia_fac,
            muestra_actividad_fac=$muestra_actividad_fac,
            agrega_delivery_auto=$agrega_delivery_auto,
            usa_vendedor=$usa_vendedor,
            usa_factura_a4_auto=$usa_factura_a4_auto,
            usa_adherente=$usa_adherente
        where
            idempresa = $idempresa
        ";
        $conexion->Execute($consulta_pref) or die(errorpg($conexion, $consulta_pref));

        $consulta_prefcaj = "
        update preferencias_caja
        set
            usar_pin=$usapin,
            obliga_motivos=$obligamotivos,
            usa_motorista=$usa_motorista,
            obliga_motorista=$obliga_motorista,
            usa_ventarapida=$usa_ventarapida,
            filtros_reimp=$filtros_reimp,
            linea_auto_creacliente=$linea_auto_creacliente,
            avisar_quedanfac=$avisar_quedanfac,
            fantasia_sucursal_fact=$fantasia_sucursal_fact,
            leyenda_termico=$leyenda_termico,
            pie_final_factura=$pie_final_factura,
            texto_matriz_fact=$texto_matriz_fact,
            permite_desc_factura=$permite_desc_factura,
            permite_desc_productos=$permite_desc_productos,
            desc_redondeo_dir=$desc_redondeo_dir,
            desc_redondeo_ceros=$desc_redondeo_ceros,
            imprime_ticket_venta=$imprime_ticket_venta,
            leyenda_credito=$leyenda_credito,
            desglosa_impuesto_multiple=$desglosa_impuesto_multiple,
            usa_canalventa=$usa_canalventa,
            obliga_cierremesa_caja=$obliga_cierremesa_caja,
            obliga_carry_caja=$obliga_carry_caja,
            obliga_deliv_caja=$obliga_deliv_caja,
            reimpresion_muestra_cant=$reimpresion_muestra_cant,
            leyenda_credito_a4=$leyenda_credito_a4,
            leyenda_contado_a4=$leyenda_contado_a4,
            mail_cierrecaja=$mail_cierrecaja,
            mails_cierre_caja_csv=$mails_cierre_caja_csv,
            imp_comanda_borra=$imp_comanda_borra,
            usa_tk_prod=$imp_vales_productos,
            sucursal_tablet=$sucursal_tablet,
            tipo_a4=$tipo_a4,
            vencimiento_credito=$vencimiento_credito,
            agregado_usaprecioprod=$agregado_usaprecioprod,
            pdf_en_reimpresion_caja=$pdf_en_reimpresion_caja,
            monto_endeliverycaja=$monto_endeliverycaja,
            permite_reimpresion_masiva=$permite_reimpresion_masiva,
            caja_compartida=$caja_compartida,
            permite_reimpresion_centralped=$permite_reimpresion_centralped,
            pulsera_vcaja=$pulsera_vcaja,
            permite_cambiar_canal=$permite_cambiar_canal,
            permite_editar_pedido=$permite_editar_pedido,
            cambioformapago_muestra_cant=$cambioformapago_muestra_cant,
            barcode_muestrainfo=$barcode_muestrainfo
        where
            idempresa = $idempresa
        ";
        $conexion->Execute($consulta_prefcaj) or die(errorpg($conexion, $consulta_prefcaj));

        $consulta_mesa = "
        update mesas_preferencias
        set
            usar_cod_mozo=$usar_cod_mozo
        where
        idempresa = $idempresa
        ";
        $conexion->Execute($consulta_mesa) or die(errorpg($conexion, $consulta_mesa));

        $consulta_pref = antisqlinyeccion($consulta_pref, "textbox");
        $consulta_prefcaj = antisqlinyeccion($consulta_prefcaj, "textbox");
        $consulta_mesa = antisqlinyeccion($consulta_mesa, "textbox");

        $consulta = "
        INSERT INTO `preferencias_log_temporal`
        (`txt_pref`, `txt_prefcaja`, `txt_prefmes`, `log_registrado_por`, `log_registrado_el`) 
        VALUES 
        ($consulta_pref,$consulta_prefcaj,$consulta_mesa,$idusu,'$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        /*
        cuando se usa esta preferencias de codigos mozo luego se deben crear los codigos en el modulo "codigos de  mozos" #253
        */



        header("location: preferencias_ventas.php?ok=1");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
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
                    <h2>Preferencias Ventas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (intval($_GET['ok']) == 0) { ?>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">



<?php
$consulta = "
select idfranquicia from empresas limit 1
";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
select super, franq_m from usuarios where idusu = $idusu limit 1
";
    $rsusuario = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si no es una franquicia
    if (intval($rsemp->fields['idfranquicia']) == 0) {
        $muestra = "S";
        // si es una franquicia
    } else {
        $muestra = "N";
        if ($rsusuario->fields['franq_m'] == 'S') {
            $muestra = "S";
        }
        if ($rsusuario->fields['super'] == 'S') {
            $muestra = "S";
        }

    }

    $muestra = "S";
    if ($muestra == 'S') {
        ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Ticket *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['factura_obliga'])) {
    $value_selected = htmlentities($_POST['factura_obliga']);
} else {
    $value_selected = $rs->fields['factura_obliga'];
}
        // opciones
        $opciones = [
            'SI' => 'N',
            'NO' => 'S'
        ];
        // parametros
        $parametros_array = [
            'nombre_campo' => 'factura_obliga',
            'id_campo' => 'factura_obliga',

            'value_selected' => $value_selected,

            'pricampo_name' => 'Seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' required="required" ',
            'autosel_1registro' => 'S',
            'opciones' => $opciones

        ];

        // construye campo
        echo campo_select_sinbd($parametros_array);?>
    </div>
</div>
<?php } ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite vender sin stock *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

        // valor seleccionado
        if (isset($_POST['ventas_nostock'])) {
            $value_selected = htmlentities($_POST['ventas_nostock']);
        } else {
            $value_selected = $rs->fields['ventas_nostock'];
        }
    // opciones
    $opciones = [
        'SI' => '1',
        'NO' => '2'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'ventas_nostock',
        'id_campo' => 'ventas_nostock',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>

    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo iva venta por Defecto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // consulta
    $consulta = "
    SELECT idtipoiva, iva_porc, iva_describe
    FROM tipo_iva
    where
    estado = 1
    and hab_venta = 'S'
    order by iva_porc desc
     ";

    // valor seleccionado
    if (isset($_POST['idtipoiva_venta_pred'])) {
        $value_selected = htmlentities($_POST['idtipoiva_venta_pred']);
    } else {
        $value_selected = $rs->fields['idtipoiva_venta_pred'];
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idtipoiva_venta_pred',
        'id_campo' => 'idtipoiva_venta_pred',

        'nombre_campo_bd' => 'iva_describe',
        'id_campo_bd' => 'idtipoiva',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Alerta ventas </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="alerta_ventas" id="alerta_ventas" value="<?php  if (isset($_POST['alerta_ventas'])) {
        echo htmlentities($_POST['alerta_ventas']);
    } else {
        echo htmlentities($rs->fields['alerta_ventas']);
    }?>" placeholder="Alerta ventas" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usa Lista de Precios *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// valor seleccionado
if (isset($_POST['usa_listaprecio'])) {
    $value_selected = htmlentities($_POST['usa_listaprecio']);
} else {
    $value_selected = $rs->fields['usa_listaprecio'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_listaprecio',
        'id_campo' => 'usa_listaprecio',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>

    </div>
</div>
    
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usa Canal Venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// valor seleccionado
if (isset($_POST['usa_canalventa'])) {
    $value_selected = htmlentities($_POST['usa_canalventa']);
} else {
    $value_selected = $rspv->fields['usa_canalventa'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_canalventa',
        'id_campo' => 'usa_canalventa',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>

    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Imprime ticket con la Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['imprime_ambos'])) {
    $value_selected = htmlentities($_POST['imprime_ambos']);
} else {
    $value_selected = $rs->fields['imprime_ambos'];
}
    // opciones
    $opciones = [
        'SI' => '1',
        'NO' => '0'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'imprime_ambos',
        'id_campo' => 'imprime_ambos',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Imprime ticket en ventas sin factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['imprime_ticket_venta'])) {
        $value_selected = htmlentities($_POST['imprime_ticket_venta']);
    } else {
        $value_selected = $rspv->fields['imprime_ticket_venta'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'imprime_ticket_venta',
        'id_campo' => 'imprime_ticket_venta',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Imprime comandas borradas en cocina *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['imp_comanda_borra'])) {
        $value_selected = htmlentities($_POST['imp_comanda_borra']);
    } else {
        $value_selected = $rspv->fields['imp_comanda_borra'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'imp_comanda_borra',
        'id_campo' => 'imp_comanda_borra',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Imprime vales por producto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['imp_vales'])) {
        $value_selected = htmlentities($_POST['imp_vales']);
    } else {
        $value_selected = $rspv->fields['usa_tk_prod'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'imp_vales',
        'id_campo' => 'imp_vales',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">PDF en reimpresion cajero *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['pdf_en_reimpresion_caja'])) {
        $value_selected = htmlentities($_POST['pdf_en_reimpresion_caja']);
    } else {
        $value_selected = $rspv->fields['pdf_en_reimpresion_caja'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'pdf_en_reimpresion_caja',
        'id_campo' => 'pdf_en_reimpresion_caja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Reimpresion masiva *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['permite_reimpresion_masiva'])) {
        $value_selected = htmlentities($_POST['permite_reimpresion_masiva']);
    } else {
        $value_selected = $rspv->fields['permite_reimpresion_masiva'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_reimpresion_masiva',
        'id_campo' => 'permite_reimpresion_masiva',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>




<div class="clearfix"></div>
<hr />
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Bloquea cierre de caja si hay mesas abiertas *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['obliga_cierremesa_caja'])) {
        $value_selected = htmlentities($_POST['obliga_cierremesa_caja']);
    } else {
        $value_selected = $rspv->fields['obliga_cierremesa_caja'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'obliga_cierremesa_caja',
        'id_campo' => 'obliga_cierremesa_caja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Bloquea cierre de caja si hay Carry Out pendientes *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['obliga_carry_caja'])) {
        $value_selected = htmlentities($_POST['obliga_carry_caja']);
    } else {
        $value_selected = $rspv->fields['obliga_carry_caja'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'obliga_carry_caja',
        'id_campo' => 'obliga_carry_caja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Bloquea cierre de caja si hay Delivery pendientes *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['obliga_deliv_caja'])) {
        $value_selected = htmlentities($_POST['obliga_deliv_caja']);
    } else {
        $value_selected = $rspv->fields['obliga_deliv_caja'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'obliga_deliv_caja',
        'id_campo' => 'obliga_deliv_caja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);

    ?>
    </div>
</div>


    
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Agregar Costo de Delivery Automatico *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['agrega_delivery_auto'])) {
        $value_selected = htmlentities($_POST['agrega_delivery_auto']);
    } else {
        $value_selected = $rs->fields['agrega_delivery_auto'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'agrega_delivery_auto',
        'id_campo' => 'agrega_delivery_auto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Delivery sin datos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['permite_delivery_sinitem'])) {
        $value_selected = htmlentities($_POST['permite_delivery_sinitem']);
    } else {
        $value_selected = $rs->fields['permite_delivery_sinitem'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_delivery_sinitem',
        'id_campo' => 'permite_delivery_sinitem',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usar Motorista en Delivery *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['usa_motorista'])) {
        $value_selected = htmlentities($_POST['usa_motorista']);
    } else {
        $value_selected = $rspv->fields['usa_motorista'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_motorista',
        'id_campo' => 'usa_motorista',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Obligar Motorista en Delivery *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['obliga_motorista'])) {
        $value_selected = htmlentities($_POST['obliga_motorista']);
    } else {
        $value_selected = $rspv->fields['obliga_motorista'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'obliga_motorista',
        'id_campo' => 'obliga_motorista',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar monto en delivery de mi caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['monto_endeliverycaja'])) {
        $value_selected = htmlentities($_POST['monto_endeliverycaja']);
    } else {
        $value_selected = $rspv->fields['monto_endeliverycaja'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'monto_endeliverycaja',
        'id_campo' => 'monto_endeliverycaja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal en  tablet *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['sucursal_tablet'])) {
        $value_selected = htmlentities($_POST['sucursal_tablet']);
    } else {
        $value_selected = $rspv->fields['sucursal_tablet'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'sucursal_tablet',
        'id_campo' => 'sucursal_tablet',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Boton Venta Rapida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['usa_ventarapida'])) {
        $value_selected = htmlentities($_POST['usa_ventarapida']);
    } else {
        $value_selected = $rspv->fields['usa_ventarapida'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_ventarapida',
        'id_campo' => 'usa_ventarapida',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Reimpresion Extendida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['filtros_reimp'])) {
        $value_selected = htmlentities($_POST['filtros_reimp']);
    } else {
        $value_selected = $rspv->fields['filtros_reimp'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'filtros_reimp',
        'id_campo' => 'filtros_reimp',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Reimpresion muestra cant *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="reimpresion_muestra_cant" id="reimpresion_muestra_cant" value="<?php  if (isset($_POST['reimpresion_muestra_cant'])) {
        echo intval($_POST['reimpresion_muestra_cant']);
    } else {
        echo intval($rspv->fields['reimpresion_muestra_cant']);
    }?>" placeholder="Reimpresion muestra cant" class="form-control" required="required" />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Reimpresion en central pedidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['permite_reimpresion_centralped'])) {
    $value_selected = htmlentities($_POST['permite_reimpresion_centralped']);
} else {
    $value_selected = $rspv->fields['permite_reimpresion_centralped'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_reimpresion_centralped',
        'id_campo' => 'permite_reimpresion_centralped',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cambio forma pago muestra cant *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cambioformapago_muestra_cant" id="cambioformapago_muestra_cant" value="<?php  if (isset($_POST['cambioformapago_muestra_cant'])) {
        echo intval($_POST['cambioformapago_muestra_cant']);
    } else {
        echo intval($rspv->fields['cambioformapago_muestra_cant']);
    }?>" placeholder="cambio formapago muestra cant" class="form-control" required="required" />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pulseras en Ventas *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['pulsera_vcaja'])) {
    $value_selected = htmlentities($_POST['pulsera_vcaja']);
} else {
    $value_selected = $rspv->fields['pulsera_vcaja'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'pulsera_vcaja',
        'id_campo' => 'pulsera_vcaja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Mozo en Mesa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['usar_cod_mozo'])) {
        $value_selected = htmlentities($_POST['usar_cod_mozo']);
    } else {
        $value_selected = $rspm->fields['usar_cod_mozo'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usar_cod_mozo',
        'id_campo' => 'usar_cod_mozo',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite borrar Pedidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['borrar_ped'])) {
        $value_selected = htmlentities($_POST['borrar_ped']);
    } else {
        $value_selected = $rs->fields['borrar_ped'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'borrar_ped',
        'id_campo' => 'borrar_ped',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo para borrar Pedidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['borrar_ped_cod'])) {
        $value_selected = htmlentities($_POST['borrar_ped_cod']);
    } else {
        $value_selected = $rs->fields['borrar_ped_cod'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'borrar_ped_cod',
        'id_campo' => 'borrar_ped_cod',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tiempo Max para borrar pedidos en minutos (0 para ilimitado) *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input class="form-control" name="tiempo_seguro" id="tiempo_seguro" placeholder="Minutos para borrar pedidos" value="<?php if (isset($_POST['tiempo_seguro'])) {
            echo antixss($_POST['tiempo_seguro']);
        } else {
            echo $rs->fields['tiempo_borrado_pedidos'];
        } ?>"  />
    </div>
</div>
<div class="clearfix"></div>
<hr />    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Descuento sobre Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['permite_desc_factura'])) {
    $value_selected = htmlentities($_POST['permite_desc_factura']);
} else {
    $value_selected = $rspv->fields['permite_desc_factura'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_desc_factura',
        'id_campo' => 'permite_desc_factura',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Descuento sobre Productos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['permite_desc_productos'])) {
        $value_selected = htmlentities($_POST['permite_desc_productos']);
    } else {
        $value_selected = $rspv->fields['permite_desc_productos'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_desc_productos',
        'id_campo' => 'permite_desc_productos',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion Redondeo Desc *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['desc_redondeo_dir'])) {
        $value_selected = htmlentities($_POST['desc_redondeo_dir']);
    } else {
        $value_selected = $rspv->fields['desc_redondeo_dir'];
    }
    // opciones
    $opciones = [
        'HACIA ARRIBA' => 'A',
        'HACIA ABAJO' => 'B',
        'NORMAL (5 HACIA ARRIBA)' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'desc_redondeo_dir',
        'id_campo' => 'desc_redondeo_dir',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Redondeo Ceros Desc. *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="desc_redondeo_ceros" id="desc_redondeo_ceros" value="<?php  if (isset($_POST['desc_redondeo_ceros'])) {
        echo intval($_POST['desc_redondeo_ceros']);
    } else {
        echo intval($rspv->fields['desc_redondeo_ceros']);
    }?>" placeholder="Redondeo Ceros" class="form-control" required="required" />                    
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Enviar Mail al cerrar caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
   
<?php
$mail_cierrecaja = $rspv->fields['mail_cierrecaja'];
    if ($mail_cierrecaja == '') {
        $mail_cierrecaja = 'N';
    }
    // valor seleccionado
    if (isset($_POST['mail_cierrecaja'])) {
        $value_selected = htmlentities($_POST['mail_cierrecaja']);
    } else {
        $value_selected = $mail_cierrecaja;
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'mail_cierrecaja',
        'id_campo' => 'mail_cierrecaja',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mails cierre caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="mails_cierre_caja_csv" id="mails_cierre_caja_csv" value="<?php  if (isset($_POST['mails_cierre_caja_csv'])) {
        echo antixss($_POST['mails_cierre_caja_csv']);
    } else {
        echo antixss($rspv->fields['mails_cierre_caja_csv']);
    }?>" placeholder="Correos separados por ; (hasta 3) " class="form-control"  />                    
    </div>
</div>

    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usar Vendedor *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['usa_vendedor'])) {
    $value_selected = htmlentities($_POST['usa_vendedor']);
} else {
    $value_selected = $rs->fields['usa_vendedor'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_vendedor',
        'id_campo' => 'usa_vendedor',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usar Adherente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['usa_adherente'])) {
        $value_selected = htmlentities($_POST['usa_adherente']);
    } else {
        $value_selected = $rs->fields['usa_adherente'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_adherente',
        'id_campo' => 'usa_adherente',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio del Prod en Agregado *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['agregado_usaprecioprod'])) {
        $value_selected = htmlentities($_POST['agregado_usaprecioprod']);
    } else {
        $value_selected = $rspv->fields['agregado_usaprecioprod'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'agregado_usaprecioprod',
        'id_campo' => 'agregado_usaprecioprod',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>    
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usa caja compartida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['caja_compartida'])) {
        $value_selected = htmlentities($_POST['caja_compartida']);
    } else {
        $value_selected = $rspv->fields['caja_compartida'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'caja_compartida',
        'id_campo' => 'caja_compartida',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>    


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite cambiar canal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['permite_cambiar_canal'])) {
        $value_selected = htmlentities($_POST['permite_cambiar_canal']);
    } else {
        $value_selected = $rspv->fields['permite_cambiar_canal'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_cambiar_canal',
        'id_campo' => 'permite_cambiar_canal',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>    
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite editar pedido *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['permite_editar_pedido'])) {
        $value_selected = htmlentities($_POST['permite_editar_pedido']);
    } else {
        $value_selected = $rspv->fields['permite_editar_pedido'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_editar_pedido',
        'id_campo' => 'permite_editar_pedido',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>    


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Muestra info al escanear Codigo de Barra *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['barcode_muestrainfo'])) {
        $value_selected = htmlentities($_POST['barcode_muestrainfo']);
    } else {
        $value_selected = $rspv->fields['barcode_muestrainfo'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'barcode_muestrainfo',
        'id_campo' => 'barcode_muestrainfo',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>        
    
    
    
<div class="clearfix"></div>
<hr />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Muestra Stock en Ventas *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['muestra_stock_ven'])) {
        $value_selected = htmlentities($_POST['muestra_stock_ven']);
    } else {
        $value_selected = $rs->fields['muestra_stock_ven'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'muestra_stock_ven',
        'id_campo' => 'muestra_stock_ven',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>





<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Muestra Fantasia en Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['muestra_fantasia_fac'])) {
        $value_selected = htmlentities($_POST['muestra_fantasia_fac']);
    } else {
        $value_selected = $rs->fields['muestra_fantasia_fac'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'muestra_fantasia_fac',
        'id_campo' => 'muestra_fantasia_fac',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required"  ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Muestra Actividad en Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['muestra_actividad_fac'])) {
        $value_selected = htmlentities($_POST['muestra_actividad_fac']);
    } else {
        $value_selected = $rs->fields['muestra_actividad_fac'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'muestra_actividad_fac',
        'id_campo' => 'muestra_actividad_fac',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required"  ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fantasia diferente por Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['fantasia_sucursal_fact'])) {
        $value_selected = htmlentities($_POST['fantasia_sucursal_fact']);
    } else {
        $value_selected = $rspv->fields['fantasia_sucursal_fact'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'fantasia_sucursal_fact',
        'id_campo' => 'fantasia_sucursal_fact',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Linea Credito auto al crear cliente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="linea_auto_creacliente" id="linea_auto_creacliente" value="<?php  if (isset($_POST['linea_auto_creacliente'])) {
        echo floatval($_POST['linea_auto_creacliente']);
    } else {
        echo floatval($rspv->fields['linea_auto_creacliente']);
    }?>" placeholder="Linea Credito auto al crear cliente" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Aviso Factura cuando quedan *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="avisar_quedanfac" id="avisar_quedanfac" value="<?php  if (isset($_POST['avisar_quedanfac'])) {
        echo floatval($_POST['avisar_quedanfac']);
    } else {
        echo floatval($rspv->fields['avisar_quedanfac']);
    }?>" placeholder="Avisar cuando quedan solo esta cantiad de facturas" class="form-control"  />                    
    </div>
</div>




<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Texto Dir Matriz Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="texto_matriz_fact" id="texto_matriz_fact" value="<?php  if (isset($_POST['texto_matriz_fact'])) {
        echo antixss($_POST['texto_matriz_fact']);
    } else {
        echo antixss($rspv->fields['texto_matriz_fact']);
    }?>" placeholder="Texto que sale antes de la direccion de casa matriz de la factura. EJ: C. MATRIZ" class="form-control"  />                    
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desglosar impuesto multiple en factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
if (isset($_POST['desglosa_impuesto_multiple'])) {
    $value_selected = htmlentities($_POST['desglosa_impuesto_multiple']);
} else {
    $value_selected = $rspv->fields['desglosa_impuesto_multiple'];
}
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'desglosa_impuesto_multiple',
        'id_campo' => 'desglosa_impuesto_multiple',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>            
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usar factura A4 *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['usa_factura_a4_auto'])) {
        $value_selected = htmlentities($_POST['usa_factura_a4_auto']);
    } else {
        $value_selected = $rs->fields['usa_factura_a4_auto'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'usa_factura_a4_auto',
        'id_campo' => 'usa_factura_a4_auto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>            
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo A4 *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php // valor seleccionado
    if (isset($_POST['tipo_a4'])) {
        $value_selected = htmlentities($_POST['tipo_a4']);
    } else {
        $value_selected = $rspv->fields['tipo_a4'];
    }
    // opciones
    $opciones = [
        'SIMPLE' => '1',
        'DOBLE' => '2'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'tipo_a4',
        'id_campo' => 'tipo_a4',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>            
    </div>
</div>    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fec Vencimiento en factura Credito *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // valor seleccionado
    if (isset($_POST['vencimiento_credito'])) {
        $value_selected = htmlentities($_POST['vencimiento_credito']);
    } else {
        $value_selected = $rspv->fields['vencimiento_credito'];
    }
    // opciones
    $opciones = [
        'SI' => 'S',
        'NO' => 'N'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'vencimiento_credito',
        'id_campo' => 'vencimiento_credito',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        'opciones' => $opciones

    ];

    // construye campo
    echo campo_select_sinbd($parametros_array);
    ?>            
    </div>
</div>    

    

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Leyenda Factura (Termica) *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<textarea name="leyenda_termico" id="leyenda_termico" placeholder="Leyenda al final de la factura normalmente usada para impresoras termicas" class="form-control" cols="" rows="4"><?php  if (isset($_POST['leyenda_termico'])) {
    echo antixss($_POST['leyenda_termico']);
} else {
    echo antixss($rspv->fields['leyenda_termico']);
}?></textarea>                      
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pie Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <textarea name="pie_final_factura" id="pie_final_factura" placeholder="Original: cliente Duplicado: contabilidad" class="form-control" cols="" rows="4"><?php  if (isset($_POST['pie_final_factura'])) {
        echo antixss($_POST['pie_final_factura']);
    } else {
        echo antixss($rspv->fields['pie_final_factura']);
    }?></textarea>                 
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Leyenda Factura Credito TK*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<textarea name="leyenda_credito" id="leyenda_credito" placeholder="Leyenda para facturas a credito" class="form-control" cols="" rows="4"><?php  if (isset($_POST['leyenda_credito'])) {
    echo antixss($_POST['leyenda_credito']);
} else {
    echo antixss($rspv->fields['leyenda_credito']);
}?></textarea>                      
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Leyenda Factura Credito A4*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<textarea name="leyenda_credito_a4" id="leyenda_credito_a4" placeholder="Leyenda para facturas a credito A4" class="form-control" cols="" rows="4"><?php  if (isset($_POST['leyenda_credito_a4'])) {
    echo antixss($_POST['leyenda_credito_a4']);
} else {
    echo antixss($rspv->fields['leyenda_credito_a4']);
}?></textarea>                      
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Leyenda Factura Contado A4*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<textarea name="leyenda_contado_a4" id="leyenda_contado_a4" placeholder="Leyenda para facturas contado A4" class="form-control" cols="" rows="4"><?php  if (isset($_POST['leyenda_contado_a4'])) {
    echo antixss($_POST['leyenda_contado_a4']);
} else {
    echo antixss($rspv->fields['leyenda_contado_a4']);
}?></textarea>                      
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Usar PIN Acciones*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <select name="usar_pin_acciones" id="usar_pin_acciones" class="form-control" required>
            <option value="N"<?php  if ($_POST['usar_pin_acciones'] == 'N') {
                echo "selected='selected'";
            } else {
                if ($rspv->fields['usar_pin'] == 'N') {
                    echo "selected='selected'";
                }
            }?>>NO</option>
            <option value="S"<?php  if ($_POST['usar_pin_acciones'] == 'S') {
                echo "selected='selected'";
            } else {
                if ($rspv->fields['usar_pin'] == 'S') {
                    echo "selected='selected'";
                }
            }?>>SI</option>
        
        </select>
    </div>
</div>    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Obligar motivo Acciones*</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <select name="obliga_motivos_acciones" id="obliga_motivos_acciones" class="form-control" required>
            <option value="N"<?php  if ($_POST['obliga_motivos_acciones'] == 'N') {
                echo "selected='selected'";
            } else {
                if ($rspv->fields['obliga_motivos'] == 'N') {
                    echo "selected='selected'";
                }
            }?>>NO</option>
            <option value="S"<?php  if ($_POST['obliga_motivos_acciones'] == 'S') {
                echo "selected='selected'";
            } else {
                if ($rspv->fields['obliga_motivos'] == 'S') {
                    echo "selected='selected'";
                }
            }?>>SI</option>
        
        </select>
    </div>
</div>    
    

<div class="clearfix"></div>
<br />
<br /><br />
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>

<br /><hr /><br />
<strong>Ejemplo Leyenda Termica:</strong><br />
<pre>
LOS DATOS IMPRESOS REQUIEREN DE CUIDADOS ESPECIALES. PARA ELLO DEBE EVITARSE EL CONTACTO DIRECTO CON PLASTICOS, SOLVENTES DE PRODUCTOS QUIMICOS. EVITE TAMBIEN LA EXPOSICION AL CALOR Y HUMEDAD  EN EXCESO, LUZ SOLAR O LAMPARAS FLUORESCENTES.
</pre>
<br />
<strong>Ejemplo Pie Factura:</strong><br />
<pre>
ORIGINAL: CLIENTE
DUPLICADO: ARCHIVO TRIBUTARIO
TRIPLICADO: CONTABILIDAD
</pre>
<br />
<strong>Ejemplo Leyenda Credito:</strong><br />        
<pre>
       *** VENTA A CREDITO ***   
     RECONOZCO Y PAGARE EL MONTO     
          DE ESTA OPERACION       


FIRMA:..................................
</pre>
<br /><br />
<?php } else { ?>
<p><a href="preferencias_ventas.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<h1>
Registo correcto!
</h1>

<?php }?>



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
<script>
/*
// poner en el select data-live-search="true"
$(document).ready(function() {
    // Seleccionar todos los elementos select con data-live-search='true'
    $("select[data-live-search='true']").each(function() {
        // Obtener el ID del select
        var selectId = $(this).attr('id');
        // Mostrar el ID del select en un alert
        alert("Elemento select con ID: " + selectId);
    });
});*/
</script>
  </body>
</html>
