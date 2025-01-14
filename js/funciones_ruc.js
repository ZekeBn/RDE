// JavaScript Document
var LNK_CONSULTA_RUC      = "http://www.ruc.com.py/index.php/inicio/consulta_ruc";
//var LNK_CONSULTA_RUC      = "test_rucrecibe.php";

$(document).ready(function(){

   $('#btn_buscar').click(function(){
      $(".help-block").removeClass('has-error');
      $(".help-block label").addClass('hide');
      var buscar        = $("#txt_buscar").val();
      var array_buscar  = buscar.split(' ');
      var permitido     = true;
      var min_carac     = 3;
      for(var i=0; i< array_buscar.length; i++)
      {
         if(array_buscar[i].length < min_carac)
         {
            permitido = false;
         }
         else
         {
            permitido = true;
            break;
         }
      }
      if(buscar == "")
      {
         $("#txt_buscar").focus();
         $(".help-block").addClass('has-error');
         $(".help-block label").stop().removeClass('hide').fadeIn().text('Favor digitar un valor antes de buscar').fadeOut(5500,function(){
            $(".help-block").removeClass('has-error');
         });
      }
      else if(buscar.length < min_carac || !permitido)
      {
         $("#txt_buscar").focus();
         $(".help-block").addClass('has-error');
         $(".help-block label").stop().removeClass('hide').fadeIn().text('Favor digitar un valor con mas caracteres').fadeOut(5500,function(){
            $(".help-block").removeClass('has-error');
         });

      }
      else
      {
         $('#resultados').removeClass('hide');
         $('#base_ruc tbody').html('<tr><td colspan="2" width="100%" style="text-align: center;">Buscando...</td></tr>');
		 //alert(LNK_CONSULTA_RUC);
         $.post(LNK_CONSULTA_RUC,'buscar=' + buscar ,function(resultado){
            $('#base_ruc tbody').fadeOut(500,function(){
                  $(this).html(resultado);
            }).fadeIn(500);
            var posicion = $( '#linea_resultados' ).offset().top-10;
            $('html, body').stop().animate({
            scrollTop: posicion
            }, 1500);
         });
      }
   });
   $("#txt_buscar").focus().bind('keyup', function(e)
   {
      if (e.which == 13)
      {
         $('#btn_buscar').trigger('click');
      }
   });

});
