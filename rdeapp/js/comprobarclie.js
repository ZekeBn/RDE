function buscliente(){
                         
      var consulta;
      var r=''; 
	  var noe=0;      
      //hacemos focus
      $("#razon").focus();
      consulta = $("#razon").val();                                           
      
	   //hace la búsqueda
             $("#listota").delay(400).queue(function(n) {      
                                           
                  $("#listota").html('<img src="img/cargando.gif" />');
                                           
                        $.ajax({
                              type: "POST",
                              url: "includes/clientitos.php",
                              data: "b="+consulta,
                              dataType: "html",
                              error: function(){
                                    alert("error petición ajax");
                              },
                              success: function(data){                                                      
                                     r=$("#listota").html(data);
									 
                                   n();
                              }
                  });
                                           
             });
                          
};