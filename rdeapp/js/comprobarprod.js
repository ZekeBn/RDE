function cambio(){
      var nivel=document.getElementById('idlog').value;                 
      var consulta;
      var r=''; 
	  var noe=0;      
      //hacemos focus
      $("#codigo").focus();
      consulta = $("#codigo").val();                                           
      if (consulta !=''){
	   //hace la búsqueda
             $("#mensa").delay(200).queue(function(n) {      
                                           
                //  $("#mensa").html('<img src="img/cargando.gif" />');
                                           
                        $.ajax({
                              type: "POST",
                              url: "includes/buscaprodv.php",
                              data: "b="+consulta+"&nivel="+nivel,
                              dataType: "html",
                              error: function(){
                                    alert("error petición ajax");
                              },
                              success: function(data){                                                      
                                     r=$("#mensa").html(data);
									 if (document.getElementById('dpoc')){
										 var re=parseInt(document.getElementById('dpoc').value);
										// alert(re);
										 document.getElementById('dispo').value=re;
										
									 } else {
										document.getElementById('dispo').value='0';
										//alert('No existe producto buscado.');
									 }
									 
                                   n();
                              }
                  });
                                           
             });
			 
	  } else {
		  $("#mensa").html('');
		  document.getElementById('dispo').value='0';
	  }
	
};