<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

$idsucursal_clie = intval($_GET['id']);
if ($idsucursal_clie == 0) {
    header("location:cliente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from sucursal_cliente 
where 
idsucursal_clie = $idsucursal_clie
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal_clie = intval($rs->fields['idsucursal_clie']);
if ($idsucursal_clie == 0) {
    header("location: sucursal_cliente.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $idcliente = antisqlinyeccion($_POST['idcliente'], "int");


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

    $parametros_array = [
        "idsucursal_clie" => $idsucursal_clie,
        "ahora" => $ahora,
        "idusu" => $idusu

    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = sucursal_cliente_delete($parametros_array);
        if ($res["valido"] == "S") {
            header("location: cliente.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    }

}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



// se puede mover esta funcion al archivo funciones_sucursal_cliente.php y realizar un require_once
function sucursal_cliente_delete($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $idsucursal_clie = antisqlinyeccion($parametros_array['idsucursal_clie'], "int");
    $ahora = antisqlinyeccion($parametros_array['$ahora'], "text");
    $idusu = antisqlinyeccion($parametros_array['$idusu'], "int");




    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update sucursal_cliente
		set
			estado = 6,
			borrado_por = $idusu,
			borrado_el = $ahora
		where
			idsucursal_clie = $idsucursal_clie
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["error" => $errores,"valido" => $valido];
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once("../includes/head_gen.php"); ?>
</head>

<body class="nav-md">
<div class="container body">
    <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
        <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                </div>
                <div class="clearfix"></div>
                <?php require_once("../includes/lic_gen.php");?>
                
                <!-- SECCION -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Borrar Sucursal</h2>
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                    </li>
                                </ul>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <!-- AQUI SE COLOCA EL HTML -->
                                <?php if (trim($errores) != "") { ?>
                                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                        <strong>Errores:</strong><br /><?php echo $errores; ?>
                                    </div>
                                <?php } ?>
                                <form id="form1" name="form1" method="post" action="">
                                    <hr />
                                    <div class="table-responsive">
                                        <table width="100%" class="table table-bordered jambo_table bulk_action">
                                            <thead>
                                                <tr>
                                                    <th align="center">Idsucursal clie</th>
                                                    <th align="center">Idcliente</th>
                                                    <th align="center">Sucursal</th>
                                                    <th align="center">Direccion</th>
                                                    <th align="center">Telefono</th>
                                                    <th align="center">Mail</th>
                                                    <th align="center">Estado</th>
                                                    <th align="center">Registrado por</th>
                                                    <th align="center">Registrado el</th>
                                                    <th align="center">Borrado por</th>
                                                    <th align="center">Borrado el</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while (!$rs->EOF) { ?>
                                                    <tr>
                                                        <td align="center"><?php echo intval($rs->fields['idsucursal_clie']); ?></td>
                                                        <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['mail']); ?></td>
                                                        <td align="center"><?php echo intval($rs->fields['estado']); ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
                                                        <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                                                            echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
                                                        }  ?></td>
                                                        <td align="center"><?php echo antixss($rs->fields['borrado_por']); ?></td>
                                                        <td align="center"><?php if ($rs->fields['borrado_el'] != "") {
                                                            echo date("d/m/Y H:i:s", strtotime($rs->fields['borrado_el']));
                                                        }  ?></td>
                                                    </tr>
                                                <?php
                                                    $rs->MoveNext();
                                                }
//$rs->MoveFirst();
?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <br />
                                    <div class="clearfix"></div>
                                    <br />
                                    <div class="form-group">
                                        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
                                            <button type="button" class="btn btn-danger" onclick="confirmarBorrado()"><span class="fa fa-trash-o"></span> Borrar</button>
                                            <button type="button" class="btn btn-primary" onclick="window.location.href='sucursal_cliente_deñ.php'"><span class="fa fa-ban"></span> Cancelar</button>
                                        </div>
                                    </div>

                                    <input type="hidden" name="MM_update" value="form1" />
                                    <input type="hidden" name="form_control" value="<?php echo antixss($_SESSION['form_control']); ?>">
                                    <br />
                                </form>
                                <div class="clearfix"></div>
                                <br /><br />
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SECCION --> 
            </div>
        </div>
        <!-- /page content -->
    
        <!-- POPUP DE MODAL OCULTO -->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                    </div>
                    <div class="modal-body" id="modal_cuerpo">
                        Contenido...
                    </div>
                    <div class="modal-footer" id="modal_pie">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                
                </div>
            </div>
        </div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
    <?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
    </div>
</div>
<?php require_once("../includes/footer_gen.php"); ?>
<script>
    function confirmarBorrado() {
        var confirmacion = confirm("¿Está seguro de que desea borrar el registro?");
        if (confirmacion) {
            alert("Sucursal eliminada correctamente");
            // Aquí puedes enviar el formulario para procesar el borrado
            document.getElementById("form1").submit();
        }
    }
</script>

  </body>
</html>
