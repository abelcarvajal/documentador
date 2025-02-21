<?php
  include("include.php");
  //include("functions.inc");
  //setlocale(LC_ALL, "es_ES");
  session_start();
  $query="SELECT usuario FROM sesiones_usuarios_reportes WHERE hash='$ID' and desconexion is NULL ";
  $res = $cnn->Execute($query);//pg_query($cnn,$query);
  $num_rows = $res->RecordCount();//pg_num_rows($result);
  //echo $query;

  if(!$num_rows)
  {
    header("Location: index.php"); 
  }
  //echo date("Y-m-d");
  $expire=time()-60;
  //echo $expire;
  setcookie("nickname","",$expire);
  setcookie("nivel","",$expire);
  setcookie("ID","",$expire);
  
  $query="SELECT permisos FROM menu_reportes WHERE permisos ilike '%,".$nivel.",%' and enlace='reporte_cajeras_recaudo.php' ";
  $res = $cnn->Execute($query);
  
  $num_rows2 = $res->RecordCount();
  //echo $num_rows2."-algo";
  
  if($num_rows2==0)
  {
	header("Location: logout.php");  
  }
  //if($comun==0)
  //{
  //  $comun=1;
  //}
  
  function script1()
  {  //echo '<META HTTP-EQUIV="Refresh" CONTENT="3600">';
    echo '<script type="text/javascript" src="lista_clientes2.php?con=4"></script>';
	echo '<script>
	function sesion()
	  {
	    var contadorsesion = $.cookie("contadorsesion");//parseFloat($("#contadorsesion").val());
		
		var d = new Date();
		var tiempo=d.getTime();
		var tiempo2=parseFloat(tiempo-contadorsesion);
		//alert(ID+"--"+nickname+"--"+contadorsesion+"--"+tiempo+"--"+tiempo2);
		
		if(tiempo2<=52000)
		{
		  $.cookie("contadorsesion", tiempo);
		}
		else
		{
	      document.location="logout.php";
		}
      }
	/*function borrar() 
	{
		//alert("mueve");
 	  $("#tabla").empty();
	  //$("#tabla").hide();	
	}*/
	function validar(e,tipo) 
	{   
      tecla = (document.all) ? e.keyCode : e.which;
	  //alert(tecla);
	  $("#tabla").empty();
	  //$("#tabla").hide();
	  
	  if (tecla==0)
	  {
	    //probar();
		return true; //Tecla de tabulacion (para poder tabulador)
	  }
	  if (tecla==8) return true; //Tecla de retroceso (para poder borrar)
      //if (tecla==44) return true; //Coma ( En este caso para diferenciar los decimales )
	  if(tipo==1)
	  {
	    if (tecla==45) return true; //- ( En este caso para el guion del nit)
	  }
	  else if(tipo==2)
	  {
		if (tecla==46) return true; //punto ( En este caso para diferenciar los decimales )  
	  }
	  if (tecla==48) return true;
      if (tecla==49) return true;
      if (tecla==50) return true;
      if (tecla==51) return true;
      if (tecla==52) return true;
      if (tecla==53) return true;
      if (tecla==54) return true;
      if (tecla==55) return true;
      if (tecla==56) return true;
	  if (tecla==57) return true;
      patron = /1/; //ver nota
      te = String.fromCharCode(tecla);
      return patron.test(te);
    } 
	
	
    $(function() 
	{
	  //$("#fechaini").datepick({dateFormat: "yyyy-mm-dd"});	
	  $("#fechaini").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){controlfecha1()}});
	  $("#fechafin").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){controlfecha2()}});
	  
	  //$("#onClosePicker").datepick({ 
      //onClose: function(dates) { alert("Closed with date(s): " + dates); }, 
      //showTrigger: "#calImg"});
    });
	function propStopped(e) {
    var msg = "";
    if ( e.isPropagationStopped() ) {
		var cedula = $("#cedula").val();
		 var cedulaid = $("#cedulaid").val();
		 var cedulaname = $("#cedulaname").val();
		 alert("algo11 ="+this.value+"--"+$("input[name=cedula]").val()+"=="+cedula+"=="+cedulaid+"=="+cedulaname);
     msg =  "called"
    } else {
	  var cedula = $("#cedula").val();
		 var cedulaid = $("#cedulaid").val();
		 var cedulaname = $("#cedulaname").val();
		 alert("algo12 ="+this.value+"--"+$("input[name=cedula]").val()+"=="+cedula+"=="+cedulaid+"=="+cedulaname);	
      msg = "not called";
    }
     $("#cedula-result").append( "<div>" + msg + "</div>" );
   }

    $(document).ready(function()
    { 
	  var d = new Date();
	  var tiempo=d.getTime();
	  document.getElementById("tiemposesion").value=tiempo;
	  //sesion();
		
	  $(this).mousemove(function(e)
	  { 
	    var tiempo = $("#tiemposesion").val();
	    var estadotiempo = $("#estadotiempo").val();
         
		var d = new Date();
		var tiempo1=d.getTime();
		var tiempo2=parseFloat(tiempo1-tiempo);
		  
		if(tiempo2<=1400000)//350000
		{
			//alert(tiempo2+"<="+350000);  
		  document.getElementById("tiemposesion").value=tiempo1;  
		}
		else if(estadotiempo==0)
		{
		  document.getElementById("estadotiempo").value=1; 
			//alert(tiempo2+"<="+350000);   
		  document.location="logout.php";  
		}
		  //alert(tiempo+"--"+tiempo1+"--"+tiempo2);
      });	
	  
	  $("#loading ").hide();
	  //$("#seleccion ").hide();
	  
	  //$("#seleccion2").hide();
	  //$("#seleccion4").hide();
	  //$("#loading1 ").hide();
	  var usuario = $("#usuario").val();
	  //var producto=$("#producto").val();
	  //var subproducto=$("#subproducto").val();
	  var cedula=$("#cedula").val();
	  //var ccostos=$("#ccostos").val();
	  //var fechaini=$("#fechaini").val();
	   //var fechafin=$("#fechafin").val();
	  
	  //var tipo=$("#tipo").val();
	  
	  /*if(producto==0)
	  {
		$("#subproducto").load("datos.php", {con : 4.2, producto : 0, usuario : usuario}); 
		document.getElementById("valorboleta").value=0;
		document.getElementById("cupo").value=0;
	  }
	  else
	  {
	    $("#subproducto").load("datos.php", {con : 4.2, producto : producto, usuario : usuario});
	  }
	 
	  $("#producto").change(function()
      {
		var producto=$(this).attr("value");  
		$("#subproducto").load("datos.php", {con : 4.2, producto : producto, usuario : usuario});
		document.getElementById("valorboleta").value=0;
		document.getElementById("cupo").value=0;
	  });
	  
	  ///alert(subproducto);
	  if(subproducto==0 || subproducto==null)
	  {//alert("algo");
		//$("#cupo").val("0");//load("datos.php", {con : 4.2, producto : 0, usuario : usuario}); 
		document.getElementById("valorboleta").value=0;
		document.getElementById("cupo").value=0;
		
		//$("#cupo").load("datos.php", {con : 3.5, subproducto : 0, usuario : usuario}); 
	  }
	  else
	  {
	    //$("#cupo").load("datos.php", {con : 3.5, subproducto : subproducto, usuario : usuario});
		$.ajax(
	    {
          url: "datos.php",
          type: "POST",
          data: "submit=&con=4.4&subproducto="+subproducto+"&usuario="+usuario,
          success: function(datos)
          {//ConsultaDatos();
            //alert(datos);
			document.getElementById("valorboleta").value=datos;
		  }
        });
		$.ajax(
	    {
          url: "datos.php",
          type: "POST",
          data: "submit=&con=4.1&subproducto="+subproducto+"&usuario="+usuario,
          success: function(datos)
          {//ConsultaDatos();
            //alert(datos);
			document.getElementById("cupo").value=datos;
		  }
        });
		
	  }
	 
	  $("#subproducto").change(function()
      {
		var subproducto=$(this).attr("value"); 
		
		$.ajax(
	    {
          url: "datos.php",
          type: "POST",
          data: "submit=&con=4.4&subproducto="+subproducto+"&usuario="+usuario,
          success: function(datos)
          {//ConsultaDatos();
            //alert(datos);
			document.getElementById("valorboleta").value=datos;
		  }
        });		
		$.ajax(
	    {
          url: "datos.php",
          type: "POST",
          data: "submit=&con=4.1&subproducto="+subproducto+"&usuario="+usuario,
          success: function(datos)
          {//ConsultaDatos();
            //alert(datos);
			document.getElementById("cupo").value=datos;
		  }
        });
		$("#cupo").load("datos.php", {con : 4.1, subproducto : subproducto, usuario : usuario}, function(response, status, xhr) 
		{
          if (status == "success") 
		  {
            var msg = response+"-"+"Sorry but there was an error: ";
            //$("#error").html(msg + xhr.status + " " + xhr.statusText);
          }		
        });

		//$("#cupo").load("datos.php", {con : 3.5, subproducto : subproducto, usuario : usuario});  
	  });
	  
	  var control=1;
	  var control2=1;
	  var control3=1;
	  
	  /*$("#cedula").ajaxSuccess(function(evt, request, settings)
      {  //change(function() .bind("change",function ()
		var cedula=$(this).attr("value");
		var cedulaid = $("#cedulaid").val();
		var cedulaname = $("#cedulaname").val();
		var cedula2 =this.value;
		alert(cedula+"-"+cedulaid+"=="+cedula2+"=||="+cedulaname+"=||="+control);
		//$("#ccostos").load("datos.php", {con : 3.3, zona : zona, usuario : usuario}); 
		//$("#pventa").load("datos.php", {con : 3.4, zona : zona, ccostos : 0, usuario : usuario});
	    //if(control==1)
		//{
		  //document.getElementById("cedula").value=0;
		  document.getElementById("control2").value=3;
		  //control2=3;
		//}
		control=0;
		
	  });
	  $("#cedula").focusout(function()
	  { 
	    
	    //window.setTimeout( runScript, 50 );
	     //document.getElementById("control2").value=5;
		//alert("2");
		 
		//$("#control2").val("0");
		//control2=0;
	  });
	  $("#control2").ajaxSuccess(function(evt, request, settings)
      {  //change(function() .bind("change",function ()
		var control2 = $("#control2").val();
		alert("control2="+control2);
		var cedula1 = $(this).attr("value");// $("input[name=cedula]");//$("#cedula").val();
		var cedula2=$("input[name=cedula]").val();
		var cedulaid = $("#cedulaid").val();
		var cedulaname = $("#cedulaname").val();
		//var control2 =probar();
		//var control2 = $("#control2").attr("value");
		alert("cedula1=="+cedula1+"--"+cedulaid+"||"+cedulaname+"||"+cedula2+"||"+control2);
		if(control2<1)
		{
		  if(cedula2!=cedulaname)
		  {
		    alert(control2);
			document.getElementById("cedulaid").value=0;
		    document.getElementById("cedulaname").value=cedula2;
		    document.getElementById("nombres").value="";
		    document.getElementById("apellidos").value="";
		    document.getElementById("direccion").value="";
		    document.getElementById("correo").value="";
		    document.getElementById("telefono").value=""; 
		  }
		}   
	  });*/
	  
	  	  
	  /*if(ccostos==0)
	  { 
		$("#pventa").load("datos.php", {con : 3.4, zona : zona, ccostos : 0, usuario : usuario}); 
	  }
	  else
	  {
	    //$("#ccostos").load("datos.php", {con : 3.3, zona : zona, usuario : usuario});
		$("#pventa").load("datos.php", {con : 3.4, zona : zona, ccostos : ccostos, usuario : usuario});
	  }
	 
	  $("#ccostos").change(function()
      {
		var ccostos=$(this).attr("value"); 
		var zona=$("#zona").val();
		//$("#ccostos").load("datos.php", {con : 3.3, zona : zona, usuario : usuario}); 
		$("#pventa").load("datos.php", {con : 3.4, zona : zona, ccostos : ccostos, usuario : usuario});
	  });
      /*
	  //verifica si se copia, pega o corta el contenido de un item
      $("#iva").bind("copy", function(e)  
	  {
        //alert("copying text!");
      });
	  
      $("#iva").bind("paste", function(e) 
	  {
        //alert("pasting text!");
		//var producto=$("#producto").val();
		//alert("producto");
		//document.getElementById("iva").value="NULL";
		//$("#iva").val("");
		//$("#iva").focus();
      });
      $("#iva").bind("cut", function(e) 
	  {
        //alert("cut text!");
      });
	  
	  var coniva= $("input[name=coniva]:radio:checked").val();
	  if(coniva==0)
	  {
		$("#seleccion").show();  
	  }
	  else
	  {
		$("#seleccion").hide(); 
		//document.getElementById("dia").value=0;
	  }
	  
	  var coniva1="input[name=coniva]:radio";//tipoo ="input[name=tipo]"; //"input[name=tipo]:Checkbox";  $("input[name="cambiar"]:checked").val();   
      $(coniva1).change(function()
      {
        var coniva=$(this).attr("value");
        
        //alert("coniva="+coniva); 
        
        if(coniva==0)
        {
          $("#seleccion").show();
        }
        else
        {
          $("#seleccion").hide();
        }        
      }); 
	  
	  /*$("#vendedor").load("datos.php", {con : 2.2, tipo : 0, tipo1 : 0, tipo2 : 0, tipo3 : 0, fechaini : fechaini, fechafin : fechafin, usuario : usuario});
	  
	  //alert(tipo+"-"+fechaini+"-"+fechafin);
	  if(tipo==0)
	  {
		$("#seleccion").hide();  
	    $("#seleccion1").hide();
		$("#seleccion2").hide();
	  }
	  else if(tipo==13)
	  {
		$("#seleccion").hide();
		$("#seleccion1").hide();
		$("#seleccion2").hide();
	  }
	  else if(tipo==8)
	  {
		$("#seleccion").hide();
		$("#seleccion1").hide();
		$("#seleccion2").hide();
	  }
	  else if(tipo==14)
	  {
		$("#seleccion").hide();
		$("#seleccion1").hide();
		$("#seleccion2").hide();
	  }
	  else if(tipo==15)
	  {
		$("#seleccion").hide();
		$("#seleccion1").hide();
		$("#seleccion2").hide();
	  }
	  
	  var agrupar= $("input[name=agrupar]:radio:checked").val();
	  if(agrupar==0)
	  {
		$("#seleccion3").show();  
	  }
	  else
	  {
		$("#seleccion3").hide(); 
		document.getElementById("dia").value=0;
	  }
	  
	  var mosgrafica = $("input[name=mosgrafica]:radio:checked").val();
	  if(mosgrafica==1)
	  {
	    $("#seleccion4").show();
	  }
	  else
	  {
	    $("#seleccion4").hide();	   
	  }
	  //$("#nombre").load("datos.php", {con : 1.5});
	  //$("#orden").load("datos.php", {con : 1.6, asociado : 0});
	  
	  var mosgrafica1="input[name=mosgrafica]:radio";//tipoo ="input[name=tipo]"; //"input[name=tipo]:Checkbox";  $("input[name="cambiar"]:checked").val();   
      $(mosgrafica1).change(function()
      {
        var mosgrafica=$(this).attr("value");
        
        //alert("enca2="+enca2); 
        
        if(mosgrafica==1)
        {
          $("#seleccion4").show();
        }
        else
        {
          $("#seleccion4").hide();
        }        
      }); 
	  
	  
	  var agrupar1="input[name=agrupar]:radio";//tipoo ="input[name=tipo]"; //"input[name=tipo]:Checkbox";  $("input[name="cambiar"]:checked").val();   
      $(agrupar1).change(function()
      {
        var agrupar=$(this).attr("value");
        
        //alert("enca2="+enca2); 
        //var ante = $("input[name="ante"]:Checkbox").attr("checked");
        //alert(tipo2+"algo");
        if(agrupar==0)
        {
          $("#seleccion3").show();
          //$("#seleccion1").hide();
		  //$("#des").load("datos.php", {con : 1.3, menup : menup, aso : 0 });
		  //$("#orden").load("datos.php", {con : 1.6, menup : menup, aso : 0});
        }
        else
        {
          $("#seleccion3").hide();
		  document.getElementById("dia").value=0;
          //$("#seleccion1").show();
		  //$("#des").load("datos.php", {con : 1.3, menup : menup, aso : 0 });
		  //$("#submenu").load("datos.php", {con : 1.4, aso : aso});
		  //$("#orden").load("datos.php", {con : 1.6, menup : menup, aso : 0});
        }
        
      }); 
	  
	  */
	  
	  
	  //alert("prueba");
      //$("#selecion").hide();
      //$("#selecion1").hide();
      //$("#tabla2").hide();
    
      //var cedula = $("#cedula").val()
      //alert(cedula);
    
    });
	
	
	$(function() 
	{
	  //dp.SyntaxHighlighter.ClipboardSwf = " js/dp.SyntaxHighlighter/clipboard.swf";
	  //dp.SyntaxHighlighter.HighlightAll("code");
			
	  // Give each example an anchor tag
	  /*var count = 1;
	  $.each($(".demo"), function() 
	  {
	    var $example = $(this);
		$example.before("<a name="demo + count++ + " />");
	  });

	  // Set up our example table of contents
	  count = 1; // reset our counter
	  var $toc = $("#toc");
	  $.each($(".example > td"), function() 
	  {
	    var $td = $(this);
		var anchor = $("<a></a>").attr("href", "#demo" + count++);
        $(document.createElement("li"))
		.html($td.html())
		.wrapInner(anchor)
		.appendTo($toc);

		// Create link back to top (floated right)
		$(document.createElement("div"))
		.html("<a href=#toc>Top</a>")
		.css("float", "right")
		.appendTo($td);
	  });
      */
	  var usuario = $("#usuario").val();
	  // Do not autocomplete initial value, and handle non-existent values
	  $("#cedula").flexbox(vendedores, 
	  {
	    autoCompleteFirstMatch: false,
		width: 350, 
		noResultsText: "",
		onSelect: function() 
		{
		  //$("#cedula-result").html("You selected " + this.value + ","  + "which has a hidden value of " + $("input[name=cedula]").val());
		  //document.getElementById("cedula").value=this.value;
		  //document.getElementById("cedula_input").value=this.value;
		  //document.getElementById("cedula_input").value=$("input[name=cedula]").val();
		  document.getElementById("cedulaid").value=$("input[name=cedula]").val();
		  document.getElementById("cedulaname").value=this.value;
		  //alert("algo "+this.value);
		  /*$.ajax(
	      {
            url: "datos.php",
            type: "POST",
            data: "submit=&con=4&id="+$("input[name=cedula]").val()+"&usuario="+usuario,
          	success: function(datos)
            {//ConsultaDatos();
            		//alert(datos);
			  var datos1=datos.split("|");
			  document.getElementById("nombres").value=datos1[0];
			  document.getElementById("apellidos").value=datos1[1];
			  //document.getElementById("direccion").value=datos1[2];
			  //document.getElementById("correo").value=datos1[3];
			  //document.getElementById("telefono").value=datos1[4];
		  	}
          });*/ 
		}
		
	  });
	  
	  $("#cedula_input").blur(function()
	  {  //blur change(function()  focusout(function()  live("focusout", function()
		document.getElementById("cedula").value=this.value; 			   
	    //$("#cedula-result").html("The value passed when the form is " + "submitted is " + $("input[name=cedula]").val());
		 
	  });			
				
	});
	
	/*(function( $ ) 
	{ //alert("prueba");
	  var usuario = $("#usuario").val();
	  $.widget( "ui.combobox", 
	  {
	    _create: function() 
		{
		  var self = this,
		  select = this.element.hide(),
		  selected = select.children( ":selected" ),
		  value = selected.val() ? selected.text() : "";
		  var input = this.input = $( "<input>" )
		  .insertAfter( select )
		  .val( value )
		  .autocomplete(
		  {
		    delay: 0,
			minLength: 0,
			source: function( request, response ) 
			{
			  var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
			  response( select.children( "option" ).map(function() 
			  {
			    var text = $( this ).text();
				//alert(text);
				if( this.value && ( !request.term || matcher.test(text) ) )
				{
				  return{
				         label: text.replace(
								new RegExp(
									"(?![^&;]+;)(?!<[^<>]*)(" +
				                    $.ui.autocomplete.escapeRegex(request.term) +
				                    ")(?![^<>]*>)(?![^&;]+;)", "gi"
				                ), "<strong>$1</strong>" ),
				         value: text,
				         option: this
				  };
				}
			  }) );
			},
			select: function( event, ui ) 
			{
			  
			  ui.item.option.selected = true;
			  self._trigger( "selected", event, 
			  {
			    item: ui.item.option
				
			  });
			},
			change: function( event, ui )
			{
			  var dato_c= $( this ).val();	
			  alert(dato_c);
			  var id_c=select.val();
			  
			  if(id_c==0)
			  {
				 
				alert("prueba cero");
			    document.getElementById("nombres").value="";
				document.getElementById("apellidos").value="";
				document.getElementById("direccion").value="";
				document.getElementById("correo").value="";
				document.getElementById("telefono").value="";   
			    
			  }
			  else
			  {
				$.ajax(
	            {
          		  url: "datos.php",
          		  type: "POST",
          		  data: "submit=&con=4&id="+id_c+"&usuario="+usuario,
          		  success: function(datos)
          		  {//ConsultaDatos();
            		//alert(datos);
					var datos1=datos.split("|");
					document.getElementById("nombres").value=datos1[0];
					document.getElementById("apellidos").value=datos1[1];
				    document.getElementById("direccion").value=datos1[2];
				    document.getElementById("correo").value=datos1[3];
				    document.getElementById("telefono").value=datos1[4];
		  		  }
        		}); 
			    
			  }
			  
			  if ( !ui.item )
			  {
				
			    var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
				valid = false;
				select.children( "option" ).each(function() 
				{
				  	
				  if ( $( this ).text().match( matcher ) ) 
				  {					  
				    this.selected = valid = true;
					return false;
				  }
				});
				if ( !valid )
				{
				  var prueba= $( this ).val();//$( this ).text();//$( this ).val( "" );
				  //alert(prueba);
				  // remove invalid value, as it didn t match anything
				  //$( this ).val( "" );
				  select.val( "" );
				  input.data( "autocomplete" ).term = "";
				  var id=select.val();
				  //alert(id);
				  return false;
				}
				
			  }
			  
			}
		  })
		  .addClass( "ui-widget ui-widget-content ui-corner-left" );
          input.data( "autocomplete" )._renderItem = function( ul, item ) 
		  {
		    return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + item.label + "</a>" )
			.appendTo( ul );
		  };
          
		  this.button = $( "<button type=button>&nbsp;</button>" )
		  .attr( "tabIndex", -1 )
		  .attr( "title", "Show All Items" )
		  .insertAfter( input )
		  .button(
		  {
		    icons: {
			  primary: "ui-icon-triangle-1-s"
			},
			text: false
		  })
		  .removeClass( "ui-corner-all" )
		  .addClass( "ui-corner-right ui-button-icon" )
		  .click(function() 
		  {
			// close if already visible
			if ( input.autocomplete( "widget" ).is( ":visible" ) ) 
			{
			  input.autocomplete( "close" );
			  return;
			}

			// work around a bug (likely same cause as #5265)
			$( this ).blur();

		    // pass empty string as value to search for, displaying all results
			input.autocomplete( "search", "" );
			input.focus();
		  });
		},

		destroy: function() 
		{
		  this.input.remove();
		  this.button.remove();
		  this.element.show();
		  $.Widget.prototype.destroy.call( this );
		  alert("algo");
		}
	  });
	})( jQuery );
   
	$(function() 
	{
	  $( "#cedula" ).combobox();
	  
	});
	*/
	
	
    function dateComapreTo(yy1, mm1, dd1, yy2, mm2, dd2) 
    {

      var f1 =  new Date(yy1, mm1, dd1);
      var f2 =  new Date(yy2, mm2, dd2);
      return f1.getTime() - f2.getTime();

    }
	function comparaFecha(fecha,fecha1)
    {
      fech=fecha.split("-");
      fech1=fecha1.split("-");
	  var fec =[];
	  var fec1 =[];
	  fec[0]=fech[2];
	  fec[1]=fech[1];
	  fec[2]=fech[0];
	  fec1[0]=fech1[2];
	  fec1[1]=fech1[1];
	  fec1[2]=fech1[0];
	  //fec=fecha.split("/");
      //fec1=fecha1.split("/");
	  //alert(fec[0]+"--"+fec1[0]);
	
 	  if(fec[2]>fec1[2])
	  {
	    return 1;
	  }
	  else if(fec[2]<fec1[2])
	  {
	    return -1;
	  }
	  else
	  {
	    if(fec[1]>fec1[1])
	    {
	  	  return 1;
	    }
	    else if(fec[1]<fec1[1])
	    { 
		  return -1;
	    }
	    else
	    {
		  if(fec[0]>fec1[0])
		  {
		    return 1;
		  }
		  else if(fec[0]<fec1[0])
		  {
		    return -1;
		  }
		  else
	  	  {
		    return 0;
		  }
	    }
	  }
	  //Esta funcion te devuelve 0 en caso de que sean iguales 1 en caso de que fecha sea mayor que fecha1 y -1 cuando fecha sea menor que fecha1.
    }
    function Solo_Numerico(variable)
    {
        Numer=parseInt(variable);
        if (isNaN(Numer)){
            return "NO";
        }
        return "SI";
    }
	
	function DiferenciaFechas ()//(formulario) 
	{  
      //Obtiene los datos del formulario  
      CadenaFecha1 = $("#fechafin").val();//formulario.fecha1.value  
      CadenaFecha2 = $("#fechaini").val();//formulario.fecha2.value  
     
      //Obtiene dia, mes y año  
      var fecha1 = new fecha( CadenaFecha1 );     
      var fecha2 = new fecha( CadenaFecha2 ); 
      
      //Obtiene objetos Date  
      var miFecha1 = new Date( fecha1.anio, fecha1.mes, fecha1.dia );  
      var miFecha2 = new Date( fecha2.anio, fecha2.mes, fecha2.dia );  
  

      //Resta fechas y redondea  
      var diferencia = miFecha1.getTime() - miFecha2.getTime()  
      var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));  
      var segundos = Math.floor(diferencia / 1000);  
      //alert ("La diferencia es de " + dias + " dias,\no " + segundos + " segundos.");  
      
	  dias=dias+1;
      return dias;  
    }  
  
    function fecha( cadena ) 
	{  
  
      //Separador para la introduccion de las fechas  
      var separador = "-"  
  
      //Separa por dia, mes y año  
      if ( cadena.indexOf( separador ) != -1 ) 
	  {  
        var posi1 = 0; 
		//alert("dato="+cadena.indexOf( separador, posi1 + 1 ));
        var posi2 = cadena.indexOf( separador, posi1 + 1 );  
        var posi3 = cadena.indexOf( separador, posi2 + 1 );  
        this.anio = cadena.substring( posi1, posi2 );  
        this.mes = cadena.substring( posi2 + 1, posi3 );  
        this.dia = cadena.substring( posi3 + 1, cadena.length );  
      } 
	  else 
	  {  
        this.anio = 0  
        this.mes = 0  
        this.dia = 0     
      }  
    }  
	
    function controlfecha1()
	{
	  var fechaini = $("#fechaini").val();
	  var fechafin = $("#fechafin").val();
	  
	  var f=comparaFecha(fechaini,fechafin);
	  
	  $("#msgbox3").empty(); 
	  $("#msgbox3").css("display","none");
	  //$("#msgbox1").css("display","none");
	  
	  if(fechaini=="")
	  {
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Inicio").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f>0)
	  {
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Inicio no Puede Ser Superior a la Fecha de Finalización").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  
	  
	}
	
	function controlfecha2()
	{
	  var fechaini = $("#fechaini").val();
	  var fechafin = $("#fechafin").val();
	  
	  var f=comparaFecha(fechafin,fechaini);
	  $("#msgbox4").empty(); 
	  //$("#msgbox").css("display","none");
	  $("#msgbox4").css("display","none");
	 
	  //alert(f);
	  if(fechafin=="")
	  {
		  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Finalización").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f<0)
	  {
		  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Finalización no Puede Ser Inferior a la Fecha de Inicio").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  
	}
	function dateadd(fecha,addAmount,addPeriod) 
	{ 
	  var fecha1= fecha.split("-");
      var date = $.datepick.newDate(fecha1[0],fecha1[1],fecha1[2] );//($("#fechaini").datepick("getDate")[0].getTime()); 
      $.datepick.add(date,  parseInt(addAmount, 10), addPeriod); 
      //alert(date);
	  var fecha = $.datepick.formatDate("yyyy-mm-dd",date);//dateFormat: "yyyy-mm-dd"
	  return fecha;
    }
	function diasfechas(fechare)
	{
	  var fecha=fechare.split("-");
	  var today = $.datepick.newDate(fecha[0], fecha[1], fecha[2]);  
	  //var today=new Date(vecfecha[2],vecfecha[1],vecfecha[0] ); 
      var thisDay=today.getDay() 
	  //alert(today+"-"+thisDay);
	  return thisDay; 
	}
    function ocultar(tipo)
    { //alert(tipo);
      /*if(tipo==1)
      {
        //var boton3 = $("#boton3").val();
        // alert(boton3);
        //$("#formato").hide();
        //$("#boton3").show();
      }
      if(tipo==2)
      {
        //var boton3 = $("#boton3").val();
        // alert(boton3);
        //$("#tabla3").hide();
        //$("#boton6").show();
      } */   
    }
	
	function listafechas()
	{
	  var fechaini = $("#fechaini").val();
	  var fechafin = $("#fechafin").val();
	  var fecha=fechaini;
	  //echo $fechaini."-".$fechafin."<br>";
      //$fec=explode("-",$fechaini);
	  var vec = []; 
	  vec[0]=fechaini;
	  var x=1;
	  if(fechaini!=fechafin)
	  {
	    x=1;
	    do
	    {
		  fecha=dateadd(fecha,"1","d");
	      //alert(fecha);		  
		  vec[x]=fecha;
		  x++;	
		  //echo $fecha."!=".$fechafin."<br>";
	    }while(fecha!=fechafin);
	  }
	  return vec;
	  
	}
    function returnRefresh(returnVal) 
	{
       window.document.reload();
    }

	function reporte_recaudo()
    { 
	  $("#tabla").empty();
	  //alert("algo");
	  var usuario = $("#usuario").val();
	  //var nit = $("#nit").val();
	  //var producto = $("#producto").val();
	  //var subproducto = $("#subproducto").val();
	  var cedula = $("#cedula").val();
	  //var cedula1 = $("#cedula").attr();
	  var cedulaid = $("#cedulaid").val();
	  var cedulaname = $("#cedulaname").val();
	  //var nombres = $("#nombres").val();
	  //var apellidos = $("#apellidos").val();
	  //var direccion = $("#direccion").val();
	  //var correo = $("#correo").val();
	  //var telefono = $("#telefono").val();
	  //var cupo = $("#cupo").val();
	  //var cantidad = $("#cantidad").val();
	  var fecha_ini = $("#fechaini").val();
	  var fecha_fin = $("#fechafin").val();
	  var fecha_sys = $("#fecha_sys").val();
	  
	  var cedula2=cedula.split("-");
	  cedula = cedula2[0];
	  //alert("?"+cedula+"|-"+cedulaid+"-"+cedulaname);
	  /*var cedula1;
	  if(cedula!="")
	  {
	    if(cedula==cedulaname)
		{
		  cedula1=cedulaid;	
		}
		else
		{
		  cedula1=0;
		}
	  
	  }*/
	  //alert(cedula1);
	  /*var tipo = $("#tipo").val();
	  var visual= $("input[name=visual]:radio:checked").val();
	  */
	  //alert(asignar+"---");
	  //var cantidad1=parseInt($("#cantidad").val());
	
	  
	  //alert(control2);
	  //alert(cupo);
	  var con=1;
	  $("#msgbox0").css("display","none");
	  $("#msgbox").css("display","none");
	  /*$("#msgbox1").css("display","none");
	  $("#msgbox2").css("display","none");*/
	  $("#msgbox3").css("display","none");
	  /*$("#msgbox4").css("display","none");
	  $("#msgbox5").css("display","none");
	  $("#msgbox6").css("display","none");
	  $("#msgbox7").css("display","none");
	  $("#msgbox8").css("display","none");
	  $("#msgbox9").css("display","none");*/
	  //alert(fecha_ini+"--"+fecha_sys);
	  var f=comparaFecha(fecha_ini,fecha_sys);
	  var f1=comparaFecha(fecha_ini,fecha_fin);
	  //var f2=comparaFecha(fecha_fin,fecha_ini);
	  //f=0;
	  //f1=0;
	  //alert(f);
	  
	  if(cedula=="")
	  {
		//alert("algo");  
	    $("#msgbox0").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite la Cedula del Vendedor").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(fecha_ini=="")
	  {
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Venta").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f>0)
	  {  //alert("prueba");
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Inicio no Puede Ser Superior a la Fecha Actual").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(fecha_fin=="")
	  {
		  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Venta").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f1>0)
	  {  //alert("prueba");
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Inicio no Puede Ser Superior a la Fecha de Finalización").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  /*else if(subproducto==0)
	  {
	    $("#msgbox").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Seleccione un Subproducto").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(cedula=="")
	  {
	    $("#msgbox1").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite o Seleccione la Cedula").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(nombres=="")
	  {
	    $("#msgbox2").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite los Nombres").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(apellidos=="")
	  {
	    $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite los Apellidos").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(direccion=="")
	  {
	    $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite la Dirección").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(correo=="")
	  {
	    $("#msgbox5").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite el Correo").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(telefono=="")
	  {
	    $("#msgbox6").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite el Teléfono").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(cupo<=0 )
	  {
	    $("#msgbox7").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("No Hay Cupos Disponibles").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(cantidad=="" )
	  {
	    $("#msgbox8").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite la Cantidad a Vender").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(cantidad<=0)
	  {
	    $("#msgbox8").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Por Favor Digite una Cantidad a Vender Mayor a Cero").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0;
	  }
	  else if(cantidad>cupo)
	  {
		$("#msgbox8").fadeTo(200,0.1,function() //start fading the messagebox
	    { 
			//add message and change the class of the box and start fading
		  $(this).html("Error: La cantidad a Vender no Puede Superar el Cupo Disponible").addClass("messageboxerror").fadeTo(200,1);
		});
		con=0; 
	  }
	  /*else if(fecha_sys=="")
	  {
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Inicio").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f<0)
	  {  //alert("prueba");
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Inicio no Puede Ser Inferior a la Fecha Actual").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  /*else if(f1>0)
	  {  //alert("prueba");
		  $("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Inicio no Puede Ser Superior a la Fecha de Finalización").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(fecha_fin=="")
	  {
		  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione una Fecha de Finalización").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }
	  else if(f2<0)
	  {  //alert("prueba");
		  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("La Fecha de Finalización no Puede Ser Inferior a la Fecha de Inicio").addClass("messageboxerror").fadeTo(200,1);
		  });
		  con=0;
	  }*/
	  var cupo2=0;
	  
		
	  //alert(usuario+"-"+fecha_ini+"-"+cedula+"-"+cedulaid+"-"+cedulaname);
	  
	  if(con==1)
	  {
		 $("#loading ").ajaxStart(function()
         {
           $(this).show();
         }).ajaxStop(function()
         {
           $(this).hide();
         }); 
        
        $("#tabla").load("datos.php", {con : 5.1, usuario : usuario, cedula : cedula, fecha_ini : fecha_ini, fecha_fin : fecha_fin}, function(response, status, xhr) 
	    {
			 //alert(status);
          if (status == "success") 
		  { //alert(status);
		    
			$("#msgbox").fadeTo(200,0.1,function()  //start fading the messagebox
			{ 
			  //add message and change the class of the box and start fading removeClass("messageboxerror").addClass("messageboxok").fadeTo(900,1,
			  $(this).html("Reporte Generado con Exito").addClass("messageboxok").fadeTo(200,1);
			  setTimeout(function() 
              {
                $("#msgbox").css("display","none");
			  },3000);
			  //doSomething();
			  //wait(1000);
			  //doSomethingElse();	
			  
			});
			
		  }
		  else
		  {
            //var msg = "Sorry but there was an error: ";
            //$("#error").html(msg + xhr.status + " " + xhr.statusText);
			$("#msgbox").fadeTo(200,0.1,function() //start fading the messagebox
			{ 
			        //add message and change the class of the box and start fading
			  $(this).html("Error: En la Generación del Reporte").addClass("messageboxerror").fadeTo(200,1);
			});
          }
		}); 
	  }
	  if(con==11)
      {
			  
            //var mensaje="Realmente Desea Generar los Promedios para el Periodo "+periodo+" ?"; 
            //if(confirm(mensaje))
            //{
            $("#loading ").ajaxStart(function()
            {
              $(this).show();
            }).ajaxStop(function()
            {
              $(this).hide();
            }); 
        
            //form.submit();
          
            $.ajax(
	        {
              url: "datos.php",
              type: "POST",
              data: "submit=&con=5.1&usuario="+usuario+"&cedula="+cedula+"&fecha_ini="+fecha_ini,
              success: function(datos)
              {
                //ConsultaDatos();
                alert(datos);
                //$("#formulario").hide();
                //$("#tabla").show();
			    var datos1=datos.split("|");
				$("#seleccion ").show();
	            if(datos1[0]==1)
			    {
				  //window.location = "start.php?con="+con;
			      $("#msgbox9").fadeTo(200,0.1,function()  //start fading the messagebox
			      { 
			        //add message and change the class of the box and start fading removeClass("messageboxerror").addClass("messageboxok").fadeTo(900,1,
			        $(this).html("Reporte Generado con Exito").addClass("messageboxok").fadeTo(200,1,
                    function()
			        {  
				      //redirect to secure page
			 	     //document.location="imprimir_venta.php?subproducto="+subproducto+"&idcedula="+datos1[1]+"&pventa="+datos1[2]+"&fecha_venta="+datos1[3];
					 // window.open("imprimir_venta.php?subproducto="+subproducto+"&idcedula="+datos1[1]+"&pventa="+datos1[2]+"&fecha_venta="+datos1[3]);//( "url","nombre_target","parametros" );  
					 
					  //document.getElementById("descripcion").value=datos1[1];
					  $("#tabla").empty();
                      $("#tabla").val(datos1[1]);//load("datos2.php", {con : 69.3, cant : cant}); 
					  /*document.location="imprimir_venta.php?subproducto="+subproducto+"&idcedula="+datos1[1]+"&pventa="+datos1[2]+"&fecha_venta="+datos1[3];
					  $("body").click(function()
					  {
                        //Hide the menus if visible
					    //alert("1");
					    document.location="venta_producto.php";
                      });*/
					  //alert(prueba);
                      
					  //setTimeout("",500);
					  //doSomething();
					   //wait(1000);
					  // doSomethingElse();					
				    });
			      });  
			    }
			    else if(datos1[0]==2)
			    { 
				  //alert("Solo Hubo Cupo Para : "+ datos1[1]+" Productos");	
				  
				  //alert("Error: Verifique su Contraseña Antigua");
				  $("#msgbox9").fadeTo(200,0.1,function() //start fading the messagebox
			      { 
			        //add message and change the class of the box and start fading
			        $(this).html("Solo Hubo Cupo Para : "+ datos1[1]+" Productos").addClass("messageboxerror").fadeTo(200,1);
			      });
				  var ask=window.confirm("Solo Hubo Cupo Para : "+ datos1[1]+" Productos Desea Continuar con la Venta");
				  //alert(ask);
				  if (ask)
				  {
				    //window.alert("Ok")
					document.location="imprimir_venta.php?subproducto="+subproducto+"&idcedula="+datos1[2]+"&pventa="+datos1[3]+"&fecha_venta="+datos1[4];
					$("body").click(function()
					{
                      //Hide the menus if visible
					  //alert("1");
					  document.location="venta_producto.php";
                    });
				  }
				  else
				  {
				    $.ajax(
	                {
          		      url: "datos.php",
          		      type: "POST",
          		      data: "submit=&con=4.5&subproducto="+subproducto+"&usuario="+usuario+"&idcedula="+datos1[2]+"&pventa="+datos1[3]+"&fecha_venta="+datos1[4],
          		      success: function(datos)
          		      {//ConsultaDatos();
            		    //alert(datos);
					    //var datos1=datos.split("|");
						alert("Venta Anulada con Exito");
						document.location="venta_producto.php";
          		  	  }
        		    }); 	  
				    //window.alert("Select Another Function") 
				  }
			    }
			    else if(datos1[0]==3)
			    { 
			      //if(datos1[1]!=0)
			  	  //{
				  alert("Error: Venta Cancelada, Por Favor Verifique los Datos del Cliente");	
				  //}
				  //alert("Error: Verifique su Contraseña Antigua");
				  $("#msgbox9").fadeTo(200,0.1,function() //start fading the messagebox
			      { 
			        //add message and change the class of the box and start fading
			        $(this).html("Error: Venta Cancelada, Por Favor Verifique los Datos del Cliente").addClass("messageboxerror").fadeTo(200,1);
			      });
				  $("#seleccion ").show();
			    }
			  }
            });
          }
		
		
	  
	  
	  
	  
	  /*if(con==10)
	  {
	    //window.location = "generar_reporte_chance.php?tipo="+tipo+"&tipo1="+tipo1+"&tipo2="+tipo2+"&tipo3="+tipo3+"&vendedor="+vendedor+"&visual="+visual;
	    document.forma.action="generar_reporte_chance.php";
	    document.forma.target="_blank";
	    document.forma.submit();
	  }*/
	}
    function ingresar(tipo)
    { 
	  if(tipo==1)
	  {
        var menup= $("input[name=menup]:radio:checked").val();
	    var nombre = $("#nombre").val();
		var nombre1 = $("#nombre1").val();
	    var submenu = $("#submenu").val();
		var des = $("#des").val();
		var url = $("#url").val();
		var permiso = $("input[name=permiso]:radio:checked").val();
		var con=1;
	  
	    alert(menup+"-"+nombre+"-"+nombre1+"-"+submenu+"-"+des+"-"+url+"-"+permiso);
	    
		//$("#msgbox1").empty();  
	    //$("#msgbox2").empty();  
	    //$("#msgbox").empty();
	    //$("#msgbox3").removeClass("messageboxerror");
	    $("#msgbox").css("display","none");
	    $("#msgbox1").css("display","none");
	    $("#msgbox2").css("display","none");
	    $("#msgbox3").css("display","none");
	  
	    if(nombre==0)
	    {
	  	  con=0;
	 	  //alert("Por Favor Seleccione un Nombre");
		  $("#msgbox").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione un Nombre").addClass("messageboxerror").fadeTo(200,1);
		  });
	    }
	    else if(nombre=="#" && nombre1=="")
	    {
	      con=0;
		  //alert("Por Favor Digite EL Nombre del Menú");
		  $("#msgbox1").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Digite EL Nombre del Menú").addClass("messageboxerror").fadeTo(200,1);
		  });
	    }
	    else if(submenu==0)
	    {
	      con=0;
		  //alert("Por Favor Seleccione Donde Ingresar el Menú");
		  $("#msgbox2").fadeTo(200,0.1,function() //start fading the messagebox
	      { 
			//add message and change the class of the box and start fading
			$(this).html("Por Favor Seleccione Donde Ingresar el Menú").addClass("messageboxerror").fadeTo(200,1);
		  });
	    }
	   
	    if(con==11)
        {
          //var mensaje="Realmente Desea Generar los Promedios para el Periodo "+periodo+" ?"; 
          //if(confirm(mensaje))
          //{
          $("#loading ").ajaxStart(function()
          {
            $(this).show();
          }).ajaxStop(function()
          {
            $(this).hide();
          }); 
        
          //form.submit();
          
          $.ajax(
	      {
            url: "datos.php",
            type: "POST",
            data: "submit=&con=1&password="+password+"&password1="+password1+"&password2="+password2,
            success: function(datos)
            {
              //ConsultaDatos();
              //alert(datos);
              //$("#formulario").hide();
              //$("#tabla").show();
	          if(datos==1)
			  {
			    //window.location = "start.php?con="+con;
			    $("#msgbox3").fadeTo(200,0.1,function()  //start fading the messagebox
			    { 
			      //add message and change the class of the box and start fading removeClass("messageboxerror").addClass("messageboxok").fadeTo(900,1,
			      $(this).html("Contraseña Actualizada.....").addClass("messageboxok").fadeTo(200,1,
                  function()
			      { 
				    //redirect to secure page
			 	    //document.location="start.php?con=1";
					//$("#password").empty();
					document.getElementById("password").value="";
					document.getElementById("password1").value="";
					document.getElementById("password2").value="";
				  });
			    });		    
			  }
			  else if(datos==2)
			  { 
				//alert("Error: Verifique su Contraseña Antigua");
				$("#msgbox3").fadeTo(200,0.1,function() //start fading the messagebox
			    { 
			      //add message and change the class of the box and start fading
			      $(this).html("Error: Verifique su Contraseña Antigua...").addClass("messageboxerror").fadeTo(200,1);
			    });
			  }
			}
          });
        }
	  }
	}       

  </script> ';
  
  echo '<style type="text/css">
      body {
	  //font-family:Verdana, Arial, Helvetica, sans-serif;
	  //font-size:11px;
	  }
	  .top {
	  margin-bottom: 15px;
	  }
	  .buttondiv {
	  margin-top: 10px;
	  }
	  .messagebox{
	  position:absolute;
	  width:100px;
	  margin-left:30px;
	  border:1px solid #c93;
	  background:#ffc;
	  padding:3px;
	  }
      .messageboxok{
	  position:absolute;
	  width:auto;
	  margin-left:30px;
	  border:1px solid #349534;
	  background:#C9FFCA;
	  padding:3px;
	  font-weight:bold;
	  color:#008000;	
      }
      .messageboxerror{
	  position:absolute;
	  width:auto;
	  margin-left:30px;
	  border:1px solid #CC0000;
	  background:#F7CBCA;
	  padding:3px;
	  font-weight:bold;
	  color:#CC0000;
      }
    </style>';	
  }
    
  function menu()
  {
     global $cnn, $nivel;
    echo '<ul class="sf-menu">';
	
	/*if($nivel==1)
	{
	  $sql="SELECT mr.id,mn.nombre,mr.enlace,menup, mr.asociado, mr.orden, mr.suborden FROM menu_reportes mr, menu_reportes_nombres mn
      where mr.nombre=mn.id order by mr.orden,mr.suborden ";
	}
	else
	{
	  $sql="SELECT mr.id,mn.nombre,mr.enlace,menup, mr.asociado, mr.orden, mr.suborden FROM menu_reportes mr, menu_reportes_nombres mn
      where mr.nombre=mn.id and permisos='0' order by mr.orden,mr.suborden ";	
    }*/
	$sql="SELECT mr.id,mn.nombre,mr.enlace,menup, mr.asociado, mr.orden, mr.suborden FROM menu_reportes mr, menu_reportes_nombres mn
      where mr.nombre=mn.id and permisos like'%,".$nivel.",%' order by mr.orden,mr.suborden ";
	//$sql="SELECT mr.id,mn.nombre,mr.enlace,menup, mr.asociado, mr.orden, mr.suborden FROM menu_reportes mr, menu_reportes_nombres mn
    //where mr.nombre=mn.id order by mr.orden,mr.suborden ";
	//$sql="SELECT id,nombre,enlace,menup, asociado, orden FROM menu_reportes where menup='S' and asociado='-1'  order by orden";
    $res=$cnn->Execute($sql);//pg_query($cnn,$sql);
	$num_rows = $res->RecordCount();//pg_num_rows($result);
    //$row=pg_fetch_array($result);
    //echo $sql;
    //if username exists
	//echo $num_rows;
	$x=0;
	$aso="0";
	$aso1="";
	$x1="";
	$c=1;
	$x1[0]=0;
    while (!$res->EOF)//($r = pg_fetch_row($result))  
    {
	  if($x==0)
	  {
	    echo '<li class="current">
	    <a href='.$res->fields[2].'>'.$res->fields[1].'</a>';
	   
		$x1[$c]=$res->fields[0];
		
	  }
	  else
	  { 
	    if($res->fields[3]=="S")
	    {
		  if($c>0)
		  {
			$c1=$c;  
	        for($i=0; $i<$c1-1; $i++)
		    {
		     echo '</li>
			    </ul>';
			  $c--;	
		  
			}
			echo '</li>
		      <li class="current">
	            <a href='.$res->fields[2].'>'.$res->fields[1].'</a>'; 
				$c=1;
				$x1[$c]=$res->fields[0];
		  }
		  else
		  {
			echo '</li>
			    </ul>
			  </li>	
		      <li class="current">
	            <a href='.$res->fields[2].'>'.$res->fields[1].'</a>'; 
				$c=1;
				$x1[$c]=$res->fields[0];
		  }
		}
	    else
	    {
	      if($x1[$c]==$res->fields[4])
	      { 
	        echo'<ul>
  	          <li>
		        <a href='.$res->fields[2].'>'.$res->fields[1].'</a>';
		    $c++;
			$x1[$c]=$res->fields[0];
			
	      }
		  elseif($x1[$c-1]==$res->fields[4])
		  {
			 echo'</li>
		        <li>
		          <a href='.$res->fields[2].'>'.$res->fields[1].'</a>';
			 $x1[$c]=$res->fields[0];	  
		  }
		  else
		  {
			$c=$c-1;    
			do
			{
			  echo'</li>
			  </ul>';
			  $c--;
			}while($x1[$c]!=$res->fields[4]);
		      echo '</li>
				<li>
		          <a href='.$res->fields[2].'>'.$res->fields[1].'</a>'; 
			$c++;
			$x1[$c]=$res->fields[0];
		    
		  }
	    }
	  }
	   
	    $aso=$res->fields[0];
	    $aso1=$res->fields[3];
		$x++;
		if($x==$num_rows)
	    {
		  if($c>0)
		  {
			$c1=$c;  
	        for($i=0; $i<$c1-1; $i++)
		    {
		     echo '</li>
			    </ul>
			  </li>';
			  $c--;	
		  
			}
			
				$c=1;
				$x1[$c]=$res->fields[0];
		  }
		  else
		  {	
		    echo '</li>';
		  }
		}
	  $res->moveNext();	
	}
	echo '</ul>';                     
  }    
    
  function contenido()
  { 
    global $nivel, $nickname, $cnn;
    echo '<tr> 
      <td height="10" colspan="2"  class="text"><table width="90%" align="center" class="text">
        <tr> 
          <td> 
            <p class="title" align=center> <strong><B><font color=#ffffff>Generar Reporte Recaudo</font></B></strong></p>
			<br>
			<form id="forma" name="forma" method="post" action="" >';
			  echo "<input type=hidden id=usuario name=usuario value='".$nickname."'>";
			  /*echo ' <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>
  		         <tr> 
                   <td width=40% align=left><strong><B><font color=#ffffff size=2>Seleccione el Producto: </font></b></strong></td>
    			   <td width=60%><select id=producto name=producto >';
				      
				     $sql="select id, producto from productos_inv order by producto ";
    			     $res=$cnn->Execute($sql);//pg_query($cnn,$sql);
    				 //$row=pg_fetch_array($result);
    				 //echo $sql;
					 //echo $res->fields[1];
    				 //if username exists
				     echo '<option value=0 selected>Seleccione</option>';
	
    				 while (!$res->EOF)//while ($r = pg_fetch_row($result)) 
    				 { 
      				   echo "<option value='".$res->fields[0]."' >".utf8_encode($res->fields[1])."</option>";
	  				   $res->moveNext();	
	  				   //echo "loginuser: $row[0]  password: $row[1] loginregistro: $row[2] fechasys: $row[3]";
      				   //echo "\n\n";
    				 }
				   echo '</select>
				   <span id="msgbox0" style="display:none"></span></td>
			     </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left ><strong><B><font color=#ffffff size=2>Seleccione el Subproducto: </font></b></strong></td>
    			   <td width=60% ><select id=subproducto name=subproducto> </select>
                   <span id="msgbox" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>
				  <tr> 
                    <td width=40% align=left><strong><B><font color=#ffffff size=2>Valor Boleta: </font></b></strong></td>
    			    <td width=60%> <input type=text name=valorboleta id=valorboleta size=10 maxlength=1 readonly=true class=color1 >
					<strong><B><font color=#ffffff size=2></font></b></strong> 
					</td> 
				  </tr>
				</table>
				<br>*/
			    echo '<table width=70% border=0 cellspacing=0 cellpadding=0 class=text align=center>
  		         <tr>
				   <input type=hidden id=tiemposesion name=tiemposesion >
				   <input type=hidden id=estadotiempo name=estadotiempo value=0 >
                   <td width=30% align=left><strong><B><font color=#ffffff size=2>Digite la Cedula del Vendedor: </font></b></strong></td>
    			   <td width=70% ><div id=cedula name=cedula size=80 onKeyPress="return validar(event,3)" >';//<select id=cedula name=cedula >';
				     echo '<input type=hidden id=cedulaid name=cedulaid value="0">';
					 echo '<input type=hidden id=cedulaname name=cedulaname value="">';
					 echo '<input type=hidden id=control2 name=control2 value="1">';
					 //$sql="select id, cedula from clientes_inv order by cedula "; <input type=text id=cedulaid name=cedulaid value="0">
    			     //$res=$cnn->Execute($sql);//pg_query($cnn,$sql);
    				 //$row=pg_fetch_array($result);
    				 //echo $sql;
					 //echo $res->fields[1];
    				 //if username exists
				     /*echo '<option value=0 selected>+</option>';
	
    				 while (!$res->EOF)//while ($r = pg_fetch_row($result)) 
    				 { 
      				   echo "<option value='".$res->fields[0]."' >".utf8_encode($res->fields[1])."</option>";
	  				   $res->moveNext();	
	  				   //echo "loginuser: $row[0]  password: $row[1] loginregistro: $row[2] fechasys: $row[3]";
      				   //echo "\n\n";
    				 }
					 </select>*/
				   echo '</div><div id="cedula-result" style="clear:both"></div>
				   <span id="msgbox0" style="display:none"></span></td>
			     </tr>
			   </table>
			   <br>';
			   /*<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left id=dato name=dato><strong><B><font color=#ffffff size=2>Nombres: </font></b></strong></td>
    			   <td width=60%><input type=text name=nombres id=nombres size=30 maxlength=100 >
                   <span id="msgbox2" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left id=dato name=dato><strong><B><font color=#ffffff size=2>Apellidos: </font></b></strong></td>
    			   <td width=60%><input type=text name=apellidos id=apellidos size=30 maxlength=100 >
                   <span id="msgbox3" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left id=dato name=dato><strong><B><font color=#ffffff size=2>Dirección: </font></b></strong></td>
    			   <td width=60%><input type=text name=direccion id=direccion size=30 maxlength=100 >
                   <span id="msgbox4" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left id=dato name=dato><strong><B><font color=#ffffff size=2>Correo: </font></b></strong></td>
    			   <td width=60%><input type=text name=correo id=correo size=30 maxlength=100 >
                   <span id="msgbox5" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>
			   <table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
			     <tr>
                   <td width=40% align=left id=dato name=dato><strong><B><font color=#ffffff size=2>Teléfono: </font></b></strong></td>
    			   <td width=60%><input type=text name=telefono id=telefono size=10 maxlength=10 onKeyPress="return validar(event,3)">
                   <span id="msgbox6" style="display:none"></span></td> 
				 </tr>
			   </table>
			   <br>';
			   $p="86069529";
			   for($x=0; $x<5; $x++)
			   {
				 $codigo=microtime();//date("c");//date("Y-m-d H:i:s:u:B");  
			     $p1 = hash("crc32", $p.$codigo);
			     //$p2=crc32($p.$codigo);
				 //echo $codigo."= ".$p1." ".$p2."<br>";
			   }
			   //echo (int)$p;//"prueba";
			   //echo "<br>".md5("pruebo",false); 
               /*<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>
				  <tr> 
                    <td width=40% align=left><strong><B><font color=#ffffff size=2>Nuevo Subproducto: </font></b></strong></td>
    			    <td width=60%><input type=text name=subproducto id=subproducto size=40 maxlength=40 > 
					<span id="msgbox" style="display:none"></span></td>
				  </tr>
				</table>
				<br>
				echo '
				<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>
				  <tr> 
                    <td width=40% align=left><strong><B><font color=#ffffff size=2>Cupo Disponible: </font></b></strong></td>
    			    <td width=60%> <input type=text name=cupo id=cupo size=5 maxlength=5 readonly=true class=color1 >
					<strong><B><font color=#ffffff size=2></font></b></strong> 
					<span id="msgbox7" style="display:none"></span></td> 
				  </tr>
				</table>
				<br>
				<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>
				  <tr> 
                    <td width=40% align=left><strong><B><font color=#ffffff size=2>Cantidad a Vender: </font></b></strong></td>
    			    <td width=60%><input type=text name=cantidad id=cantidad size=3 maxlength=3 onKeyPress="return validar(event,3)" ><strong><B><font color=#ffffff size=2> </font></b></strong> 
					<span id="msgbox8" style="display:none"></span></td> 
				  </tr>
				</table>
				<br>';*/
				$fecha_sys= date("Y-m-d");
				echo '<input type=hidden id=fecha_sys name=fecha_sys value='.$fecha_sys.'>';
                echo '<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
				  <tr>
				  <input type=hidden id=usuario name=usuario value='.$nickname.'>';
				  $fechaini=date("Y-m-d");//restarosumardias(-1);//date("Y-m-d");
				  //echo date("Y-m-d h:i:s");
				  //$fecha_sys= date("Y-m-d");
				  //echo '<input type=hidden id=fecha_sys name=fecha_sys value='.$fecha_sys.'>';
                  echo '<td width=40% align=left ><strong><B><font color=#ffffff size=2>Fecha de Inicio: </font></b></strong></td>
    			    <td width=60%><p><input type=text id=fechaini name=fechaini value='.$fechaini.' ></p><span id="msgbox3" style="display:none"></span>
                    </td> 
				  </tr>
				</table>
				<br>
				<table width=60% border=0 cellspacing=0 cellpadding=0 class=text align=center>  
				  <tr>';
				  $fechafin=date("Y-m-d");//restarosumardias(-1);
                  echo '<td width=40% align=left ><strong><B><font color=#ffffff size=2>Fecha de Finalización: </font></b></strong></td>
    			    <td width=60%><p><input type=text id=fechafin name=fechafin value='.$fechafin.'></p><span id="msgbox4" style="display:none"></span>
                    </td> 
				  </tr>
				</table>
				<br>
				<div id=seleccion name=seleccion>
				<table width=100% border=0 cellspacing=0 cellpadding=0  >
				  <tr>				    
    			    <td align=center><input type=button name=btngenerar id=btngenerar value="Generar" style="margin-left:-10px; height:23px" class=buttons_aplicar onclick=reporte_recaudo();> <span id="loading"> <img src="images/loader.gif" border="0" width="15" height="15" > </span> <span id="msgbox" style="display:none"></span> </td>  
				  </tr>
				</table>
				</div>
				<br>
				<table width=70% border=0 cellspacing=0 cellpadding=0 class=text align=center id=tabla>  
				 
				</table>
			 </form>
          </td>
        </tr>
      </table></td>
    </tr>';
 
  }         

   include($plantilla); 
?>

		