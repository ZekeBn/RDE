<?php

require_once("../proveedores/preferencias_proveedores.php");
function validar_proveedor($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $proveedores_importacion;
    global $proveedores_agente_retencion;
    global $proveedores_sin_factura;
    global $proveedores_acuerdos_comerciales_archivo;
    global $proveedores_obliga_ruc;
    global $proveedores_ruc_duplicado;
    global $proveedores_razon_social_duplicado;


    $bandera_proveedor_repetido = 0;

    // validaciones basicas
    $valido = "S";
    $errores = "";
    $idempresa = $_SESSION['idempresa'];
    $ruc = $parametros_array['ruc'];//*
    $nombre = $parametros_array['nombre'];//*
    $diasvence = ($parametros_array['diasvence']);//*
    $incrementa = $parametros_array['incrementa'];//*
    $acuerdo_comercial = $parametros_array['acuerdo_comercial'];
    $acuerdo_comercial_coment = $parametros_array['acuerdo_comercial_coment'];
    $archivo = $parametros_array['archivo_acuerdo_comercial'];
    $telefono = $parametros_array['telefono'];
    $email = $parametros_array['email'];
    $fantasia = $parametros_array['fantasia'];//*


    $idtipo_origen = $parametros_array['idtipo_origen'];
    $idtipo_servicio = $parametros_array['idtipo_servicio'];
    $idproveedor = $parametros_array['idproveedor'];
    $persona = $parametros_array['persona'];
    $acuerdo_comercial_desde = $parametros_array['acuerdo_comercial_desde'];
    $acuerdo_comercial_hasta = $parametros_array['acuerdo_comercial_hasta'];

    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $form_completo = $parametros_array['form_completo'];



    if ($proveedores_obliga_ruc == "S") {
        if (strlen($ruc) < 4) {
            $valido = "N";
            $errores .= " - El RUC no puede tener menos de 4 caracteres.<br> ".$saltolinea;
        }

        if (trim($ruc) == "" || ($ruc) == "NULL") {
            $valido = "N";
            $errores .= " - El RUC no puede estar vacío.<br>".$saltolinea;
        }
    }
    $whereadd = "";
    if (trim($ruc) != "" || ($ruc) != "NULL") {
        $whereadd = " and ruc=$ruc";

        $consulta = "
		SELECT idproveedor FROM proveedores WHERE ruc=$ruc 
		";
        //echo $consulta;
        $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsc->fields['idproveedor']) > 0 && $proveedores_ruc_duplicado == "N") {
            $valido = "N";
            $errores .= " - El RUC ya existe.<br>".$saltolinea;
        }
    }


    if ($diasvence === '') {
        $valido = "N";
        $errores .= " - $diasvence El campo dias de credito no puede estar vacio.<br />";
    }

    if (strlen($nombre) < 4) {
        $valido = "N";
        $errores .= " - No se envio ninguna Razon Social o es menor a 4 caracteres.<br>".$saltolinea;
    }
    $consulta = "
	select * from proveedores where estado = 1 and nombre = $nombre limit 1;
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idproveedor']) > 0 && $proveedores_razon_social_duplicado == "N") {
        $valido = "N";
        $errores .= " - Ya existe otro proveedor registrado con la misma razon social.<br />";
    }
    if ($proveedores_ruc_duplicado == "S" && $proveedores_razon_social_duplicado == "S") {
        $consulta = "";
        if ($nombre != "" && $nombre != "NULL" && $fantasia != "" && $fantasia != "NULL" && $ruc != "" && $ruc != "NULL") {
            $consulta = "
			select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia=$fantasia and ruc=$ruc limit 1;
			";
        } elseif ($nombre != "" && $nombre != "NULL" && ($fantasia == "NULL" || $fantasia == "") && $ruc != "" && $ruc != "NULL") {
            $consulta = "
			select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia is NULL and ruc=$ruc limit 1;
			";
        } elseif ($nombre != "" && $nombre != "NULL" && ($fantasia != "NULL" && $fantasia != "") && ($ruc == "NULL" || $ruc == "")) {
            $consulta = "
			select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia=$fantasia and ruc is NULL limit 1;
			";
        } elseif ($nombre != "" && $nombre != "NULL" && ($fantasia == "NULL" || $fantasia == "") && ($ruc == "NULL" || $ruc == "")) {
            $consulta = "
			select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia is NULL and ruc is NULL limit 1;
			";
        } else {
        }

        // $consulta = ""; error lo de arriba
        // 	$consulta="
        // 	select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia=$fantasia and ruc=$ruc limit 1;
        // 	";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idproveedor = intval($rsex->fields['idproveedor']);
        if ($idproveedor > 0) {
            $valido = "N";
            $bandera_proveedor_repetido = 1;
            $errores .= " - Ya existe otro proveedor registrado con la misma razon social, fantasia y ruc.<br />";
        }
    }

    if ((trim($incrementa) == "" || $incrementa == "NULL") && $proveedores_sin_factura == "S") {
        $valido = "N";
        $errores .= " - Sin Factura no puede estar vacío.<br>".$saltolinea;
    }
    if ($proveedores_sin_factura == "N" && ((trim($persona) == "" || $persona == "NULL"))) {
        $valido = "N";
        $errores .= " - El campo Persona, debe ser completado indicando si es persona física o jurídica..<br>".$saltolinea;
    }
    if ($proveedores_acuerdos_comerciales_archivo == "S") {
        $tamanoMaximo = 900 * 1024;
        $acuerdo_comercial = str_replace("'", "", $acuerdo_comercial);
        $dest_file = $parametros_array["dest_file"];
        if (($archivo['name'] != 'NULL' && $archivo['name'] != '')) {
            $extension = end(explode('.', $archivo['name']));
            if ($archivo['size'] >= $tamanoMaximo) {
                $valido = "N";
                $errores .= "El archivo .pdf,.jpg o .jpeg no puede pesar mas de 900KB peso actual=".($archivo['size'] / 1024)."KB.<br />";
            }
            $type = str_replace("'", "", $archivo['type']);
            if ($type != "application/pdf" && $extension != "jpg" && $extension != "jpeg") {
                $valido = "N";
                $errores .= "- El archivo debe ser .pdf,.jpg o .jpeg.</br>";
            }
        }
        if (($archivo['name'] != 'NULL' || $archivo['name'] != '' || $archivo['name'] != "") && $acuerdo_comercial != 'N') {
            $valido = "N";

            $errores .= " -   El campo Acuerdo Comercial debe ser si, cuando hay un archivo del acuerdo comercial sobre el acuerdo.<br />";
        }
        if (($acuerdo_comercial_desde == 'NULL' or $acuerdo_comercial_desde == '') && $acuerdo_comercial == 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial Fecha Desde debe ser completado al marcar <strong>Acuerdo Comercial</strong> = SI .<br />";
        }
        if (($acuerdo_comercial_hasta == 'NULL' or $acuerdo_comercial_hasta == '') && $acuerdo_comercial == 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial Fecha Hasta debe ser completado al marcar <strong>Acuerdo Comercial</strong> = SI .<br />";
        }

        if (isset($dest_file) && file_exists($dest_file)) {
            $valido = "N";
            $errores .= " - El archivo ya existe ".$dest_file.".</br>";
        }
    }


    $acuerdo_comercial = str_replace("'", "", $acuerdo_comercial);
    if (($acuerdo_comercial_coment != 'NULL' && $acuerdo_comercial_coment != '') && $acuerdo_comercial != 'S') {
        $valido = "N";
        $errores .= " - El campo Acuerdo Comercial debe ser si, cuando hay un detalle sobre el acuerdo.<br />";
    }


    if (trim($nombre) == "" || $nombre == "NULL") {
        $valido = "N";
        $errores .= " - Razon Social no puede estar vacío.<br>".$saltolinea;
    }
    if ($proveedores_importacion == "S") {
        if ($idtipo_origen == "NULL") {
            $valido = "N";
            $errores .= " - El Origen no puede estar vacío.<br>".$saltolinea;
        }
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores,
        "bandera_proveedor_repetido" => $bandera_proveedor_repetido
    ];


    // echo json_encode($res);

    return $res;

}
function validar_proveedor_array(&$parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $proveedores_importacion;
    global $proveedores_agente_retencion;
    global $proveedores_sin_factura;
    global $proveedores_acuerdos_comerciales_archivo;
    global $proveedores_obliga_ruc;
    global $proveedores_ruc_duplicado;
    global $proveedores_razon_social_duplicado;


    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);

    $idempresa = $_SESSION['idempresa'];
    $bandera_proveedor_repetido = 0;
    $idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];
    $contador = 0;
    $array_datos_falla = [];
    foreach ($parametros_array as $key => $proveedor) {


        // validaciones basicas
        $valido = "S";
        $errores = "";
        $ruc = $proveedor->ruc;//*
        $nombre = $proveedor->nombre;//*
        $diasvence = ($proveedor->diasvence);//*
        $incrementa = $proveedor->incrementa;//*
        $acuerdo_comercial = $proveedor->acuerdo_comercial;
        $acuerdo_comercial_coment = $proveedor->acuerdo_comercial_coment;
        $archivo = $proveedor->archivo_acuerdo_comercial;
        $telefono = $proveedor->telefono;
        $email = $proveedor->email;
        $fantasia = $proveedor->fantasia;//*


        $idtipo_origen = $proveedor->idtipo_origen;
        $idtipo_servicio = $proveedor->idtipo_servicio;
        $idproveedor = $proveedor->idproveedor;
        $persona = $proveedor->persona;
        $acuerdo_comercial_desde = $proveedor->acuerdo_comercial_desde;
        $acuerdo_comercial_hasta = $proveedor->acuerdo_comercial_hasta;


        $idtipo_origen = $proveedor->idtipo_origen;
        $form_completo = $proveedor->form_completo;


        if ($proveedores_obliga_ruc == "S") {
            if (strlen($ruc) < 4) {
                $valido = "N";
                $errores .= " - El RUC no puede tener menos de 4 caracteres.<br> ".$saltolinea;
            }

            if (trim($ruc) == "" || ($ruc) == "NULL") {
                $valido = "N";
                $errores .= " - El RUC no puede estar vacío.<br>".$saltolinea;
            }
        }
        $whereadd = "";
        if (trim($ruc) != "" || ($ruc) != "NULL") {
            $whereadd = " and ruc=$ruc";

            $consulta = "
				SELECT idproveedor FROM proveedores WHERE ruc=$ruc 
				";
            //echo $consulta;
            $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rsc->fields['idproveedor']) > 0 && $proveedores_ruc_duplicado == "N") {
                $valido = "N";
                $errores .= " - El RUC ya existe.<br>".$saltolinea;
            }
        }


        if ($diasvence === '') {
            $valido = "N";
            $errores .= " - $diasvence El campo dias de credito no puede estar vacio.<br />";
        }

        if (strlen($nombre) < 4) {
            $valido = "N";
            $errores .= " - No se envio ninguna Razon Social o es menor a 4 caracteres.<br>".$saltolinea;
        }
        $consulta = "
			select * from proveedores where estado = 1 and nombre = $nombre limit 1;
			";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsex->fields['idproveedor']) > 0 && $proveedores_razon_social_duplicado == "N") {
            $valido = "N";
            $errores .= " - Ya existe otro proveedor registrado con la misma razon social.<br />";
        }
        if ($proveedores_ruc_duplicado == "S" && $proveedores_razon_social_duplicado == "S") {
            $consulta = "
				select idproveedor from proveedores where estado = 1 and nombre = $nombre and fantasia=$fantasia and ruc=$ruc limit 1;
				";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproveedor = intval($rsex->fields['idproveedor']);
            if ($idproveedor > 0) {
                $valido = "N";
                $bandera_proveedor_repetido = 1;
                $errores .= " - Ya existe otro proveedor registrado con la misma razon social, fantasia y ruc.<br />";
            }
        }

        if ((trim($incrementa) == "" || $incrementa == "NULL") && $proveedores_sin_factura == "S") {
            $valido = "N";
            $errores .= " - Sin Factura no puede estar vacío.<br>".$saltolinea;
        }
        if ($proveedores_sin_factura == "N" && ((trim($persona) == "" || $persona == "NULL"))) {
            $valido = "N";
            $errores .= " - El campo Persona, debe ser completado indicando si es persona física o jurídica..<br>".$saltolinea;
        }
        if ($proveedores_acuerdos_comerciales_archivo == "S") {
            $tamanoMaximo = 900 * 1024;
            $acuerdo_comercial = str_replace("'", "", $acuerdo_comercial);
            $dest_file = $parametros_array["dest_file"];
            if (($archivo['name'] != 'NULL' && $archivo['name'] != '')) {
                $extension = end(explode('.', $archivo['name']));
                if ($archivo['size'] >= $tamanoMaximo) {
                    $valido = "N";
                    $errores .= "El archivo .pdf,.jpg o .jpeg no puede pesar mas de 900KB peso actual=".($archivo['size'] / 1024)."KB.<br />";
                }
                $type = str_replace("'", "", $archivo['type']);
                if ($type != "application/pdf" && $extension != "jpg" && $extension != "jpeg") {
                    $valido = "N";
                    $errores .= "- El archivo debe ser .pdf,.jpg o .jpeg.</br>";
                }
            }
            if (($archivo['name'] != 'NULL' || $archivo['name'] != '' || $archivo['name'] != "") && $acuerdo_comercial != 'N') {
                $valido = "N";

                $errores .= " -   El campo Acuerdo Comercial debe ser si, cuando hay un archivo del acuerdo comercial sobre el acuerdo.<br />";
            }
            if (($acuerdo_comercial_desde == 'NULL' or $acuerdo_comercial_desde == '') && $acuerdo_comercial == 'S') {
                $valido = "N";
                $errores .= " - El campo Acuerdo Comercial Fecha Desde debe ser completado al marcar <strong>Acuerdo Comercial</strong> = SI .<br />";
            }
            if (($acuerdo_comercial_hasta == 'NULL' or $acuerdo_comercial_hasta == '') && $acuerdo_comercial == 'S') {
                $valido = "N";
                $errores .= " - El campo Acuerdo Comercial Fecha Hasta debe ser completado al marcar <strong>Acuerdo Comercial</strong> = SI .<br />";
            }

            if (isset($dest_file) && file_exists($dest_file)) {
                $valido = "N";
                $errores .= " - El archivo ya existe ".$dest_file.".</br>";
            }
        }


        $acuerdo_comercial = str_replace("'", "", $acuerdo_comercial);
        if (($acuerdo_comercial_coment != 'NULL' && $acuerdo_comercial_coment != '') && $acuerdo_comercial != 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial debe ser si, cuando hay un detalle sobre el acuerdo.<br />";
        }


        if (trim($nombre) == "" || $nombre == "NULL") {
            $valido = "N";
            $errores .= " - Razon Social no puede estar vacío.<br>".$saltolinea;
        }
        if ($proveedores_importacion == "S") {
            if ($idtipo_origen == "NULL") {
                $valido = "N";
                $errores .= " - El Origen no puede estar vacío.<br>".$saltolinea;
            }
        }

        if ($valido == "N") {
            $array_datos_falla[] = [
                'valido' => $valido,
                'errores' => $errores,
                "nombre" => $nombre,
                "bandera_proveedor_repetido" => $bandera_proveedor_repetido
            ];

        }


        $idproveedor++;
        $contador++;
    }





    // echo json_encode($res);

    return $array_datos_falla;

}
function agregar_proveedor_masivo($parametros_array)
{

    global $conexion;
    global $ahora;
    global $idusu;
    global $saltolinea;
    global $proveedores_importacion;
    global $proveedores_agente_retencion;
    global $proveedores_sin_factura;


    $idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];

    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
    $contador = 0;
    foreach ($parametros_array as $key => $value) {
        $ruc = $value->ruc;//*
        $nombre = $value->nombre;//*
        $fantasia = $value->fantasia;//*
        $diasvence = intval($value->diasvence);//*
        $dias_entrega = intval($value->dias_entrega);
        $incrementa = $value->incrementa;//*
        $acuerdo_comercial = $value->acuerdo_comercial;
        $acuerdo_comercial_coment = $value->acuerdo_comercial_coment;
        $telefono = $value->telefono;
        $email = $value->email;
        $idmoneda = $value->idmoneda;
        $idempresa = $value->idempresa;
        $idtipocompra = $value->idtipocompra;
        $idpais = $value->idpais;
        $agente_retencion = $value->agente_retencion;
        $idtipo_servicio = $value->idtipo_servicio;
        $idtipo_servicio = $value->idtipo_servicio;
        $direccion = $value->direccion;
        $sucursal = $value->sucursal;
        $comentarios = $value->comentarios;
        $web = $value->web;
        $contacto = $value->contacto;
        $area = $value->area;
        $email_conta = $value->email_conta;
        $cuenta_cte_mercaderia = $value->cuenta_cte_mercaderia;
        $cuenta_cte_deuda = $value->cuenta_cte_deuda;
        $archivo = $value->archivo_acuerdo_comercial;
        $ac_desde = $value->acuerdo_comercial_desde;
        $ac_hasta = $value->acuerdo_comercial_hasta;
        $persona = $value->persona;
        $registrado_por = $idusu;
        $idtipo_origen = $value->idtipo_origen;

        $registrado_el = antisqlinyeccion($ahora, "text");



        $form_completo = $value->form_completo;

        // $archivo_cargado = 0;
        $dest_file = null;
        if ($proveedores_importacion == "N") {
            $idtipo_origen = $id_tipo_origen_local;
        }
        if ($proveedores_agente_retencion == "N") {
            $agente_retencion = "'N'";
        }
        if ($proveedores_sin_factura == "N") {
            $incrementa = "'N'";
        } else {
            $persona = 0;
        }

        if ($contador == 0) {
            $consulta = "insert into proveedores
			(idproveedor,idempresa, ruc, nombre, estado, diasvence, dias_entrega, incrementa, acuerdo_comercial, idpais, agente_retencion, idtipo_servicio, tipocompra, email, acuerdo_comercial_coment, telefono, idmoneda, fantasia, direccion, sucursal, comentarios, web, contacto, area, email_conta,idtipo_origen,cuenta_cte_mercaderia,cuenta_cte_deuda,ac_archivo,registrado_por,registrado_el,ac_desde,ac_hasta,persona) 
			values
			($idproveedor,$idempresa, $ruc, $nombre, 1, $diasvence, $dias_entrega, $incrementa, $acuerdo_comercial, $idpais, $agente_retencion, $idtipo_servicio, $idtipocompra, $email, $acuerdo_comercial_coment, $telefono, $idmoneda, $fantasia, $direccion, $sucursal, $comentarios, $web, $contacto, $area, $email_conta,$idtipo_origen,$cuenta_cte_mercaderia,$cuenta_cte_deuda,'$dest_file',$registrado_por,$registrado_el,$ac_desde,$ac_hasta,$persona),
			";
            $contador++;
        } elseif ($contador != 0 && $contador < 900) {
            $consulta .= "
			($idproveedor,$idempresa, $ruc, $nombre, 1, $diasvence, $dias_entrega, $incrementa, $acuerdo_comercial, $idpais, $agente_retencion, $idtipo_servicio, $idtipocompra, $email, $acuerdo_comercial_coment, $telefono, $idmoneda, $fantasia, $direccion, $sucursal, $comentarios, $web, $contacto, $area, $email_conta,$idtipo_origen,$cuenta_cte_mercaderia,$cuenta_cte_deuda,'$dest_file',$registrado_por,$registrado_el,$ac_desde,$ac_hasta,$persona),
			";
            $contador++;
        } else {
            $consulta .= "
			($idproveedor,$idempresa, $ruc, $nombre, 1, $diasvence, $dias_entrega, $incrementa, $acuerdo_comercial, $idpais, $agente_retencion, $idtipo_servicio, $idtipocompra, $email, $acuerdo_comercial_coment, $telefono, $idmoneda, $fantasia, $direccion, $sucursal, $comentarios, $web, $contacto, $area, $email_conta,$idtipo_origen,$cuenta_cte_mercaderia,$cuenta_cte_deuda,'$dest_file',$registrado_por,$registrado_el,$ac_desde,$ac_hasta,$persona);";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $contador = 0;
        }
        $idproveedor++;
    }




    $res = [
        'success' => true
    ];
    return $res;

}
function agregar_proveedor($parametros_array)
{

    global $conexion;
    global $ahora;
    global $idusu;
    global $saltolinea;
    global $proveedores_importacion;
    global $proveedores_agente_retencion;
    global $proveedores_sin_factura;

    $ruc = $parametros_array['ruc'];//*
    $nombre = $parametros_array['nombre'];//*
    $fantasia = $parametros_array['fantasia'];//*
    $diasvence = intval($parametros_array['diasvence']);//*
    $dias_entrega = intval($parametros_array['dias_entrega']);
    $incrementa = $parametros_array['incrementa'];//*
    $acuerdo_comercial = $parametros_array['acuerdo_comercial'];
    $acuerdo_comercial_coment = $parametros_array['acuerdo_comercial_coment'];
    $telefono = $parametros_array['telefono'];
    $email = $parametros_array['email'];
    $idmoneda = $parametros_array['idmoneda'];
    $idempresa = $parametros_array['idempresa'];
    $idtipocompra = $parametros_array['idtipocompra'];
    $idpais = $parametros_array['idpais'];
    $agente_retencion = $parametros_array['agente_retencion'];
    $idtipo_servicio = $parametros_array['idtipo_servicio'];
    $idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];
    $idtipo_servicio = $parametros_array['idtipo_servicio'];
    $direccion = $parametros_array['direccion'];
    $sucursal = $parametros_array['sucursal'];
    $comentarios = $parametros_array['comentarios'];
    $web = $parametros_array['web'];
    $contacto = $parametros_array['contacto'];
    $area = $parametros_array['area'];
    $email_conta = $parametros_array['email_conta'];
    $cuenta_cte_mercaderia = $parametros_array['cuenta_cte_mercaderia'];
    $cuenta_cte_deuda = $parametros_array['cuenta_cte_deuda'];
    $archivo = $parametros_array['archivo_acuerdo_comercial'];
    $ac_desde = $parametros_array['acuerdo_comercial_desde'];
    $ac_hasta = $parametros_array['acuerdo_comercial_hasta'];
    $persona = $parametros_array['persona'];
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");


    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $form_completo = $parametros_array['form_completo'];

    // $archivo_cargado = 0;
    $dest_file = null;
    if ($proveedores_importacion == "N") {
        $idtipo_origen = $id_tipo_origen_local;
    }
    if ($proveedores_agente_retencion == "N") {
        $agente_retencion = "'N'";
    }
    if ($proveedores_sin_factura == "N") {
        $incrementa = "'N'";
    } else {
        $persona = 0;
    }

    if ($archivo['name'] != "") {
        if (is_dir("../gfx/proveedores/acuerdos_comercial")) {

        } else {
            //creamos
            mkdir("../gfx/proveedores", "0777");
            mkdir("../gfx/proveedores/acuerdos_comercial", "0777");

        }
        $source_file = $archivo['tmp_name'];
        $dest_file = $parametros_array["dest_file"];
        $directorio = $parametros_array["directorio"];
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777);
        }


    }



    // busca en tabla de conversioens si hay el producto sino iguala
    // $consulta="";
    // if($agente_retencion != "NULL"  ){

    if ($form_completo == 1) {
        $consulta = "
			insert into proveedores
			(idproveedor,idempresa, ruc, nombre, estado, diasvence, dias_entrega, incrementa, acuerdo_comercial, idpais, agente_retencion, idtipo_servicio, tipocompra, email, acuerdo_comercial_coment, telefono, idmoneda, fantasia, direccion, sucursal, comentarios, web, contacto, area, email_conta,idtipo_origen,cuenta_cte_mercaderia,cuenta_cte_deuda,ac_archivo,registrado_por,registrado_el,ac_desde,ac_hasta,persona) 
			values
			($idproveedor,$idempresa, $ruc, $nombre, 1, $diasvence, $dias_entrega, $incrementa, $acuerdo_comercial, $idpais, $agente_retencion, $idtipo_servicio, $idtipocompra, $email, $acuerdo_comercial_coment, $telefono, $idmoneda, $fantasia, $direccion, $sucursal, $comentarios, $web, $contacto, $area, $email_conta,$idtipo_origen,$cuenta_cte_mercaderia,$cuenta_cte_deuda,'$dest_file',$registrado_por,$registrado_el,$ac_desde,$ac_hasta,$persona)
			";

    } else {
        $consulta = "
		INSERT INTO proveedores 
		(idproveedor, idempresa, ruc, nombre,estado,diasvence,incrementa,acuerdo_comercial,idpais,agente_retencion,idtipo_servicio,tipocompra,email,acuerdo_comercial_coment,telefono,idmoneda,fantasia,idtipo_origen,cuenta_cte_mercaderia,cuenta_cte_deuda,registrado_por,registrado_el) VALUES 
		($idproveedor,$idempresa, $ruc, $nombre,1,$diasvence, $incrementa,$acuerdo_comercial,$idpais,$agente_retencion,$idtipo_servicio,$idtipocompra,$email,$acuerdo_comercial_coment,$telefono,$idmoneda,$fantasia,$idtipo_origen,$cuenta_cte_mercaderia,$cuenta_cte_deuda,$registrado_por,$registrado_el);
		";
        //boorable obligatorio defecto s si no se manda
    }
    if ($dest_file != "" and $dest_file != "NULL" and $dest_file != null) {

        if (!file_exists($dest_file)) {
            move_uploaded_file($source_file, $dest_file) or die("Error!!");
            // $archivo_cargado = 1;
        }

    }



    // }else{
    // 	$consulta="
    // 	INSERT INTO proveedores (idproveedor, idempresa, ruc, nombre,estado,diasvence,incrementa,acuerdo_comercial,idpais,tipocompra,email,acuerdo_comercial_coment,telefono,idmoneda,fantasia) VALUES
    // 	($idproveedor,$idempresa, $ruc, $nombre,1,$diasvence, $incrementa,$acuerdo_comercial,$idpais,$idtipocompra,$email,$acuerdo_comercial_coment,$telefono,$idmoneda,$fantasia);
    // 	";
    // }

    //echo $consulta;
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = [
        'idproveedor' => $idproveedor
    ];
    return $res;



}
function update_proveedor($parametros_array)
{

    global $conexion;
    global $ahora;
    global $saltolinea;
    global $idusu;
    global $proveedores_importacion;
    global $proveedores_agente_retencion;
    global $proveedores_sin_factura;

    $ruc = $parametros_array['ruc'];//*
    $nombre = $parametros_array['nombre'];//*
    $fantasia = $parametros_array['fantasia'];//*
    $diasvence = intval($parametros_array['diasvence']);//*
    $dias_entrega = intval($parametros_array['dias_entrega']);
    $incrementa = $parametros_array['incrementa'];//*
    $acuerdo_comercial = $parametros_array['acuerdo_comercial'];
    $acuerdo_comercial_coment = $parametros_array['acuerdo_comercial_coment'];
    $telefono = $parametros_array['telefono'];
    $email = $parametros_array['email'];
    $idmoneda = $parametros_array['idmoneda'];
    $idempresa = $parametros_array['idempresa'];
    $idtipocompra = $parametros_array['idtipocompra'];
    $idpais = $parametros_array['idpais'];
    $agente_retencion = $parametros_array['agente_retencion'];
    $idtipo_servicio = $parametros_array['idtipo_servicio'];
    $idproveedor = $parametros_array['idproveedor'];
    $idtipo_servicio = $parametros_array['idtipo_servicio'];
    $direccion = $parametros_array['direccion'];
    $sucursal = $parametros_array['sucursal'];
    $comentarios = $parametros_array['comentarios'];
    $web = $parametros_array['web'];
    $contacto = $parametros_array['contacto'];
    $area = $parametros_array['area'];
    $email_conta = $parametros_array['email_conta'];
    $cuenta_cte_mercaderia = $parametros_array['cuenta_cte_mercaderia'];
    $cuenta_cte_deuda = $parametros_array['cuenta_cte_deuda'];
    $archivo = $parametros_array['archivo_acuerdo_comercial'];
    $actualizado_por = $idusu;
    $actualizado_el = antisqlinyeccion($ahora, "text");
    $ac_desde = $parametros_array['acuerdo_comercial_desde'];
    $ac_hasta = $parametros_array['acuerdo_comercial_hasta'];
    $persona = $parametros_array['persona'];


    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $form_completo = $parametros_array['form_completo'];

    // $archivo_cargado = 0;
    $dest_file = "NULL";
    if ($proveedores_importacion == "N") {
        $idtipo_origen = $id_tipo_origen_local;
    }
    if ($proveedores_agente_retencion == "N") {
        $agente_retencion = "'N'";
    }
    if ($proveedores_sin_factura == "N") {
        $incrementa = "'N'";
    }
    if ($proveedores_sin_factura == "S") {
        $persona = 0;
    }
    $dest_file = null;
    if ($archivo['name'] != "") {
        if (is_dir("../gfx/proveedores/acuerdos_comercial")) {

        } else {
            //creamos
            mkdir("../gfx/proveedores", "0777");
            mkdir("../gfx/proveedores/acuerdos_comercial", "0777");

        }
        $source_file = $archivo['tmp_name'];
        $dest_file = $parametros_array["dest_file"];
        $directorio = $parametros_array["directorio"];
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777);
        }


    }

    // busca en tabla de conversioens si hay el producto sino iguala
    // $consulta="";
    // if($agente_retencion != "NULL"  ){
    if ($form_completo == 1 && $dest_file != null) {
        $consulta = "
			update proveedores
			set
				ruc=$ruc,
				nombre=$nombre,
				direccion=$direccion,
				comentarios=$comentarios,
				idtipo_servicio=$idtipo_servicio,
				cuenta_cte_mercaderia=$cuenta_cte_mercaderia,
				cuenta_cte_deuda=$cuenta_cte_deuda,
				tipocompra=$idtipocompra,
				idmoneda=$idmoneda,
				fantasia=$fantasia,
				idtipo_origen=$idtipo_origen,
				web=$web,
				idpais=$idpais,
				agente_retencion=$agente_retencion,
				telefono=$telefono,
				email=$email,
				contacto=$contacto,
				area=$area,
				email_conta=$email_conta,
				diasvence=$diasvence,
				dias_entrega=$dias_entrega,
				incrementa=$incrementa,
				acuerdo_comercial=$acuerdo_comercial,
				acuerdo_comercial_coment=$acuerdo_comercial_coment,
				ac_archivo='$dest_file',
				actualizado_por=$actualizado_por,
				actualizado_el=$actualizado_el,
				ac_desde=$ac_desde,
				ac_hasta=$ac_hasta,
				persona=$persona
			where
				idproveedor = $idproveedor
				and estado = 1
			";

    }
    if ($form_completo == 1 && $dest_file == null) {
        $consulta = "
			update proveedores
			set
				ruc=$ruc,
				nombre=$nombre,
				direccion=$direccion,
				comentarios=$comentarios,
				idtipo_servicio=$idtipo_servicio,
				cuenta_cte_mercaderia=$cuenta_cte_mercaderia,
				cuenta_cte_deuda=$cuenta_cte_deuda,
				tipocompra=$idtipocompra,
				idmoneda=$idmoneda,
				fantasia=$fantasia,
				idtipo_origen=$idtipo_origen,
				web=$web,
				idpais=$idpais,
				agente_retencion=$agente_retencion,
				telefono=$telefono,
				email=$email,
				contacto=$contacto,
				area=$area,
				email_conta=$email_conta,
				diasvence=$diasvence,
				dias_entrega=$dias_entrega,
				incrementa=$incrementa,
				acuerdo_comercial=$acuerdo_comercial,
				acuerdo_comercial_coment=$acuerdo_comercial_coment,
				actualizado_por=$actualizado_por,
				actualizado_el=$actualizado_el,
				ac_desde=$ac_desde,
				ac_hasta=$ac_hasta,
				persona=$persona
			where
				idproveedor = $idproveedor
				and estado = 1
			";
    }

    if ($dest_file != "" and $dest_file != "NULL" and $dest_file != null) {

        if (!file_exists($dest_file)) {
            $r = json_encode($archivo);
            move_uploaded_file($source_file, $dest_file) or die("Error!! al mover  $dest_file   desde $source_file  el siguiente archivo $r");
            // $archivo_cargado = 1;
        }

    }



    // }else{
    // 	$consulta="
    // 	INSERT INTO proveedores (idproveedor, idempresa, ruc, nombre,estado,diasvence,incrementa,acuerdo_comercial,idpais,tipocompra,email,acuerdo_comercial_coment,telefono,idmoneda,fantasia) VALUES
    // 	($idproveedor,$idempresa, $ruc, $nombre,1,$diasvence, $incrementa,$acuerdo_comercial,$idpais,$idtipocompra,$email,$acuerdo_comercial_coment,$telefono,$idmoneda,$fantasia);
    // 	";
    // }

    //echo $consulta;
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = [
        'idproveedor' => $idproveedor
    ];
    return $res;
}
