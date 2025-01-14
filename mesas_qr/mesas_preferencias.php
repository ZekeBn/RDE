<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";


global $preferencia_usar_iconos;
global $preferencia_usar_cod_mozo;
global $preferencia_usar_cod_adm;
global $preferencia_usa_servicio;
global $preferencia_porc_servicio;
global $preferencia_idempresa;
global $preferencia_imprime_directo;
global $preferencia_boton_consumo;
global $preferencia_permite_separacuenta;
global $preferencia_permite_agrupar;
global $preferencia_permite_mudarmesa;
global $preferencia_mozo_permite_diplomatico;
global $preferencia_regresa_mesa_final;
global $preferencia_usar_codigo_barras;
global $preferencia_mostrar_categorias;
global $preferencia_usa_pin;
global $preferencia_cliente_gen_pin;


$rsprefcompra = select_table_col_limit("mesas_preferencias", "  usar_iconos, usar_cod_mozo, usar_cod_adm, usa_servicio, porc_servicio, idempresa, imprime_directo, boton_consumo, permite_separacuenta, permite_agrupar, permite_mudarmesa, mozo_permite_diplomatico, regresa_mesa_final, usar_codigo_barras, mostrar_categorias, usa_pin,cliente_gen_pin", "1");
$preferencia_usar_iconos = $rsprefcompra['usar_iconos'];
$preferencia_usar_cod_mozo = $rsprefcompra['usar_cod_mozo'];
$preferencia_usar_cod_adm = $rsprefcompra['usar_cod_adm'];
$preferencia_usa_servicio = $rsprefcompra['usa_servicio'];
$preferencia_porc_servicio = $rsprefcompra['porc_servicio'];
$preferencia_idempresa = $rsprefcompra['idempresa'];
$preferencia_imprime_directo = $rsprefcompra['imprime_directo'];
$preferencia_boton_consumo = $rsprefcompra['boton_consumo'];
$preferencia_permite_separacuenta = $rsprefcompra['permite_separacuenta'];
$preferencia_permite_agrupar = $rsprefcompra['permite_agrupar'];
$preferencia_permite_mudarmesa = $rsprefcompra['permite_mudarmesa'];
$preferencia_mozo_permite_diplomatico = $rsprefcompra['mozo_permite_diplomatico'];
$preferencia_regresa_mesa_final = $rsprefcompra['regresa_mesa_final'];
$preferencia_usar_codigo_barras = $rsprefcompra['usar_codigo_barras'];
$preferencia_mostrar_categorias = $rsprefcompra['mostrar_categorias'];
$preferencia_usa_pin = $rsprefcompra['usa_pin'];
$preferencia_cliente_gen_pin = $rsprefcompra['cliente_gen_pin'];
