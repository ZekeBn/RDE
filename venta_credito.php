 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");

header("location: cliente.php");
exit;




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
    <script>
        function filtrar(valor){
            var direccionurl='minivc.php';
            
                var parametros = {
                  "busca" : valor
                  
                };
                   $.ajax({
                      
                            data:  parametros,
                            url:   direccionurl,
                            type:  'post',
                            beforeSend: function () {
                                    
                            },
                            success:  function (response) {
                                
                                    $("#filtradod").html(response);
                                    
                            }
                    });
            
            
        }
        
    </script>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">

           <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Clientes con Linea de Credito</span>
                </div>

<br />
                <div align="center">
                    <table width="200" border="1">
                      <tbody>
                        <tr>
                          <td align="center"><a href="cliente_agrega_cred.php"><img src="img/02p64.png" width="64" height="64" alt=""/><br />[Agregar]</a></td>
                          <td align="center"><a href="venta_credito_habilita.php"><img src="img/1476042761_office-04.png" width="48" height="48" alt=""/><br />[Permitir Credito]</a></td>
                         <?php /*?> <td align="center"><a href="venta_credito_pagomasivo.php"><img src="img/icon-sticky-menu-account-activity.png" width="60" height="48" alt=""/>[Pago Masivo]</a></td>
                             <td align="center"><a href="adherentes_cobranzas.php"><img src="img/pagos.png" width="48" height="48" alt=""/>[Cobrar Cuenta]</a></td><?php */?>
                        </tr>
                      </tbody>
                    </table>

                    
                    
                </div>
                <div align="center">
                    <br />
                    <input type="text" name="filtrar" id="filtrar" style="height: 40px; width: 300px;" placeholder="Indique texto a filtrar" onKeyUp="filtrar(this.value)" />    
                </div>
                <br />
                <br />
                <div id="filtradod">
                    <?php require_once('minivc.php')?>
                </div>

          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
