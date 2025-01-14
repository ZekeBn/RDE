 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");


$idproducto = intval($_GET['idp']);
if ($idproducto > 0) {
    $consulta = "Select * from productos where idprod_serial=$idproducto";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $desp = trim($rs->fields['descripcion']);
    //con el id del producto armamos los items
    //Grupos
    $buscar = "Select * from combos_listas where idproducto=$idproducto and estado <> 6 order by nombre asc";
    $rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
    <script>
        function recalcular(id1,id2){
            
            var tgrupo=0;
            var iddetalle=parseInt(id1);
            var idlistacombo=parseInt(id2);
            //P1: vemos si esta prendido o apagado
            if($("#entrante_"+iddetalle).prop("checked") == true){
               //Sumar
               tgrupo=parseInt($("#ocgruposele_"+idlistacombo).val());
               tgrupo=tgrupo+1;
               $("#ocgruposele_"+idlistacombo).val(tgrupo);
            }
            if($("#entrante_"+iddetalle).prop("checked") == false){
                $("#cant_"+iddetalle+"_"+idlistacombo).val("");
               //Restar
               tgrupo=parseInt($("#ocgruposele_"+idlistacombo).val());
               tgrupo=tgrupo-1;
               $("#ocgruposele_"+idlistacombo).val(tgrupo);
               $("#cant_"+iddetalle+"_"+idlistacombo).val("");
               //Buscamos esa expresion en el input y reemplazamos para los recalculos
               var cop="'"+iddetalle+",'";
               
                const str = "'"+$("#ocelementogrupo_"+idlistacombo).val()+"'";
                const nuevaStr = str.replace(cop, " ");
                console.log("Este "+nuevaStr);
               
            }
            if (tgrupo==0){
                    $("#ocelementogrupo_"+idlistacombo).val("");
                    
            }
            var vg=$("#ocelementogrupo_"+idlistacombo).val();
            //alert(vg);
            //alert(iddetalle);
            if($("#entrante_"+iddetalle).prop("checked") == true){
                if (vg==''){
                    var tmp=parseInt(iddetalle);
                    $("#ocelementogrupo_"+idlistacombo).val(tmp);
                } else {
                    vg=vg+","+iddetalle;
                    $("#ocelementogrupo_"+idlistacombo).val(vg);
                }
                var elInput = $("#ocelementogrupo_"+idlistacombo).val();
                let arr=elInput.split(','); 
                
                //arreglo.push(elInput);
                //alert(arreglo);
                //var arr =  Array.from(arreglo); 
                //console.log(arr);
                //arr.forEach(element => alert(element));
                //for (let i = 0; i < arr.length; i++) {
                //    var nm=arr[i];
                //    alert(nm);
                //}
                var cuantos=parseFloat($("#ocgruposele_"+idlistacombo).val());
                var cantidadpersonas=$("#cantiper").val();
                var cantiporpersona=$("#ocmper_"+id2).val();
                //colocamos los ids en un input separado por algo y hacemos un for
                arr.forEach((number, index) => {
                    console.log('Index: ' + index + ' Value: ' + number);
                    //ahora que ya tenemos acceso a los items, recalculamos de cada detalle
                    
                    var rp='';
                    rp=(cantidadpersonas*cantiporpersona)/cuantos;
                    //alert(rp);
                    $("#cant_"+number+"_"+idlistacombo).val(rp);
                    
                    
                    
                });
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
                    <h2>Armar Combo Catering: <?php echo $desp; ?> &nbsp;&nbsp;</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <h4> Cantidad personas para evento: &nbsp;<?php echo $_GET['cp']; ?> <input type="hidden" name="cantiper" id="cantiper" value="<?php echo $_GET['cp']; ?>" /></h4>
                  <table width="100%" class="table table-bordered jambo_table bulk_action">
                        
                    <?php while (!$rsl->EOF) {
                        $grupo = trim($rsl->fields['nombre']);
                        $idlistacombo = intval($rsl->fields['idlistacombo']);
                        $cantidadpermite = intval($rsl->fields['cantidad']);
                        $maxporpersona = floatval($rsl->fields['cantidad_porpers']);


                        ?>
                            <thead>
                                <tr>
                                    <th>Grupo : <?php echo $grupo; ?>
                                        <input type="hidden" name="ocgruposele_<?php echo $idlistacombo; ?>" id="ocgruposele_<?php echo $idlistacombo; ?>" value="0" />
                                        <input type="hidden" name="ocelementogrupo_<?php echo $idlistacombo; ?>" id="ocelementogrupo_<?php echo $idlistacombo; ?>" value="" />
                                    </th>
                                    <th>Seleccionar</th>
                                    <th>M&aacute;ximo Variedades : <?php echo $cantidadpermite; ?>
                                    <input type="hidden" name="ocvar_<?php echo $idlistacombo ?>" id="ocvar_<?php echo $idlistacombo ?>" value="<?php echo $cantidadpermite?>" />
                                    </th>
                                    <th>M&aacute;ximo x persona : <?php echo $maxporpersona; ?>
                                        <input type="hidden" name="ocmper_<?php echo $idlistacombo ?>" id="ocmper_<?php echo $idlistacombo ?>" value="<?php echo $maxporpersona ?>" />
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $buscar = "Select idlistacombodet,idlistacombo,(select descripcion from productos where idprod_serial=combos_listas_det.idproducto) as producto
                                from
                                combos_listas_det 
                                where idlistacombo=$idlistacombo and estado <> 6 order by producto asc ";
                        $rsl2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                        $tregi = 0;
                        while (!$rsl2->EOF) {
                            $tregi = $tregi + 1;
                            $dp = trim($rsl2->fields['producto']);
                            $iddetalle = intval($rsl2->fields['idlistacombodet']);
                            ?>
                                <tr id="grupo_<?php echo "$idlistacombo"; ?>_num_<?php echo $tregi ?>">
                                    <th><?php echo $dp; ?></th>
                                    
                                    <th id="entrante_td_<?php echo $iddetalle; ?>">
                                    
                                        <input name="entrante_<?php echo $iddetalle ?>" id="entrante_<?php echo $iddetalle ?>" type="checkbox" value="S" class="js-switch" onChange="recalcular(<?php echo $iddetalle ?>,<?php echo $idlistacombo ?>);" <?php if ($entrante == 'S') {
                                            echo "checked";
                                        } ?>   >
        
                                        <input type="button" name="ch_<?php echo $iddetalle ?>" id="ch_<?php echo $iddetalle ?>"   onclick="recalcular(<?php echo $iddetalle ?>,<?php echo $idlistacombo ?>);" value="Seleccionar" />
                                    
                                    </th>
                                    <th><input type="text" name="cant_<?php echo $iddetalle ?>_<?php echo $idlistacombo ?>" id="cant_<?php echo $iddetalle ?>_<?php echo $idlistacombo ?>" value="" readonly /></th>
                                    <th></th>
                                </tr>
                                <?php $rsl2->MoveNext();
                        }?>
                            <?php $rsl->MoveNext();
                    } ?>
                            </tbody>
                        </table>
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
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
