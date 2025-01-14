 <?php

    /**
     * Validate:
     *
     * @author Gustavo Piris
     * @since 1.0.0
     * @copyright Innovasys SRL 2023
     *
    */

    require_once('init.php');

class Validate extends Init
{
    private $status = "OK";
    private $errors = [];
    private $generalArray = [];

    public function checkFields(array $params)
    {

        ##Prepasos: Unifica todo los campos
        $this->generalArray = array_merge(
            $this->array_nodo_a,
            $this->array_nodo_b,
            $this->array_nodo_c,
            $this->array_nodo_d,
            (isset($params['iTiDE']) && $params['iTiDE'] != 7) ? $this->array_nodo_d1 : [],
            $this->array_nodo_d2,
            $this->array_nodo_d2_1,
            (isset($params['iTipCont']) && $params['iTipCont'] == 1) ? $this->array_nodo_d2_2 : [],
            $this->array_nodo_d3,
            (isset($params['iTiDE']) && $params['iTiDE'] == 1) ? $this->array_nodo_e1 : [],
            (isset($params['iTiOpe']) && $params['iTiOpe'] == 3) ? $this->array_nodo_e1_1 : [],
            (isset($params['iTiDE']) && $params['iTiDE'] == 4) ? $this->array_nodo_e4 : [],
            (isset($params['iTiDE']) && ($params['iTiDE'] == 5 || $params['iTiDE'] == 6)) ? $this->array_nodo_e5 : [],
            (isset($params['iTiDE']) && $params['iTiDE'] == 7) ? $this->array_nodo_e6 : [],
            (isset($params['iTiDE']) && ($params['iTiDE'] == 1 || $params['iTiDE'] == 4)) ? $this->array_nodo_e7 : [],
            (isset($params['iCondOpe']) && $params['iCondOpe'] == 1) ? $this->array_nodo_e7_1 : [],
            (isset($params['iTiPago']) && ($params['iTiPago'] == 3 || $params['E606'] == 4)) ? $this->array_nodo_e7_1_1 : [],
            (isset($params['iTiPago']) && $params['iTiPago'] == 2) ? $this->array_nodo_e7_1_2 : [],
            (isset($params['iCondOpe']) && $params['iCondOpe'] == 2) ? $this->array_nodo_e7_2 : [],
            (isset($params['iCondCred']) && $params['iCondCred'] == 2) ? $this->array_nodo_e7_2_1 : [],
            $this->array_nodo_e8,
            (isset($params['iTiDE']) && $params['iTiDE'] != 7) ? $this->array_nodo_e8_1 : [],
            $this->array_nodo_e8_1_1,
            ((isset($params['iTiDE']) && isset($params['iTImp']))
            && ($params['iTImp'] == 1 || $params['iTImp'] == 3 || $params['iTImp'] == 4 || $params['iTImp'] == 5)
            && ($params['iTiDE'] != 4 || $params['iTiDE'] != 7)) ? $this->array_nodo_e8_2 : [],
            $this->array_nodo_e8_4,
            $this->array_nodo_e8_5,
            $this->array_nodo_e9_2,
            $this->array_nodo_e9_3,
            $this->array_nodo_e9_3_1,
            $this->array_nodo_e9_4,
            $this->array_nodo_e9_5,
            (isset($params['iTiDE']) && ($params['iTiDE'] == 7 || $params['iTiDE'] == 1)) ? $this->array_nodo_e10 : [],
            (isset($params['iTiDE']) && ($params['iTiDE'] == 7 || $params['iTiDE'] == 1)) ? $this->array_nodo_e10_1 : [],
            (isset($params['iTiDE']) && ($params['iTiDE'] == 7)) ? $this->array_nodo_e10_2 : [],
            (isset($params['iTiDE']) && ($params['iTiDE'] == 7)) ? $this->array_nodo_e10_3 : [],
            (isset($params['iTiDE']) && (($params['iTiDE'] == 7) || ($params['iModTrans'] == 7 || $params['dTipIdenVeh'] == 1))) ? $this->array_nodo_e10_4 : [],
            (isset($params['iTiDE']) && $params['iTiDE'] != 7) ? $this->array_nodo_f : [],
            $this->array_nodo_g,
            (isset($params['iTiDE']) && ($params['iTiDE'] == 1 || $params['iTiDE'] == 7)) ? $this->array_nodo_g_1 : [],
            (isset($params['iTiDE']) && (($params['iTiDE'] == 4 || $params['iTiDE'] == 5 || $params['iTiDE'] == 6))
            || ($params['iTiDE'] == 1 || $params['iTiDE'] == 7)) ? $this->array_nodo_h : [],
            $this->array_nodo_j
        );

        ##Paso 1: Verifica los campos obligatorios
        foreach ($this->generalArray as $key => $value) {

            if (($params[$key] == '' || ($params[$key] == 0 && $value['type'] == 'number')) && $value['required'] && !array_key_exists('default', $value)) {

                $this->status = "KO";
                $concatErrorMsg = (isset($value['errorMSg'])) ? 'El campo ' . $key . ' es obligatorio, ' . $value['errorMSg'] : 'El campo ' . $key . ' es obligatorio';
                $this->errors[] = $concatErrorMsg;

            }

        }

        ##Paso 2: Verifica si el tipo de datos es correcto por cada fila
        foreach ($params as $key => $value) {

            if (array_key_exists($key, $this->generalArray)) {

                $checkFieldType = $this->checkFieldType($this->generalArray[$key]['type'], $value, $key);

                if (!$checkFieldType['status']) {

                    $this->status = "KO";
                    $this->errors[] = $checkFieldType['msg'];

                }

            }

        }

        ##Paso 3: Verifica la longitud de los campos
        foreach ($params as $key => $value) {

            if (array_key_exists($key, $this->generalArray)) {

                $minLenght = $this->generalArray[$key]['minlenght'];
                $maxLenght = $this->generalArray[$key]['maxlenght'];

                if (iconv_strlen($value) < $minLenght || iconv_strlen($value) > $maxLenght) {

                    $this->status = "KO";
                    $this->errors[] = 'El campo ' . $key . ' debe ser mayor o igual a '. $minLenght .' y menor o igual a '. $maxLenght;

                }

            }

        }

        ##Paso 4: Verifica los campos obligatorios segun otros campos
        foreach ($params as $key => $value) {

            if (array_key_exists($key, $this->generalArray)) {

                if (array_key_exists('joinMandatory', $this->generalArray[$key])) {

                    foreach ($this->generalArray[$key]['joinMandatory'] as $mandatoryKey => $mandatoryValue) {

                        $asArrayMandatoryValue = (array) $mandatoryValue;

                        if (in_array($value, $asArrayMandatoryValue) && (!$params[$mandatoryKey] || $params[$mandatoryKey] == '')) {

                            $implodeMandatoryValues = implode(' o ', $mandatoryValue);

                            $this->status = "KO";
                            $this->errors[] = 'Si el valor del campo ' . $key . ' es ['. $implodeMandatoryValues.'], '. $mandatoryKey .' no debe ser vacio';

                        }

                    }

                }

            }

        }

        ##Paso 5: Verifica los campos con sentencias compuestas
        foreach ($params as $key => $value) {

            if (array_key_exists($key, $this->generalArray)) {

                if (array_key_exists('checkSentences', $this->generalArray[$key])) {

                    foreach ($this->generalArray[$key]['checkSentences'] as $sentence) {

                        switch ($sentence[1]) {

                            case "<>": //Solo para monedas

                                if (!isset($params[$sentence[0]]) && $params[$key] != $sentence[2] && in_array($params[$key], $this->array_countries_iso_cod_money)) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe ser obligatorio si '. $key . ' es distinto a '.$sentence[2];

                                }

                                break;

                            case "<=>": //Para todos

                                if (!isset($params[$sentence[0]]) && $params[$key] != $sentence[2]) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe ser obligatorio si '. $key . ' es distinto a '.$sentence[2];

                                }

                                break;

                            case "="://Para monedas unicamente

                                if ($params[$key] != $sentence[2] && in_array($params[$key], $this->array_countries_iso_cod_money)) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe ser obligatorio si '. $key . ' es distinto  a '.$sentence[2];

                                }

                                break;

                            case "notnull"://Para todos

                                if ($params[$key] == '') {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe ser obligatorio si ' . $key . ' es distinto a vacio';

                                }

                                break;

                            case "=="://Para todos

                                if ($params[$key] != $sentence[2]) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe ser obligatorio si '. $key . (($sentence[2] != "") ? ' es distinto a '.$sentence[2] : ' es distinto a vacio');

                                }

                                break;

                            case "!=":

                                if (isset($params[$sentence[0]]) && $params[$key] == $sentence[2]) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' no debe informarse si '. $key . ' es igual a '.$sentence[2];

                                }

                                break;

                            case "!=!":

                                if (isset($params[$sentence[0]]) && !in_array($params[$key], $sentence[2])) {

                                    $implodeMandatoryValues = implode(' o ', $sentence[2]);
                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' no debe informarse si '. $key . ' es distinto a ['. $implodeMandatoryValues.']';

                                }

                                break;

                            case ">":

                                if (!isset($params[$sentence[0]]) && $params[$key] > $sentence[2]) {

                                    $this->status = "KO";
                                    $this->errors[] = $sentence[0].' debe informarse si '. $key . ' es mayor a '.$sentence[2];

                                }

                                break;

                        }

                    }

                }

            }

        }

        ##Paso 6: Uno todos los arrays a la cabecera general

        $this->array_cab_general["dDVId"] = '';
        $this->array_cab_general["dFecFirma"] = '';
        $this->array_cab_general["dSisFact"] = '';

        $this->array_cab_general["gOpeDE"] = $this->array_nodo_b;
        $this->array_cab_general["gTimb"] = $this->array_nodo_c;

        #################################################################
        ##########################Grupo D################################
        #################################################################

        $this->array_cab_general["gDatGralOpe"] = $this->array_nodo_d;
        ##SubGrupoD1
        $this->array_cab_general["gOpeCom"] = $this->array_nodo_d1;
        ##SubGrupoD2
        $this->array_cab_general["gEmis"] = $this->array_nodo_d2;
        ##SubGrupoD2.1
        $this->array_cab_general["gActEco"] = $this->array_nodo_d2_1;
        $this->array_cab_general["gRespDE"] = $this->array_nodo_d2_2;
        ########################################################
        #######################AgrupaGrupoD2####################
        ########################################################
        $this->array_cab_general["gEmis"]["gActEco"] = $this->array_cab_general["gActEco"];
        unset($this->array_cab_general["gActEco"]);
        $this->array_cab_general["gEmis"]["gRespDE"] = $this->array_cab_general["gRespDE"];
        unset($this->array_cab_general["gRespDE"]);
        ##SubGrupoD3
        $this->array_cab_general["gDatRec"] = $this->array_nodo_d3;
        ########################################################
        #######################AgrupaGrupoD####################
        ########################################################
        $this->array_cab_general["gDatGralOpe"]["gOpeCom"] = $this->array_cab_general["gOpeCom"];
        unset($this->array_cab_general["gOpeCom"]);
        $this->array_cab_general["gDatGralOpe"]["gEmis"] = $this->array_cab_general["gEmis"] ;
        unset($this->array_cab_general["gEmis"]);
        $this->array_cab_general["gDatGralOpe"]["gDatRec"] = $this->array_cab_general["gDatRec"] ;
        unset($this->array_cab_general["gDatRec"]);

        #################################################################
        ##########################Grupo E################################
        #################################################################

        //$this->array_cab_general["gDtipDE"] =  $this->array_cab_nodo_e;
        ##SubGrupoE1
        $this->array_cab_general["gCamFE"] = $this->array_nodo_e1;
        ##SubGrupoE1.1
        $this->array_cab_general["gCompPub"] = $this->array_nodo_e1_1;
        ########################################################
        #######################AgrupaGrupoE1####################
        ########################################################
        $this->array_cab_general["gCamFE"]["gCompPub"] = $this->array_cab_general["gCompPub"];
        unset($this->array_cab_general["gCompPub"]);
        ##SubGrupoE4
        $this->array_cab_general["gCamAE"] = $this->array_nodo_e4;
        ##SubGrupoE5
        $this->array_cab_general["gCamNCDE"] = $this->array_nodo_e5;
        ##SubGrupoE6
        $this->array_cab_general["gCamNRE"] = $this->array_nodo_e6;
        ##SubGrupoE7
        $this->array_cab_general["gCamCond"] = $this->array_nodo_e7;
        ##SubGrupoE7.1
        $this->array_cab_general["gPaConEIni"] = $this->array_nodo_e7_1;
        ##SubGrupoE7.1.1
        $this->array_cab_general["gPagTarCD"] = $this->array_nodo_e7_1_1;
        ##SubGrupoE7.1.2
        $this->array_cab_general["gPagCheq"] = $this->array_nodo_e7_1_2;
        ########################################################
        #######################AgrupaGrupoE7.1##################
        ########################################################
        $this->array_cab_general["gPaConEIni"]["gPagTarCD"] = $this->array_cab_general["gPagTarCD"];
        unset($this->array_cab_general["gPagTarCD"]);
        $this->array_cab_general["gPaConEIni"]["gPagCheq"] = $this->array_cab_general["gPagCheq"];
        unset($this->array_cab_general["gPagCheq"]);
        ##SubGrupoE7.2
        $this->array_cab_general["gPagCred"] = $this->array_nodo_e7_2;
        ##SubGrupoE7.2.1
        $this->array_cab_general["gCuotas"] = $this->array_nodo_e7_2_1;
        ########################################################
        #######################AgrupaGrupoE7####################
        ########################################################
        $this->array_cab_general["gCamCond"]["gPaConEIni"] = $this->array_cab_general["gPaConEIni"];
        unset($this->array_cab_general["gPaConEIni"]);
        $this->array_cab_general["gCamCond"]["gPagCred"] = $this->array_cab_general["gPagCred"];
        unset($this->array_cab_general["gPagCred"]);
        ##SubGrupoE8
        $this->array_cab_general["gCamItem"] = $this->array_nodo_e8;
        ##SubGrupoE8.1
        $this->array_cab_general["gValorItem"] = $this->array_nodo_e8_1;
        ##SubGrupoE8.1.1
        $this->array_cab_general["gValorRestaItem"] = $this->array_nodo_e8_1_1;
        ########################################################
        #######################AgrupaGrupoE8.1.1################
        ########################################################
        $this->array_cab_general["gValorItem"]["gValorRestaItem"] = $this->array_cab_general["gValorRestaItem"];
        unset($this->array_cab_general["gValorRestaItem"]);
        ##SubGrupoE8.2
        $this->array_cab_general["gCamIVA"] = $this->array_nodo_e8_2;
        ##SubGrupoE8.4
        $this->array_cab_general["gRasMerc"] = $this->array_nodo_e8_4;
        ##SubGrupoE8.5
        $this->array_cab_general["gVehNuevo"] = $this->array_nodo_e8_5;
        ########################################################
        #######################AgrupaGrupoE8####################
        ########################################################
        $this->array_cab_general["gCamItem"]["gValorItem"] = $this->array_cab_general["gValorItem"];
        unset($this->array_cab_general["gValorItem"]);
        $this->array_cab_general["gCamItem"]["gCamIVA"] = $this->array_cab_general["gCamIVA"];
        unset($this->array_cab_general["gCamIVA"]);
        $this->array_cab_general["gCamItem"]["gRasMerc"] = $this->array_cab_general["gRasMerc"];
        unset($this->array_cab_general["gRasMerc"]);
        $this->array_cab_general["gCamItem"]["gVehNuevo"] = $this->array_cab_general["gVehNuevo"];
        unset($this->array_cab_general["gVehNuevo"]);
        ##SubGrupoE9
        //$this->array_cab_general["gCamEsp"] =  $this->array_cab_nodo_e9;
        ##SubGrupoE9.2
        $this->array_cab_general["gGrupEner"] = $this->array_nodo_e9_2;
        ##SubGrupoE9.3
        $this->array_cab_general["gGrupSeg"] = $this->array_nodo_e9_3;
        ##SubGrupoE9.3.1
        $this->array_cab_general["gGrupPolSeg"] = $this->array_nodo_e9_3_1;
        ########################################################
        #######################AgrupaGrupoE9.3##################
        ########################################################
        $this->array_cab_general["gGrupSeg"]["gGrupPolSeg"] = $this->array_cab_general["gGrupPolSeg"];
        unset($this->array_cab_general["gGrupPolSeg"]);
        ##SubGrupoE9.4
        $this->array_cab_general["gGrupSup"] = $this->array_nodo_e9_4;
        ##SubGrupoE9.5
        $this->array_cab_general["gGrupAdi"] = $this->array_nodo_e9_5;
        ########################################################
        #######################AgrupaGrupoE9####################
        ########################################################
        $this->array_cab_general["gCamEsp"]["gGrupEner"] = $this->array_cab_general["gGrupEner"];
        unset($this->array_cab_general["gGrupEner"]);
        $this->array_cab_general["gCamEsp"]["gGrupSeg"] = $this->array_cab_general["gGrupSeg"];
        unset($this->array_cab_general["gGrupSeg"]);
        $this->array_cab_general["gCamEsp"]["gGrupSup"] = $this->array_cab_general["gGrupSup"];
        unset($this->array_cab_general["gGrupSup"]);
        $this->array_cab_general["gCamEsp"]["gGrupAdi"] = $this->array_cab_general["gGrupAdi"];
        unset($this->array_cab_general["gGrupAdi"]);
        ##SubGrupoE10
        $this->array_cab_general["gTransp"] = $this->array_nodo_e10;
        ##SubGrupoE10.1
        $this->array_cab_general["gCamSal"] = $this->array_nodo_e10_1;
        ##SubGrupoE10.2
        $this->array_cab_general["gCamEnt"] = $this->array_nodo_e10_2;
        ##SubGrupoE10.3
        $this->array_cab_general["gVehTras"] = $this->array_nodo_e10_3;
        ##SubGrupoE10.4
        $this->array_cab_general["gCamTrans"] = $this->array_nodo_e10_4;
        ########################################################
        #######################AgrupaGrupoE10###################
        ########################################################
        $this->array_cab_general["gTransp"]["gCamSal"] = $this->array_cab_general["gCamSal"];
        unset($this->array_cab_general["gCamSal"]);
        $this->array_cab_general["gTransp"]["gCamEnt"] = $this->array_cab_general["gCamEnt"];
        unset($this->array_cab_general["gCamEnt"]);
        $this->array_cab_general["gTransp"]["gVehTras"] = $this->array_cab_general["gVehTras"];
        unset($this->array_cab_general["gVehTras"]);
        $this->array_cab_general["gTransp"]["gCamTrans"] = $this->array_cab_general["gCamTrans"];
        unset($this->array_cab_general["gCamTrans"]);
        ########################################################
        #######################AgrupaGrupoE#####################
        ########################################################
        $this->array_cab_general["gDtipDE"]["gCamFE"] = $this->array_cab_general["gCamFE"];
        unset($this->array_cab_general["gCamFE"]);
        $this->array_cab_general["gDtipDE"]["gCamAE"] = $this->array_cab_general["gCamAE"];
        unset($this->array_cab_general["gCamAE"]);
        $this->array_cab_general["gDtipDE"]["gCamNCDE"] = $this->array_cab_general["gCamNCDE"];
        unset($this->array_cab_general["gCamNCDE"]);
        $this->array_cab_general["gDtipDE"]["gCamNRE"] = $this->array_cab_general["gCamNRE"];
        unset($this->array_cab_general["gCamNRE"]);
        $this->array_cab_general["gDtipDE"]["gCamCond"] = $this->array_cab_general["gCamCond"];
        unset($this->array_cab_general["gCamCond"]);
        $this->array_cab_general["gDtipDE"]["gCamItem"] = $this->array_cab_general["gCamItem"];
        unset($this->array_cab_general["gCamItem"]);
        $this->array_cab_general["gDtipDE"]["gCamEsp"] = $this->array_cab_general["gCamEsp"];
        unset($this->array_cab_general["gCamEsp"]);
        $this->array_cab_general["gDtipDE"]["gTransp"] = $this->array_cab_general["gTransp"];
        unset($this->array_cab_general["gTransp"]);

        #################################################################
        ##########################Grupo F################################
        #################################################################
        $this->array_cab_general["gTotSub"] = $this->array_nodo_f;

        #################################################################
        ##########################Grupo G################################
        #################################################################
        $this->array_cab_general["gCamGen"] = $this->array_nodo_g;
        $this->array_cab_general["gCamCarg"] = $this->array_nodo_g_1;
        $this->array_cab_general["gCamGen"]["gCamCarg"] = $this->array_cab_general["gCamCarg"];
        unset($this->array_cab_general["gCamCarg"]);

        #################################################################
        ##########################Grupo H################################
        #################################################################
        $this->array_cab_general["gCamDEAsoc"] = $this->array_nodo_h;

        #################################################################
        ##########################Grupo I################################
        #################################################################
        $this->array_cab_general["Signature"] = '';

        #################################################################
        ##########################Grupo J################################
        #################################################################
        $this->array_cab_general["gCamFuFD"] = $this->array_nodo_j;

        ##Paso 7: Comienza a rellenar los campos para el XML
        foreach ($this->array_cab_general as $key => $value) {

            if (isset($params[$key])) {

                $this->array_cab_general[$key] = $params[$key];

            } elseif (!isset($params[$key])) {

                foreach ($value as $nestedKey => $nestedValue) {

                    if ($nestedKey != 'gCamItem') {

                        if (isset($params[$nestedKey])) {

                            $this->array_cab_general[$key][$nestedKey] = $params[$nestedKey];

                        } else {

                            foreach ($nestedValue as $nestedNestedKey => $nestedNestedValue) {

                                if (isset($params[$nestedNestedKey])) {

                                    $this->array_cab_general[$key][$nestedKey][$nestedNestedKey] = $params[$nestedNestedKey];

                                } else {

                                    foreach ($nestedNestedValue as $nestedNestedNestedKey => $nestedNestedNestedValue) {

                                        if (is_array($nestedNestedNestedValue) && !in_array($nestedNestedNestedKey, ['joinMandatory', 'checkSentences'])) {

                                            $this->nestedArrayWorker($nestedNestedValue, [$key,$nestedKey,$nestedNestedKey], $this->array_cab_general, $params, 0);

                                        }

                                    }

                                }

                            }

                        }

                    } else {

                        $this->array_cab_general["gDtipDE"]['gCamItem'] = $params['gCamItem'];

                    }

                }

            } else {

                unset($this->array_cab_general[$key]);

            }

        }

        ##Paso 8: Convierte el ARRAY de los campos a XML
        $xml = $this->array2xml($this->array_cab_general);

        ##Paso 9: Retorna el XML o el resultado de los campos o los errores generados
        return [

            "status" => $this->status,
            "errors" => $this->errors,
            "arrayData" => $this->array_cab_general,
            "xmlData" => $xml,

        ];

    }

    private function nestedArrayWorker($array_cab_general_aux, $keyStepPath, &$array, $params, $level)
    {

        foreach ($array_cab_general_aux as $nestedKeyAux => $nestedValueAux) {

            if ($level == 0) {

                $lastKey = $nestedKeyAux;

            } else {

                $lastKey = end($keyStepPath);

            }

            if (is_array($nestedValueAux) && in_array($lastKey, $this->array_cabeceras)) {

                array_push($keyStepPath, $nestedKeyAux);

                $this->nestedArrayWorker($nestedValueAux, $keyStepPath, $this->array_cab_general, $params, $level);
                $level = 1;

            } else {

                if ($level == 0) {

                    $lastKey = $nestedKeyAux;

                } else {

                    $lastKey = end($keyStepPath);

                }

                if (isset($params[$lastKey])) {

                    array_push($keyStepPath, $lastKey);

                    $path = "['" . implode("']['", $keyStepPath) . "']";

                    if (is_int($params[$lastKey])) {

                        try {

                            eval("\$array{$path} = $params[$lastKey];");

                        } catch (ParseError $e) {
                        }

                    } else {

                        eval("\$array{$path} = '$params[$lastKey]';");

                    }

                    array_pop($keyStepPath);

                }

            }

        }

    }

    private function array2xml($array, $xml = false)
    {
        if ($xml === false) {

            $xml = new SimpleXMLElement('<DE Id="" />');

        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!empty($value) && (!isset($value['type']) && !isset($value['maxlenght']))) {
                    $this->array2xml($value, $xml->addChild($key));
                }
            } else {
                $xml->addChild($key, $value);
            }
        }
        return $xml->asXML();
    }

    private function checkFieldType($type, $data, $key)
    {

        $error = true;
        $errorMessage = '';
        $errorReturn = [];

        switch ($type) {

            case "number":

                if (!is_int($data) && !is_float($data)) {

                    $error = false;
                    $errorMessage = 'El tipo de datos '. $key . ' debe ser numerico';

                }

                break;

            case "date":

                if (!strtotime($data)) {

                    $error = false;
                    $errorMessage = 'El tipo de datos '. $key . '  debe ser fecha';

                }

                break;

            case "spdate":

                $d = DateTime::createFromFormat('Y-m-d\TH:i:s', $data);
                $kinda = $d && $d->format('Y-m-d\TH:i:s') == $data;

                if (!$kinda) {

                    $error = false;
                    $errorMessage = 'El tipo de datos '. $key . '  debe ser fecha en formato Y-m-d\TH:i:s';

                }

                break;

            case "string":

                if (!is_string($data)) {

                    $error = false;
                    $errorMessage = 'El tipo de datos '. $key . '  debe ser string';

                }

                break;

            case "currency":

                if (is_string($data)) {

                    if (!in_array($data, $this->array_countries_iso_cod_money)) {

                        $error = false;
                        $errorMessage = 'El tipo de datos '. $key . '  debe ser tipo moneda ISO 4217';
                    }

                }

                break;

        }

        $errorReturn = [

            "status" => $error,
            "msg" => $errorMessage,

        ];

        return $errorReturn;

    }

}
