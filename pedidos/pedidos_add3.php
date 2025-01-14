<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "619";
$dirsup = "S";
require_once("../includes/rsusuario.php");

if ($_POST && isset($_POST['idcli'])) {
    $idclipedido = $_POST['idcli'];
    grabar_log($idclipedido, 'i');
}

$consulta = "select idsucu,nombre from sucursales";
$rss = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal = $rss->fields['idsucu'];
$nombre_sucursal = $rss->fields['nombre'];

$buscar = "select nomape,codigo_vendedor from vendedor where estado = 'A' order by nomape";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$vendedor_nombre = $rsd->fields['nomape'];
$idvendedor = $rsd->fields['codigo_vendedor'];

$resultado_vendedores = null;
$rsd->MoveFirst();
while (!$rsd->EOF) {
    $id = intval(trim(antixss($rsd->fields['codigo_vendedor'])));
    $nombre = trim(antixss($rsd->fields['nomape']));
    $resultado_vendedores .= "
	<a class='a_link_vendedores'  href='javascript:void(0);' onclick=\"cambia_vendedor($id, '$nombre');\">[$id]-$nombre</a>
	";

    $rsd->MoveNext();
}

$buscar = "
select cliente.idcliente, cliente.razon_social, cliente.dias_credito, tipo_credito.descripcion, cliente.linea_sobregiro
from cliente
left join tipo_credito on tipo_credito.dias_credito = cliente.dias_credito
where cliente.estado = '1' order by cliente.razon_social
";
$rsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$cliente_nombre = $rsc->fields['razon_social'];
$idcliente = $rsc->fields['idcliente'];
$$dias_credito = $rsc->fields['dias_credito'];
$$dcdescripcion = $rsc->fields['descripcion'];
$$linea_sobregiro = $rsc->fields['linea_sobregiro'];

$resultado_clientes = null;
$rsc->MoveFirst();
while (!$rsc->EOF) {
    $idc = intval(trim(antixss($rsc->fields['idcliente'])));
    $nombrec = trim(antixss($rsc->fields['razon_social']));
    $resultado_clientes .= "
	<a class='a_link_clientes'  href='javascript:void(0);' onclick=\"cambia_cliente($idc, '$nombrec');\">[$idc]-$nombrec</a>
	";

    $rsc->MoveNext();
}
$buscar = "select idprod,descripcion from productos where hab_venta = 'S' order by descripcion";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$producto_nombre = $rsp->fields['descripcion'];
$idproducto = $rsp->fields['idprod'];

$resultado_productoss = null;
$rsp->MoveFirst();
while (!$rsp->EOF) {
    $idp = intval(trim(antixss($rsp->fields['idprod'])));
    $nombrep = trim(str_replace(["'", "."], "", antixss($rsp->fields['descripcion'])));
    $resultado_productos .= "
	<a class='a_link_productos'  href='javascript:void(0);' onclick=\"cambia_producto($idp, '$nombrep');\">[$idp]-$nombrep</a>
	";

    $rsp->MoveNext();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once("../includes/head_gen.php"); ?>
    <script>
        document.querySelectorAll('.a_link_clientes').forEach(element => {
            element.addEventListener('click', function() {
                console.log("Enlace clicado");
            });
        });

        function cerrarAlerta() {
            var mensajeAlerta = document.getElementById('mensajeAlerta');
            mensajeAlerta.style.display = "none";
        }
        // VENDEDOR
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
                var div = $("#lista_vendedores");
                // Verificar si el clic ocurrió fuera del elemento #my_input
                if (!target.is(myInput) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
                    // Remover la clase "show" del elemento #my_input
                    myInput.removeClass('show');
                    myDropdown.removeClass('show');
                }

            });
        }

        function filterFunction2(event) {
            event.preventDefault();
            var input, filter, ul, li, a, i;
            input = document.getElementById("myInput2");
            filter = input.value.toUpperCase();
            div = document.getElementById("myDropdown2");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
        }

        function cambia_vendedor(idvendedor, nombre) {
            // alerta_modal("contenido",idtipo_origen+ " "+idmoneda);
            $('#idvendedor').html($('<option>', {
                value: idvendedor,
                text: nombre,
            }));

            var myInput = $('#myInput2');
            var myDropdown = $('#myDropdown2');
            myInput.removeClass('show');
            myDropdown.removeClass('show');
        }
        // CLIENTES
        function myFunction3(event) {
            event.preventDefault();
            var div, ul, li, a, i;
            div = document.getElementById("myDropdown3");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                a[i].style.display = "block";
            }
            document.getElementById("myInput3").classList.toggle("show");
            document.getElementById("myDropdown3").classList.toggle("show");
            div = document.getElementById("myDropdown3");
            $("#myInput3").focus();

            $(document).mousedown(function(event) {
                var estado = $("#idcliente").val();
                alert(estado);

                var target = $(event.target);
                var myInput = $('#myInput3');
                var myDropdown = $('#myDropdown3');
                var div = $("#lista_clientes");
                // Verificar si el clic ocurrió fuera del elemento #my_input
                if (!target.is(myInput) && !target.closest("#myDropdown3").length && myInput.hasClass('show')) {
                    // Remover la clase "show" del elemento #my_input
                    myInput.removeClass('show');
                    myDropdown.removeClass('show');
                }

            });
        }

        function filterFunction3(event) {
            event.preventDefault();
            var input, filter, ul, li, a, i;
            input = document.getElementById("myInput3");
            filter = input.value.toUpperCase();
            div = document.getElementById("myDropdown3");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
        }

        function cambia_cliente(idcliente, nombre) {
            const boton = document.getElementById('agregarprod');
            boton.setAttribute('onclick', 'agregarProducto('+idcliente+')');
            nombre = '['+idcliente+']-'+nombre;
            $('#idcliente').html($('<option>', {
                value: idcliente,
                text: nombre,
            }));
            var parametros = {
                    "idcliente" : idcliente
                };
            $.ajax({
                method: 'POST',
                url: "pedidos_datos_clientes.php",
                data : parametros,
                success: function (response) {
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);
                         $("#doccliente").html($('<label>', {text:obj[0].ruc,}));
                         $("#direccioncliente").html($('<label>', {text:obj[0].direccion,}));
                         $("#listaprecio").html($('<label>', {text:obj[0].lista_precio,}));
                         $("#plazocliente").html($('<label>', {text:obj[0].descripcion,}));
                         $("#montolimite").html($('<label>', {text:obj[0].linea_sobregiro,}));
                         $("#cargar").removeAttr("disabled");



                    }else{
                        alert(response);
                    }
                },error: function(jqXHR, textStatus, errorThrown) {
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
            var myInput = $('#myInput3');
            var myDropdown = $('#myDropdown3');
            myInput.removeClass('show');
            myDropdown.removeClass('show');


        }

        // PRODUCTOS
        function myFunction4(event) {
            event.preventDefault();
            var div, ul, li, a, i;
            div = document.getElementById("myDropdown4");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                a[i].style.display = "block";
            }
            document.getElementById("myInput4").classList.toggle("show");
            document.getElementById("myDropdown4").classList.toggle("show");
            div = document.getElementById("myDropdown4");
            $("#myInput4").focus();

            $(document).mousedown(function(event) {
                // var estado = $("#idproductos").val();
                // alert(estado);

                var target = $(event.target);
                var myInput = $('#myInput4');
                var myDropdown = $('#myDropdown4');
                var div = $("#lista_productos");
                // Verificar si el clic ocurrió fuera del elemento #my_input
                // if (!target.is(myInput) && !target.closest("#myDropdown4").length && myInput.hasClass('show')) {
                //     // Remover la clase "show" del elemento #my_input
                    myInput.removeClass('show');
                    myDropdown.removeClass('show');
                // }

            });
        }

        function filterFunction4(event) {
            event.preventDefault();
            var input, filter, ul, li, a, i;
            input = document.getElementById("myInput4");
            filter = input.value.toUpperCase();
            div = document.getElementById("myDropdown4");
            a = div.getElementsByTagName("a");
            for (i = 0; i < a.length; i++) {
                txtValue = a[i].textContent || a[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
        }

        function cambia_producto(idproducto, nombre) {
            // console.log('idproducto='+idproducto+' nombre='+nombre);
            $('#idproducto').html($('<option>', {
                value: idproducto,
                text: idproducto,
            }));
            $("#nombreProducto").val(nombre);
            const $cantidad = document.querySelector("#cantidadProducto");
            // obtenerDatosProductos(idproducto);
            $cantidad.focus();


            var myInput = $('#myInput4');
            var myDropdown = $('#myDropdown4');
            myInput.removeClass('show');
            myDropdown.removeClass('show');
        }
        function obtenerDatosProductos(id) {
            fetch('pedidos_add3.php?id=${id}') // Asegúrate de poner la ruta correcta al archivo PHP
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        console.log(data); // Aquí manejas los datos que has recibido
                        // Puedes recorrer los productos y hacer algo con ellos
                        data.forEach(producto => {
                        // console.log(`ID: ${producto.idprod}, Precio: ${producto.precio}`);
                        $('#listaprecio').val(producto.precio);
                    });
                    }else {
                        console.log('Producto no encontrado.');
                    }
                })
                .catch(error => console.error('Error al obtener los datos:', error));
        }

    function agregarProducto(idclienete) {
        console.log('ESTOY');
        var errores='';

        var form = document.getElementById('form2');

        var _idproducto = document.getElementById("idproducto").value;
        var _nombreProducto = document.getElementById("nombreProducto").value;
        var cajaProducto = document.getElementById("cajaProducto").value;
        // var _precioProducto = document.getElementById("precioProducto").value;
        var _unidadProducto = document.getElementById("unidadProducto").value;
        var _totalconIVA = _precioProducto * _cantidadProducto;
        // console.log('_idproducto='+_idproducto);
        if (_idproducto == '') {
            errores=errores+'- El campo de Código de Producto está vacío. \n<br>';
        }
        if (_nombreProducto == '') {
            errores=errores+'- El campo de Nombre de Producto está vacío. \n<br>';
        }
        if (cajaProducto == '' || _unidadProducto == '') {
            errores=errores+'- Debe cargar cantidad de cajas o unidad. \n<br>';
        }
        // if (_precioProducto == '') {
        //     errores=errores+'- El campo de Precio de Producto está vacío. \n<br>';
        // }
        // if (_unidadProducto) {
        //     errores=errores+'- El campo de Unidades de Producto está vacio. \n<br>'
        // }
        if (errores=='') {
            // console.log('idcliente='+idcliente+' _idproducto'+_idproducto);
            var parametros = {
                "idcliente" : idcliente,
                "idproducto" : _idproducto
            };
            $.ajax({
                method: 'POST',
                url: 'pedidos_datos_productos.php',
                data: parametros,
                success: function(response) {
                    var obj = jQuery.parseJSON(response);
            var fila = `
                <tr>
                    <td>
                    <a href="pedidos_edit.php?id=${_idproducto}"
                        class="btn btn-sm btn-default"
                        title="Editar"
                        data-toggle="tooltip"
                        data-placement="right"
                        data-original-title="Editar">
                        <span class="fa fa-edit"></span>
                    </a>
                    <a href="pedidos_del.php?id=${_idproducto}"
                        class="btn btn-sm btn-default"
                        title="Borrar"
                        data-toggle="tooltip"
                        data-placement="right"
                        data-original-title="Borrar">
                        <span class="fa fa-trash-o"></span>
                    </a>
                    </td>
                    <td>${_idproducto}</td>
                    <td>${_nombreProducto}</td>
                    <td>${_cajaProducto}</td>
                    <td>${_unidadProducto}</td>
                    <td>${obj[0].idmedida2}</td>
                    <td>${_cantidadProducto}</td>
                    <td>${_precioProducto}</td>
                    <td>${_precioProducto}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>${_totalconIVA}</td>
                </tr>
                `;
            var btn = document.createElement("TR");
            btn.innerHTML=fila;
            document.getElementById("grillaProductos").appendChild(btn);
            form.reset();
            },error: function(jqXHR, textStatus, errorThrown) {
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
            $('#modalIngresoProducto').on('hidden.bs.modal', function () {
                var form = document.getElementById('modalIngresoProducto');
                form.reset();
             });

        } else {
            mensajeAlerta.className = "alert alert-danger";
            mensajeAlerta.innerHTML = "<strong>Error: "+errores+"</strong> <button type='button' class='close' onclick='cerrarAlerta()'>&times;</button>";
            mensajeAlerta.style.display = "block";
            alerta_modal('Errores:\n',errores);
        }
    }
    document.addEventListener('keydown', function(event) {
        // Detecta Ctrl + Shift + A
        if (event.ctrlKey && event.shiftKey && event.key === 'A') {
            event.preventDefault(); // Evita cualquier acción predeterminada
            document.getElementById('cargar').click(); // Simula un clic en el elemento
        }
    });
    </script>
    <style>
        .borde {
            border: #c2c2c2 solid 1px;
        }
        .align-labels {
            display: flex;
            align-items: center;
        }
        .igualar-tamaño {
            flex: 1; /* Esto hace que el label ocupe todo el espacio disponible dentro del contenedor flex */
        }
        /* VENDEDORES */
        #lista_vendedores {
            width: 100%;
        }

        .a_link_vendedores {
            display: block;
            padding: 0.8rem;
        }

        .a_link_vendedores:hover {
            color: white;
            background: #73879C;
        }

        .dropdown_vendedores {
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }

        .dropdown_vendedores_input {
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display: none;
            width: 100% !important;
            padding: 5px !important;
        }

        /* CLIENTES */
        #lista_clientes {
            width: 100%;
        }

        .a_link_clientes {
            display: block;
            padding: 0.8rem;
        }

        .a_link_clientes:hover {
            color: white;
            background: #73879C;
        }

        .dropdown_clientes {
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }

        .dropdown_clientes_input {
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display: none;
            width: 100% !important;
            padding: 5px !important;
        }

        /* PRODUCTOS */
        #lista_productos {
            width: 100%;
        }

        .a_link_productos {
            display: block;
            padding: 0.8rem;
        }

        .a_link_productos:hover {
            color: white;
            background: #73879C;
        }

        .dropdown_productos {
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }

        .dropdown_productos_input {
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display: none;
            width: 100% !important;
            padding: 5px !important;
        }

        .btn_proveedor_select {
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php require_once("../includes/menu_gen.php");
?>

            <!-- top navigation -->
            <?php require_once("../includes/menu_top_gen.php"); ?>
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
                                        <h2>Carga de Pedidos</h2>
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
                                        <div class="col-md-12 col-sm-12  ">
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="" style="display:flex;">
                                                        <div class="dropdown" id="lista_vendedores">
                                                        <select onclick="myFunction2(event)" name="idvendedor" id="idvendedor" class="form-control">
                                                                <option value="" disabled selected></option>
                                                                <?php if ($vendedor_nombre) { ?>
                                                                    <option value="<?php echo $idvendedor; ?>"><?php echo $idvendedor . '-' . $vendedor_nombre ?></option>
                                                                <?php } ?>
                                                            </select>
                                                            <input type="text" class="dropdown_vendedores_input col-md-9 col-sm-9 col-xs-12" placeholder="Código/Nombre Vendedor" id="myInput2" onkeyup="filterFunction2(event)">
                                                            <div class="dropdown-content hide dropdown_vendedores links wrapper col-md-9 col-sm-9 col-xs-12" id="myDropdown2" style="max-height: 200px;overflow: auto;">
                                                                <?php echo $resultado_vendedores ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-12 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="" style="display:flex;">
                                                        <div class="dropdown" id="lista_clientes">
                                                            <select onclick="myFunction3(event)" name="idcliente" id="idcliente" class="form-control">
                                                                <option value="" disabled selected></option>
                                                                <?php if ($cliente_nombre) { ?>
                                                                    <option value="<?php echo $idcliente; ?>"><?php echo $idcliente . '-' . $cliente_nombre ?></option>
                                                                <?php } ?>
                                                            </select>
                                                            <input type="text" class="dropdown_clientes_input col-md-9 col-sm-9 col-xs-12" placeholder="Código/Nombre Cliente" id="myInput3" onkeyup="filterFunction3(event)">
                                                            <div class="dropdown-content hide dropdown_clientes links wrapper col-md-9 col-sm-9 col-xs-12" id="myDropdown3" style="max-height: 200px;overflow: auto;">
                                                                <?php echo $resultado_clientes ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-12 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="align-labels" style="display:flex;">
                                                        <label class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"><?php echo $idsucursal . " - " . $nombre_sucursal; ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">RUC/CI:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="" style="display:flex;">
                                                        <label id="doccliente" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Dirección</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="align-labels" style="display:flex;">
                                                        <label id="direccioncliente" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Lista de Precio:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="align-labels" style="display:flex;">
                                                        <label id="listaprecio" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Plazo Pedido:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="align-labels" style="display:flex;">
                                                        <label id="plazopedido" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño">0</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Plazo Cliente:</label>
                                                <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <div class="align-labels" style="display:flex;">
                                                        <label id="plazocliente" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"></label>
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
                    <h2 style="text-decoration: underline;">Carga de Articulos</h2>
                    <a href="#" class="btn btn-sm btn-default" data-toggle="modal" data-target="#modalIngresoProducto" id="cargar" disabled>
                        <span class="fa fa-plus"></span> Agregar Producto
                    </a>
                    <!-- Modal de ingreso de productos -->
                    <div class="modal fade" id="modalIngresoProducto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Ingresar Producto</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div id="mensajeAlerta" class="alert" role="alert" style="display: none;"></div>
                                <div class="modal-body" id="modal_cuerpo">
                                    <!-- Formulario para ingresar producto -->
                                    <form action="" id="form2" name="form2" method="post">
                                        <div class="form-group">
                                            <label for="codigoProducto">Código:</label>
                                            <!-- <input type="text" class="form-control" id="codigoProducto" placeholder="Código del producto"> -->
                                            <select onclick="myFunction4(event)" name="idproducto" id="idproducto" class="form-control">
                                                <option value="" disabled selected></option>
                                                <?php if ($descripcion) { ?>
                                                    <option value="<?php echo $idproducto; ?>"><?php echo $idproducto . '-' . $descripcion ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="text" class="dropdown_productos_input col-md-9 col-sm-9 col-xs-12" placeholder="Código de Producto" id="myInput4" onkeyup="filterFunction4(event)">
                                            <div class="dropdown-content hide dropdown_productos links wrapper col-md-9 col-sm-9 col-xs-12" id="myDropdown4" style="max-height: 200px; overflow: auto;">
                                                <?php echo $resultado_productos ?>
                                            </div>
                                            <div class="form-group">
                                                <label for="nombreProducto">Nombre:</label>
                                                <input type="text" class="form-control" id="nombreProducto" placeholder="Nombre del producto">
                                            </div>
                                            <div class="form-group">
                                                <label for="cantidadProducto">Caja:</label>
                                                <input type="number" class="form-control" id="cajaProducto" placeholder="Caja">
                                            </div>
                                            <div class="form-group">
                                                <label for="cantidadProducto">Unidad:</label>
                                                <input type="number" class="form-control" id="unidadProducto" placeholder="Cantidad">
                                            </div>
                                            <!-- <div class="form-group">
                                                <label for="precioProducto">Precio:</label>
                                                <input type="number" class="form-control" id="precioProducto" placeholder="Precio">
                                            </div>
                                            <div class="form-group">
                                                <label for="unidadProducto">Unidad:</label>
                                                <input type="text" class="form-control" id="unidadProducto" placeholder="Unidad">
                                            </div> -->
                                        </div>
                                    </form>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-success" id="agregarprod" onclick="agregarProducto()">Agregar Producto</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table width="100%" class="table table-bordered jambo_table bulk_action">
                        <thead>
                            <tr>
                                <th></th>
                                <th align="center">Código</th>
                                <th align="center">Descripción</th>
                                <th align="center">Caja</th>
                                <th align="center">Unidad</th>
                                <th align="center">UxC</th>
                                <th align="center">Cant.Uni</th>
                                <th align="center">Precio Lista</th>
                                <th align="center">Precio Unitario</th>
                                <th align="center">% Desc.</th>
                                <th align="center">% Iva</th>
                                <th align="center">IVA</th>
                                <th align="center">Total c/IVA</th>
                                <th align="center">Cant.Max.Ped.</th>
                                <th align="center">Bloq.</th>
                            </tr>
                        </thead>

                        <tbody id="grillaProductos">
                            <tr>
                                <td>
                                    <!-- <a href="pedidos_edit.php?id=<?php // echo $rs->fields['idempresa'];?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
                                    <a href="pedidos_del.php?id=<?php // echo $rs->fields['idempresa'];?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a> -->
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th scope="row"> Totales</th>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Limite:</label>
                        <label id="montolimite" class="control-label col-md-9 col-sm-9 col-xs-12 borde igualar-tamaño"></label>
                    </div>
                    <!-- /page content -->

                    <!-- footer content -->
                    <?php require_once("../includes/pie_gen.php"); ?>
                    <!-- /footer content -->
                </div>
            </div>
            <?php require_once("../includes/footer_gen.php"); ?>
</body>

</html>