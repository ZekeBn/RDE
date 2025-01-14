 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");


// archivos
require_once("includes/upload.php");
require_once("includes/funcion_upload.php");
set_time_limit(120);


$idajuste = intval($_GET['id']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos_ajustes_stock 
where 
idajuste = $idajuste
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajuste = intval($rs->fields['idajuste']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}


$consulta = "
select *,
(select usuario from usuarios where gest_depositos_ajustes_stock.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from gest_depositos where iddeposito = gest_depositos_ajustes_stock.iddeposito) as deposito,
(select motivo from motivos_ajuste where idmotivo = gest_depositos_ajustes_stock.idmotivo) as motivo_ajuste
from gest_depositos_ajustes_stock 
where 
 estado = 'A'
 and idajuste = $idajuste
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));






$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {

    $directorio = "gfx";

    $fupload = new Upload();
    $fupload->setPath($directorio);
    $fupload->setMinSize(0);
    $exten = ['csv'];
    $extension = strtolower(substr($_FILES['archivo']['name'], strrpos($_FILES['archivo']['name'], '.') + 1));

    $tiempo = date("YmdHis");
    $nombrearchivo = 'ajust_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'ajust_'.$tiempo;

    $ip_real = htmlentities(ip_real());

    if ($extension == 'csv') {
        $fupload->setFile("archivo", $nombrearchivo2, 'S');
        $fupload->isImage(false);
        // IMAGEN
    } else {
        //$fupload->setFile("archivo",$nombrearchivo2,'N',$extension);
        //$fupload->isImage(true);
    }
    //$fupload->isImage(true);
    $fupload->save();

    $cargado = $fupload->isupload;
    $status = $fupload->message;

    // si se cargo
    if ($cargado) {


        $archivo_csv = file_get_contents($directorio.'/'.$nombrearchivo);
        $array_res = csv_to_array($archivo_csv, ";");
        //print_r($array_res);exit;

        // borra el archivo
        if (file_exists($directorio.'/'.$nombrearchivo)) {
            if (trim($nombrearchivo) != '') {
                unlink($directorio.'/'.$nombrearchivo);
            }
        }

        // validaciones basicas
        $valido = "S";
        $errores = "";
        $i = 1;
        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                $cantidad = floatval(str_replace(',', '.', $fila[1]));
                $idinsumo = intval($fila[5]);

                $consulta = "
                select idinsumo from insumos_lista where idinsumo = $idinsumo and estado = 'A'
                ";
                $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idinsumoex = intval($rsins->fields['idinsumo']);

                if (intval($idinsumoex) <= 0) {
                    $valido = "N";
                    $errores .= " - El articulo con codigo [$idinsumo] no existe o fue borrado. Linea: $i.<br />";
                }
                /*if(trim($cantidad) == ''){
                    $valido="N";
                    $errores.=" - El campo cantidad no puede estar vacio. Linea: $i, Codigo: [$idinsumo].<br />";
                }*/
                $consulta = "
                select idinsumo from tmp_ajuste where idinsumo = $idinsumo and idajuste = $idajuste limit 1
                ";
                $rsinvex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $idinsumoinvex = intval($rsinvex->fields['idinsumo']);
                if (intval($idinsumoinvex) > 0) {
                    //echo $consulta;exit;
                    $valido = "N";
                    $errores .= " - El articulo con codigo [$idinsumo] ya fue cargado, borrelo antes de volver a cargar para evitar duplicidad. Linea: $i.<br />";
                }


            } // if($i > 1){
            $i++;

        }
        // reset del array
        reset($array_res);

        //echo $errores;
        //exit;
        // si todo es valido inserta
        if ($valido == 'S') {
            $i = 1;
            foreach ($array_res as $fila) {
                // la cabecera se salta
                if ($i > 1) {
                    $cantidad = floatval(str_replace(',', '.', $fila[1]));
                    $idinsumo = intval($fila[5]);
                    // solo si envio cantidad (ya sea negativa o positiva)
                    if ($cantidad != 0) {
                        $consulta = "
                        select descripcion from insumos_lista where idinsumo = $idinsumo
                        ";
                        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $descripcion = antisqlinyeccion($rsins->fields['descripcion'], "text");
                        if ($cantidad >= 0) {
                            $tipoajuste = "+";
                        } else {
                            $tipoajuste = "-";
                        }


                        if ($cantidad < 0) {
                            $cantidad = $cantidad * -1;
                        }

                        $consulta = "
                        insert into
                        tmp_ajuste
                        (idajuste,idinsumo,cantidad,tipoajuste,horareg)
                        values
                        ($idajuste,$idinsumo,$cantidad,'$tipoajuste','$ahora')
                        ";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    }


                } // if($i > 1){
                $i++;
            }


            // redireccionar
            header("location: ajustar_stock_det.php?id=$idajuste");
            exit;

        }
    } else {
        header("location: ajustar_stock_import.php?id=$idajuste&cargado=n&status=".$status);
        exit;
    }


}

if (isset($_GET['status']) && ($_GET['status'] != '')) {
    $status = substr(htmlentities($_GET['status']), 0, 200);
}
if ($_GET['cargado'] == 'n') {
    $errores = htmlentities($_GET['status']);
}


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
                    <h2>Carga masiva de Ajuste</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idajuste</th>

            <th align="center">Deposito</th>

            <th align="center">Motivo</th>
            <th align="center">Fecha Ajuste</th>
            <th align="center">Registrado el</th>
            <th align="center">Registrado por</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idajuste']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['deposito']); ?> [<?php echo intval($rs->fields['iddeposito']); ?>]</td>

            <td align="center"><?php echo antixss($rs->fields['motivo_ajuste']); ?> [<?php echo antixss($rs->fields['idmotivo']); ?>]</td>
            <td align="center"><?php if ($rs->fields['fechaajuste'] != "") {
                echo date("d/m/Y", strtotime($rs->fields['fechaajuste']));
            }  ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>

        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>

                                        
                      
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>



<form action="ajustar_stock_import.php?id=<?php echo $idajuste; ?>" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo CSV *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".csv"  />
    </div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='ajustar_stock_det.php?id=<?php echo $idajuste ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='ajustar_stock_import_csv.php?id=<?php echo $idajuste; ?>'"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button><br />
<br />
<strong>Paso 2:</strong><br />
Completar el excel sin agregar ni sacar columnas.
<strong style="color:#F00">NO MODIFICAR</strong> las columnas de Codigo y/o Descripcion, <br />
<strong style="color:#060;">SOLAMENTE MODIFICAR</strong> "Cantidad" ninguna otra columna.<br />
En caso que modifique otra columna podria ocasionar errores en el stock de otros productos y el cambio sera irreversible (No se podra solucionar).<br />
<br />
<strong>Paso 3:</strong><br />
Cargar aqui el archivo excel con las nuevas cantidades.
<br />
 </form>

<p>&nbsp;</p>
<br />
                      
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
