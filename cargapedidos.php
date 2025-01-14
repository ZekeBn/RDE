<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "619";
$dirsup = "S";
require_once("includes/rsusuario.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once("includes/head_gen.php"); ?>
    <script>
        function myFunction2(event) {
            event.preventDefault();
            var div, ul, li, a, i;
            div = document.getElementById("myDropdown2");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                a[i].style.display = "block";
            }
            document.getElementById("myInput2").classList.toggle("show");
            document.getElementById("myDropdown2").classList.toggle("show");
            div = document.getElementById("myDropdown2");
            $("#myInput2").focus();



            $(document).mousedown(function(event) {
                var target = $(event.target);
                var myInput = $('#myInput2');
                var myDropdown = $('#myDropdown2');
                var div = $("#lista_proveedores");
                var button = $("#iddepartameto");
                // Verificar si el clic ocurrió fuera del elemento #my_input
                if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
                    // Remover la clase "show" del elemento #my_input
                    myInput.removeClass('show');
                    myDropdown.removeClass('show');
                }

            });
        }

        function filterFunction2(event) {
            event.preventDefault();
            var idtipo_servicio = $("#idtipo_servicio").val();
            var input, filter, ul, li, a, i;
            input = document.getElementById("myInput2");
            filter = input.value.toUpperCase();
            div = document.getElementById("myDropdown2");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                rucValue = a[i].getAttribute('data-hidden-value');
                idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
                if (parseInt(idtipo_servicio) > 0) {
                    if (idtipo_servicio_hidden == idtipo_servicio && (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 || filter == "")) {
                        a[i].style.display = "block";
                    } else {
                        a[i].style.display = "none";
                    }
                } else {
                    if (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1) {
                        a[i].style.display = "block";
                    } else {
                        a[i].style.display = "none";
                    }
                }



            }
        }
    </script>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php // require_once("includes/menu_gen.php");
            ?>

            <!-- top navigation -->
            <?php require_once("includes/menu_top_gen.php"); ?>
            <!-- /top navigation -->

            <!-- page content -->
            <div class="right_col" role="main">
                <div class="">
                    <div class="page-title">
                    </div>

                    <div class="clearfix">
                        <!-- SECCION -->
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Cargar Pedidos</h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <?php if (trim($errores) != "") { ?>
                                            <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">x</span></button>
                                                <strong>Errores:</strong><br /><?php echo $errores; ?>
                                            </div>
                                        <?php } ?>

                                        <form id="form1" name="form1" method="post" action="">
                                            <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="" style="display:flex;">
                                                        <div class="dropdown" id="lista_vendedor">
                                                            <select onclick="myFunction2(event)" name="idvendedor" id="idvendedor" class="form-control">
                                                                <option value="" disabled selected></option>
                                                                <?php if ($vendedor_nombre) { ?>
                                                                    <option value="<?php echo $idvendedor; ?>" selected><?php echo $vendedor_nombre ?></option>
                                                                <?php } ?>
                                                            </select>
                                                            <input type="text" class="dropdown_vendedor_input col-md-9 col-sm-9 col-xs-12" placeholder="Nombre/Código Vendedor" id="myInput2" onkeyup="filterFunction2(event)">
                                                            <div class="dropdown-content hide dorpdown_vendedor links wrapper col-md-9 col-sm-9 col-xs-12" id="myDropdown2" style="max-height: 200px;overflow: auto;">
                                                                <?php echo $resultado_vendedores ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /page content -->

                    <!-- footer content -->
                    <?php require_once("includes/pie_gen.php"); ?>
                    <!-- /footer content -->
                </div>
            </div>
        </div>
        <?php require_once("includes/footer_gen.php"); ?>
</body>

</html>