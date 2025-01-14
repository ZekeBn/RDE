 <?php

    class GeneradorXMLFacturaElectronica
    {
        private $datosVentas = [];
        private $datosVentasItems = [];
        private $datosFormasPago = [];
        private $params = [];
        private $xmlGenerated;
        private $signedXML;
        private $processRSP = [];
        private $slot;
        private $arrayToReplace;
        private $arrayToReplaceValue = [""];
        private $dSubExe = 0;
        private $dSubExo = 0;
        private $dSub5 = 0;
        private $dSub10 = 0;
        private $dTotDesc = 0;
        private $dPorcDescTotal = 0;
        private $dDescTotal = 0;
        private $dIVA5;
        private $dIVA10;
        private $sumBasGravIva5;
        private $sumBasGravIva10;
        private $diasVtoPlazoFacCredito = 0;
        private $dsub10_nota_credito = 0;
        private $dtot_op_nota_credito = 0;
        private $dtotgral_op_nota_credito = 0;
        private $diva10_nota_credito = 0;
        private $diva10totiva_nota_credito = 0;
        private $dbasegrav10_nota_credito = 0;
        private $dtotbasegrav10_nota_credito = 0;
        private $numeroComprobante = '';
        private $fecha_comprobante = '';
        private $dcodseg;
        private $dfeemide;
        private $cdc;
        private $dFecFirma;
        private $rucGenerico;
        private $url_enviarloterde;
        private $url_enviarrde;
        private $url_firmarde;
        private $url_consultarlotede;
        private $idventa;
        private $idnotacredito;
        private $idnotadebito;
        private $idnotaremision;
        private $idautofactura;
        private $arrayNotaCreditoCabeza;
        private $arrayNotaCreditoDetalle = [];
        private $tipoSolicitud;

        public function generarXML($idVenta, $slot, $tipoSolicitud)
        {

            $validate = new Validate();
            $this->tipoSolicitud = $tipoSolicitud;

            // variables globales
            if ($tipoSolicitud == 1) {  // factura
                $this->idventa = $idVenta;
            }
            if ($tipoSolicitud == 2) { // nota credito
                $this->idnotacredito = $idVenta;
            }
            if ($tipoSolicitud == 3) { // nota debito
                $this->idnotadebito = $idVenta;
            }
            if ($tipoSolicitud == 4) { // nota remision
                $this->idnotaremision = $idVenta;
            }
            if ($tipoSolicitud == 5) { // autofactura
                $this->idautofactura = $idVenta;
            }

            ##Paso 1: Inicia los datos de ventas
            #$this->iniciarDatosVentas($idVenta);

            ## Paso 1.1: Inicia el RUC generico
            $this->obtenerClienteGenerico();

            ##Paso 1: Inicia los datos de ventas
            #$this->iniciarDatosVentas($idVenta);

            ## Paso 1.1: Inicia el RUC generico
            $this->obtenerClienteGenerico();

            ## Paso 1.2: Obtener URL de las APIS
            $this->obtenerURLAPI();

            ##Paso 2: Genero la factura, Nota de credito, etc.

            if ($tipoSolicitud == 1) { //Facturas

                $this->iniciarDatosVentas($idVenta);

                $rs_tipo_venta = $this->verificaTipoFactura($idVenta);

                if ($rs_tipo_venta == 1) { //Factura contado

                    $this->armarArrayFacturaContado();

                } else { //Factura Credito

                    #Antes recoger la fecha de vencimiento de la factura
                    $this->diasVtoPlazoFacCredito = $this->obtenerFechaVtoFactura($idVenta);
                    $this->armarArrayFacturaCredito();

                }

            } elseif ($tipoSolicitud == 2) { //Nota de credito

                $idventaobtenido = $this->buscaVentaAsociadaNC($this->idnotacredito);

                if ($idventaobtenido != 0) {

                    $this->iniciarDatosVentas($idventaobtenido);

                } else {

                    $this->iniciarDatosNotaCreditoImpreso();

                }

                $this->iniciarDatosNC($this->idnotacredito);

                $this->iniciarCabeceraNotaCredito();
                $this->iniciarDetalleNotaCredito();

                $this->armarArrayNotaCredito();

            }

            #Paso 3: Inicializa el slot
            $this->slot = $slot;

            $rsp = $validate->checkFields($this->params);

            if ($rsp['status'] == 'OK') {

                for ($i = 0;$i < 2000;$i++) {

                    $this->arrayToReplace[] = '<'.$i.'>';
                    $this->arrayToReplace[] = '</'.$i.'>';

                }

                $this->xmlGenerated = str_replace($this->arrayToReplace, $this->arrayToReplaceValue, $rsp['xmlData']);
                $this->xmlGenerated = str_replace('<gCamItem><gCamItem>', '<gCamItem>', $this->xmlGenerated);
                $this->xmlGenerated = str_replace('</gCamItem></gCamItem>', '</gCamItem>', $this->xmlGenerated);
                $this->xmlGenerated = str_replace('<gPaConEIni><gPaConEIni>', '<gPaConEIni>', $this->xmlGenerated);
                $this->xmlGenerated = str_replace('</gPaConEIni></gPaConEIni>', '</gPaConEIni>', $this->xmlGenerated);

                $doc = new DOMDocument();
                $doc->preserveWhiteSpace = false;
                $doc->loadxml($this->xmlGenerated);

                $xpath = new DOMXPath($doc);

                foreach ($xpath->query('/*//*[
                    normalize-space(.) = "" and
                    not(
                      @* or 
                      .//*[@*] or 
                      .//comment() or
                      .//processing-instruction()
                    )
                  ]') as $node) {
                    $node->parentNode->removeChild($node);
                }

                $doc->formatOutput = true;

                $this->xmlGenerated = $doc->savexml();

                // Si es una factura ya procesada, envio el mismo cdc
                if ($this->cdc != '') {

                    $this->xmlGenerated = str_replace('DE Id=""', "DE Id='".$this->cdc."'", $this->xmlGenerated);
                    $this->xmlGenerated = str_replace('<dFecFirma>2023-04-10T14:38:24</dFecFirma>', '<dFecFirma></dFecFirma>', $this->xmlGenerated);
                    $this->dFecFirma = '';

                } else {

                    $this->dFecFirma = date('Y-m-d\TH:i:s');
                    $this->xmlGenerated = str_replace('<dFecFirma>2023-04-10T14:38:24</dFecFirma>', '<dFecFirma>'.$this->dFecFirma.'</dFecFirma>', $this->xmlGenerated);

                }

                //header('Content-type: text/xml');
                //echo $this->xmlGenerated;die();

                $rspFirmaXML = $this->firmarXML();

                #Buscar antes en documentos emitidos este idventa

                $busca_documento_emitido = $this->buscaIDVentaDocumentosEmitidos($idVenta);

                if ($busca_documento_emitido['filas'] > 0) {
                    // error de la set
                    if ($busca_documento_emitido['datos']['estado_set'] == 4) {

                        # Actualiza el documento emitido a estado = 6 y envia el iddocumentoemitido
                        $this->actualizarDocumentoEmitido($busca_documento_emitido['datos']['iddocumentoemitido']);

                    } elseif ($busca_documento_emitido['datos']['estado_set'] != 1) {

                        $this->processRSP = [

                            "status" => false,
                            "position" => "DUPLICADO",
                            "msg" => "Ya existe un documento pendiente con este ID de venta"

                        ];

                        return $this->processRSP;
                        die();

                    }

                }

                $rspFirmaXMLJson = json_decode($rspFirmaXML, true);

                if ($rspFirmaXMLJson['status']['success'] == true) {

                    $this->signedXML = $rspFirmaXMLJson['payload']["rDe"]['xml']; # Procesa un XML firmado

                    #Genera en documentos_emitidos
                    //$this->actualizaDocumentoElectronicoEmitido($idVenta, $this->signedXML, $rspFirmaXMLJson['payload']['rDe']['cdc'], 1, 1, 1, $rspFirmaXMLJson['payload']['rDe']['dFecFirma'], $this->numeroComprobante, $rspFirmaXMLJson['payload']['rDe']['dCarQRUrl'], '', $this->fecha_comprobante, '');

                    // envia a la SET
                    $processedSIFENXML = $this->procesarXMLSIFENAsincrono();

                    $rspSIFENJson = json_decode($processedSIFENXML, true);

                    if ($rspSIFENJson['status']["success"] == true) {

                        if ($rspSIFENJson['payload']["procesado"]['dEstRes'] != 'Rechazado') {

                            #Indica que ya esta firmado y enviado como lote
                            $this->actualizaDocumentoElectronicoEmitido($idVenta, $this->signedXML, $rspFirmaXMLJson['payload']['rDe']['cdc'], 1, 1, 2, $rspFirmaXMLJson['payload']['rDe']['dFecFirma'], $this->numeroComprobante, $rspFirmaXMLJson['payload']['rDe']['dCarQRUrl'], $rspSIFENJson['payload']['procesado']['dMsgRes'], $this->fecha_comprobante, $rspSIFENJson['payload']['procesado']['dProtConsLote']);

                            $this->processRSP = [

                                "status" => true,
                                "position" => "SIFEN",
                                "msg" => $rspSIFENJson['payload']["procesado"]['dMsgRes']

                            ];

                            $this->updateCDC($idVenta, $rspFirmaXMLJson['payload']['rDe']['cdc'], $tipoSolicitud);

                        } else {

                            #Indica que ya esta firmado pero Rechazado por la SET como lote
                            $this->actualizaDocumentoElectronicoEmitido($idVenta, $this->signedXML, $rspFirmaXMLJson['payload']['rDe']['cdc'], 1, 1, 4, $rspFirmaXMLJson['payload']['rDe']['dFecFirma'], $this->numeroComprobante, $rspSIFENJson['payload']['rDe']['dCarQRUrl'], $rspSIFENJson['payload']['procesado']['gResProcLote'][0]['gResProc'][0]['dMsgRes'], $this->fecha_comprobante, "");

                            $this->processRSP = [

                                "status" => false,
                                "position" => "SIFEN",
                                "msg" => $rspSIFENJson['payload']['procesado']['gResProcLote'][0]['gResProc'][0]['dMsgRes']

                            ];

                        }

                    } else {

                        if (strstr($rspSIFENJson['status']['errno'], 'SET')) {

                            #Indica que hacienda esta caida
                            $this->actualizaDocumentoElectronicoEmitido($idVenta, $this->signedXML, $rspFirmaXMLJson['payload']['rDe']['cdc'], 1, 1, 1, $rspFirmaXMLJson['payload']['rDe']['dFecFirma'], $this->numeroComprobante, $rspFirmaXMLJson['payload']['rDe']['dCarQRUrl'], $rspSIFENJson['status']['cause'], $this->fecha_comprobante, "");

                        } else {

                            #Indica que hubo un error al procesar el XML
                            $this->actualizaDocumentoElectronicoEmitido($idVenta, $this->signedXML, $rspFirmaXMLJson['payload']['rDe']['cdc'], 1, 1, 4, $rspFirmaXMLJson['payload']['rDe']['dFecFirma'], $this->numeroComprobante, $rspFirmaXMLJson['payload']['rDe']['dCarQRUrl'], $rspSIFENJson['status']['cause'], $this->fecha_comprobante, "");

                        }
                        $this->processRSP = [

                            "status" => false,
                            "position" => "SIFEN",
                            "msg" => $rspSIFENJson['status']['cause']

                        ];

                    }

                } else {

                    #Indica que se proceso pero hubo un error en la firma del XML
                    $this->actualizaDocumentoElectronicoEmitido($idVenta, "", "", 1, 1, 4, date('Y-m-d H:i:s'), $this->numeroComprobante, "", "Error al firmar el XML", $this->fecha_comprobante, "");

                    $this->processRSP = [

                        "status" => false,
                        "position" => "GENERARSIFEN",
                        "msg" => $rspFirmaXMLJson['status']['cause']

                    ];

                }

                $rsp = json_encode($this->processRSP, JSON_UNESCAPED_UNICODE);
                return $rsp;


            } else {

                // $estado, $estado_envio_cliente, $estado_set
                $this->actualizaDocumentoElectronicoEmitido($idVenta, "", "", 1, 1, 4, "", $this->numeroComprobante, "", $rsp['errors'], $this->fecha_comprobante, "");
                $rsp = json_encode($rsp['errors'], JSON_UNESCAPED_UNICODE);
                return $rsp;

            }

        }

        private function buscaVentaAsociadaNC()
        {

            global $conexion;
            $idnc = intval($this->idnotacredito);

            $consulta = "select idventacab from nota_credito_cabeza where idnotacred = $idnc limit 1";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $rs_cantidad_obtenida = $rs->recordCount();

            if ($rs_cantidad_obtenida > 0) {

                return $rs->fields['idventacab'];

            } else {

                return 0;

            }


        }

        private function obtenerClienteGenerico()
        {

            global $conexion;

            $clientegenerico = "select ruc from cliente where borrable = 'N' order by idcliente asc limit 1";
            $rs = $conexion->getRow($clientegenerico) or die(errorpg($conexion, $clientegenerico));

            $this->rucGenerico = $rs['ruc'];

        }

        private function actualizarDocumentoEmitido($iddocumentoemitido)
        {

            $iddocumentoemitido = intval($iddocumentoemitido);
            global $conexion;

            $update = "UPDATE documentos_electronicos_emitidos SET estado = 6 WHERE iddocumentoemitido = $iddocumentoemitido";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

        }

        private function buscaIDVentaDocumentosEmitidos($idVenta)
        {

            $idVenta = intval($idVenta);
            global $conexion;

            #Guarda en la tabla documentos emitidos
            $busca_resultado = "SELECT iddocumentoemitido, nro_lote, estado_set
                            FROM documentos_electronicos_emitidos  
                            where idventa = $idVenta 
                            and estado <> 6";

            $rs = $conexion->Execute($busca_resultado) or die(errorpg($conexion, $busca_resultado));
            $rs_count = $rs->recordCount();
            $datos = [];

            if ($rs_count > 0) {

                $busca_venta = "SELECT iddocumentoemitido, nro_lote, estado_set
                FROM documentos_electronicos_emitidos  
                where idventa = $idVenta 
                and estado <> 6";

                $datos = $conexion->getRow($busca_venta) or die(errorpg($conexion, $busca_venta));

            }

            $rs = [

                "filas" => $rs_count,
                "datos" => $datos

            ];

            return $rs;

        }

        private function verificaTipoFactura($idVenta)
        {

            $idVenta = intval($idVenta);
            global $conexion;

            #Guarda en la tabla documentos emitidos
            $verifica_tipo_venta = "SELECT tipo_venta FROM ventas  where idventa = $idVenta";
            $rs = $conexion->getRow($verifica_tipo_venta) or die(errorpg($conexion, $verifica_tipo_venta));

            return $rs['tipo_venta'];

        }

        private function actualizaDocumentoElectronicoEmitido($idVenta, $xml, $cdc, $estado, $estado_envio_cliente, $estado_set, $fecha_firma, $comprobante, $qr, $json_resp, $fecha_comprobante, $nro_lote)
        {

            $idVenta = intval($idVenta);
            $fechaCorrecta = str_replace('T', ' ', $fecha_firma);
            $finalRSP = (is_array($json_resp)) ? serialize($json_resp) : $json_resp;
            global $conexion;


            $xml = antisqlinyeccion($xml, "clave");
            $qr = antisqlinyeccion($qr, "clave");
            $nro_lote = antisqlinyeccion($nro_lote, "int");
            $cdc = antisqlinyeccion($cdc, "text");
            $dcodseg = antisqlinyeccion($this->dcodseg, "int");
            $dfeemide = antisqlinyeccion($this->dfeemide, "text");
            $comprobante = antisqlinyeccion($this->numeroComprobante, "text");


            $buscacdc = "SELECT iddocumentoemitido FROM documentos_electronicos_emitidos WHERE cdc  = $cdc and estado_set = 1";
            $rscdc = $conexion->Execute($buscacdc) or die(errorpg($conexion, $buscacdc));

            if ($rscdc->recordCount() == 0) {

                if ($this->tipoSolicitud == 1) {

                    #Guarda en la tabla documentos emitidos
                    $insercion = "INSERT INTO documentos_electronicos_emitidos (iddocumentoelectronico, xml, qr, cdc,  fecha_firma, 
                                    numero_comprobante, idtipoenvio, idventa, estado, estado_enviocliente, 
                                    estado_set, json_resp, fecha_comprobante, nro_lote, dcodseg, dfeemide) 
                                    VALUES (1, $xml,$qr,$cdc,
                                    '".date('Y-m-d H:i:s', strtotime($fechaCorrecta))."', $comprobante, 2, $idVenta, $estado, $estado_envio_cliente, $estado_set, '".str_replace("'", '"', $finalRSP)."', '".date('Y-m-d H:i:s', strtotime($fecha_comprobante))."', ".$nro_lote.", $dcodseg, $dfeemide)";
                    $conexion->Execute($insercion) or die(errorpg($conexion, $insercion));

                } elseif ($this->tipoSolicitud == 2) {

                    #Guarda en la tabla documentos emitidos
                    $insercion = "INSERT INTO documentos_electronicos_emitidos (iddocumentoelectronico, xml, qr, cdc,  fecha_firma, 
                                    numero_comprobante, idtipoenvio, idnotacredito, estado, estado_enviocliente, 
                                    estado_set, json_resp, fecha_comprobante, nro_lote, dcodseg, dfeemide) 
                                    VALUES (2, $xml,$qr,$cdc,
                                    '".date('Y-m-d H:i:s', strtotime($fechaCorrecta))."', $comprobante, 2, $idVenta, $estado, $estado_envio_cliente, $estado_set, '".str_replace("'", '"', $finalRSP)."', '".date('Y-m-d H:i:s', strtotime($fecha_comprobante))."', ".$nro_lote.", $dcodseg, $dfeemide)";
                    $conexion->Execute($insercion) or die(errorpg($conexion, $insercion));

                }

            } else {

                #Actualiza en la tabla documentos emitidos
                $actualizacion = "UPDATE documentos_electronicos_emitidos SET estado_set = 2 WHERE cdc  = $cdc and estado_set = 1";
                $conexion->Execute($actualizacion) or die(errorpg($conexion, $actualizacion));

            }

        }

        private function procesarXMLSIFENAsincrono()
        {

            global $conexion;
            global $ahora;


            // parametros globales de la clase
            $idventa = antisqlinyeccion($this->idventa, "int");
            $idnotacredito = antisqlinyeccion($this->idnotacredito, "int");
            $idnotadebito = antisqlinyeccion($this->idnotadebito, "int");
            $idnotaremision = antisqlinyeccion($this->idnotaremision, "int");
            $idautofactura = antisqlinyeccion($this->idautofactura, "int");

            $curl = curl_init();
            $data = [
                'rDes' => [
                    [
                        'rDe' => $this->signedXML
                    ]
                ],
                'dId' => (int) $this->generateUniqueIDRand()
            ];

            $json = json_encode($data);

            $json_ins = antisqlinyeccion($json, "textbox");
            $idtranlog = antisqlinyeccion(date("YmdHis").rand(1000, 9999), "text");

            $consulta = "
            INSERT INTO documentos_electronicos_log
            (json_enviado, json_respuesta, registrado_el, idservicio, idventa, idnotacredito, idnotaremision, idautofactura, idnotadebito, idtranlog) 
            VALUES 
            ($json_ins,NULL,'$ahora',1,$idventa,$idnotacredito,$idnotaremision,$idautofactura,$idnotadebito, $idtranlog)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
            select max(idlog) as idlog from documentos_electronicos_log where  idtranlog = $idtranlog
            ";
            $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idlog = $rslog->fields['idlog'];

            curl_setopt_array($curl, [
              CURLOPT_URL => $this->url_enviarloterde,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 5,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $json,
              CURLOPT_HTTPHEADER => [
                'X-Set-Contribuyente-Slot: '.$this->slot,
                'Content-Type: application/json'
              ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            $response_ins = antisqlinyeccion($response, "textbox");
            $consulta = "
            update documentos_electronicos_log
            set
            json_respuesta = $response_ins
            where
            idlog = $idlog
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            return $response;

        }

        private function firmarXML()
        {

            global $conexion;
            global $ahora;


            // parametros globales de la clase
            $idventa = antisqlinyeccion($this->idventa, "int");
            $idnotacredito = antisqlinyeccion($this->idnotacredito, "int");
            $idnotadebito = antisqlinyeccion($this->idnotadebito, "int");
            $idnotaremision = antisqlinyeccion($this->idnotaremision, "int");
            $idautofactura = antisqlinyeccion($this->idautofactura, "int");

            $texto_envio = '{
                    "dE": "'.addslashes($this->xmlGenerated).'",
                    "de":'.(int) $this->generateUniqueIDRand().'
              }';

            $idtranlog = antisqlinyeccion(date("YmdHis").rand(1000, 9999), "text");

            $json_ins = antisqlinyeccion($texto_envio, "textbox");

            $consulta = "
            INSERT INTO documentos_electronicos_log
            (json_enviado, json_respuesta, registrado_el, idservicio, idventa, idnotacredito, idnotaremision, idautofactura, idnotadebito, idtranlog) 
            VALUES 
            ($json_ins,NULL,'$ahora',2,$idventa,$idnotacredito,$idnotaremision,$idautofactura,$idnotadebito, $idtranlog)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
            select max(idlog) as idlog from documentos_electronicos_log where  idtranlog = $idtranlog
            ";
            $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idlog = $rslog->fields['idlog'];

            $curl = curl_init();

            curl_setopt_array($curl, [
              CURLOPT_URL => $this->url_firmarde,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 3,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $texto_envio,
              CURLOPT_HTTPHEADER => [
                'X-Set-Contribuyente-Slot: '.$this->slot,
                'Content-Type: application/json'
              ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            $response_ins = antisqlinyeccion($response, "textbox");
            $consulta = "
            update documentos_electronicos_log
            set
            json_respuesta = $response_ins
            where
            idlog = $idlog
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            return $response;

        }

        private function updateCDC($idVenta, $cdc, $tipoSolicitud)
        {

            $idVenta = intval($idVenta);
            $cdc = htmlspecialchars($cdc);

            $consulta = "UPDATE ventas SET cdc = $cdc where idventa = $idVenta";

            global $conexion;

            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }

        private function procesarXMLSIFEN()
        {
            /*
            falta agregar el log
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => $this->url_enviarrde,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 5,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => '{
                    "rDe": "'.addslashes($this->signedXML).'",
                    "dId":'.(int) $this->generateUniqueIDRand().'
              }',
              CURLOPT_HTTPHEADER => array(
                'X-Set-Contribuyente-Slot: '.$this->slot,
                'Content-Type: application/json'
              ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
            */
        }

        private function armarArrayFacturaCredito()
        {


            global $conexion;

            $this->numeroComprobante = $this->datosVentas['factura'];
            $this->fecha_comprobante = $this->datosVentas['fecha'];

            $rs_documento_electronico = $this->buscarDocumentoElectronicoGenerado($this->numeroComprobante);

            if ($rs_documento_electronico['total'] > 0) {

                $this->dcodseg = (int) $rs_documento_electronico['dcodseg'];
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($rs_documento_electronico['dfeemide']));
                $this->cdc = $rs_documento_electronico['cdc'];

            } else {

                $this->dcodseg = (int) $this->generateUniqueIDRand();
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($this->datosVentas['fecha']));
                $this->cdc = '';

            }

            $this->params = [
                "dDVId" => 6,
                "dFecFirma" => '2023-04-10T14:38:24',
                "dSisFact" => 1,
                "iTipEmi" => 1,
                "dDesTipEmi" => "Normal",
                "dCodSeg" => $this->dcodseg,
                "iTiDE" => 1,
                "dDesTiDE" => "Factura electrónica",
                "dNumTim" => (int) $this->datosVentas['timbradoEmpresa'],
                "dEst" => $this->completarCerosIzquierda(3, $this->datosVentas['factura_sucursal']),
                "dPunExp" => $this->completarCerosIzquierda(3, $this->datosVentas['factura_puntoexpedicion']),
                "dNumDoc" => $this->completarCerosIzquierda(7, $this->datosVentas['numfactura']),
                "dSerieNum" => "AA",
                "dFeIniT" => (string) $this->datosVentas['FecInitTimbElec'],
                "dFeEmiDE" => $this->dfeemide,
                "iTipTra" => (int) $this->datosVentas['idtipotranset'], //1:Venta de mercaderias, 2:Prestacion de servicios, etc.
                "dDesTipTra" => "Venta de mercadería",
                "iTImp" => 1,
                "dDesTImp" => "IVA",//Agregar tabla de parametrizaciones
                "cMoneOpe" => "PYG",//Agregar tabla de parametrizaciones
                "dDesMoneOpe" => "Guarani",//Agregar tabla de parametrizaciones
                "dRucEm" => (string) $this->datosVentas['rucempresa'],
                "dDVEmi" => (int) $this->datosVentas['dvempresa'],
                "iTipCont" => (int) $this->datosVentas['tipoPersonaEmpresa'],
                "cTipReg" => 8, //Consultar, tipo de regimen
                "dNomEmi" => $this->datosVentas['rsempresa'], //En produccion poner la razon social del cliente
                //"dNomFanEmi" => $this->datosVentas['nomFantasiaCliente'],
                "iTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? 1 : 6,
                "dDTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? "Cédula paraguaya" : "Tarjeta Diplomática de exoneración fiscal",
                "dNumIDRec" => "x",
                "dDirEmi" => $this->datosVentas['direccionempresa'],
                "dNumCas" => 0, //Si no tiene numero de casa, poner cero
                "dCompDir1" => "-",
                "dCompDir2" => "-",
                "cDepEmi" => (int) $this->datosVentas['departamento_emisor_set'], //Departamento del Emisor Según XSD de Departamentos
                "dDesDepEmi" => (string) $this->datosVentas['descDepaEmisorSet'],//Debe ser Según XSD de Departamentos Debe corresponder a lo declarado en el RUC
                "cDisEmi" => (int) $this->datosVentas['distritoEmisor'],
                "dDesDisEmi" => (string) $this->datosVentas['distritoEmisorDesc'],
                "cCiuEmi" => (int) $this->datosVentas['ciuEmisorSet'],
                "dDesCiuEmi" => (string) $this->datosVentas['desCiuEmisorSet'],
                "dTelEmi" => $this->datosVentas['telefonoempresa'],
                "dEmailE" => $this->datosVentas['emailempresa'],
                "cActEco" => $this->datosVentas['actEconEmpresaCod'], //Debe agregarse una tabla o un campo en la tabla empresas, es el tipo de actividad de la empresa
                "dDesActEco" => $this->datosVentas['actEconEmpresa'],
                "iNatRec" => ($this->datosVentas['diplomatico'] == 'N') ? (int) $this->datosVentas['tipoContribuyente'] : 2, //Debe agregarse si el cliente es contribuyente o no en la tabla de clientes
                "iTiOpe" => ((int) $this->datosVentas['tipoPersonaCliente'] == 1) ? 2 : 1,
                "cPaisRec" => "PRY",//Parametrizar
                "dDesPaisRe" => "Paraguay",//Parametrizar
                "dDVRec" => (int) $this->datosVentas['dv'], //Agregar digito verificador a la tabla de clientes
                "dNomRec" => htmlspecialchars($this->datosVentas['razonSocialCliente']),
                //"dNomFanRec" => "Cliente",
                "dDirRec" => (string) $this->datosVentas['direccionCliente'],
                "dNumCasRec" => (int) $this->datosVentas['numCasaCliente'], //Debe agregarse el campo en la tabla clientes
                "cDepRec" => (int) $this->datosVentas['departamentoCliente'],
                "dDesDepRec" => (string) $this->datosVentas['departamentoClienteDesc'],
                "cDisRec" => (int) $this->datosVentas['distritoCliente'],
                "dDesDisRec" => (string) $this->datosVentas['desDistritoCliente'],
                "cCiuRec" => (int) $this->datosVentas['ciudadCliente'],
                "dDesCiuRec" => (string) $this->datosVentas['desCiudadCliente'],
                "dTelRec" => (string) $this->datosVentas['celularCliente'],
                "dEmailRec" => (string) $this->datosVentas['emailCliente'],
                "dCodCliente" => (string) $this->generateUniqueIDRand(),
                "iIndPres" => (int) $this->datosVentas['idIndiPresencia'],
                "dDesIndPres" => (string) $this->datosVentas['descIndiPresencia'],
                ///Formas de pago
                "gCamCond" => [

                    "iCondOpe" => 2,
                    "dDCondOpe" => "Crédito",
                    "gPagCred" => [
                        "iCondCred" => 1,
                        "dDCondCred" => "Plazo",
                        "dPlazoCre" => $this->diasVtoPlazoFacCredito." dias"
                    ],
                    "gPaConEIni" => $this->datosFormasPago

                ],

                // INICIO ITEMS DE LA FACTURA

                "gCamItem" => $this->datosVentasItems,

                // FIN ITEMS DE LA FACTURA

                "dSubExe" => floatval($this->dSubExe),
                "dSubExo" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : floatval($this->dSubExo),
                "dSub5" => floatval($this->dSub5),
                "dSub10" => floatval($this->dSub10),
                "dTotOpe" => ($this->datosVentas['diplomatico'] == 'S') ? floatval($this->dSubExe) : floatval($this->dSubExe + $this->dSubExo + $this->dSub5 + $this->dSub10),
                "dTotDesc" => 0,
                "dTotDescGlotem" => 0,
                "dTotAntItem" => 0, //Anticipos
                "dTotAnt" => 0, //Anticipos
                "dPorcDescTotal" => (int) $this->datosVentas['descporc'],
                "dDescTotal" => 0,
                "dAnticipo" => 0,
                "dRedon" => 0,
                "dComi" => 0,
                "dTotGralOpe" => ($this->datosVentas['diplomatico'] == 'S') ? floatval($this->dSubExe) : floatval($this->dSubExe + $this->dSubExo + $this->dSub5 + $this->dSub10) - 0 /* Redondeo */ + 0 /* dComi */,
                "dIVA5" => $this->dIVA5,
                "dIVA10" => $this->dIVA10,
                "dIVAComi" => 0,
                "dTotIVA" => $this->dIVA5 + $this->dIVA10 - (0 /* Redondeo al 5% */ + 0 /* Redondeo al 10% */),//Correcto
                "dBaseGrav5" => (float) round($this->datosVentas['dBaseGrav5'], 2),//Correcto
                "dBaseGrav10" => (float) round($this->datosVentas['dBaseGrav10'], 2),//Correcto
                "dTBasGraIVA" => (float) round(($this->datosVentas['dBaseGrav5'] + (float) $this->datosVentas['dBaseGrav10']), 2),

            ];

            # Si la naturaleza del receptor es contribuyente
            if ($this->datosVentas['tipoContribuyente'] == 1) {

                unset($this->params["iTipIDRec"]);
                unset($this->params["dDTipIDRec"]);
                $this->params["iTiContRec"] = (int) $this->datosVentas['tipoPersonaCliente'];

                if ($this->datosVentas["tipoPersonaCliente"] == 1) {//Si es Persona Fisica

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                        // Separa el ruc por guion
                        $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                        $this->params["dRucRec"] = $partes_ruc[0];
                        $this->params["dDVRec"] = $partes_ruc[1];
                        $this->params["dNumIDRec"] = $partes_ruc[0];

                    } else {

                        unset($this->params["iTiContRec"]);
                        $this->params["iNatRec"] = 2;
                        $this->params["dNumIDRec"] = '0';
                        $this->params["iTipIDRec"] = 5;
                        $this->params["dDTipIDRec"] = "Innominado";
                        $this->params["dNomRec"] = "Sin Nombre";

                    }

                } else { // Si es una empresa

                    // Separa el ruc por guion
                    $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                    $this->params["dRucRec"] = $partes_ruc[0];
                    $this->params["dDVRec"] = $partes_ruc[1];
                    $this->params["iTiOpe"] = 1;
                    $this->params["dNumIDRec"] = $partes_ruc[0];

                }

                if ($this->datosVentas['diplomatico'] == 'S') {

                    $this->params["iTipIDRec"] = 6;
                    $this->params["dDTipIDRec"] = "Tarjeta Diplomática de exoneración fiscal";
                    $this->params["dNumIDRec"] = (string) $this->datosVentas['ruc'];
                    unset($this->params["dRucRec"]);
                    unset($this->params["iTiContRec"]);

                } else {

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                        /* Hago split del RUC */
                        $ruc_split = explode('-', $this->datosVentas['ruc']);
                        $this->params["dRucRec"] = (string) $ruc_split[0];

                    }

                }

                if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                    $this->params["dDVRec"] = (int) $this->datosVentas['dv'];

                } else {

                    unset($this->params["dDVRec"]);

                }

            }

            // si telefono no esta entre 6 y 15
            if (strlen($this->datosVentas['dTelRec'].'') < 6 or strlen($this->datosVentas['dTelRec'].'') > 15) {
                unset($this->params["dTelRec"]);
            }

            #Si no tiene direccion, no es obligatorio informar distrito, ni departamento ni ciudad
            if ((empty($this->datosVentas['direccionCliente']) || $this->datosVentas['direccionCliente'] == '')
            || (empty($this->datosVentas['departamentoCliente']) || $this->datosVentas['departamentoCliente'] == '' || $this->datosVentas['departamentoCliente'] == 0)
            || (empty($this->datosVentas['distritoCliente']) || $this->datosVentas['distritoCliente'] == '' || $this->datosVentas['distritoCliente'] == 0)
            || (empty($this->datosVentas['ciudadCliente']) || $this->datosVentas['ciudadCliente'] == '' || $this->datosVentas['ciudadCliente'] == 0)) {

                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);

            }

            // valida vinculacion de departamento, distrito y cuidad
            $id_departamentoCliente = intval($this->datosVentas['departamentoCliente']);
            $id_ciudadCliente = intval($this->datosVentas['ciudadCliente']);
            $id_distritoCliente = intval($this->datosVentas['distritoCliente']);
            $consulta = "
            select departamentos.id as iddepartamento
            FROM departamentos
            inner join distrito on distrito.iddepartamento = departamentos.id
            inner join ciudades on ciudades.iddistrito = distrito.iddistrito
            WHERE
            departamentos.estado = 1
            and distrito.estado = 1
            and ciudades.estado = 1
            and departamentos.id = $id_departamentoCliente
            and distrito.iddistrito = $id_distritoCliente
            and ciudades.idciudad = $id_ciudadCliente
            ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //si no esta bien vinculado no informa
            if (intval($rs->fields['iddepartamento']) == 0) {
                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);
            }


        }

        private function armarArrayFacturaContado()
        {

            global $conexion;

            $this->numeroComprobante = $this->datosVentas['factura'];
            $this->fecha_comprobante = $this->datosVentas['fecha'];

            $rs_documento_electronico = $this->buscarDocumentoElectronicoGenerado($this->numeroComprobante);

            if ($rs_documento_electronico['total'] > 0) {

                $this->dcodseg = (int) $rs_documento_electronico['dcodseg'];
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($rs_documento_electronico['dfeemide']));
                $this->cdc = $rs_documento_electronico['cdc'];

            } else {

                $this->dcodseg = (int) $this->generateUniqueIDRand();
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($this->datosVentas['fecha']));
                $this->cdc = '';

            }


            $this->params = [
                "dDVId" => 6,
                "dFecFirma" => '2023-04-10T14:38:24',
                "dSisFact" => 1,
                "iTipEmi" => 1,
                "dDesTipEmi" => "Normal",
                "dCodSeg" => $this->dcodseg,
                "iTiDE" => 1,
                "dDesTiDE" => "Factura electrónica",
                "dNumTim" => (int) $this->datosVentas['timbradoEmpresa'],
                "dEst" => $this->completarCerosIzquierda(3, $this->datosVentas['factura_sucursal']),
                "dPunExp" => $this->completarCerosIzquierda(3, $this->datosVentas['factura_puntoexpedicion']),
                "dNumDoc" => $this->completarCerosIzquierda(7, $this->datosVentas['numfactura']),
                "dSerieNum" => "AA",
                "dFeIniT" => (string) $this->datosVentas['FecInitTimbElec'],
                "dFeEmiDE" => $this->dfeemide,
                "iTipTra" => (int) $this->datosVentas['idtipotranset'], //1:Venta de mercaderias, 2:Prestacion de servicios, etc.
                "dDesTipTra" => "Venta de mercadería",
                "iTImp" => 1,
                "dDesTImp" => "IVA",//Agregar tabla de parametrizaciones
                "cMoneOpe" => "PYG",//Agregar tabla de parametrizaciones
                "dDesMoneOpe" => "Guarani",//Agregar tabla de parametrizaciones
                "dRucEm" => (string) $this->datosVentas['rucempresa'],
                "dDVEmi" => (int) $this->datosVentas['dvempresa'],
                "iTipCont" => (int) $this->datosVentas['tipoPersonaEmpresa'],
                "cTipReg" => 8, //Consultar, tipo de regimen
                "dNomEmi" => $this->datosVentas['rsempresa'], //En produccion poner la razon social del cliente
                //"dNomFanEmi" => $this->datosVentas['nomFantasiaCliente'],
                "iTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? 1 : 6,
                "dDTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? "Cédula paraguaya" : "Tarjeta Diplomática de exoneración fiscal",
                "dNumIDRec" => "x",
                "dDirEmi" => $this->datosVentas['direccionempresa'],
                "dNumCas" => 0, //Si no tiene numero de casa, poner cero
                "dCompDir1" => "-",
                "dCompDir2" => "-",
                "cDepEmi" => (int) $this->datosVentas['departamento_emisor_set'], //Departamento del Emisor Según XSD de Departamentos
                "dDesDepEmi" => (string) $this->datosVentas['descDepaEmisorSet'],//Debe ser Según XSD de Departamentos Debe corresponder a lo declarado en el RUC
                "cDisEmi" => (int) $this->datosVentas['distritoEmisor'],
                "dDesDisEmi" => (string) $this->datosVentas['distritoEmisorDesc'],
                "cCiuEmi" => (int) $this->datosVentas['ciuEmisorSet'],
                "dDesCiuEmi" => (string) $this->datosVentas['desCiuEmisorSet'],
                "dTelEmi" => $this->datosVentas['telefonoempresa'],
                "dEmailE" => $this->datosVentas['emailempresa'],
                "cActEco" => $this->datosVentas['actEconEmpresaCod'], //Debe agregarse una tabla o un campo en la tabla empresas, es el tipo de actividad de la empresa
                "dDesActEco" => $this->datosVentas['actEconEmpresa'],
                "iNatRec" => ($this->datosVentas['diplomatico'] == 'N') ? (int) $this->datosVentas['tipoContribuyente'] : 2, //Debe agregarse si el cliente es contribuyente o no en la tabla de clientes
                "iTiOpe" => ((int) $this->datosVentas['tipoPersonaCliente'] == 1) ? 2 : 1,
                "cPaisRec" => "PRY",//Parametrizar
                "dDesPaisRe" => "Paraguay",//Parametrizar
                "dDVRec" => (int) $this->datosVentas['dv'], //Agregar digito verificador a la tabla de clientes
                "dNomRec" => htmlspecialchars($this->datosVentas['razonSocialCliente']),
                //"dNomFanRec" => "Cliente",
                "dDirRec" => (string) $this->datosVentas['direccionCliente'],
                "dNumCasRec" => (int) $this->datosVentas['numCasaCliente'], //Debe agregarse el campo en la tabla clientes
                "cDepRec" => (int) $this->datosVentas['departamentoCliente'],
                "dDesDepRec" => (string) $this->datosVentas['departamentoClienteDesc'],
                "cDisRec" => (int) $this->datosVentas['distritoCliente'],
                "dDesDisRec" => (string) $this->datosVentas['desDistritoCliente'],
                "cCiuRec" => (int) $this->datosVentas['ciudadCliente'],
                "dDesCiuRec" => (string) $this->datosVentas['desCiudadCliente'],
                "dTelRec" => (string) $this->datosVentas['celularCliente'],
                "dEmailRec" => (string) $this->datosVentas['emailCliente'],
                "dCodCliente" => (string) $this->generateUniqueIDRand(),
                "iIndPres" => (int) $this->datosVentas['idIndiPresencia'],
                "dDesIndPres" => (string) $this->datosVentas['descIndiPresencia'],
                ///Formas de pago
                "gCamCond" => [

                    "iCondOpe" => 1,
                    "dDCondOpe" => "Contado",
                    "gPaConEIni" => $this->datosFormasPago

                ],

                // INICIO ITEMS DE LA FACTURA

                "gCamItem" => $this->datosVentasItems,

                // FIN ITEMS DE LA FACTURA

                "dSubExe" => floatval($this->dSubExe),
                "dSubExo" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : floatval($this->dSubExo),
                "dSub5" => floatval($this->dSub5),
                "dSub10" => floatval($this->dSub10),
                "dTotOpe" => ($this->datosVentas['diplomatico'] == 'S') ? floatval($this->dSubExe) : floatval($this->dSubExe + $this->dSubExo + $this->dSub5 + $this->dSub10),
                "dTotDesc" => 0,
                "dTotDescGlotem" => 0,
                "dTotAntItem" => 0, //Anticipos
                "dTotAnt" => 0, //Anticipos
                "dPorcDescTotal" => (int) $this->datosVentas['descporc'],
                "dDescTotal" => 0,
                "dAnticipo" => 0,
                "dRedon" => 0,
                "dComi" => 0,
                "dTotGralOpe" => ($this->datosVentas['diplomatico'] == 'S') ? floatval($this->dSubExe) : floatval($this->dSubExe + $this->dSubExo + $this->dSub5 + $this->dSub10) - 0 /* Redondeo */ + 0 /* dComi */,
                "dIVA5" => $this->dIVA5,
                "dIVA10" => $this->dIVA10,
                "dIVAComi" => 0,
                "dTotIVA" => $this->dIVA5 + $this->dIVA10 - (0 /* Redondeo al 5% */ + 0 /* Redondeo al 10% */),//Correcto
                "dBaseGrav5" => (float) round($this->datosVentas['dBaseGrav5'], 2),//Correcto
                "dBaseGrav10" => (float) round($this->datosVentas['dBaseGrav10'], 2),//Correcto
                "dTBasGraIVA" => (float) round(($this->datosVentas['dBaseGrav5'] + (float) $this->datosVentas['dBaseGrav10']), 2),

            ];

            # Si la naturaleza del receptor es contribuyente
            if ($this->datosVentas['tipoContribuyente'] == 1) {

                unset($this->params["iTipIDRec"]);
                unset($this->params["dDTipIDRec"]);
                $this->params["iTiContRec"] = (int) $this->datosVentas['tipoPersonaCliente'];

                if ($this->datosVentas["tipoPersonaCliente"] == 1) {//Si es Persona Fisica

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                        // Separa el ruc por guion
                        $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                        $this->params["dRucRec"] = $partes_ruc[0];
                        $this->params["dDVRec"] = $partes_ruc[1];
                        $this->params["dNumIDRec"] = $partes_ruc[0];

                    } else {

                        unset($this->params["iTiContRec"]);
                        $this->params["iNatRec"] = 2;
                        $this->params["dNumIDRec"] = '0';
                        $this->params["iTipIDRec"] = 5;
                        $this->params["dDTipIDRec"] = "Innominado";
                        $this->params["dNomRec"] = "Sin Nombre";

                    }

                } else { // Si es una empresa

                    // Separa el ruc por guion
                    $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                    $this->params["dRucRec"] = $partes_ruc[0];
                    $this->params["dDVRec"] = $partes_ruc[1];
                    $this->params["iTiOpe"] = ((int) $this->datosVentas['tipoPersonaCliente'] == 1) ? 2 : 1;
                    $this->params["dNumIDRec"] = $partes_ruc[0];

                }

                if ($this->datosVentas['diplomatico'] == 'S') {

                    $this->params["iTipIDRec"] = 6;
                    $this->params["dDTipIDRec"] = "Tarjeta Diplomática de exoneración fiscal";
                    $this->params["dNumIDRec"] = (string) $this->datosVentas['ruc'];
                    unset($this->params["dRucRec"]);
                    unset($this->params["iTiContRec"]);

                } else {

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                        /* Hago split del RUC */
                        $ruc_split = explode('-', $this->datosVentas['ruc']);
                        $this->params["dRucRec"] = (string) $ruc_split[0];

                    }

                }

                if ($this->datosVentas['rucReceptor'] != $this->rucGenerico) {

                    $this->params["dDVRec"] = (int) $this->datosVentas['dv'];

                } else {

                    unset($this->params["dDVRec"]);

                }

            }

            // si telefono no esta entre 6 y 15
            if (strlen($this->datosVentas['dTelRec'].'') < 6 or strlen($this->datosVentas['dTelRec'].'') > 15) {
                unset($this->params["dTelRec"]);
            }

            #Si no tiene direccion, no es obligatorio informar distrito, ni departamento ni ciudad
            if ((empty($this->datosVentas['direccionCliente']) || $this->datosVentas['direccionCliente'] == '')
               || (empty($this->datosVentas['departamentoCliente']) || $this->datosVentas['departamentoCliente'] == '' || $this->datosVentas['departamentoCliente'] == 0)
               || (empty($this->datosVentas['distritoCliente']) || $this->datosVentas['distritoCliente'] == '' || $this->datosVentas['distritoCliente'] == 0)
               || (empty($this->datosVentas['ciudadCliente']) || $this->datosVentas['ciudadCliente'] == '' || $this->datosVentas['ciudadCliente'] == 0)) {

                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);

            }

            // valida vinculacion de departamento, distrito y cuidad
            $id_departamentoCliente = intval($this->datosVentas['departamentoCliente']);
            $id_ciudadCliente = intval($this->datosVentas['ciudadCliente']);
            $id_distritoCliente = intval($this->datosVentas['distritoCliente']);
            $consulta = "
            select departamentos.id as iddepartamento
            FROM departamentos
            inner join distrito on distrito.iddepartamento = departamentos.id
            inner join ciudades on ciudades.iddistrito = distrito.iddistrito
            WHERE
            departamentos.estado = 1
            and distrito.estado = 1
            and ciudades.estado = 1
            and departamentos.id = $id_departamentoCliente
            and distrito.iddistrito = $id_distritoCliente
            and ciudades.idciudad = $id_ciudadCliente
            ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //si no esta bien vinculado no informa
            if (intval($rs->fields['iddepartamento']) == 0) {
                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);
            }

        }

        private function armarArrayNotaCredito()
        {

            global $conexion;

            $this->numeroComprobante = $this->arrayNotaCreditoCabeza['numero'];
            $this->fecha_comprobante = $this->arrayNotaCreditoCabeza['fecha_nota'];

            $rs_documento_electronico = $this->buscarDocumentoElectronicoGenerado($this->numeroComprobante);

            if ($rs_documento_electronico['total'] > 0) {

                $this->dcodseg = (int) $rs_documento_electronico['dcodseg'];
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($rs_documento_electronico['dfeemide']));
                $this->cdc = $rs_documento_electronico['cdc'];

            } else {

                $this->dcodseg = (int) $this->generateUniqueIDRand();
                $this->dfeemide = date('Y-m-d\TH:i:s', strtotime($this->arrayNotaCreditoCabeza['fecha_nota']));
                $this->cdc = '';

            }

            // Separa el numero de comprobante en tres partes por -
            # parte[001]: Sucursal
            # parte[001]: Punto de expedicion
            # parte[0000001]: Correlativo

            $this->params = [
                "dDVId" => 6,
                "dFecFirma" => '2023-04-10T14:38:24',
                "dSisFact" => 1,
                "iTipEmi" => 1,
                "dDesTipEmi" => "Normal",
                "dCodSeg" => $this->dcodseg,
                "iTiDE" => 5,
                "dDesTiDE" => "Nota de crédito electrónica",
                "dNumTim" => (int) $this->arrayNotaCreditoCabeza['timbradonc'],
                "dEst" => (string) substr($this->numeroComprobante, 0, 3),
                "dPunExp" => (string) substr($this->numeroComprobante, 4, 3),
                "dNumDoc" => (string) substr($this->numeroComprobante, 8),
                "dSerieNum" => "AA",
                "dFeIniT" => (string) $this->arrayNotaCreditoCabeza['timb_valido_desde'],
                "dFeEmiDE" => $this->dfeemide,
                //"iTipTra" => (int) $this->datosVentas['idtipotranset'], //1:Venta de mercaderias, 2:Prestacion de servicios, etc.
                //"dDesTipTra" => "Prestación de servicios",
                "iTImp" => 1,
                "dDesTImp" => "IVA",//Agregar tabla de parametrizaciones
                "cMoneOpe" => "PYG",//Agregar tabla de parametrizaciones
                "dDesMoneOpe" => "Guarani",//Agregar tabla de parametrizaciones
                "dRucEm" => (string) $this->datosVentas['rucempresa'],
                "dDVEmi" => (int) $this->datosVentas['dvempresa'],
                "iTipCont" => (int) $this->datosVentas['tipoPersonaEmpresa'],
                "cTipReg" => 8, //Consultar, tipo de regimen
                "dNomEmi" => $this->datosVentas['rsempresa'], //En produccion poner la razon social del cliente
                //"dNomFanEmi" => $this->datosVentas['nomFantasiaCliente'],
                "iTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? 1 : 6,
                "dDTipIDRec" => ($this->datosVentas['diplomatico'] == 'N') ? "Cédula paraguaya" : "Tarjeta Diplomática de exoneración fiscal",
                "dNumIDRec" => "x",
                "dDirEmi" => $this->datosVentas['direccionempresa'],
                "dNumCas" => 0, //Si no tiene numero de casa, poner cero
                "dCompDir1" => "-",
                "dCompDir2" => "-",
                "cDepEmi" => (int) $this->datosVentas['departamento_emisor_set'], //Departamento del Emisor Según XSD de Departamentos
                "dDesDepEmi" => (string) $this->datosVentas['descDepaEmisorSet'],//Debe ser Según XSD de Departamentos Debe corresponder a lo declarado en el RUC
                "cDisEmi" => (int) $this->datosVentas['distritoEmisor'],
                "dDesDisEmi" => (string) $this->datosVentas['distritoEmisorDesc'],
                "cCiuEmi" => (int) $this->datosVentas['ciuEmisorSet'],
                "dDesCiuEmi" => (string) $this->datosVentas['desCiuEmisorSet'],
                "dTelEmi" => $this->datosVentas['telefonoempresa'],
                "dEmailE" => $this->datosVentas['emailempresa'],
                "cActEco" => $this->datosVentas['actEconEmpresaCod'], //Debe agregarse una tabla o un campo en la tabla empresas, es el tipo de actividad de la empresa
                "dDesActEco" => $this->datosVentas['actEconEmpresa'],
                "iNatRec" => ($this->datosVentas['diplomatico'] == 'N') ? (int) $this->datosVentas['tipoContribuyente'] : 2, //Debe agregarse si el cliente es contribuyente o no en la tabla de clientes
                "iTiOpe" => 2,
                "cPaisRec" => "PRY",//Parametrizar
                "dDesPaisRe" => "Paraguay",//Parametrizar
                "dDVRec" => (int) $this->datosVentas['dv'], //Agregar digito verificador a la tabla de clientes
                "dNomRec" => htmlspecialchars($this->datosVentas['razonSocialCliente']),
                //"dNomFanRec" => "Cliente",
                "dDirRec" => (string) $this->datosVentas['direccionCliente'],
                "dNumCasRec" => (int) $this->datosVentas['numCasaCliente'], //Debe agregarse el campo en la tabla clientes
                "cDepRec" => (int) $this->datosVentas['departamentoCliente'],
                "dDesDepRec" => (string) $this->datosVentas['departamentoClienteDesc'],
                "cDisRec" => (int) $this->datosVentas['distritoCliente'],
                "dDesDisRec" => (string) $this->datosVentas['desDistritoCliente'],
                "cCiuRec" => (int) $this->datosVentas['ciudadCliente'],
                "dDesCiuRec" => (string) $this->datosVentas['desCiudadCliente'],
                "dTelRec" => (string) $this->datosVentas['celularCliente'],
                "dEmailRec" => (string) $this->datosVentas['emailCliente'],
                "dCodCliente" => (string) $this->generateUniqueIDRand(),
                "iMotEmi" => (int) $this->arrayNotaCreditoCabeza['id_motivo_emision_nota_credito'],
                "dDesMotEmi" => (string) $this->arrayNotaCreditoCabeza['motivo_emision_nc'],
                // INICIO ITEMS DE LA NC

                "gCamItem" => $this->arrayNotaCreditoDetalle,

                // FIN ITEMS DE LA FACTURA

                "dSubExe" => floatval($this->dSubExe),
                "dSubExo" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : floatval($this->dSubExo),
                "dSub5" => floatval($this->dSub5),
                "dSub10" => floatval($this->dsub10_nota_credito),
                "dTotOpe" => floatval($this->dtot_op_nota_credito),
                "dTotDesc" => 0,
                "dTotDescGlotem" => 0,
                "dTotAntItem" => 0, //Anticipos
                "dTotAnt" => 0, //Anticipos
                "dPorcDescTotal" => (int) $this->datosVentas['descporc'],
                "dDescTotal" => 0,
                "dAnticipo" => 0,
                "dRedon" => 0,
                "dComi" => 0,
                "dTotGralOpe" => floatval($this->dtotgral_op_nota_credito) - 0 /* Redondeo */ + 0 /* dComi */,
                "dIVA5" => 0,
                "dIVA10" => floatval($this->diva10_nota_credito),
                "dIVAComi" => 0,
                "dTotIVA" => floatval($this->diva10totiva_nota_credito) - (0 /* Redondeo al 5% */ + 0 /* Redondeo al 10% */),//Correcto
                "dBaseGrav5" => (float) round($this->datosVentas['dbasegrav5'], 2),//Correcto
                "dBaseGrav10" => (float) round($this->dbasegrav10_nota_credito, 2),//Correcto
                "dTBasGraIVA" => (float) round(($this->dtotbasegrav10_nota_credito), 2),
                "iTipDocAso" => (int) $this->arrayNotaCreditoCabeza['IDTipoDocumentoAsociado'],
                "dDesTipDocAso" => (string) $this->arrayNotaCreditoCabeza['descTipoDocumentoAsociado'],
                "dCdCDERef" => (string) $this->buscarDocumentoElectronicoGeneradoXIDVenta($this->datosVentas['idventa']),
                "dNTimDI" => (int) $this->datosVentas['timbradoEmpresa'],
                //"dEstDocAso" => (string) $partesComprobante[0]
                "dEstDocAso" => $this->cdc
            ];

            // Si iTipDocAso == 2 o 3 se elimina dCdCDERef
            if ($this->arrayNotaCreditoCabeza['id_tipo_documento_asociado'] == 2 || $this->arrayNotaCreditoCabeza['id_tipo_documento_asociado'] == 3) {

                unset($this->params["dCdCDERef"]);
                // Obtengo la sucursal, punto expedicion y numero de factura del documento asociado
                $this->params["iTipDocAso"] = (int) $this->datosVentas['id_tipo_documento_asociado'];
                $this->params["dNTimDI"] = intval($this->datosVentas['timbrado_documento_electronico_asociado']);
                $this->params["dEstDocAso"] = substr($this->datosVentas['numero_factura'], 0, 3);
                $this->params["dPExpDocAso"] = substr($this->datosVentas['numero_factura'], 3, 3);
                $this->params["dNumDocAso"] = substr($this->datosVentas['numero_factura'], 6);
                $this->params["iTipoDocAso"] = 1;
                $this->params["dDTipoDocAso"] = "Factura";
                $this->params["dDTipoDocAso"] = date('Y-m-d', strtotime($this->datosVentas['fecha_operacion_factura']));

            }

            // Si iTipDocAso == 2 o 3 se elimina dCdCDERef, dEstDocAso
            if ($this->arrayNotaCreditoCabeza['id_tipo_documento_asociado'] == 1 || $this->arrayNotaCreditoCabeza['id_tipo_documento_asociado'] == 3) {

                unset($this->params["dNTimDI"]);
                unset($this->params["dEstDocAso"]);

            }

            # Si la naturaleza del receptor es contribuyente
            if ($this->datosVentas['tipoContribuyente'] == 1) {

                unset($this->params["iTipIDRec"]);
                unset($this->params["dDTipIDRec"]);

                $this->params["iTiContRec"] = (int) $this->datosVentas['tipoPersonaCliente'];

                if ($this->datosVentas["tipoPersonaCliente"] == 1) {//Si es Persona Fisica

                    // Verifica si es que tiene guion el ruc o cedula
                    $contieneguion = strstr($this->datosVentas["rucReceptor"], '-');

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico && $contieneguion) {

                        // Separa el ruc por guion
                        $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                        $this->params["dRucRec"] = $partes_ruc[0];
                        $this->params["dDVRec"] = $partes_ruc[1];
                        $this->params["dNumIDRec"] = $partes_ruc[0];

                    } else {

                        if ($this->datosVentas['rucReceptor'] == $this->rucGenerico) {

                            unset($this->params["iTiContRec"]);
                            // Elimina los datos de RUC
                            unset($this->params["dRucRec"]);
                            unset($this->params["dDVRec"]);
                            $this->params["iNatRec"] = 2;
                            $this->params["dNumIDRec"] = '0';
                            $this->params["iTipIDRec"] = 5;
                            $this->params["dDTipIDRec"] = "Innominado";
                            $this->params["dNomRec"] = "Sin Nombre";

                        } else {

                            unset($this->params["iTiContRec"]);
                            // Elimina los datos de RUC
                            unset($this->params["dRucRec"]);
                            unset($this->params["dDVRec"]);
                            $this->params["iNatRec"] = 2;
                            $this->params["dNumIDRec"] = $this->datosVentas["rucReceptor"];
                            $this->params["iTipIDRec"] = 1;
                            $this->params["dDTipIDRec"] = "Cédula paraguaya";
                            $this->params["dNomRec"] = $this->datosVentas["razonSocialCliente"];

                        }

                    }

                } else { // Si es una empresa

                    // Separa el ruc por guion
                    $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                    $this->params["dRucRec"] = $partes_ruc[0];
                    $this->params["dDVRec"] = $partes_ruc[1];
                    $this->params["iTiOpe"] = 1;
                    //$this->params["dNumIDRec"] = (string) $partes_ruc[0];

                }

                if ($this->datosVentas['diplomatico'] == 'S') {

                    $this->params["iTipIDRec"] = 6;
                    $this->params["dDTipIDRec"] = "Tarjeta Diplomática de exoneración fiscal";
                    $this->params["dNumIDRec"] = (string) $this->datosVentas['ruc'];
                    unset($this->params["dRucRec"]);
                    unset($this->params["iTiContRec"]);

                } else {

                    // Verifica si es que tiene guion el ruc o cedula
                    $contieneguion = strstr($this->datosVentas["rucReceptor"], '-');

                    if ($this->datosVentas['rucReceptor'] != $this->rucGenerico && $contieneguion) {

                        /* Hago split del RUC */
                        $ruc_split = explode('-', $this->datosVentas['ruc']);
                        $this->params["dRucRec"] = (string) $ruc_split[0];

                    }

                }

                if ($this->datosVentas['rucReceptor'] != $this->rucGenerico && $contieneguion) {

                    $partes_ruc = explode('-', $this->datosVentas["rucReceptor"]);
                    $this->params["dDVRec"] = (int) $partes_ruc[1];

                } else {

                    unset($this->params["dDVRec"]);

                }

            }

            // si telefono no esta entre 6 y 15
            if (strlen($this->datosVentas['dTelRec'].'') < 6 or strlen($this->datosVentas['dTelRec'].'') > 15) {
                unset($this->params["dTelRec"]);
            }

            #Si no tiene direccion, no es obligatorio informar distrito, ni departamento ni ciudad
            if ((empty($this->datosVentas['direccionCliente']) || $this->datosVentas['direccionCliente'] == '')
               || (empty($this->datosVentas['departamentoCliente']) || $this->datosVentas['departamentoCliente'] == '' || $this->datosVentas['departamentoCliente'] == 0)
               || (empty($this->datosVentas['distritoCliente']) || $this->datosVentas['distritoCliente'] == '' || $this->datosVentas['distritoCliente'] == 0)
               || (empty($this->datosVentas['ciudadCliente']) || $this->datosVentas['ciudadCliente'] == '' || $this->datosVentas['ciudadCliente'] == 0)) {

                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);

            }

            // valida vinculacion de departamento, distrito y cuidad
            $id_departamentoCliente = intval($this->datosVentas['departamentoCliente']);
            $id_ciudadCliente = intval($this->datosVentas['ciudadCliente']);
            $id_distritoCliente = intval($this->datosVentas['distritoCliente']);
            $consulta = "
            select departamentos.id as iddepartamento
            FROM departamentos
            inner join distrito on distrito.iddepartamento = departamentos.id
            inner join ciudades on ciudades.iddistrito = distrito.iddistrito
            WHERE
            departamentos.estado = 1
            and distrito.estado = 1
            and ciudades.estado = 1
            and departamentos.id = $id_departamentoCliente
            and distrito.iddistrito = $id_distritoCliente
            and ciudades.idciudad = $id_ciudadCliente
            ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //si no esta bien vinculado no informa
            if (intval($rs->fields['iddepartamento']) == 0) {
                unset($this->params["dDirRec"]);
                unset($this->params["dNumCasRec"]);
                unset($this->params["cDepRec"]);
                unset($this->params["dDesDepRec"]);
                unset($this->params["cDisRec"]);
                unset($this->params["dDesDisRec"]);
                unset($this->params["cCiuRec"]);
                unset($this->params["dDesCiuRec"]);
                unset($this->params["dTelRec"]);
                unset($this->params["dEmailRec"]);
            }

        }

        private function iniciarDatosNC($idNotaCredito)
        {

            global $conexion;
            $idNotaCredito = intval($idNotaCredito);

            $consulta = "select sum(precio) as suma_precio from nota_credito_cuerpo where idnotacred = $idNotaCredito";

            $rs_datos_cuerpo_nc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $this->dsub10_nota_credito = $rs_datos_cuerpo_nc->fields['suma_precio'];
            $this->dtot_op_nota_credito = $rs_datos_cuerpo_nc->fields['suma_precio'];
            $this->dtotgral_op_nota_credito = $rs_datos_cuerpo_nc->fields['suma_precio'];
            $this->diva10_nota_credito = round(($rs_datos_cuerpo_nc->fields['suma_precio'] / 11), 2);
            $this->diva10totiva_nota_credito = round(($rs_datos_cuerpo_nc->fields['suma_precio'] / 11), 2);
            $this->dbasegrav10_nota_credito = round(($rs_datos_cuerpo_nc->fields['suma_precio'] / 1.1), 2);
            $this->dtotbasegrav10_nota_credito = round(($rs_datos_cuerpo_nc->fields['suma_precio'] / 1.1), 2);


        }

        private function iniciarDatosVentas($idVenta)
        {

            global $conexion;
            $idVenta = intval($idVenta);

            $consulta = "SELECT v.*, replace(substring(factura,7,7), '-', '') as numfactura,        
                         e.ruc as rucempresa, e.dv as dvempresa, e.razon_social rsempresa, e.direccion as direccionempresa,  
                         e.telefono as telefonoempresa, e.email as emailempresa, actividad_economica_codigo as actEconEmpresaCod,
                         actividad_economica as actEconEmpresa,timbrado.timbrado as timbradoEmpresa, timbrado.inicio_vigencia as FecInitTimbElec,
                         e.tipo_contribuyente as tipoPersonaEmpresa,
                         e.departamento_emisor_set, depa.descripcion as descDepaEmisorSet,
                         e.distrito_emisor_set as distritoEmisor, disEmi.distrito as distritoEmisorDesc,
                         e.ciudad_emisor_set as ciudadEmisor,
                         e.ciudad_emisor_set as ciuEmisorSet, ciu.nombre as desCiuEmisorSet,
                         c.direccion as direccionCliente, 
                         c.telefono as celularCliente, c.email as emailCliente,
                         c.idtiporeceptor_set as tipoContribuyente, c.fantasia as nomFantasiaCliente,
                         c.departamento as departamentoCliente,
                         c.numero_casa as numCasaCliente,
                         d.descripcion as departamentoClienteDesc,
                         c.idciudad as ciudadCliente, ciuReceptor.nombre as desCiudadCliente,
                         c.id_distrito as distritoCliente, disReceptor.distrito as desDistritoCliente,
                         c.tipocliente as tipoPersonaCliente,
                         v.ruc as rucReceptor,
                         v.razon_social as razonSocialCliente,
                         ip.id as idIndiPresencia, ip.descripcion as descIndiPresencia,
                         (select sum(gravadoml) from ventas_detalles_impuesto where idventa = v.idventa and iva_porc_col = 10 and gravadoml >= 0) as dBaseGrav10,
                         (select sum(gravadoml) from ventas_detalles_impuesto where idventa = v.idventa and iva_porc_col = 5 and gravadoml >= 0) as dBaseGrav5
                         FROM ventas v
                         JOIN empresas e ON v.idempresa = e.idempresa
                         INNER JOIN facturas on facturas.idtanda = v.idtandatimbrado
                         INNER JOIN timbrado on timbrado.idtimbrado = facturas.idtimbrado
                         LEFT JOIN cliente c ON c.idcliente = v.idcliente
                         LEFT JOIN departamentos d ON d.id = c.departamento
                         LEFT JOIN ciudades ciuReceptor ON ciuReceptor.idciudad = c.idciudad
                         LEFT JOIN distrito disReceptor ON disReceptor.iddistrito = c.id_distrito
                         LEFT JOIN indicador_presencia ip ON ip.id = v.id_indicador_presencia
                         LEFT JOIN departamentos depa ON depa.id = e.departamento_emisor_set
                         LEFT JOIN distrito disEmi ON disEmi.iddistrito = e.distrito_emisor_set
                         LEFT JOIN ciudades ciu ON ciu.idciudad = e.ciudad_emisor_set
                         WHERE 
                         idventa = $idVenta";

            $this->datosVentas = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "SELECT vd.*, p.descripcion as descripcionProducto, 
                        vd.subtotal,
                        vd.descuento as descItem, fati.descripcion as descFati,
                        ti.iva_porc as ivaVentaPorcentaje,
                        fati.descripcion as descripcionFormaAfecIVA
                        FROM ventas_detalles vd
                        LEFT JOIN productos p ON p.idprod = vd.idprod
                        LEFT JOIN forma_afectacion_tributaria_iva fati ON fati.id = vd.id_forma_afectacion_tributaria_iva 
                        LEFT JOIN tipo_iva ti ON ti.idtipoiva = vd.idtipoiva
                        where vd.idventa = $idVenta
                        AND vd.subtotal >= 0";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            foreach ($rs as $fila) {

                //$auxTotOpeItem = (($fila['precioUniVentaProducto'] - $fila['descItem'] - 0 - 0 - 0) * $fila['cantidad']);
                $auxTotOpeItem = $fila['subtotal'];
                $dBasGravIVA = ($fila['id_forma_afectacion_tributaria_iva'] == 1 || $fila['id_forma_afectacion_tributaria_iva'] == 4) ? (($fila['ivaVentaPorcentaje'] == 10) ? round((($auxTotOpeItem) * ($fila['proporcion_gravada_iva'] / 100) / 1.1), 2) : round((($auxTotOpeItem) * ($fila['proporcion_gravada_iva'] / 100) / 1.05), 2)) : 0;
                $this->dSub5 = ($fila['ivaVentaPorcentaje'] == 5) ? ($this->dSub5 + $auxTotOpeItem) : ($this->dSub5 + 0);
                $this->dSub10 = ($fila['ivaVentaPorcentaje'] == 10) ? ($this->dSub10 + $auxTotOpeItem) : ($this->dSub10 + 0);
                $this->dSubExe = ($fila['ivaVentaPorcentaje'] == 0) ? ($this->dSubExe + $auxTotOpeItem) : ($this->dSubExe + 0);
                $this->dSubExo = ($fila['ivaVentaPorcentaje'] == 0) ? ($this->dSubExo + $auxTotOpeItem) : ($this->dSubExo + 0);
                $this->dTotDesc = $this->dTotDesc + $fila['descItem'];
                //$this->dPorcDescTotal = ($fila['descItem']>0)?($this->dPorcDescTotal + (($fila['descItem'] * 100)/$fila['precioUniVentaProducto'])):$this->dPorcDescTotal + 0;
                $this->dDescTotal = $this->dDescTotal + $fila['descItem'];

                $this->sumBasGravIva5 = ($fila['ivaVentaPorcentaje'] == 5) ? $this->sumBasGravIva5 + $dBasGravIVA : $this->sumBasGravIva5 + 0;
                $this->sumBasGravIva10 = ($fila['ivaVentaPorcentaje'] == 10) ? $this->sumBasGravIva10 + $dBasGravIVA : $this->sumBasGravIva10 + 0;

                $consulta_iva = "select * from ventas_detalles
                                inner join ventas_detalles_impuesto vdt ON vdt.idventadet = ventas_detalles.idventadet
                                where ventas_detalles.idventadet = ".intval($fila['idventadet']);

                $rs_iva = $conexion->Execute($consulta_iva) or die(errorpg($conexion, $consulta_iva));

                foreach ($rs_iva as $fila_iva) {

                    $baseIVAGrav = ($fila_iva['iva_porc_col'] > 0) ? $fila_iva['gravadoml'] : 0;
                    $dLiqIVAItem = ($fila_iva['iva_porc_col'] > 0) ? $baseIVAGrav * ($fila_iva['iva_porc_col'] / 100) : 0;

                    $this->dIVA5 = ($fila['ivaVentaPorcentaje'] == 5) ? $this->dIVA5 + $dLiqIVAItem : $this->dIVA5 + 0;
                    $this->dIVA10 = ($fila['ivaVentaPorcentaje'] == 10) ? $this->dIVA10 + $dLiqIVAItem : $this->dIVA10 + 0;

                    $gCamIVA = [

                        "iAfecIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 3 : $fila['id_forma_afectacion_tributaria_iva'],
                        "dDesAfecIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 'Exento' : $fila['descripcionFormaAfecIVA'],
                        "dPropIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : (($fila['id_forma_afectacion_tributaria_iva'] == 3) ? 0 : 100),
                        "dTasaIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : intval($fila_iva['iva_porc_col']),
                        "dBasGravIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : round($baseIVAGrav, 2),
                        "dLiqIVAItem" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : round($dLiqIVAItem, 2),
                        "dBasExe" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : (($fila['id_forma_afectacion_tributaria_iva'] != 4) ? 0 : ((100 * $fila['subtotal'] * (100 - 100)) / (10000 + (intval($fila_iva['iva_porc_col']) * 100)))),

                    ];

                    /*if($idVenta == 647145){
                        echo $this->datosVentas['diplomatico'];
                        if($this->datosVentas['diplomatico'] == 'S'){
                            //echo ($this->datosVentas['diplomatico'] == 'S')?0:($fila['id_forma_afectacion_tributaria_iva'] == 3)?0:100;
                            echo ($this->datosVentas['diplomatico'] == 'S')?0:(($fila['id_forma_afectacion_tributaria_iva'] == 3)?0:100);
                        }
                        print_r($this->datosVentas['diplomatico']);exit;
                    }*/

                }

                $items = [

                    "gCamItem" => [
                        "dCodInt" => $fila['idprod'],
                        "dDesProSer" => htmlspecialchars($fila['descripcionProducto']),
                        "cUniMed" => 77,
                        "dDesUniMed" => 'UNI',
                        "dCantProSer" => $fila['cantidad'],

                        "gValorItem" => [

                            "dPUniProSer" => round(($fila['subtotal'] / $fila['cantidad']), 2),
                            "dTotBruOpeItem" => $fila['subtotal'],
                            "gValorRestaItem" => [

                                "dDescItem" => 0,
                                "dPorcDesIt" => 0,
                                "dDescGloItem" => 0,
                                "dAntPreUniIt" => 0,
                                "dAntGloPreUniIt" => 0,
                                "dTotOpeItem" => $auxTotOpeItem,

                            ]

                        ],

                        "gCamIVA" => $gCamIVA

                    ]

                ];

                array_push($this->datosVentasItems, $items);

            }

            //////////////////////Formas de pago/////////////////////////////////////////

            $consulta = "SELECT formas_pago.idformapago_set, gest_pagos_det.idformapago as idformapago_sistema,
            gest_pagos_det.monto_pago_det, formas_pago_set.descripcion as descFormaPagoSet,
            denominacion_tarjeta.id as id_denominacion_tarjeta, denominacion_tarjeta.descripcion as descripcion_denominacion_tarjeta,
            id_forma_procesamiento_pago, gest_pagos_det_datos.cheque_numero, bancos.nombre as nombre_banco
            FROM gest_pagos_det
            left join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
            left join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
            left join formas_pago_set on formas_pago_set.id = formas_pago.idformapago_set
            left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
            left join denominacion_tarjeta ON denominacion_tarjeta.id = gest_pagos_det_datos.id_denominacion_tarjeta
            left join bancos ON bancos.idbanco = gest_pagos_det_datos.idbanco
            where
            idventa = $idVenta";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            #Reinicio el array de items
            unset($items);

            foreach ($rs as $fila) {

                if ($fila['idformapago_set'] == 3 || $fila['idformapago_set'] == 4) {

                    $gPagTarCD = [

                        "iDenTarj" => (isset($fila['id_denominacion_tarjeta']) || $fila['id_denominacion_tarjeta'] != "") ? $fila['id_denominacion_tarjeta'] : 1,
                        "dDesDenTarj" => (isset($fila['descripcion_denominacion_tarjeta']) || $fila['descripcion_denominacion_tarjeta'] != "") ? $fila['descripcion_denominacion_tarjeta'] : "Visa",
                        "iForProPa" => (isset($fila['descripcion_denominacion_tarjeta']) || $fila['descripcion_denominacion_tarjeta'] != "") ? $fila['id_forma_procesamiento_pago'] : 1,

                    ];

                } elseif ($fila['idformapago_set'] == 2) {

                    $gPagCheq = [

                        "dNumCheq" => (isset($fila['numero_cheque']) || $fila['numero_cheque'] != "") ? $fila['numero_cheque'] : agregacero("0".rand(1000, 1000000), 8),
                        "dBcoEmi" => (isset($fila['nombre_banco']) || $fila['nombre_banco'] != "") ? $fila['nombre_banco'] : "Itau",

                    ];

                }

                $items = [

                    "gPaConEIni" => [

                        "iTiPago" => $fila['idformapago_set'],
                        "dDesTiPag" => $fila['descFormaPagoSet'],
                        "dMonTiPag" => $fila['monto_pago_det'],
                        "cMoneTiPag" => "PYG",
                        "dDMoneTiPag" => "Guarani",
                        "gPagTarCD" => $gPagTarCD,
                        "gPagCheq" => $gPagCheq
                    ]

                ];


                if ($fila['idformapago_set'] != 3 && $fila['idformapago_set'] != 4 && $fila['idformapago_set'] != 2) {

                    unset($items['gPaConEIni']['gPagTarCD']);
                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] != 3 && $fila['idformapago_set'] != 4) {

                    unset($items['gPaConEIni']['gPagTarCD']);

                } elseif ($fila['idformapago_set'] != 2) {

                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] == 3 || $fila['idformapago_set'] == 4) {

                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] == 2) {

                    unset($items['gPaConEIni']['gPagTarCD']);

                }

                array_push($this->datosFormasPago, $items);

            }

        }

        private function iniciarDatosNotaCreditoImpreso()
        {

            // Camuflar los datos de la nota de credito para que se muestren como ventas

            global $conexion;
            $idNc = intval($this->idnotacredito);

            $consulta = "select
                            v.*,
                            e.ruc as rucempresa,
                            e.dv as dvempresa,
                            e.razon_social rsempresa,
                            e.direccion as direccionempresa,
                            e.telefono as telefonoempresa,
                            e.email as emailempresa,
                            actividad_economica_codigo as actEconEmpresaCod,
                            actividad_economica as actEconEmpresa,
                            timbrado.timbrado as timbradoEmpresa,
                            timbrado.inicio_vigencia as FecInitTimbElec,
                            coalesce(c.tipocliente, 2) as tipoPersonaEmpresa,
                            e.departamento_emisor_set,
                            depa.descripcion as descDepaEmisorSet,
                            e.distrito_emisor_set as distritoEmisor,
                            disEmi.distrito as distritoEmisorDesc,
                            e.ciudad_emisor_set as ciudadEmisor,
                            e.ciudad_emisor_set as ciuEmisorSet,
                            ciu.nombre as desCiuEmisorSet,
                            '' as direccionCliente,
                            '' as celularCliente,
                            c.email as emailCliente,
                            coalesce(c.idtiporeceptor_set, 1) as tipoContribuyente,
                            c.fantasia as nomFantasiaCliente,
                            0 as departamentoCliente,
                            c.numero_casa as numCasaCliente,
                            '' as departamentoClienteDesc,
                            0 as ciudadCliente,
                            '' as desCiudadCliente,
                            0 as distritoCliente,
                            '' as desDistritoCliente,
                            coalesce(c.tipocliente, 1) as tipoPersonaCliente,
                            coalesce(CAST(c.ruc AS CHAR), CAST(c.documento AS CHAR)) as rucReceptor,
                            concat(c.nombre, ' ', c.apellido) as razonSocialCliente,
                            v.razon_social as razonSocialCliente,
                            ip.id as idIndiPresencia,
                            ip.descripcion as descIndiPresencia,
                            'N' as diplomatico,
                            (select sum(gravadoml) from nota_credito_cuerpo_impuesto where idnotacred = v.idnotacred and iva_porc_col = 10 and gravadoml >= 0) as dBaseGrav10,
                            (select sum(gravadoml) from nota_credito_cuerpo_impuesto where idnotacred = v.idnotacred and iva_porc_col = 5 and gravadoml >= 0) as dBaseGrav5,
                            fact.sucursal as factura_sucursal,
                            fact.punto_expedicion as factura_puntoexpedicion,
                            timbrado.inicio_vigencia as fecinittimbelec
                        from
                            nota_credito_cabeza v
                        join empresas e on
                            e.idempresa = 1
                        inner join timbrado on
                            timbrado.timbrado = v.timbrado
                        left join cliente c on
                            c.codclie = v.idcliente
                        left join departamentos d on
                            d.id = c.departamento
                        left join indicador_presencia ip on
                            ip.id = 1
                        left join departamentos depa on
                            depa.id = e.departamento_emisor_set
                        left join distrito disEmi on
                            disEmi.iddistrito = e.distrito_emisor_set
                        left join ciudades ciu on
                            ciu.idciudad = e.ciudad_emisor_set
                        left join usuarios oper on
                            oper.idusu = v.registrado_por
                        left join facturas fact on
                            fact.idtanda = v.idtandatimbrado
                        where 
                            idnotacred = $idNc";

            $this->datosVentas = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "select
                            vd.*,
                            p.descripcion as descripcionProducto,
                            vd.subtotal,
                            0 as descItem,
                            fati.descripcion as descFati,
                            ti.iva_porc as ivaVentaPorcentaje,
                            fati.descripcion as descripcionFormaAfecIVA
                        from
                            nota_credito_cuerpo vd
                        left join productos p on
                            p.idprod = vd.codproducto
                        left join forma_afectacion_tributaria_iva fati on
                            fati.id = vd.id_forma_afectacion_tributaria_iva
                        left join tipo_iva ti on
                            ti.idtipoiva = 1
                        where
                            vd.idnotacred = $idNc
                            and vd.subtotal >= 0";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            foreach ($rs as $fila) {

                //$auxTotOpeItem = (($fila['precioUniVentaProducto'] - $fila['descItem'] - 0 - 0 - 0) * $fila['cantidad']);
                $auxTotOpeItem = $fila['subtotal'];
                $dBasGravIVA = ($fila['id_forma_afectacion_tributaria_iva'] == 1 || $fila['id_forma_afectacion_tributaria_iva'] == 4) ? (($fila['ivaventaporcentaje'] == 10) ? round((($auxTotOpeItem) * ($fila['proporcion_gravada_iva'] / 100) / 1.1), 2) : round((($auxTotOpeItem) * ($fila['proporcion_gravada_iva'] / 100) / 1.05), 2)) : 0;
                $this->dSub5 = ($fila['ivaventaporcentaje'] == 5) ? ($this->dSub5 + $auxTotOpeItem) : ($this->dSub5 + 0);
                $this->dSub10 = ($fila['ivaventaporcentaje'] == 10) ? ($this->dSub10 + $auxTotOpeItem) : ($this->dSub10 + 0);
                $this->dSubExe = ($fila['ivaventaporcentaje'] == 0) ? ($this->dSubExe + $auxTotOpeItem) : ($this->dSubExe + 0);
                $this->dSubExo = ($fila['ivaventaporcentaje'] == 0) ? ($this->dSubExo + $auxTotOpeItem) : ($this->dSubExo + 0);
                $this->dTotDesc = $this->dTotDesc + $fila['descItem'];
                //$this->dPorcDescTotal = ($fila['descItem']>0)?($this->dPorcDescTotal + (($fila['descItem'] * 100)/$fila['precioUniVentaProducto'])):$this->dPorcDescTotal + 0;
                $this->dDescTotal = $this->dDescTotal + $fila['descItem'];

                $this->sumBasGravIva5 = ($fila['ivaventaporcentaje'] == 5) ? $this->sumBasGravIva5 + $dBasGravIVA : $this->sumBasGravIva5 + 0;
                $this->sumBasGravIva10 = ($fila['ivaventaporcentaje'] == 10) ? $this->sumBasGravIva10 + $dBasGravIVA : $this->sumBasGravIva10 + 0;

                $consulta_iva = "select * from ventas_detalles
                                    inner join ventas_detalles_impuesto vdt ON vdt.idventadet = ventas_detalles.idventadet
                                    where ventas_detalles.idventadet = ".intval($fila['idventadet']);

                $rs_iva = $conexion->Execute($consulta_iva) or die(errorpg($conexion, $consulta_iva));

                foreach ($rs_iva as $fila_iva) {

                    $baseIVAGrav = ($fila_iva['iva_porc_col'] > 0) ? $fila_iva['gravadoml'] : 0;
                    $dLiqIVAItem = ($fila_iva['iva_porc_col'] > 0) ? $baseIVAGrav * ($fila_iva['iva_porc_col'] / 100) : 0;

                    $this->dIVA5 = 0;
                    //$this->dIVA10 = ($fila['ivaventaporcentaje'] == 10)?$this->dIVA10 + $dLiqIVAItem:$this->dIVA10 + 0;
                    $this->dIVA10 = ($fila['ivaventaporcentaje'] == 10) ? $this->dIVA10 + $fila_iva['gravadoml'] : $this->dIVA10 + 0;

                    $gCamIVA = [

                        "iAfecIVA" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S') ? 3 : $fila['id_forma_afectacion_tributaria_iva'],
                        "dDesAfecIVA" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S') ? 'Exento' : $fila['descripcionformaafeciva'],
                        "dPropIVA" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S') ? 0 : 100,
                        "dTasaIVA" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S') ? 0 : intval($fila_iva['iva_porc_col']),
                        //"dBasGravIVA" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S')?0:round($baseIVAGrav, 2),
                        "dBasGravIVA" => round(($fila['subtotal'] / 1.1), 2),
                        "dLiqIVAItem" => round(($fila['subtotal'] / 11), 2),
                        "dBasExe" => (isset($this->datosVentas['diplomatico']) && $this->datosVentas['diplomatico'] == 'S') ? 0 : (($fila['id_forma_afectacion_tributaria_iva'] != 4) ? 0 : ((100 * $fila['subtotal'] * (100 - 100)) / (10000 + (intval($fila_iva['iva_porc_col']) * 100)))),

                    ];

                }

                $items = [

                    "gCamItem" => [
                        "dCodInt" => $fila['idproducto'],
                        "dDesProSer" => htmlspecialchars($fila['descripcionproducto']),
                        "cUniMed" => 77,
                        "dDesUniMed" => 'UNI',
                        "dCantProSer" => $fila['cantidad'],

                        "gValorItem" => [

                            "dPUniProSer" => round(($fila['subtotal'] / $fila['cantidad']), 2),
                            "dTotBruOpeItem" => $fila['subtotal'],
                            "gValorRestaItem" => [

                                "dDescItem" => 0,
                                "dPorcDesIt" => 0,
                                "dDescGloItem" => 0,
                                "dAntPreUniIt" => 0,
                                "dAntGloPreUniIt" => 0,
                                "dTotOpeItem" => $auxTotOpeItem,

                            ]

                        ],

                        "gCamIVA" => $gCamIVA

                    ]

                ];

                array_push($this->datosVentasItems, $items);

            }

            //////////////////////Formas de pago/////////////////////////////////////////

            $consulta = "select
                    formas_pago.idformapago_set,
                    gest_pagos_det.idformapago as idformapago_sistema,
                    gest_pagos_det.monto_pago_det,
                    formas_pago_set.descripcion as descFormaPagoSet,
                    denominacion_tarjeta.id as id_denominacion_tarjeta,
                    denominacion_tarjeta.descripcion as descripcion_denominacion_tarjeta,
                    id_forma_procesamiento_pago,
                    gest_pagos_det_datos.numero_cheque,
                    bancos.nombre as nombre_banco
                from
                    gest_pagos_det
                left join gest_pagos on
                    gest_pagos.idpago = gest_pagos_det.idpago
                left join formas_pago on
                    formas_pago.idforma = gest_pagos_det.idformapago
                left join formas_pago_set on
                    formas_pago_set.id = formas_pago.idformapago_set
                left join gest_pagos_det_datos on
                    gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
                left join denominacion_tarjeta on
                    denominacion_tarjeta.id = gest_pagos_det_datos.id_denominacion_tarjeta
                left join bancos on
                    bancos.idbanco = gest_pagos_det_datos.idbanco
                where
                    idventa = 0";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            #Reinicio el array de items
            unset($items);

            foreach ($rs as $fila) {

                if ($fila['idformapago_set'] == 3 || $fila['idformapago_set'] == 4) {

                    $gPagTarCD = [

                        "iDenTarj" => (isset($fila['id_denominacion_tarjeta']) || $fila['id_denominacion_tarjeta'] != "") ? $fila['id_denominacion_tarjeta'] : 1,
                        "dDesDenTarj" => (isset($fila['descripcion_denominacion_tarjeta']) || $fila['descripcion_denominacion_tarjeta'] != "") ? $fila['descripcion_denominacion_tarjeta'] : "Visa",
                        "iForProPa" => (isset($fila['descripcion_denominacion_tarjeta']) || $fila['descripcion_denominacion_tarjeta'] != "") ? $fila['id_forma_procesamiento_pago'] : 1,

                    ];

                } elseif ($fila['idformapago_set'] == 2) {

                    $gPagCheq = [

                        "dNumCheq" => (isset($fila['numero_cheque']) || $fila['numero_cheque'] != "") ? $fila['numero_cheque'] : agregacero("0".rand(1000, 1000000), 8),
                        "dBcoEmi" => (isset($fila['nombre_banco']) || $fila['nombre_banco'] != "") ? $fila['nombre_banco'] : "Itau",

                    ];

                }

                $items = [

                    "gPaConEIni" => [

                        "iTiPago" => $fila['idformapago_set'],
                        "dDesTiPag" => $fila['descformapagoset'],
                        "dMonTiPag" => $fila['monto_pago_det'],
                        "cMoneTiPag" => "PYG",
                        "dDMoneTiPag" => "Guarani",
                        "gPagTarCD" => $gPagTarCD,
                        "gPagCheq" => $gPagCheq
                    ]

                ];


                if ($fila['idformapago_set'] != 3 && $fila['idformapago_set'] != 4 && $fila['idformapago_set'] != 2) {

                    unset($items['gPaConEIni']['gPagTarCD']);
                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] != 3 && $fila['idformapago_set'] != 4) {

                    unset($items['gPaConEIni']['gPagTarCD']);

                } elseif ($fila['idformapago_set'] != 2) {

                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] == 3 || $fila['idformapago_set'] == 4) {

                    unset($items['gPaConEIni']['gPagCheq']);

                } elseif ($fila['idformapago_set'] == 2) {

                    unset($items['gPaConEIni']['gPagTarCD']);

                }

                array_push($this->datosFormasPago, $items);

            }

        }

        private function iniciarCabeceraNotaCredito()
        {

            global $conexion;

            $idNotaCredito = antisqlinyeccion($this->idnotacredito, "text");

            $consulta = "SELECT ncc.*, ncme.idmotivo_set as id_motivo_emision_nota_credito, ncme.descripcion as motivo_emision_nc, timbrado.timbrado as timbradonc,
                         tda.id as IDTipoDocumentoAsociado,
                         tda.descripcion as descTipoDocumentoAsociado
                         FROM nota_credito_cabeza ncc
                         LEFT JOIN nota_cred_motivos_cli motivo ON motivo.idmotivo = ncc.idmotivo
                         LEFT JOIN nota_cred_motivos_cli_set ncme ON ncme.idmotivo_set = motivo.idmotivo_set
                         LEFT JOIN timbrado on timbrado.timbrado = ncc.timbrado
                         LEFT JOIN tipo_documento_electronico_asociado tda ON tda.id = ncc.id_tipo_documento_asociado
                         WHERE ncc.idnotacred=$idNotaCredito
                        ";

            $rs = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            $this->arrayNotaCreditoCabeza = $rs;

        }

        private function iniciarDetalleNotaCredito()
        {

            global $conexion;

            $idNotaCredito = antisqlinyeccion($this->idnotacredito, "int");

            $consulta = "SELECT * FROM nota_credito_cuerpo
                        WHERE idnotacred = $idNotaCredito";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            foreach ($rs as $fila) {

                $idNotaCreditoDetalle = antisqlinyeccion($fila['registro'], "int");

                $consulta_iva = "select ncc.*, fativa.descripcion as descripcionFormaAfecIVA
                                 from nota_credito_cuerpo_impuesto ncc
                                 LEFT JOIN forma_afectacion_tributaria_iva fativa ON fativa.id = $idNotaCreditoDetalle
                                 where ncc.idnotacreddet = ".intval($fila['registro']);


                $rs_iva = $conexion->Execute($consulta_iva) or die(errorpg($conexion, $consulta_iva));

                foreach ($rs_iva as $fila_iva) {

                    $baseIVAGrav = ($fila_iva['iva_porc_col'] > 0) ? $fila_iva['gravadoml'] : 0;
                    $dLiqIVAItem = ($fila_iva['iva_porc_col'] > 0) ? $baseIVAGrav * ($fila_iva['iva_porc_col'] / 100) : 0;
                    $this->dSub5 = ($fila['iva_porc'] == 5) ? ($this->dSub5 + $fila['subtotal']) : ($this->dSub5 + 0);
                    $this->dSub10 = ($fila['iva_porc'] == 10) ? ($this->dSub10 + $fila['subtotal']) : ($this->dSub10 + 0);
                    $this->dSubExe = ($fila['iva_porc'] == 0) ? ($this->dSubExe + $fila['subtotal']) : ($this->dSubExe + 0);
                    $this->dSubExo = ($fila['iva_porc'] == 0) ? ($this->dSubExo + $fila['subtotal']) : ($this->dSubExo + 0);

                    $gCamIVA = [

                        "iAfecIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 3 : $fila['id_forma_afectacion_tributaria_iva'],
                        //"dDesAfecIVA" => ($this->datosVentas['diplomatico'] == 'S')?'Exento':$fila_iva['descripcionFormaAfecIVA'],
                        "dDesAfecIVA" => "Gravado IVA",
                        "dPropIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : 100,
                        "dTasaIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : intval($fila_iva['iva_porc_col']),
                        "dBasGravIVA" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : round($baseIVAGrav, 2),
                        "dLiqIVAItem" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : round($dLiqIVAItem, 2),
                        "dBasExe" => ($this->datosVentas['diplomatico'] == 'S') ? 0 : (($fila['id_forma_afectacion_tributaria_iva'] != 4) ? 0 : ((100 * $fila['subtotal'] * (100 - 100)) / (10000 + (intval($fila_iva['iva_porc_col']) * 100)))),

                    ];

                }

                $items = [

                    "gCamItem" => [
                        "dCodInt" => $fila['codproducto'],
                        "dDesProSer" => htmlspecialchars($fila['descripcion']),
                        "cUniMed" => 77,
                        "dDesUniMed" => 'UNI',
                        "dCantProSer" => $fila['cantidad'],

                        "gValorItem" => [

                            "dPUniProSer" => round(($fila['precio'] / $fila['cantidad']), 2),
                            "dTotBruOpeItem" => $fila['precio'],
                            "gValorRestaItem" => [

                                "dDescItem" => 0,
                                "dPorcDesIt" => 0,
                                "dDescGloItem" => 0,
                                "dAntPreUniIt" => 0,
                                "dAntGloPreUniIt" => 0,
                                "dTotOpeItem" => $fila['precio'],

                            ]

                        ],

                        "gCamIVA" => $gCamIVA

                    ]

                ];

                array_push($this->arrayNotaCreditoDetalle, $items);

            }

        }

        private function buscarDocumentoElectronicoGenerado($nroComprobante)
        {

            global $conexion;

            $nroComprobante = antisqlinyeccion($nroComprobante, "text");

            $consulta = "SELECT dcodseg, dfeemide, cdc, count(dfeemide) as total FROM documentos_electronicos_emitidos WHERE numero_comprobante = $nroComprobante and estado_set = 1";

            $rs = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            return $rs;

        }

        private function buscarDocumentoElectronicoGeneradoXIDVenta($idventa)
        {

            global $conexion;

            $idventa = antisqlinyeccion($idventa, "int");

            $consulta = "SELECT cdc FROM documentos_electronicos_emitidos WHERE idventa = $idventa and estado_set = 3";

            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            return $rs->fields['cdc'];

        }

        private function obtenerURLAPI()
        {

            global $conexion;

            $consulta = "SELECT * FROM preferencias_electronica where estado = 1";

            $rs = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            $this->url_enviarloterde = $rs['url_enviarloterde'];
            $this->url_enviarrde = $rs['url_enviarrde'];
            $this->url_firmarde = $rs['url_firmarde'];
            $this->url_consultarlotede = $rs['url_consultarlotede'];

        }

        private function obtenerFechaVtoFactura($idVenta)
        {
            global $conexion;

            $idVenta = intval($idVenta);

            $consulta = "SELECT DATEDIFF(cc.prox_vencimiento,v.fecha) as dias 
                         FROM cuentas_clientes cc
                         LEFT JOIN ventas v ON v.idventa = cc.idventa 
                         where
                         cc.idventa = $idVenta";

            $rs = $conexion->getRow($consulta) or die(errorpg($conexion, $consulta));

            return $rs['dias'];

        }

        private function generateUniqueIDRand()
        {

            /*$len = 8;   // total number of numbers
            $min = 1;  // minimum
            $max = 999999999;  // maximum
            $range = []; // initialize array
            foreach (range(0, $len - 1) as $i) {
                while(in_array($num = mt_rand($min, $max), $range));
                $range[] = $num;
            }

            return $range[0];*/
            $uniquerand = date('His').rand(100, 999);
            return $uniquerand;

        }

        private function completarCerosIzquierda($cantidadCeros, $dato)
        {

            $valorRetorno = '';

            if ($cantidadCeros == 3) {

                switch (strlen($dato)) {

                    case 1:
                        $valorRetorno = '00'.$dato;
                        break;
                    case 2:
                        $valorRetorno = '0'.$dato;
                        break;
                    case 3:
                        $valorRetorno = $dato;
                        break;

                }

            } elseif ($cantidadCeros == 7) {

                switch (strlen($dato)) {

                    case 1:
                        $valorRetorno = '000000'.($dato);
                        break;
                    case 2:
                        $valorRetorno = '00000'.($dato);
                        break;
                    case 3:
                        $valorRetorno = '0000'.($dato);
                        break;
                    case 4:
                        $valorRetorno = '000'.($dato);
                        break;
                    case 5:
                        $valorRetorno = '00'.($dato);
                        break;
                    case 6:
                        $valorRetorno = '0'.($dato);
                        break;
                    case 7:
                        $valorRetorno = ($dato);
                        break;

                }

            }

            return $valorRetorno;

        }

    }
