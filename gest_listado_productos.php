<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");

// parametros
$nropagina = intval($_GET["pagina"]);
$cat = intval($_GET["idcategoria"]);
$sub = intval($_GET["idsubcate"]);
$marc = intval($_GET["marc"]);
$prodn = antisqlinyeccion($_GET["prod"], "like");
$barcode = antisqlinyeccion($_GET["barcode"], "text");
$idproducto = intval($_GET["coprod"]);

$whereadd = "";
$url_add = "";

// construye consulta
if ($idproducto > 0) {

    $whereadd .= " and productos.idprod_serial = $idproducto ";
    $url_add .= '&prod='.$idproducto;
}
if ($cat > 0) {
    $whereadd .= " and productos.idcategoria = $cat ";
    $url_add .= '&cat='.$cat;
}
if ($sub > 0) {
    $whereadd .= " and productos.idsubcate = $sub ";
    $url_add .= '&sub='.$sub;
}
if ($marc > 0) {
    $whereadd .= " and productos.idmarca = $marc ";
    $url_add .= '&marc='.$marc;
}
if (trim($_GET["prod"]) != '') {
    $whereadd .= " and productos.descripcion like '%$prodn%' ";
    $url_add .= '&prod='.$prodn;
}
if (trim($_GET["barcode"]) != '') {
    $whereadd .= " and productos.barcode = $barcode ";
    $url_add .= '&barcode='.$barcode;
}

//(select idprod from recetas_detalles where recetas_detalles.idprod = productos.idprod_serial limit 1) as receta,
$consulta = "
    Select productos.*, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria,
    (
    SELECT idprod
    FROM recetas_detalles
    inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente
    inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where
    recetas_detalles.idprod = productos.idprod_serial
    and coalesce(insumos_lista.idproducto,0) <> productos.idprod_serial
    limit 1
    ) as receta,
    (select idproducto from agregado where agregado.idproducto = productos.idprod_serial limit 1) as agregado,
    (select idproducto from producto_impresora where producto_impresora.idproducto = productos.idprod_serial limit 1) as tieneimpre,
    (select tipoproducto from productos_tipo where productos.idtipoproducto = productos_tipo.idtipoproducto) as tipoproducto,
    (select productos_sucursales.precio as p1 from productos_sucursales where productos_sucursales.idproducto = productos.idprod_serial and activo_suc = 1 and productos_sucursales.idsucursal = $idsucursal order by productos_sucursales.idsucursal asc limit 1) as p1,
    (select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
    from productos 
    inner join categorias on productos.idcategoria = categorias.id_categoria 
    inner join sub_categorias on productos.idsubcate = sub_categorias.idsubcate 
    where 
    productos.borrado = 'N'
    and productos.idempresa = $idempresa
    $whereadd
    order by idtipoproducto desc, productos.descripcion asc
    "    ;
//echo $consulta;
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$num_total_registros = $prod->RecordCount();

/******** PAGINACION **********/

//Limito la busqueda
$TAMANO_PAGINA = 100;
//examino la página a mostrar y el inicio del registro a mostrar
if ($nropagina == 0) {
    $inicio = 0;
    $nropagina = 1;
} else {
    $inicio = ($nropagina - 1) * $TAMANO_PAGINA;
}
//calculo el total de páginas
$total_paginas = ceil($num_total_registros / $TAMANO_PAGINA);

// volver a consultar pero agregar los limites
$consulta .= " 
    Limit $inicio, $TAMANO_PAGINA
    ";
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

/******** PAGINACION **********/




// tipo de favoritos
$consulta = "
SELECT tipofavorito, maxprod, maxdias
FROM tipofavorito
where 
idempresa = $idempresa
limit 1
";
$rsfav = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipofavorito = $rsfav->fields['tipofavorito'];
$maxprod = intval($rsfav->fields['maxprod']);
$maxdias = intval($rsfav->fields['maxdias']);

if ($tipofavorito == 'F') {
    $favoritodesc = "Favoritos Seleccionados.";
} else {
    $favoritodesc = "Productos mas vendidos | Top $maxprod de los ultimos $maxdias dias.";
}

// busca si usa receta
$busca = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_receta = trim($rspref->fields['usa_receta']);
$usa_marca = trim($rspref->fields['usa_marca']);


//Categorias
$buscar = "Select * from categorias 
where 
estado = 1
and (idempresa = $idempresa or borrable = 'N')
and id_categoria not in (SELECT idcategoria FROM categoria_ocultar where idempresa = $idempresa and mostrar = 'N')
 order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//SubCategorias
$buscar = "Select * from sub_categorias where 
(idempresa = $idempresa or idcategoria in (select id_categoria from categorias where especial = 'S'))
 order by descripcion ASC";
$rssubcate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$buscar = "Select * from preferencias_produccion limit 1";
$rscp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$obligar_cpr = trim($rscp->fields['obligar_cpr']);


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
      <script>
function subcategorias(idcategoria){
    var direccionurl='subcate_new_list.php';    
    var parametros = {
      "idcategoria" : idcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#subcatebox").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
}

function borrar(desc,id){
    //if(window.confirm('Esta seguro que desea borrar: '+desc+' ?')){
        document.location.href='gest_eliminar_productos.php?id='+id;
    //}
}
function recargar(valor){
    
    if (valor!=''){
        var parametros='cat='+valor;
        OpenPage('minisub_lista.php',parametros,'GET','trsub','pred');
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
                    <h2>Productos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="productos_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="gest_listado_productos_bor.php" class="btn btn-sm btn-default"><span class="fa fa-trash"></span> Productos Borrados</a>
<a href="gest_favorito_productos.php" target="_blank" class="btn btn-sm btn-default"><span class="fa fa-star-o"></span> Tipo de Favorito</a>
<a href="favoritos_suc.php" target="_blank" class="btn btn-sm btn-default"><span class="fa fa-star-o"></span> Favoritos por Local</a>
<a href="productos_csv.php" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Productos</a>
<a href="productos_importar.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
<a href="productos_importar_edit.php?idsucu=0" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Edicion Masiva</a>
</p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="prod" id="prod" value="<?php  if (isset($_GET['prod'])) {
        echo htmlentities($_GET['prod']);
    } ?>" placeholder="Producto" class="form-control"  />                    
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Producto </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="coprod" id="coprod" value="<?php  if (isset($_GET['coprod'])) {
        echo htmlentities($_GET['coprod']);
    } ?>" placeholder="Cod Producto" class="form-control"  />                    
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Barras </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="barcode" id="barcode" value="<?php  if (trim($_GET['barcode']) > 0) {
        echo antixss($_GET['barcode']);
    } ?>" placeholder="Codigo de Barras" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
require_once("cate_new_list.php");

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Subcategoria *</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
<?php
require_once("subcate_new_list.php");

?>
    </div>
</div>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><hr />


    <ul class="pagination">
<?php
//$rs_prod->MoveFirst();
if ($url_add != '') {
    $url = "gest_listado_productos.php?sf=n".$url_add;
} else {
    $url = "gest_listado_productos.php?sf=s";
}
if ($total_paginas > 1) {
    //echo '<a href="'.$url.'?pagina='.($pagina-1).'"><img src="images/izq.gif" border="0"></a>';
    if ($nropagina != 1) {
        echo '<li><a href="'.$url.'&pagina='.($nropagina - 1).'">&laquo;</a></li>';
    }
    for ($i = 1;$i <= $total_paginas;$i++) {
        if ($nropagina == $i) {
            //si muestro el índice de la página actual, no coloco enlace
            //echo $pagina;
            echo '<li class="active" style="background-color:#FE980F; color:#FFF;"><a href="'.$url.'">'.$nropagina.'</a></li>';
        } else {
            //si el índice no corresponde con la página mostrada actualmente,
            //coloco el enlace para ir a esa página
            // echo '  <a href="'.$url.'?pagina='.$i.'">'.$i.'</a>  ';
            echo '<li><a href="'.$url.'&pagina='.$i.'">'.$i.'</a></li>';
        }

    }
    if ($nropagina != $total_paginas) {
        //echo '<a href="'.$url.'?pagina='.($pagina+1).'"><img src="images/der.gif" border="0"></a>';
        echo '<li><a href="'.$url.'&pagina='.($nropagina + 1).'">&raquo;</a></li>';
    }
}
?>
    </ul>
    <div class="clearfix"></div>
    <br />
    
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
        <th ></th>
        <th  >Producto</th>
        <th >Precio</th>
        <th >Categoria</th>
        <th >Sub-Categoria</th>
        <?php if ($obligar_cpr == 'S') { ?>
        <th>CPR</th>
        <?php } ?>
     

        <th  >Foto</th>
        </tr>
        </thead>
        <tbody>
      <?php while (!$prod->EOF) {

          $img = "gfx/productos/prod_".$prod->fields['idprod_serial'].".jpg";
          if (!file_exists($img)) {
              $img = "gfx/productos/prod_0.jpg";
          }

          $pventa = intval($prod->fields['precio_venta']);
          $pmin = intval($prod->fields['precio_min']);
          $pmax = intval($prod->fields['precio_max']);
          $idserial = intval($prod->fields['idprod_serial']);

          $buscar = "Select idcentroprod from insumos_lista where idproducto=$idserial";
          $rspcr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
          $idcpr = intval($rspcr->fields['idcentroprod']);
          if ($idcpr > 0) {
              $buscar = "Select descripcion from produccion_centros where idcentroprod=$idcpr";
              $rsdes = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
              $descripcioncpr = trim($rsdes->fields['descripcion']);
          } else {
              $descripcioncpr = "<span style='color:red'>SIN CPR</span>";
          }
          if ($pventa > 0) {
              $tp = 1;
          } else {
              if ($pmin > 0) {
                  $tp = 2;
              } else {
                  $tp = 3;
              }
          }


          ?>
      <tr>
            <td>
                
                <div class="btn-group">
                    <a href="gest_editar_productos_new.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                     <?php  if ($prod->fields['idtipoproducto'] == 1 or $prod->fields['idtipoproducto'] == 5) { ?>
                    <a href="gest_recetas.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Receta" data-toggle="tooltip" data-placement="right"  data-original-title="Receta"><span class="fa fa-cutlery"></span></a>
                   <?php } ?>
                     <?php  if ($prod->fields['idtipoproducto'] == 2) { ?>
                    <a href="gest_combo.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Armar Combo" data-toggle="tooltip" data-placement="right"  data-original-title="Armar Combo"><span class="fa fa-cogs"></span></a>
                     <?php } ?>
                    <?php  if ($prod->fields['idtipoproducto'] == 4) { ?>
                    <a href="gest_combinado.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Armar Combinado" data-toggle="tooltip" data-placement="right"  data-original-title="Armar Combinado"><span class="fa fa-cogs"></span></a>
                    <?php } ?>
                    <?php if ($prod->fields['idtipoproducto'] <> 6) { ?>
                    <a href="gest_agregados.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Agregados" data-toggle="tooltip" data-placement="right"  data-original-title="Agregados"><span class="fa fa-plus-square"></span></a>
                   
                    <?php } ?>
                     <?php  if ($prod->fields['idtipoproducto'] == 11) { ?>
                        <a href="gest_combo_cat.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Armar Combo Catering" data-toggle="tooltip" data-placement="right"  data-original-title="Armar Combo Catering"><span class="fa fa-cogs"></span></a>
                        <a href="gest_combo_cat_duplica.php?id=<?php echo $prod->fields['idprod_serial']; ?>&procede=1" class="btn btn-sm btn-default" title="Duplicar Combo Catering" data-toggle="tooltip" data-placement="right"  data-original-title="Duplicar Combo Catering"><span class="fa fa-copy"></span></a>
                    <?php } ?>
                    <a href="productos_foto.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Cambiar Imagen" data-toggle="tooltip" data-placement="right"  data-original-title="Cambiar Imagen"><span class="fa fa-picture-o"></span></a>
                    <a href="gest_eliminar_productos.php?id=<?php echo $prod->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>

        <td height="27" align="center"><?php echo trim($prod->fields['descripcion']) ?><br />[A:<?php echo $prod->fields['idinsumo']; ?>] [P:<?php echo $prod->fields['idprod_serial']; ?>]<br /><?php
                echo "<strong>Tipo:</strong> ".$prod->fields['tipoproducto']."<br />";
          if (intval($prod->fields['receta']) > 0) {
              echo "TIENE RECETA<br />";
          }
          if (intval($prod->fields['agregado']) > 0) {
              echo "TIENE AGREGADO<br />";
          }
          if (intval($prod->fields['tieneimpre']) == 0) {
              echo "<strong style=\"color:#FF0000;\">SIN IMPRESORA ASIGNADA</strong>";
          }
          ?></td>
        <td align="center"><?php echo formatomoneda($prod->fields['p1'], 0) ?></td>
        <td align="center"><?php echo trim($prod->fields['categoria']) ?></td>
        <td align="center"><?php echo trim($prod->fields['subcategoria']) ?></td>
        <?php if ($obligar_cpr == 'S') { ?>
        <td align="center"><?php echo $descripcioncpr; ?></td>
        <?php } ?>
   
        

        <td align="center"><a href="productos_foto.php?id=<?php echo $prod->fields['idprod_serial']; ?>"><img src="<?php echo $img ?>" height="100" width="200" border="0"  /></a></td>
        </tr>
      <?php $prod->MoveNext();
      } ?>
      </tbody>
</table>
 </div>
    <div class="clearfix"></div>
    <br />

    <ul class="pagination">
<?php
//$rs_prod->MoveFirst();
if ($url_add != '') {
    $url = "gest_listado_productos.php?sf=n".$url_add;
} else {
    $url = "gest_listado_productos.php?sf=s";
}
if ($total_paginas > 1) {
    //echo '<a href="'.$url.'?pagina='.($pagina-1).'"><img src="images/izq.gif" border="0"></a>';
    if ($nropagina != 1) {
        echo '<li><a href="'.$url.'&pagina='.($nropagina - 1).'">&laquo;</a></li>';
    }
    for ($i = 1;$i <= $total_paginas;$i++) {
        if ($nropagina == $i) {
            //si muestro el índice de la página actual, no coloco enlace
            //echo $pagina;
            echo '<li class="active" style="background-color:#FE980F; color:#FFF;"><a href="'.$url.'">'.$nropagina.'</a></li>';
        } else {
            //si el índice no corresponde con la página mostrada actualmente,
            //coloco el enlace para ir a esa página
            // echo '  <a href="'.$url.'?pagina='.$i.'">'.$i.'</a>  ';
            echo '<li><a href="'.$url.'&pagina='.$i.'">'.$i.'</a></li>';
        }

    }
    if ($nropagina != $total_paginas) {
        //echo '<a href="'.$url.'?pagina='.($pagina+1).'"><img src="images/der.gif" border="0"></a>';
        echo '<li><a href="'.$url.'&pagina='.($nropagina + 1).'">&raquo;</a></li>';
    }
}
?>
</ul>




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
