<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");


/////////////////////////////////////////////////////////////////
/////////////////parametros para arrays

$buscar = "SELECT idvendedor, concat(nombres,' ',COALESCE(apellidos,'')) as nomape
FROM vendedor 
where 
estado = 'A' or estado = 1
order by nombres asc
";

$resultados_vendedores = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idvendedor = trim(antixss($rsd->fields['idvendedor']));
    $nombre = antisqlinyeccion(trim(antixss($rsd->fields['nomape'])), 'text');
    $resultados_vendedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idvendedor' onclick=\"cambia_vendedor( $idvendedor, $nombre);\">[$idvendedor]-$nombre</a>
	";

    $rsd->MoveNext();
}


$buscar = "SELECT c.idcliente, c.idvendedor,  c.razon_social  FROM cliente as c WHERE estado = 1
";
$resultados_cliente = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idcliente = trim(antixss($rsd->fields['idcliente']));
    $idvendedor = trim(antixss($rsd->fields['idvendedor']));
    $razon_social = antisqlinyeccion(trim(antixss($rsd->fields['razon_social'])), 'text');
    $resultados_cliente .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-vendedor='$idvendedor'  onclick=\"cambia_cliente($idcliente,$razon_social);\">[$idcliente]-$razon_social</a>
	";
    $rsd->MoveNext();
}
?>
 <script>
  
 
  function myFunction(event) {
          event.preventDefault();
          var vendedor = $("#idvendedor").val();

          if (vendedor) {
                  var div,ul, li, a, i;
                  div = document.getElementById("myDropdown");
                  a = div.getElementsByTagName("a");
                  for (i = 0; i < a.length; i++) {
                      txtValue = a[i].textContent || a[i].innerText;
                      id_vendedor = a[i].getAttribute('data-hidden-vendedor');
                      if ( id_vendedor==vendedor ) {
                          a[i].style.display = "block";
                      } else {
                          a[i].style.display = "none";
                      }
                  }

              }


          document.getElementById("myInput").classList.toggle("show");
          document.getElementById("myDropdown").classList.toggle("show");
          div = document.getElementById("myDropdown");
          $("#myInput").focus();


          
        $(document).mousedown(function(event) {
          var target = $(event.target);
          var myInput = $('#myInput');
          var myDropdown = $('#myDropdown');
          var div = $("#lista_clientes");
          var button = $("#idcliente");
          // Verificar si el clic ocurrió fuera del elemento #my_input
          if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
          // Remover la clase "show" del elemento #my_input
          myInput.removeClass('show');
          myDropdown.removeClass('show');
          }
          
        });
  }

  function myFunction2(event) {
        event.preventDefault();
        document.getElementById("myInput2").classList.toggle("show");
        document.getElementById("myDropdown2").classList.toggle("show");
        div = document.getElementById("myDropdown2");
        $("#myInput2").focus();

          
      $(document).mousedown(function(event) {
        var target = $(event.target);
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        var div = $("#lista_vendedores");
        var button = $("#iddepartameto");
        // Verificar si el clic ocurrió fuera del elemento #my_input
        if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
        // Remover la clase "show" del elemento #my_input
        myInput.removeClass('show');
        myDropdown.removeClass('show');
        }
      
      });
  }

  function filterFunction(event) {
      event.preventDefault();
      var vendedor = $("#idvendedor").val();
      var input, filter, ul, li, a, i;
      input = document.getElementById("myInput");
      filter = input.value.toUpperCase();
      div = document.getElementById("myDropdown");
      a = div.getElementsByTagName("a");
      for (i = 0; i < a.length; i++) {
          txtValue = a[i].textContent || a[i].innerText;
          id_vendedor = a[i].getAttribute('data-hidden-vendedor');
          if (vendedor != null && vendedor != undefined ) {
            if (( vendedor == id_vendedor && txtValue.toUpperCase().indexOf(filter) > -1 )){
              
                a[i].style.display = "block";
            }else{
                a[i].style.display = "none";
            }
          } else {
            if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                a[i].style.display = "block";
            } else {
                a[i].style.display = "none";
            }
          }
      }
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
              if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                  a[i].style.display = "block";
              } else {
                  a[i].style.display = "none";
              }
          }
  }
    
  

  function cambia_vendedor(idvendedor,nombre){
            $('#idvendedor').html($('<option>', {
                    value: idvendedor,
                    text: nombre
              }));
               
              // Seleccionar opción
              $('#idvendedor').val(idvendedor);
              var myInput = $('#myInput2');
              var myDropdown = $('#myDropdown2');
              myInput.removeClass('show');
              myDropdown.removeClass('show');	
            
  }
  function cambia_cliente(idcliente,nombre){
          $('#idcliente').html($('<option>', {
              value: idcliente,
              text: nombre
          }));
        
          
          $("#idcliente").val(idcliente)
          var myInput = $('#myInput');
          var myDropdown = $('#myDropdown');
          myInput.removeClass('show');
          myDropdown.removeClass('show');	
          
  }

  window.onload = function() {
          $('#idcliente').on('mousedown', function(event) {
              // Evitar que el select se abra
              event.preventDefault();
          });
          $('#idvendedor').on('mousedown', function(event) {
              // Evitar que el select se abra
              event.preventDefault();
          });
  };

  function mostrar_detalle(idventa){
    var parametros = {
              "idventa"		  : idventa
          };
    // console.log(parametros);
    $.ajax({
                  data:  parametros,
                  url:   'ventas_det.php',
                  type:  'post',
                  beforeSend: function () {
                      $("#conteo_productos").html('Cargando...');  
                  },
                  success:  function (response) {
                    //   console.log(response);
                      alerta_modal("Detalle del conteo",response)
                  }
          });
  }

</script>
<style type="text/css">
      #lista_clientes,#lista_vendedores {
          width: 100%;
      }
      .selected_pag{
          background: #c2c2c2;
      }
     
      .a_link_proveedores{
          display: block;
          padding: 0.8rem;
      }	
      .a_link_proveedores:hover{
          color:white;
          background: #73879C;
      }
      .dropdown_proveedores{
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
      .dropdown_proveedores_input{ 
          position: absolute;
          top: 37px;
          left: 0;
          z-index: 99999;
          display:none;
          width: 100% !important;
          padding: 5px !important;
      }
      .btn_proveedor_select{
          border: #c2c2c2 solid 1px;
          color: #73879C;
          width: 100%;
      }
  
  </style>
<div id="form2" style="width:100%;">

<div class="col-md-6 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_vendedores">
                <select onclick="myFunction2(event)"  class="form-control" id="idvendedor" name="idvendedor">
                <option value="" disabled selected></option>
            </select>
                <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Ciudad" id="myInput2" onkeyup="filterFunction2(event)" >
                <div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados_vendedores ?>
                </div>
            </div>
                <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                    <span  class="fa fa-plus"></span> Agregar
                </a> -->
        </div>
    </div>
</div>





<div class="col-md-6 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_clientes">
                <select onclick="myFunction(event)"  class="form-control" id="idcliente" name="idcliente">
                <option value="" disabled selected></option>
            </select>
                <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Ciudad" id="myInput" onkeyup="filterFunction(event)" >
                <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados_cliente ?>
                </div>
            </div>
                <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                    <span  class="fa fa-plus"></span> Agregar
                </a> -->
        </div>
    </div>
</div>

<div class="col-md-6 col-sm-12 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura nro</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
      <input type="text" name="num_factura" id="num_factura" value="<?php  if (isset($_POST['num_factura'])) {
          echo htmlentities($_POST['num_factura']);
      } else {
          echo htmlentities($rs->fields['num_factura']);
      }?>" placeholder="Numero de Factura" class="form-control" />                    
    </div>
</div>




<div class="clearfix"></div>
<br />
<div class="form-group">
    <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
      <button type="submit" onclick="submit_filtros(event)" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
    </div>
</div>

<br />
</div>

<div class="clearfix"></div>
<br />