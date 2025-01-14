 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");
$idcliente = intval($_GET['idc']);
if ($idcliente == 0) {
    $idcliente = intval($_POST['idc']);
    if ($idcliente == 0) {
        header("Location: cliente.php");
        exit;
    }
}



if ($_POST['idc']) {
    $errores = '';
    $idcliente = intval($_POST['idc']);
    $estaself = 0;
    $cod_usuariointerno = antisqlinyeccion($_POST['codusu'], 'text');//Para acceso web, hay que ver si es de pedidos o que cosa ( us_cod en tabla cliente_codigo)
    $pin_interno = antisqlinyeccion($_POST['pintitu'], 'text');//Para acceso web, hay que ver si es de pedidos o que cosa (pass_cod en tabla cliente_codigo)
    $cod_usuarioself = antisqlinyeccion($_POST['codself'], 'text');//Codigo de usuario para el self service en us_self SOLO para uso de TITULAR o DOCENTE
    $pass_self = antisqlinyeccion($_POST['pinself'], 'text');//Codigo de usuario para el self service en us_self

    if ($idcliente == 0) {
        $errores .= "Error al obtener id del cliente.<br />";
    }
    if ($_POST['codusu'] == '') {
        $errores .= "Debe indicar un codigo interno para el cliente. Sugerimos usar el mismo id del cliente.<br />";
    }
    //Controlamos codigo y pin para el Self service
    if ($_POST['codself'] != '' && $_POST['pinself'] != '') {
        //para el caso de los docentes se antepone una letra T al codigo enviado
        $cod_usuarioself = str_replace("'", "", $cod_usuarioself);
        $cod_usuarioself = str_replace("T", "", $cod_usuarioself);
        $cod_usuarioself = "T".$cod_usuarioself;
        $cod_usuarioself = antisqlinyeccion($cod_usuarioself, "text");
        $estaself = 1;
    } else {
        $errores .= "Debe indicar codigo y el pin para el Self Service.<br />";
    }
    //Controlamos los valores de us_cod y pass_cod




    if ($errores == '') {

        $buscar = "Select * from clientes_codigos where idcliente=$idcliente";
        $rscliente = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        if ($rscliente->fields['idcliente'] == '') {
            $existe = 0;
        } else {
            $existe = 1;
        }


        if ($existe == 0) {
            $insertar = "Insert into clientes_codigos
                    (us_self,pass_self,us_cod,pass_cod,us_web,pass_web,idcliente,idadherente,registrado_por,
                    registrado_el,idempresa,ult_modif,estado,estado_web,estado_self,bloqueado_web,cod_activacion,cod_vencimiento)
                    values
                    ($cod_usuarioself,$pass_self,$cod_usuariointerno,$pin_interno,NULL,NULL,$idcliente,NULL,$idusu,current_timestamp,$idempresa,current_timestamp,1,0,$estaself,0,NULL,NULL)
                    ";
            //echo $insertar;
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            $ok = 1;
        } else {
            $update = "Update clientes_codigos set estado_self=1,us_cod=$cod_usuariointerno,us_self=$cod_usuarioself,
            pass_self=$pass_self,pass_cod=$pin_interno
            where idcliente=$idcliente";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            $ok = 1;

        }

    }
    /*-------------------------ANULADO POR REVISION------------------------
    //Verificamos si ya exite en la tabla de codigos este cliente
    $buscar="Select * from clientes_codigos where idcliente=$idcliente";
    $rscliente=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    if ($rscliente->fields['idcliente']==''){
        $existe=0;
    } else {
        $existe=1;
    }
    //verificamos duplicidad de codigo  ingresado, no se puede permitir un codigo ya utilizado por otra persona
    $buscar="Select * from clientes_codigos where us_cod=$cod_usuariointerno";
    $rsbb=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

    $controla_codigo=trim($rsbb->fields['us_cod']);
    $cliente_existente=trim($rsbb->fields['idcliente']);

    $existecodigo=0;
    if ($rsbb->fields['us_cod']!=''){
            $existecodigo=1;
    }
    //echo $existecodigo;exit;
    if (($existecodigo==0) && $existe==0){
        //insertamos
        $insertar="Insert into clientes_codigos
        (us_self,pass_self,us_cod,pass_cod,us_web,pass_web,idcliente,idadherente,registrado_por,
        registrado_el,idempresa,ult_modif,estado,estado_web,estado_self,bloqueado_web,cod_activacion,cod_vencimiento)
        values
        ($cod_usuarioself,$pass_self,$cod_usuariointerno,$pin_interno,NULL,NULL,$idcliente,NULL,$idusu,current_timestamp,$idempresa,current_timestamp,1,0,$estaself,0,NULL,NULL)
        ";
        //echo $insertar;
        $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));

        $errorup=0;

    }else{
        //echo 'update';exit;
        if(trim($rscliente->fields['idcliente'])==$id && $existe==1){
            //echo 'es el mismo';exit;
            $update="Update clientes_codigos set estado_self=1,us_cod=$cod_usuariointerno,us_self=$cod_usuarioself where idcliente=$idcliente";
            $conexion->Execute($update) or die(errorpg($conexion,$update));
            $errorup=0;

        } else {

            $errorup=1;
        }

    }
    if ($errorup==0){
        header("Location: cliente.php");
        exit;
    }
    //echo $errorup;exit;
    --------------------------------------------------------*/


}


$buscar = "select nombre,apellido,razon_social,documento,idcliente from cliente where idcliente=$idcliente ";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$buscar = "select us_web,us_cod,us_self,us_empresa,pass_self,pass_cod,pass_web,pass_empresa from clientes_codigos where idcliente=$idcliente ";
$rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


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
                    <h2>Administrando Pines  para &nbsp; <?php echo $rs->fields['nombre'].' '.$rs->fields['apellido'] ?> &nbsp;ID: <?php echo $idcliente ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="ag1" action="" method="post">
                        <?php if ($errores != '') { ?>
                        <div class="alert alert-danger alert-dismissible fade in" role="alert" >
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Errores:</strong><br /><?php echo $errores; ?>
                        </div>
                        <?php } ?>
                        <?php if ($ok == 1) { ?>
                        <div class="alert alert-info alert-dismissible fade in" role="alert" >
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            Registro correcto!
                        </div>
                        
                        
                        <?php } ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Titular</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" name="codusu" id="codusu" value="<?php if ($_POST['codusu']) {
                                            echo $_POST['codusu'];
                                        } else {
                                            echo $rs1->fields['us_cod'];
                                        } ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">PIN Titular</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="password" name="pintitu" id="pintitu" value="<?php if ($_POST['pintitu']) {
                                            echo $_POST['pintitu'];
                                        } else {
                                            echo $rs1->fields['pass_cod'];
                                        } ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Self</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" name="codself" id="codself" value="<?php if ($_POST['codself']) {
                                            echo $_POST['codself'];
                                        } else {
                                            echo $rs1->fields['us_self'];
                                        } ?>" class="form-control" placeholder="Para Uso del docente /Titular" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">PIN Self</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="password" name="pinself" id="pinself" value="<?php if ($_POST['pinself']) {
                                            echo $_POST['pinself'];
                                        } else {
                                            echo $rs1->fields['pass_self'];
                                        } ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Portal Padres</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input disabled type="text" name="usweb" id="usweb" value="<?php echo $rs1->fields['us_web'] ?>" class="form-control" placeholder="" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">PIN Portal Padres</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input disabled type="password" name="passweb" id="passweb" value="<?php echo $rs1->fields['pass_web'] ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Empresa</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input disabled type="text" name="usweb" id="usweb" value="<?php echo $rs1->fields['us_web'] ?>" class="form-control" placeholder="" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">PIN Empresa</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input disabled type="password" name="passweb" id="passweb" value="<?php echo $rs1->fields['pass_web'] ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <br />
                        <br />
                            <div class="form-group">
                                <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
                                    <input type="hidden" name="idc" id="idc" value="<?php echo $idcliente; ?>" />
                                    <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>

                                    <button type="button" class="btn btn-primary" onMouseUp="document.location.href=cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
                                </div>
                            </div>
                    </form>



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
