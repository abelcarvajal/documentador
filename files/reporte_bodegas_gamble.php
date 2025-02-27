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
  /* $expire=time()-60;
  //echo $expire;
  setcookie("nickname","",$expire);
  setcookie("nivel","",$expire);
  setcookie("ID","",$expire); */
  
  $query="SELECT permisos FROM menu_reportes WHERE permisos ilike '%,".$nivel.",%' and enlace='reporte_bodegas_gamble.php' ";
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
	  //echo '<link rel="stylesheet" href="lib/upload/uploadfile_2.css" >';
    //echo '<script type="text/javascript" src="lib/upload/jquery.uploadfile.min.js"></script>';
	
    //echo '<script type="text/javascript" src="lista_clientes2.php?con=41"></script>';
	  //echo '<script type="text/javascript" src="lista_clientes2.php?con=4"></script>';
    //echo '<link rel="stylesheet" href="css/w3.css">';

    /* echo '<link href="lib/select2-4.1.0/dist/css/select2.css" rel="stylesheet"/>';
    echo '<script type="text/javascript" src="lib/select2-4.1.0/dist/js/select2.min.js"></script>'; */
    echo '<script type="text/javascript" src="lib/maskmoney/jquery.maskMoney.js"></script>';
    echo '<script>

	    function miles(valor)
	    {
	      /* var dato = valor.toString().split(".");
	      //alert(dato[0]+"--"+dato[1]);
	      var nums = new Array();
	      var simb = ","; //Ã‰ste es el separador
        var valor1="00";
	      if(dato[1]!=null)// || dato[1]===undefined)
	      {
	        valor=dato[0];		  	
	      }

	      valor = valor.toString();
	      valor = valor.replace(/\D/g, "");   //Ã‰sta expresión regular solo permitira ingresar números
	      nums = valor.split(""); //Se vacia el valor en un arreglo
	      var long = nums.length - 1; // Se saca la longitud del arreglo
	      var patron = 3; //Indica cada cuanto se ponen las comas
	      var prox = 2; // Indica en que lugar se debe insertar la siguiente coma
	      var res = "";
  
	      while (long > prox) 
	      {
	    	  nums.splice((long - prox),0,simb); //Se agrega la coma
	    	  prox += patron; //Se incrementa la posición próxima para colocar la coma
	      }
  
	      for (var i = 0; i <= nums.length-1; i++) 
	      {
	    	  res += nums[i]; //Se crea la nueva cadena para devolver el valor formateado
	      } 
	   
	      if(dato[1]!=null)// || dato[1]===undefined)
	      {
	        res +="."+dato[1];		
	      }
	      else
	      {
	        res +="."+valor1;	
	      }

	      return res; */

	      const exp = /(\d)(?=(\d{3})+(?!\d))/g;
        const rep = "$1,";
        let arr = valor.toString().split(".");
        arr[0] = arr[0].replace(exp,rep);
        return arr[1] ? arr.join("."): arr[0];  
	      
	    }
	
	    function borrar() 
	    {
	      $("#tabla1").empty();	
	      //$("#seleccion1").hide();
	      document.getElementById("control_paso").value=0;
	    }

			function borrar_campos(tipo)
	    {
        //alert("entra a borrar campos");
        if(tipo==1)
		    {
          $(".rls").val("").trigger("change");//para borrar seleccion
          //$(".homologacomo").val("").trigger("change");//para borrar seleccion
          //$(".homologaasig").val("").trigger("change");//para borrar seleccion
          //$(".homologacomo").empty().trigger("change");//para borrar toda la lista

          //campos modificar
          //$("#seleccion ").hide();
		
		      document.getElementById("bodega").value="";
		
        }
		  }

	    function validar(e,tipo, field) 
	    {   
        tecla = (document.all) ? e.keyCode : e.which;
	      //alert(tecla);	  
	    
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
        $("#fechames").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fechames").blur()}});
	      //$("#fechasaldo").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fechasaldo").blur()}});
		    //$("#fechasaldo2").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fechasaldo2").blur()}});
		    //$("#fechaini").datepick({dateFormat: "yyyy-mm-dd",maxDate: +0, onClose: function(dates){}});	
	      //$("#fecha_aplica").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fecha_aplica").blur()}});
        //$("#fechalog").datepick({dateFormat: "yyyy-mm-dd",maxDate: +0, onClose: function(dates){}});	
	      //$("#fechafin").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){borrar()}});	
	      //$("#fechaini2").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){controlfecha1()}});
	      //$("#fechafin2").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){controlfecha2()}});
        //$("#fechaini").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){}});
	      //$("#fechafin").datepick({dateFormat: "yyyy-mm-dd", onClose: function(dates){}});
	      
	      //$("#onClosePicker").datepick({ 
        //onClose: function(dates) { alert("Closed with date(s): " + dates); }, 
        //showTrigger: "#calImg"});
      });

	    function cargar_fecha() 
	    {
	      //console.log("entra a cargar fecha");	
	      //$("#fecha_aplica").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fecha_aplica").blur()}});
        //$("#fecha_aplica").datepick({dateFormat: "yyyy-mm",maxDate: "-1m", minDate: new Date(2019, 1-1, 1),onShow: $.datepick.monthOnly,onClose: function(dates){ $("#fecha_aplica").blur(),controlfecha_aplica()}});
        	 
	    }	
	    function propStopped(e) 
      {
        var msg = "";
        if ( e.isPropagationStopped() ) 
        {
	        var cedula = $("#cedula").val();
	        var cedulaid = $("#cedulaid").val();
	        var cedulaname = $("#cedulaname").val();
	        alert("algo11 ="+this.value+"--"+$("input[name=cedula]").val()+"=="+cedula+"=="+cedulaid+"=="+cedulaname);
          msg =  "called"
        } 
        else 
        {
	        var cedula = $("#cedula").val();
	        var cedulaid = $("#cedulaid").val();
	        var cedulaname = $("#cedulaname").val();
	        alert("algo12 ="+this.value+"--"+$("input[name=cedula]").val()+"=="+cedula+"=="+cedulaid+"=="+cedulaname);	
          msg = "not called";
        }
        $("#cedula-result").append( "<div>" + msg + "</div>" );
      }

      //var table;
      
      var url_symfony = "http://10.1.1.4:8094/";
      const carpeta_actual = window.location.href.split("/")[3];
      const ip_server= "'.$_SERVER['SERVER_ADDR'].'";
	    const ip_address = "'.$_SESSION['ip_address'].'"; //$("#ip_address").val();
	    const usuario = "'.$_SESSION['nickname'].'";// $("#usuario").val();
	    const Authentication = 
	    {
	      nickname :  "'.$_SESSION['nickname'].'"
	    };
	    //if(carpeta_actual == "consuertepruebas" && ip_server=="10.1.1.4")
	    if(ip_server=="10.1.1.4") 
	    {
        //url_symfony = "http://10.1.1.12:8094/";
	      url_symfony = "http://"+ip_server+":8094/";
      }
      else if(carpeta_actual == "consuertepruebas" )
	    {
        //url_symfony = "http://10.1.1.12:8094/";
	      url_symfony = "http://"+ip_server+":8094/";
      }

      var list_rls = []; //lista bodegas
	    var list_rlr = []; //lista raspe y listo registrados
   
			function cargar_listas() 
      {
	    	//alert("entra a cargar lista")
        let url = url_symfony; 
        url +="listaclientes";

		    list_rls = [];
        const dat =[
          {
            "con": "81",	
            "usuario": usuario,
            "ip_address": ip_address            
          }			
        ]        
        
        $.ajax(
        {
          url: url, 
          type: "POST", 
          async: false,               
          data: "json="+JSON.stringify(dat), //json1,//obj,//JSON.stringify(obj),
          dataType: "json",
          headers: {
            "Authentication": JSON.stringify(Authentication)
          },
          success: function(datos)
          { 
            //alert(datos);
            //var datos0=datos.split("^");
            //alert(JSON.stringify($(datos[0]["datos"])));
            //console.log($(datos[0]["datos"]));
            //alert($(datos[0]["datos"]).length);
                      
            if(datos[0]["status"]=="1")//if(datos0[0]==1)
            {                  
              //data1=JSON.stringify($(datos[0]["datos"]));
              let data1=$(datos[0]["datos"]);
              //console.log(data1);

              //document.createElement("`"+$(datos[0]["datos"]+"`");
              //const code = "list_rls=[{id:1081410615,text:1081410615 -  ANGELA VIVIANA PACHON ACHIPIZ},{id:40188021,text:40188021 -  ROSA TULIA MORALES TULIA MORALES}];" //$(datos[0]["datos"]);//"alert(`Hello World`); let x = 100";
              /* const F = new Function(code);
              console.log(F()); */ 
              /* var code = "var vendedores1 = {};vendedores1.results = [{id:1081410615,name:1081410615 - ANGELA VIVIANA PACHON ACHIPIZ}]; vendedores.total = vendedores.results.length;";
              $(document).ready(code); */
              //console.log("`"+$(datos[0]["datos"])+"`");
              //$(datos[0]["datos"]);
              //var data1= `"`+$(datos[0]["datos"])+`"`;
              //console.log(list_rls);
              //console.log(data1[0]);
              //$(datos[0]["datos"]);

              /* for (let value of Object.values(data4)) {
                alert(value); // John, then 30
              } */
              //let arr = data4.map(elemento => Object.entries(elemento));
              //console.log(arr);
              //let arr1 = arr.map(elemento => Object.entries(elemento));
              //console.log(Object.entries(data1));
              //console.log(Object.values(data1)); 
              /* var data7=Object.entries(data1);
              console.log(data7[0]); */
              //var data7 = [];
              //console.log(data1.length);
              let x=0;
              for (const [key, value] of Object.entries(data1)) 
              {
                //console.log(`${key} ${value}`); // "a 5", "b 7", "c 9"
                //console.log(`${key} ${value}`)  
                if(typeof value === "object" && x<=data1.length)
                {
                    list_rls.push(value);
                }
                x++;  
              }
              //console.log(list_rls);
              //data=arr; 
              /* var res = JSON.parse(data1);
              const data = res.productos.split(",");
              console.log(data); */
              //console.log(list_rls);
              //return list_rls;
              /* console.log($(datos[0]["datos"][0]));
              data2 = Object.entries(data1);
              console.log(data2[0]); */
              /*  let data3 = [{id:"1081410615",text:"1081410615 - ANGELA VIVIANA PACHON ACHIPIZ"},{id:"35260477",text:"35260477 - MARIA NELLY MONTENEGRO GUTIERREZ"},{id:"40373434",text:"40373434 - MARIA YOLANDA LEANO PINZON"}];
              console.log(data3); 
              return data3; */
            }
            else
            {	
              /* $(msg).fadeTo(200,0.1,function() //start fading the messagebox
              { 
                  //add message and change the class of the box and start fading
                  $(this).html(datos[0]["message"]).attr("class","alert alert-danger").fadeTo(200,1);
              }); */		
              return data;
            }  
          }
        }); 
		
		    $(".rls").select2(
		    {
		      data: list_rls,//buscar_vendedores(), //data2, //testData, //data1,
		      theme: "classic",
		      width: "500",
		      selectOnClose: false,
		      multiple: true,
		      //placeholder: $( this ).data("placeholder"),
		      placeholder: "Seleccione",
		      allowClear: true,
		      dropdownAutoWidth : true,
		      closeOnSelect: true,
		      maximumSelectionLength: 1,
		      debug: true,
		      // query with pagination
		      query: function(q)
		      {
		    	  var pageSize,
		    	  results,
		    	  that = this;
		    	  pageSize = 20; // or whatever pagesize
		    	  results = [];
		    	  if (q.term && q.term !== "")
		    	  {
		    	    // HEADS UP; for the _.filter function i use underscore (actually lo-dash) here
		    	    results = _.filter(that.data, function(e)
		    	    {
		    	  	  return e.text.toUpperCase().indexOf(q.term.toUpperCase()) >= 0;
		    	    });
		    	  }
		    	  else if (q.term === "")
		    	  {
		    	    results = that.data;
		    	  }
		    	  q.callback({
		    	    results: results.slice((q.page - 1) * pageSize, q.page * pageSize),
		    	    more: results.length >= q.page * pageSize,
		    	  });
		      },
		      escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
		      minimumInputLength: 0,
		      //templateResult: formatRepo, // omitted for brevity, see the source of this page
		      //templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
		      language: "es"
		    });
		    $(".rls").val("").trigger("change");//para borrar seleccion    
		   
      }
      //var vendedores1 = [];
      $(document).ready(function()
      { 
	      document.getElementById("tabla_p").width= "70%";	
	    
	      /* consultar_tabla(0);//consulta raspe y listos */
		    /* consultar_tabla(5);//consulta cociliaciones saldos creados en el día */
		    /* consultar_tabla(7);//consulta cociliaciones saldos modificadas en el día */
	    
	      var d = new Date();
	      var tiempo=d.getTime();
	      document.getElementById("tiemposesion").value=tiempo;
	      //sesion();
        //var data = [
        //var vendedores1 = [{value:"1081410615",label:"1081410615 - ANGELA VIVIANA PACHON ACHIPIZ"},{value:"35260477",label:"35260477 - MARIA NELLY MONTENEGRO GUTIERREZ"}];
        //var vendedores1 = [{id:1081410615,value:"1081410615 - ANGELA VIVIANA PACHON ACHIPIZ"},{id:35260477,value:"35260477 - MARIA NELLY MONTENEGRO GUTIERREZ"}];
	  	
	      $(this).mousemove(function(e)
	      { 
	      	//alert("prueba")
	        var t_inv = $("#t_inv").val();
	        var tiempo = $("#tiemposesion").val();
	  	    var estadotiempo = $("#estadotiempo").val();
             
	  	    var d = new Date();
	  	    var tiempo1=d.getTime();
	  	    var tiempo2=parseFloat(tiempo1-tiempo);
	  	    
	  	    if(tiempo2<=t_inv)//if(tiempo2<=1050000)//350000
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
	      $("#loading_1 ").hide();
		    $("#loading_0").hide();
		    $("#loading_2").hide();
		    $("#loading_3").hide();
		    $("#loading_4").hide();
		    $("#loading_5").hide();
        $("#seleccion ").hide();
		    $("#seleccion2 ").hide();
		    $("#seleccion3 ").hide();
        $("#f1_upload_process").hide();
	      //$("#seleccion1").hide();
	      //$("#seleccion4").hide();
	      //$("#loading1 ").hide();
	      var usuario = $("#usuario").val();
	      //var tercero=$("#tercero").val();
	      //var producto=$("#producto").val();
	      //var cedula=$("#cedula").val();
	      //var ccostos=$("#ccostos").val();
	      //var fechaini=$("#fechaini").val();
	      //var fechafin=$("#fechafin").val();
	      const Authentication = 
	      {
	  	    nickname :  "'.$_SESSION['nickname'].'"
	      };
                       
        let url = url_symfony; 
        url +="listaclientes";
		
		    cargar_listas();
        console.log(list_rls);

        /* $(".homologa").select2(
        {
          data: list_rls,//buscar_vendedores(), //data2, //testData, //data1,  
          width: "500",
          selectOnClose: false,
          multiple: true,
          //placeholder: $( this ).data("placeholder"),
          placeholder: "Seleccione",
          allowClear: true,
          dropdownAutoWidth : true,
          closeOnSelect: true,
          maximumSelectionLength: 1,
          debug: true,
          // query with pagination
          query: function(q) 
          {
            var pageSize,
            results,
            that = this;
            pageSize = 20; // or whatever pagesize
            results = [];
            if (q.term && q.term !== "") 
            {
              // HEADS UP; for the _.filter function i use underscore (actually lo-dash) here
              results = _.filter(that.data, function(e) 
              {
                return e.text.toUpperCase().indexOf(q.term.toUpperCase()) >= 0;
              });
            } 
            else if (q.term === "") 
            {
              results = that.data;
            }
            q.callback({
              results: results.slice((q.page - 1) * pageSize, q.page * pageSize),
              more: results.length >= q.page * pageSize,
            });
          },                      
          escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
          minimumInputLength: 0,
          //templateResult: formatRepo, // omitted for brevity, see the source of this page
          //templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
          language: "es"
        }); */   
		
		    /* $(".pnr").select2(
		    {
		      data: list_rls,//buscar_vendedores(), //data2, //testData, //data1,
		      theme: "classic",
		      width: "500",
		      selectOnClose: false,
		      multiple: true,
		      //placeholder: $( this ).data("placeholder"),
		      placeholder: "Seleccione",
		      allowClear: true,
		      dropdownAutoWidth : true,
		      closeOnSelect: true,
		      maximumSelectionLength: 1,
		      debug: true,
		      // query with pagination
		      query: function(q)
		      {
		    	  var pageSize,
		    	  results,
		    	  that = this;
		    	  pageSize = 20; // or whatever pagesize
		    	  results = [];
		    	  if (q.term && q.term !== "")
		    	  {
		    	    // HEADS UP; for the _.filter function i use underscore (actually lo-dash) here
		    	    results = _.filter(that.data, function(e)
		    	    {
		    		    return e.text.toUpperCase().indexOf(q.term.toUpperCase()) >= 0;
		    	    });
		    	  }
		    	  else if (q.term === "")
		    	  {
		    	    results = that.data;
		    	  }
		    	  q.callback({
		    	    results: results.slice((q.page - 1) * pageSize, q.page * pageSize),
		    	    more: results.length >= q.page * pageSize,
		    	  });
		      },
		      escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
		      minimumInputLength: 0,
		      //templateResult: formatRepo, // omitted for brevity, see the source of this page
		      //templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
		      language: "es"
		    });
		    $(".pnr").val("").trigger("change");//para borrar seleccion */
		
		    $("#rls_asig").change(function()
		    {
		      let rls_asig=$(this).attr("value");
		      //alert(rls_asig);
		      let msg="#msm1";
		      $(msg).css("display","none");
		      if(rls_asig=="")
		      {
		    	  //$("#seleccion ").hide();
		    	  //$(".homologacomo2").val("").trigger("change");//para borrar seleccion
            document.getElementById("bodega").value="";
		    	}
		      else
		      {
		    	  let list=rls_asig;//.split("|");
		        //alert(list[1]);  		
            document.getElementById("bodega").value=list;	   	  
		    	 		    	 
		      }
		    });
      
	      document.onkeypress = function(event) 
	      {
		      event = (event || window.event);
		      if (event.keyCode == 123)
		        return false;
		      if (event.which == 123)
		        return false;
	      }
	      	  
	      document.onmousedown = function(event) 
	      {
		      event = (event || window.event);
		      if (event.keyCode == 123) 
		        return false;
		      if (event.which == 123) 
		        return false;
	      }
	      	  
	      document.onkeydown = function(event) 
	      {
		      event = (event || window.event);
		      if (event.keyCode == 123) 
		        return false;
		      if (event.which == 123) 
		        return false;
	      }
      
	      onkeydown = e => 
	      {
		      let tecla = e.which || e.keyCode;
		      if ( e.shiftKey ) 
		      {
		        e.preventDefault();
		        e.stopPropagation();
		      }
	      }
	      /*var tipo="input[name=tipo]:radio";
        $(tipo).change(function()
        {
          //var p=$("#cede_1").val($(this).is(":checked"));
          var tipo1=$(this).attr("checked");
        
          //alert(tipo1);

          borrar();
    
          if(tipo1==true)
          {
          }
          else
          {
          }
      
        });
	      //var tipo=$("#tipo").val();
	      
	      /*if(tercero==0)
	      {
	        $("#seleccion ").show(); 
	      }
	      else
	      {
	        $("#seleccion ").show();  
	      }
	      
	      $("#tercero").change(function()
        {
		      var tercero=$(this).attr("value");
		      if(tercero==0)
	        {
		        $("#seleccion ").show(); 
	        }
	        else
	        {
		        $("#seleccion ").show();  
	        }
        
        });
	        
	      /* if(tercero==0)
	      {
		      $("#producto").load("datos.php", {con : 4.6, tercero : 0, usuario : usuario}); 
		      //document.getElementById("valorboleta").value=0;
		      //document.getElementById("cupo").value=0;
	      }
	      else
	      {
	        $("#producto").load("datos.php", {con : 4.6, tercero : tercero, usuario : usuario});
	      }
	       
	      $("#tercero").change(function()
        {
	        var tercero=$(this).attr("value");  
	        $("#producto").load("datos.php", {con : 4.6, tercero : tercero, usuario : usuario});
	        //document.getElementById("valorboleta").value=0;
	        //document.getElementById("cupo").value=0;
	      });	        
	      
	      ///alert(subproducto);
	      if(producto==0 || producto==null)
	      {  //alert("algo");
	        //$("#cupo").val("0");//load("datos.php", {con : 4.2, producto : 0, usuario : usuario}); 
	        document.getElementById("valorboleta").value=0;
	        document.getElementById("cupo").value=0;
	        
	        //$("#cupo").load("datos.php", {con : 3.5, subproducto : 0, usuario : usuario}); 
	      }
	      else
	      {
	        //$("#cupo").load("datos.php", {con : 3.5, producto : producto, usuario : usuario});
	      	$.ajax(
	        {
            url: "datos.php",
            type: "POST",
            data: "submit=&con=4.4&producto="+producto+"&usuario="+usuario,
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
            data: "submit=&con=4.1&producto="+producto+"&usuario="+usuario,
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
            data: "submit=&con=4.4&producto="+producto+"&usuario="+usuario,
            success: function(datos)
            {//ConsultaDatos();
              //alert(datos);
	      	    document.getElementById("valorboleta").value=datos;
	          }
          });	
	      		      	
        });*/				
    
		  	/* $(".testSelect").on("select2:select", function (e) {
          console.log("select done", e.params.data);
        }); */
			});	
    
      /*$(function() 
	    {	  
	      //var usuario = $("#usuario").val();
	      // Do not autocomplete initial value, and handle non-existent values
	      //var tipolista =$("#tipolista").val(); 
	      //alert(tipolista);
	      $("#usuario1").flexbox(vendedores, 
	      {
	        autoCompleteFirstMatch: false,
		      width: 400,
		      noResultsText: "",
		      onSelect: function() 
	    	  {
		        //$("#usuario-result").html("You selected " + this.value + ","  + "which has a hidden value of " + $("input[name=cedula]").val());
		        //alert(this.value+"--"+$("input[name=usuario]").val()+"--"+this.value.datos);
		      
		        document.getElementById("usuario1").value=this.value;
		        //document.getElementById("usuario_input").value=this.value;
		        //document.getElementById("usuario_input").value=$("input[name=usuario1]").val();
		        document.getElementById("usuarioid1").value=$("input[name=usuario1]").val();
		        document.getElementById("usuarioname1").value=this.value;
		        //alert("algo "+this.value);
		      }
	      });
	    	  
	      $("#usuario1_input").blur(function()
	      {  //blur change(function()  focusout(function()  live("focusout", function()
		      document.getElementById("usuario1").value=this.value; 			   
	        //$("#factura-result").html("The value passed when the form is " + "submitted is " + $("input[name=factura]").val());
  	    });
		  		
	      $("#usuario2").flexbox(vendedores, 
	      {
		      autoCompleteFirstMatch: false,
		      width: 400,
		      noResultsText: "",
		      onSelect: function() 
	    	  {
		        document.getElementById("usuario2").value=this.value;
		        document.getElementById("usuarioid2").value=$("input[name=usuario2]").val();
		        document.getElementById("usuarioname2").value=this.value;
		      }
	      });
  
	      $("#usuario2_input").blur(function()
	      {  //blur change(function()  focusout(function()  live("focusout", function()
		      document.getElementById("usuario2").value=this.value; 			   
	        //$("#factura-result").html("The value passed when the form is " + "submitted is " + $("input[name=factura]").val());
		    });	  			
	    }); */          

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
	
	    function controlfecha_aplica()
	    {
	      //alert("entra contolfecha aplica")	
	      var fecha_aplica = $("#fecha_aplica").val();
	      
	      $("#msgbox4").empty(); 
	      $("#msgbox4").css("display","none");	 
	      
	      if(fecha_aplica=="")
	      {
	    	  $("#msgbox4").fadeTo(200,0.1,function() //start fading the messagebox
	        { 
	    	    //add message and change the class of the box and start fading
	    	    $(this).html("Por Favor Seleccione una Fecha Aplicar").addClass("messageboxerror").fadeTo(200,1);
	    	  });	    	  
	      }  	      
	    }
	
      function controlfecha1()
	    {
	      var fechaini = $("#fechaini").val();
	      var fechafin = $("#fechafin").val();
	      if(fechaini !="" && fechafin != "")
        { 
	        var f=comparaFecha(fechaini,fechafin);
	        
	        $("#msgbox3").empty(); 
	        $("#msgbox3").css("display","none");
	        $("#msgbox4").empty(); 
	        $("#msgbox4").css("display","none");
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
	    }
	
	    function controlfecha2()
	    {
	      var fechaini = $("#fechaini").val();
	      var fechafin = $("#fechafin").val();
	      
          if(fechaini != "" && fechafin != "" )
          {
	        var f=comparaFecha(fechafin,fechaini);
	        $("#msgbox3").empty(); 
	        $("#msgbox3").css("display","none");
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

	    function control_buton(estado)//bloquea o desbloquea los botones 0 bloquea y 1 desbloquea
	    {
	      if(estado==0)
	      {	
	    	  $("#btngenerar").attr("disabled",true);
	    	  $("#btngenerar1").attr("disabled",true);
	    	  $("#btngenerar2").attr("disabled",true);
	      }
	      else
	      {
	    	  $("#btngenerar").attr("disabled",false);
	    	  $("#btngenerar1").attr("disabled",false);
	    	  $("#btngenerar2").attr("disabled",false);
	      }	
	    }
      	  
	    function sumar_total(x)
	    {
	      var total = $("#valor_total1").val();
	      var valor = $("#premio_"+x).val();
		    var saldo=0;
		    //alert(total+"--"+valor);
		    if( $("#checkbox_"+x).prop("checked") ) 
		    {
              saldo=parseFloat(total)+parseFloat(valor);
		    }
		    else
		    {
		      saldo=parseFloat(total)-parseFloat(valor);
		    }
		    //alert(total+"--"+valor+"--:" +saldo);
		    document.getElementById("valor_total").value=miles(saldo.toFixed(2));
		    document.getElementById("valor_total1").value=saldo.toFixed(2);
	    } 

			function buscar_registros(tipo)
	    { 
	      //alert("ip_server:"+ip_server);	
	      //alert("algo"+url_symfony);
		    var btn="#btn_buscar"+tipo;
		    var bodega="";
		    var msg="#msm_"+tipo;
	      var loa="#loading_"+tipo;
		    var f=0;
		    if(tipo==2)
		    {          
		      bodega = $("#bodega").val();  
		    }
		
		    $(btn).attr("disabled",true); 
	      //var usu_homo = $("#usu_homo").val();
        //var homo = $("#homo").val();        
        let ip_address = $("#ip_address").val();
	      //alert("usu_homo="+usu_homo[0]+" homo="+homo[0]+" fechaini="+fecha_ini);
	      //borrar_campos();
	      //var usuario = $("#usuario").val();	  
	      //var fecha_fin = $("#fechafin").val();
	      var fecha_sys = $("#fecha_sys").val();
	    
	      //alert(ip_address+"-"+usuario);
	     
	      var con=1;	    
	      
	      $(msg).css("display","none");
	      
	      if(usuario=="")
	      {  //alert("prueba");
	  	    $(msg).fadeTo(200,0.1,function() //start fading the messagebox
	        { 
	  	      //add message and change the class of the box and start fading
	  	      $(this).html("Error: Debe salir del Sistema y Volver a Entrar").attr("class","alert alert-danger").fadeTo(200,1);
	  	    });
	  	    con=0;
	      }		
		    else if(tipo==2)
		    {
		      if(bodega=="" || bodega === undefined)
		      {
		        $(msg).fadeTo(200,0.1,function() //start fading the messagebox
		        { 
		      	  //add message and change the class of the box and start fading
		      	  $(this).html("Por Favor Seleccione la Bodega.").attr("class","alert alert-danger").fadeTo(200,1);//.addClass("messageboxerror").fadeTo(200,1);
		        });
		        con=0;
		      }		      
		    }   
	  
	      //alert(usuario+"-"+fecha_sys+"-"+bodega);	
	      if(con==1)
        {
	  	    //alert("algo");
  	      
          $(loa).ajaxStart(function()
          {
            $(this).show();
          }).ajaxStop(function()
          {
            $(this).hide();
          }); 
          
          //form.submit();
	  	    let url = url_symfony;
          
	  	    const dat =[
	  	      {
	  	        "con": "27",	
	  	        "usuario": usuario,
	  	        "ip_address": ip_address,
	  	        "fechasys": fecha_sys,
              "bodega": bodega,			  
              "tipo": tipo
	  	      }			
	  	    ]
  
	  	    url +="datos21";
	  	    //alert(url);		
	  	    $.ajax(
	  	    {
	  	      url: url, //"imprimir_reporte_venta_vendedor.php",
	  	      type: "POST",
	  	      //data: "submit=&usuario="+usuario+"&fecha_sys="+fecha_sys+"&ip_address="+ip_address+"&tipo=0",
	  	      data: "json="+JSON.stringify(dat), //json1,//obj,//JSON.stringify(obj),
	  	      dataType: "json",
            headers: {
                "Authentication": JSON.stringify(Authentication)
            },
	  	      success: function(datos)
	  	      {                     
	  	        //alert(datos[0]["status"]);
	  	        //alert(JSON.stringify(datos));
	  	        //var datos1=JSON.stringify(datos);//datos.split("|");
      
	  	    	  //datos.msm, datos.code, datos.archivo
	  	    	  if(datos[0]["status"]=="1")//(datos1[1]==1)
	  	        {
	  	    	    //window.location = "start.php?con="+con;
	  	    	    $(msg).fadeTo(200,0.1,function()  //start fading the messagebox
	  	    	    {
	  	    	      $(this).html(datos[0]["message"]).attr("class","alert alert-success").fadeTo(200,1,
	  	    	      function()
	  	    	      {
	  	  	          //window.open("datos7.php?con=25.8&acta="+datos1[1]+"&tipo=2","_blank");
	  	  	  	      //window.open("datos7.php?con=25.8&acta="+datos1[1]+"&tipo=2&v_n=2&visual=0&id_s=0","_blank");
	  	  		        					
 					          const arc =[
	  	  		          {					    
	  	  		            "acta": datos[0]["archivo"],
	  	  		            "tipo": "8",
	  	  		            "v_n": "0",
	  	  		            "visual": "0",
	  	  			          "id_s": "0",
	  	  			          "borrar": "0"
	  	  		          }			
	  	  		        ] 
	  	  		        window.open(url_symfony+"datos/descargar_archivo?json="+JSON.stringify(arc),"_blank");
	  	  		        //[{con:25.8,acta:"+datos[0]["archivo"]+,tipo:2,v_n:2,visual:0,id_s:=0}]
	  	  		    	  
					 		      setTimeout(function() 
	  	  	  	      {
	  	  		          $(msg).css("display","none");
	  	  		          $(btn).attr("disabled",false);
					            //$("#fechames").attr("disabled",false); 
	  	  		          //$("#btngenerar1").attr("disabled",false); 
					            //borrar_campos(0);
	  	  		          //consultar_tabla(tipo);
                      borrar_campos(tipo); 
	  	  		          //document.location="reporte_papeleria_ccostos_reportes.php";
	  	  	  	      },3000);                    
	  	  		      });
	  	  	      });       
	  	  	    }
	  	        else if(datos[0]["status"]=="0")//(datos1[0]==0)
	  	  	    { 	  	  	    
	  	  	      $(msg).fadeTo(200,0.1,function() //start fading the messagebox
	  	  	      { 
	  	  	        //add message and change the class of the box and start fading
	  	  	        $(this).html(datos[0]["message"]).attr("class","alert alert-danger").fadeTo(200,1);
	  	  	      });
	  	          $(btn).attr("disabled",false);
				        //$("#fechames").attr("disabled",false);
	  	  	    	//$("#btngenerar1").attr("disabled",false); 
	  	  	    }			 
	  	        else 
	  	  	    { 
	  	  	      //alert("Error: Verifique su Contraseña Antigua");
	  	  	      $(msg).fadeTo(200,0.1,function() //start fading the messagebox
	  	  	      { 
	  	  	        //add message and change the class of the box and start fading
	  	        	    $(this).html("Error: Al Tratar de Asignar Usuario Homologado.").attr("class","alert alert-danger").fadeTo(200,1);
	  	  	      });
	  	  	      $(btn).attr("disabled",false);
				        //$("#fechames").attr("disabled",false);
	  	  	      //$("#btngenerar1").attr("disabled",false);
	  	  	    }
	  	      },
            error: function(jqXHR, textStatus, errorThrown) 
            {
              // console.log(jqXHR);
              if (jqXHR.status === 401) 
              {
                console.log("Error 401 en la solicitud AJAX: " + textStatus + " - " + errorThrown);
                //$("#res_msm").show();
                //$("#res_msm").html("<div class=\"alert alert-danger\" role=\"alert\" ><font size=4><center><h3>" + textStatus + " - " + errorThrown+ " - " +jqXHR.responseJSON.datos+".</h3></center></font></div>");
                /* setTimeout(function() 
                {
                  //window.location.href = "logout.php";
                },5000); */
              } 
	  	  	    else 
              {
                console.log("Error en la solicitud AJAX: " + textStatus + " - " + errorThrown);
                //$("#res_msm").show();
                // $("#res_msm").html("Error en la solicitud AJAX: " + textStatus + " - " + errorThrown);
                //$("#res_msm").html("<div class=\"alert alert-danger\" role=\"alert\" ><font size=4><center><h3>Error en la solicitud AJAX: " + textStatus + " - " + errorThrown+ " - " +jqXHR.responseJSON.datos+".</h3></center></font></div>");
              }
            }
	  	    });				 
	      }
	      else
	      {
	  	    $(btn).attr("disabled",false);
		      //$("#fechames").attr("disabled",false);
	  	   
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
	  .tbodyDiv{
		max-height: clamp(30em,10vh,250px);
		max-width: clamp(120em,10vh,250px);
		overflow: scroll;
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
    global $nivel, $nickname, $cnn,$t_inv, $cnn2, $ip_address,$h_c;

	
    echo '<tr> 
      <td height="10" colspan="2"  class="text"><table width="90%" align="center" class="text">
        <tr> 
          <td> 
            <p class="title" align=center> <strong><B><font color=#ffffff>Bodegas Gamble</font></B></strong></p>
			      <br>
			      <!--form id="forma" name="forma"  action="verificacion_ventas_otros_upload.php" target="upload_target"  method="post" enctype="multipart/form-data" -->';
			      echo "<input type=hidden id=usuario name=usuario value='".$nickname."'>
			      <input type=hidden id=tiemposesion name=tiemposesion >
			      <input type=hidden id=estadotiempo name=estadotiempo value=0 >
			      <input type=hidden id=ubicacion name=ubicacion >
			      <input type=hidden id=t_inv name=t_inv value='".$t_inv."'>
			      <input type=hidden id=cant_paso1 name=cant_paso1 value='0'>
			      <input type=hidden id=control_paso name=control_paso value='0'>			  
			      <input type=hidden id=ip_address name=ip_address value='".$ip_address."'>
            <input type=hidden id=bodega name=bodega value='0'>";
            $fecha_sys= date("Y-m-d");
			      $fechac=date("Y-m");
			      //$fec=explode("-",$fechac);
			      $fechac=$fechac.'-01';//$fec[0]."-".$fec[1]."-01";
			      $fechac=$h_c->operacion_fecha($fechac,-1);
			      unset($fec); 
			      $fec=explode("-",(string)$fechac);
			      $fecha_mes=$fec[0]."-".$fec[1];
            echo '<input type=hidden id=fecha_sys name=fecha_sys value='.$fecha_sys.'>';
			      /* if($nickname=='CP86069529' || $nivel==38 || $nickname=='CP40328913')
			      { */
            echo '<div class="container">
			        <div class="accordion" id="accordionExample">
			          <div class="accordion-item">
				          <h2 class="accordion-header" id="headingOne">
				            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
				  	        1. Reporte General Bodegas
				            </button>
				          </h2>
				          <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
				            <div class="accordion-body">
				  	          <form id="form_recargas" name="form_recargas" method="post">
				  	            <div class="row align-items-center">				  	  	          
                          <div class="form-row" >
				  	  	            <div class="form-group col-md-12">						      
				  	  	  	          <center>
				  	  	  	            <button type="button" id="btn_buscar_0" class="btn btn-primary" onclick="buscar_registros(0)" >Generar</button>                                                                                                         
				  	  	  	            <span id="loading_0"> <img src="images/loader.gif" border="0" width="15" height="15" > </span>
				  	  	  	          </center>
				  	  	  	          <br>
				  	  	  	          <div class="alert alert-danger" role="alert" id="msm_0" style="display:none"></div>                                                                                
				  	  	            </div> 					   
				  	              </div>
						  	  			</div>	                          				      	
				  	          </form>
				            </div>
				          </div>
			          </div>	
								<div class="accordion-item">
				          <h2 class="accordion-header" id="heading2">
				            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
				  	        2. Reporte Distribución Bodegas
				            </button>
				          </h2>
				          <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#accordionExample">
				            <div class="accordion-body">
				  	          <form id="form_recargas" name="form_recargas" method="post">
				  	            <div class="row align-items-center">				  	  	          
                          <div class="form-row" >
				  	  	            <div class="form-group col-md-12">						      
				  	  	  	          <center>
				  	  	  	            <button type="button" id="btn_buscar_1" class="btn btn-primary" onclick="buscar_registros(1)" >Generar</button>                                                                                                         
				  	  	  	            <span id="loading_1"> <img src="images/loader.gif" border="0" width="15" height="15" > </span>
				  	  	  	          </center>
				  	  	  	          <br>
				  	  	  	          <div class="alert alert-danger" role="alert" id="msm_1" style="display:none"></div>                                                                                
				  	  	            </div> 					   
				  	              </div>
						  	  			</div>	                          				      	
				  	          </form>
				            </div>
				          </div>
			          </div>	 	      
			          <div class="accordion-item">
				          <h2 class="accordion-header" id="heading2">
				            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
				  	        3. Reporte Consolidados Bodega
				            </button>
				          </h2>
				          <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#accordionExample">
				            <div class="accordion-body">
				  	          <form id="form_recargas" name="form_recargas" method="post">
				  	            <div class="row align-items-center">
				  	  	          <div class="col-2 col-sm-9"> 
                            <div class="row mb-1">
                              <label for="inputFecha" class="input-group-text col-sm-4 col-form-label">Seleccione la Bodega:</label>
                              <div class="col-sm-3" >                                  
                                <select id=rls_asig name=rls_asig class="rls">
				  				              </select>	                                    
                              </div>
                            </div>                               
				  	  	          </div>
                          <div class="form-row" >
				  	  	            <div class="form-group col-md-12">						      
				  	  	  	          <center>
				  	  	  	            <button type="button" id="btn_buscar_1" class="btn btn-primary" onclick="buscar_registros(2)" >Generar</button>                                                                                                         
				  	  	  	            <span id="loading_2"> <img src="images/loader.gif" border="0" width="15" height="15" > </span>
				  	  	  	          </center>
				  	  	  	          <br>
				  	  	  	          <div class="alert alert-danger" role="alert" id="msm_2" style="display:none"></div>                                                                                
				  	  	            </div> 					   
				  	              </div>
						  	  			</div>	                          				      	
				  	          </form>
				            </div>
				          </div>
			          </div>  
			 	      </div>
			      </div>';             
			      /* }
			      else
			      {
				    echo '<div class="alert alert-danger text-center" role="alert">
				      <strong>SU USUARIO NO TIENE PERMISOS PARA ESTE APLICATIVO</strong>
			        </div>';
			      } */	
			      echo '<!--/form-->
          </td>
        </tr>
      </table></td>
    </tr>';
 
  }         

  //echo '<link href="lib/bootstrap-4.6.2/css/bootstrap.min.css" rel="stylesheet"/>';
  //echo '<script src="lib/Bootstrap4.6.1/jquery-3.6.0.js"></script>';
  
  /* echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">';
  echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>'; 
   */
  /* echo '<link href="lib/bemovil/bootstrap.css" rel="stylesheet"/>';
  echo '<script src="lib/bemovil/jsquery.js"></script>';
  echo '<script src="lib/bemovil/bootstrap.js"></script>'; */

  /* echo '<link rel="stylesheet" href="lib/bemovil/fontawesome-all.min.css">';
  echo '<link rel="stylesheet" href="lib/bemovil/common-1.css">'; */
  /* echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">';
  echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>'; 
   */
  include($plantilla5); 
  // echo '<script src="lib/bootstrap-4.6.2/js/bootstrap.bundle.min.js"></script>';
  //echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">';
  //echo '<link href="lib/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>';
  echo '<link href="css/custom.css" rel="stylesheet"/>';
  //echo '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
  echo '<link href="lib/select2-4.1.0/dist/css/select2.min.css" rel="stylesheet" />';
  //echo '<link href="lib/select2-4.1.0/dist/css/select2.css" rel="stylesheet"/>';
  //echo '<link href="lib/flexbox/FlexBox/css/jquery.flexbox.css" rel="stylesheet" type="text/css" >';
  //echo '<link rel="stylesheet" href="lib/choices-9.0.1/public/styles/choices.min.css" />';
  

  //echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />';
  //<!-- Or for RTL support -->
  //echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />';

  echo '<script src="lib/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
  //echo '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
  echo '<script src="lib/select2-4.1.0/dist/js/select2.min.js"></script>';

  echo '<script src="lib/datatables_2.0.3/js/dataTables.min.js"></script>';
  echo '<link href="lib/datatables_2.0.3/css/dataTables.min.css">';
  echo '<script src="lib/datatables_2.0.3/js/dataTables.bootstrap5.min.js"></script>';
  echo '<link href="lib/datatables_2.0.3/css/dataTables.bootstrap5.min.css">';
  
  //echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>';
  //echo '<script src="lib/choices-9.0.1/public/scripts/choices.min.js"></script>';
  //echo '<script type="text/javascript" src="lib/select2-4.1.0/dist/js/select2.js"></script>';
  //echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">';
  //echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>'; 
  
  
?>

		