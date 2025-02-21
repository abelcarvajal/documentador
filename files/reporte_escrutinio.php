
<?php
  include("include.php");

  session_start();
  $query="SELECT usuario FROM sesiones_usuarios_reportes WHERE hash='$ID' and desconexion is NULL ";
  $res = $cnn->Execute($query);
  $num_rows = $res->RecordCount();


  if(!$num_rows)
  {
    header("Location: index.php");
  }

  $query="SELECT permisos FROM menu_reportes WHERE permisos ilike '%,".$nivel.",%' and enlace='reporte_escrutinio.php' ";
  $res = $cnn->Execute($query);

  $num_rows2 = $res->RecordCount();


  if($num_rows2==0)
  {
	header("Location: logout.php");
  }

  function script1()


  {
    echo '<script src="lib/alertify/alertify.min.js"></script>';
    echo '<link rel="stylesheet" href="lib/alertify/css/alertify.min.css" />';
    echo '<link rel="stylesheet" href="lib/alertify/css/themes/default.min.css" />';
    echo '<script src="lib/gijgo@1.9.14/gijgo.min.js" type="text/javascript"></script>';
    echo '<link href="lib/gijgo@1.9.14/gijgo.min.css" rel="stylesheet" type="text/css" />';
    echo '<script src="lib/gijgo@1.9.14/messages.es-es.js" type="text/javascript"></script>';






	echo '<script>


	let ip_symfony = "http://10.1.1.4:8094/";
        const carpeta_actual = window.location.href.split("/")[3];
        const ip_server= "'.$_SERVER['SERVER_ADDR'].'";
        if(carpeta_actual == "consuertepruebas")
          ip_symfony = "http://"+ip_server+":8094/";

	let obj=[];

	const Authentication = {
		nickname: "'. $_SESSION['nickname'] .'"
	};
	objeto = [];
	var idCodigo="";
	idCodigo ;
	var	nombre = "";
	var	direccion = "";
	var	zona = "";
	var	ipalarma ="";
	var	ipradio = "";
	var	ipdv = "";
	var	estadocamara = "";
	var	estado = "";

	var mostrar_fecha=1;



    $(document).ready(function()
    {
		if (mostrar_fecha == 1 )
		{
           $("#selec_fecha_1").hide();
		}
		  let datepicker, config, today, lastday,yesterday,twoDaysAgo;
      today = new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
      lastday = new Date(new Date().getFullYear(), new Date().getMonth(), new Date(today.getFullYear(), today.getMonth(), 0).getDate());
      yesterday = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);  // Set minDate to yesterday
      twoDaysAgo = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 3);
         config = {
             locale: "es-es",
             uiLibrary: "bootstrap5",
             format: "yyyy-mm-dd",
             maxDate: today,
        };
         config2 = {
             locale: "es-es",
             uiLibrary: "bootstrap5",
             format: "yyyy-mm-dd",
             maxDate: today,
             minDate: twoDaysAgo,
        };
        config3 = {
          locale: "es-es",
          uiLibrary: "bootstrap5",
          format: "yyyy-mm-dd",
        };
        config4 = {
          locale: "es-es",
          uiLibrary: "bootstrap5",
          format: "yyyy-mm-dd",
          maxDate: yesterday,
          minDate: yesterday ,
          };
        config5 = {
          locale: "es-es",
          uiLibrary: "bootstrap5",
          format: "yyyy-mm-dd",
          minDate: yesterday ,
          };
        config6 = {
            locale: "es-es",
            uiLibrary: "bootstrap5",
            format: "yyyy-mm-dd",
            maxDate: today,
            minDate: today  ,
          };


		datepicker = $("#fechav").datepicker(config2);
    datepicker = $("#fechai").datepicker(config);
		datepicker = $("#fecha_r").datepicker(config);
		datepicker = $("#fechaf").datepicker(config);
		datepicker = $("#fecha_r2").datepicker(config);
		datepicker = $("#fecha_p1").datepicker(config3);
		datepicker = $("#fecha_p2").datepicker(config3);
		datepicker = $("#fecha_con").datepicker(config4);//config4
		datepicker = $("#fecha_resultado").datepicker(config);
		datepicker = $("#fecha_resultado2").datepicker(config);
		datepicker = $("#fecha_balota").datepicker(config5);
		datepicker = $("#fecha_hor").datepicker(config6);//config6
		datepicker = $("#fecha_archivo").datepicker(config);
		datepicker = $("#fecha_his").datepicker(config);
		datepicker = $("#fecha_cargue").datepicker(config);
		datepicker = $("#fecha_imagen").datepicker(config);
		var d = new Date();
		var tiempo=d.getTime();
		document.getElementById("tiemposesion").value=tiempo;
		$("#seleccion ").show();
		var data = [{ id: 0, text: "Seleccione" }, { id: 1, text: "bug" }, { id: 2, text: "duplicate" }, { id: 3, text: "invalid" }, { id: 4, text: "wontfix" }];

		$(this).mousemove(function(e)
		{
			var t_inv = $("#t_inv").val();
			var tiempo = $("#tiemposesion").val();
			var estadotiempo = $("#estadotiempo").val();

			var d = new Date();

			var tiempo1=d.getTime();
			var tiempo2=parseFloat(tiempo1-tiempo);

			if(tiempo2<=t_inv)//<=350000)
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

    $("#alert-confirmacion").hide();
    $("#alert-consulta").hide()
    $("#alert-negado").hide();
    $(".cargando").hide();
    $("#loading").hide();
    $("#loading2").hide();
    $("#btn_revision").hide();
    $("#tabla_registro6").hide();
		var menu =0;


		if(menu==0)
		{

			menu=1;
			$("#contenedor_registro_horarios").show();
		}
		else if(menu==2)
		{

			$("#contenedor_reporte").show();
		}

		$("#m_reporte").on("click",function()
		{
			$("#contenedor_registro_horarios").hide();
			$("#contenedor_reporte").show();
			menu=2;
		});
		// $("#m_reporte_dia_anterior").
		//$nivel
		if(menu==0)
		{
			menu=1;
			$("#contenedor_registro_horarios").show();
		}
		else if(menu==2)
		{
			$("#contenedor_reporte").show();
		}
		$("#conte_tabla").hide();
		$("#conte_tabla2").hide();
		$("#conte_tabla3").hide();
    $("#conte_tabla6").hide();
		$("#accordionControl_1").hide();
		$("#accordionControl_2").hide();
		$("#accordionControl").hide();
		$("#contenedor2").hide();
		$("#contenedor3").hide();
		$("#fechas_archivo").hide();



		$("#btn_revision_pda").click(function(){

			$("#accordionControl_1").hide();
			$("#accordionControl_2").hide();
			$("#conte_tabla2").hide();
			$("#conte_tabla3").hide();
			$("#tabla_registro2").hide();
			$("#tabla_registro3").hide();
			$("#conte_tabla").hide();
			$("#tabla_registro").hide();
			$("#accordionControl").hide();
			$("#contenedor2").show();
			$("#contenedor3").hide();
			$("#fechas_revision").hide();
			$("#fechas_valida").hide();
			$("#fechas_historico").hide();
			$("#fechas_container").hide();
      $("#tabla_registro6").hide();
      $("#conte_tabla6").hide();


		});

		$("#btn_consultar_pda").click(function()
		{
			$("#contenedor3").show();
			$("#fechas_revision").hide();
			$("#fechas_historico").hide();
			$("#subcontenedor_registrar_loterias").hide();
			$("#fechas_container").hide();
			$("#btn_revision").hide();
			$("#fechas_valida").hide();
			$("#accordionControl_1").hide();
			$("#accordionControl_2").hide();
			$("#conte_tabla2").hide();
			$("#tabla_registro2").hide();
			$("#conte_tabla").hide();
			$("#tabla_registro").hide();
			$("#accordionControl").hide();
			$("#fechas_archivo").hide();
			$("#contenedor2").hide();
			$("#conte_tabla3").hide()
			$("#tabla_registro3").hide()
      $("#conte_tabla6").hide();
			$("#tabla_registro6").hide();
      $("#conte_tabla2").hide();
      $("#tabla_registro2").hide();
		});

    $("#numero_b").on("input", function() {
      // Convierte el valor a mayúsculas
      $(this).val($(this).val().toUpperCase());
    });


		$("#btn_consultar_lot").click(function()
		{
			$("#fechas_valida").hide();
			$("#fechas_container").hide();
			$("#fechas_revision").hide();
			$("#fechas_valida").hide();
		  $("#accordionControl").show();
			$("#accordionControl_1").show();
	    $("#accordionControl_2").show();
			$("#fechas_archivo").hide();
			$("#contenedor2").hide();
			$("#contenedor3").hide();
			$("#conte_tabla3").hide()
			$("#tabla_registro3").hide()
      $("#conte_tabla6").hide()
			$("#tabla_registro6").hide()


		});

		var nivel = $("#nivel").val();

		if (nivel == 1)
		{
			$("#btn_consultar_pda").removeAttr("disabled");
			$("#btn_revision_pda").removeAttr("disabled");
			$("#fechas_revision").removeAttr("disabled");
			$("#btn_resultados_m").removeAttr("disabled");
			$("#btn_revision").removeAttr("disabled");
			$("#btn_bal").attr("disabled","disabled");
			$("#btn_programar_pda").attr("disabled","disabled");
			alertify.notify("Al ingresar con nivel de Administrador , los modulos cuentan con restricciones de funcionamiento.");
			alertify.error("Acceso a reportes de  Escrutinio  y Control Interno");
		}




		var nivel = $("#nivel").val();
		console.log("Nivel Revision:",nivel);
		if (nivel == 83)
		{

			$("#btn_buscar_loteria").removeAttr("disabled");
			$("#fechas_revision").show();
			$("#btn_programar_pda").attr("disabled","true");
			$("#btn_resultados_m").attr("disabled","true");
			$("#btn_consultar_lot").attr("disabled","true");
			$("#opcion5").hide();
			$("#opcion51").hide();

		}

		var nivel = $("#nivel").val();
		if (nivel == 15 )
		//Nivel 15 Director Escrutinio
		{
			$("#btn_programar_pda").removeAttr("disabled");
			$("#btn_consultar_pda").removeAttr("disabled");
			$("#btn_resultados_m").removeAttr("disabled");
			$("#btn_Examinar").attr("disabled","true");
			//$("#btn_resultados_m").attr("disabled","true");
			$("#btn_consultar_lot").attr("disabled","true");
			$("#btn_revision_pda").attr("disabled","true");
			$("#btnCon").removeAttr("disabled");
			$("#btn_revision_pda").removeAttr("disabled");
			$("#opcion5").hide();
			$("#opcion51").hide();

			alertify.success("Bienvenido al Sistema  Director Escrutinio");

		}
		if (nivel == 86 )
		{
			$("#btn_programar_pda").removeAttr("disabled");
			$("#btn_consultar_pda").removeAttr("disabled");
			$("#btn_revision_pda").removeAttr("disabled");
			$("#btn_resultados_m").removeAttr("disabled");
			$("#btn_Examinar").attr("disabled","true");
			//$("#btn_resultados_m").attr("disabled","true");
			$("#btn_consultar_lot").attr("disabled","true");

			alertify.success("Bienvenido al Sistema Coordinador Escrutinio");

		}
		var nivel = $("#nivel").val();
		console.log("Nivel Revision:",nivel);
		//Nivel 23 Jefe Auditoria
		if (nivel == 23 )
		{
			$("#btn_consultar_lot").removeAttr("disabled");
			$("#btn_programar_pda").attr("disabled","true");
			$("#btn_resultados_m").attr("disabled","true");
			$("#btn_consultar_pda").attr("disabled","true");
			$("#btnCon").removeAttr("disabled");
			$("#btn_revision_pda").removeAttr("disabled");
		}
		//Nivel 11 Auditor
		var nivel = $("#nivel").val();
		console.log("Nivel Revision:",nivel);
		if (nivel == 11)
		{
			$("#btn_consultar_lot").removeAttr("disabled");
			$("#btn_programar_pda").attr("disabled","true");
			$("#btn_resultados_m").attr("disabled","true");
			$("#btn_consultar_pda").attr("disabled","true");
			$("#btnCon").removeAttr("disabled");
			$("#btn_revision_pda").removeAttr("disabled");
			// $("#opcion5").hide();
			// $("#opcion51").hide();
      $("#opcion5").show();
			$("#opcion51").show();

    }
		var nickname = $("#usuario").val();
		if(nickname == "CP1076200318")
		{

			$("#opcion5").show();
			$("#opcion51").show();
			// console.log("Nivel Coordinador ",nivel);
		}

		$("#btn_publicar").click(function()
		{
			$("#exampleModalCategoria").modal("hide");
      $("#exampleModalImagenServidor").modal("show");

		});
		// console.log("Usuario",nickname);
        // alert(nickname);

		$("#btn_resultados_m").click(function()
		{
			$("#subcontenedor_registrar_loterias").hide();
			$("#fechas_container").hide();
			$("#btn_revision").hide();
			$("#fechas_valida").hide();
			$("#accordionControl_1").hide();
			$("#accordionControl_2").hide();
			$("#conte_tabla2").hide();
			$("#tabla_registro2").hide();
			$("#conte_tabla").hide();
			$("#tabla_registro").hide();
			$("#accordionControl").hide();
			$("#fechas_archivo").hide();
			$("#contenedor2").hide();
			$("#contenedor3").hide();
			$("#conte_tabla3").hide()
			$("#tabla_registro3").hide()
			$("#btn_publicar").hide()
      $("#conte_tabla6").hide()
			$("#tabla_registro6").hide()

		});

		$("#btn_revision_pda").click(function()
		{
			$("#fechas_container").hide();
			$("#subcontenedor_registrar_loterias").hide();
			$("#fechas_revision").hide();
			$("#contenedor2").show();

		});

		$("#check_comentario").on("change", function()
    {
			var checkboxValue = $(this).is(":checked");
			console.log("Checkbox value:", checkboxValue);
			if (checkboxValue)
      {
        $("#btn_final").hide();
        $("#btn_comentario").show();
        $("#botones_observacion").show();
        $("#btn_modificar_registro_pda").hide();
        $("#btn_registar_pda").hide();
        $("#btn_editar_pda").hide();
			} else
      {
        $("#btn_comentario").hide();
        $("#botones_observacion").hide();
        $("#btn_final").show();
        $("#btn_modificar_registro_pda").hide();
        $("#btn_registar_pda").hide();
        $("#btn_editar_pda").hide();
			}

		});


		$("#btnCon").click(function(){
			$("#ExampleModalAgregar").show();

			$("#selec_fecha_1").hide();

		});

		$("#btn_programar_pda").click(function()
		{
      $("#fechas_historico").hide();
      $("#fechas_archivo").hide();
      $("#contenedor2").hide();
      $("#contenedor3").hide();
      $("#conte_tabla3").hide()
      $("#tabla_registro3").hide()
      $("#fechas_valida").show();
      $("#fechas_control").hide();
      $("#btn_editar_pda").hide();
      $("#btn_registar_pda").hide();
      $("#btn_final").show();
      $("#btn_modificar_registro_pda").hide();
      $("#fechas_container").hide();
      $("#botones_observacion").hide();
      $("#btn_comentario").hide();
      $("#check_comentario").prop("checked", false);

      $("#subcontenedor_registrar_loterias").hide();
      $("#fechas_revision").hide();

		});

		// Get references to the dropdown menu and input field
		const dropdown = document.getElementById("txt_tipo_balota");
		const inputField = document.getElementById("dia_balota");

		// Set up an event listener for the dropdown menu
		dropdown.addEventListener("change", function() {
		// Get the selected value from the dropdown menu
		const selectedValue = dropdown.value;

      // Determinar qué mensaje mostrar según el valor seleccionado
      let message;
      if (selectedValue === "1") {
        message = "Baloto: Miércoles y Sábado";
      } else if (selectedValue === "2") {
        message = "Baloto Revancha: Miércoles y Sábado";
      } else if (selectedValue === "3") {
        message = "MiLoto: Lunes, Martes, Jueves y Viernes";
      } else if (selectedValue === "4") {
        message = "ColorLoto: Lunes y Jueves";
      } else {
        message = "Seleccione el tipo de Balota";
      }

      // Mostrar el mensaje en el campo de entrada
      inputField.value = message;
    });



		flag_carga_pda=0;
		$("#btn_crear_loteria").click(function()
		{   var fechav=$("#fechav").val();

			if (fechav === "" || fechav === null)
			{
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
			}
				$("#btn_comentario").hide();
				$("#btn_final").show();



				var nivel = $("#nivel").val();
				console.log("Nivel Revision 4:",nivel);
				if (nivel == 15)
				{
					firma ="1";
					estado ="0";
				}
				else if (nivel == 86)
				{
					firma ="2";
					estado ="0";
				}

				console.log("Estado ",estado);


				const json =
				{
					con: 53,
					"estado": estado,
					fechav: fechav
				}
				$.ajax({
					url: ip_symfony+"datos24",
					type: "POST",
					data: JSON.stringify(json),
					headers:

					{
						"Authentication": JSON.stringify(Authentication)
					},
					beforeSend:function()
					{
						$(".cargando").show();
						$(".subcontenedor_registrar_loterias").show();
						$("#btn_editar_pda").hide();
						$("#btn_registar_pda").hide();
						$("#btn_final").show();
						$("#btn_modificar_registro_pda").hide();
						$("#fechas_container").hide();
						$("#botones_observacion").hide();
						$("#btn_comentario").hide();
						$("#check_comentario").prop("checked", false);
						$("#fechas_valida").hide();
						$("#tabla_acumulados").hide();
						$("#tabla_resultados").hide();
						$("#botones_container").hide();
						$("#botones_check").hide();
						$("#titulo_resultados").hide();
						$("#titulo_resultados2").hide();
					},
					success:function(dato)
					{
						$("#tabla_resultados tbody").empty();
						$(".cargando").hide();
						$("#btn_editar_pda").hide();
						$("#btn_registar_pda").hide();
						$("#btn_final").show();
						$("#btn_modificar_registro_pda").hide();
						$("#btn_comentario").hide();
						//$("#botones_observacion").hide();
						$("#fechas_valida").hide();

						$("#check_comentario").prop("checked", false);
						$("#body_tabla_resultados").hide();
						$("#tabla_acumulados").hide();
						$("#tabla_resultados").hide();
						$("#titulo_resultados").show();
						$("#titulo_resultados2").show();


						if(dato[0]["estado"] == "0")
						{
							let resultados = dato[0]["resultados"];


							//let resultados = JSON.parse(resultados_encode);
							alertify.success("Informacion Cargada");

							// Obtin la referencia al tbody
							var tbody = document.getElementById("body_tabla_resultados");


							resultados.forEach(function (dato)
							{
								var newRow = document.createElement("tr");

								// Recorre las propiedades del objeto y crea celdas td
								for (var key in dato)
								{
									if(key!="check_id")
									{
										if (dato.hasOwnProperty(key))
										{
											var newCell = document.createElement("td");
											// Configura el contenido de la celda
											if (key.includes("id_check"))
											{
												// Si la propiedad es un id_check, crea una casilla de verificaciin
												var checkbox = document.createElement("input");
												checkbox.type = "checkbox";
												checkbox.className = "form-check";
												checkbox.style.margin = "auto";
												checkbox.checked = dato[key] === 1; // Marca la casilla si el valor es 1
												let numero_check = key.charAt(key.length - 1);
												checkbox.addEventListener("change", function (event)
												{
													// Llama a la funciin cuando cambia el estado de la casilla de verificaciin
													miFuncionDeManejoDeCambio(event, dato["check_id"],numero_check); // Puedes pasar datos adicionales si es necesario
												});

												newCell.appendChild(checkbox);
											} else {
												// Para otras propiedades, establece el contenido de texto
												newCell.textContent = dato[key];

											}

											// Agrega la celda a la fila
											newRow.appendChild(newCell);
										}
									}
								}

								// Agrega la fila al tbody
								tbody.appendChild(newRow);
							});

							$(".subcontenedor_registrar_loterias").show();
							$(".cargando").hide();
							$("#tabla_acumulados").show();
							$("#botones_check").show();
							$("#tabla_resultados").hide();
							$("#btn_editar_pda").hide();
							$("#btn_registar_pda").hide();
							$("#btn_final").show();
							$("#btn_modificar_registro_pda").hide();
							$("#fechas_container").hide();
							$("#botones_observacion").hide();
							$("#btn_comentario").hide();
							$("#check_comentario").prop("checked", false);
							$("#fechas_valida").hide();
							$("#subcontenedor_registrar_loterias").show();
							$("#body_tabla_resultados").show();
							$("#tabla_resultados").show();
							$("#botones_container").show();
						}
            if(dato[0]["estado"] == "1")
						{
							alertify.error("No hay registros de Acumulados");
						}
            if(dato[0]["estado"] == "3")
						{

              $(".cargando").hide();
              $("#tabla_acumulados").hide();
              $("#botones_check").hide();
              $("#tabla_resultados").hide();
              $("#btn_editar_pda").hide();
              $("#btn_registar_pda").hide();
              $("#btn_final").hide();
              $("#btn_modificar_registro_pda").hide();
              $("#fechas_container").hide();
              $("#botones_observacion").hide();
              $("#btn_comentario").hide();
              $("#check_comentario").prop("checked", false);
              $("#fechas_valida").hide();
              $("#subcontenedor_registrar_loterias").hide();

                alertify.error("Realice Verificacion de Sorteos Pendientes de Revision.");

                alertify.success("Seleccione el Modulo de Consultar Loterias, para continuar con su proceso.");
						}

					}
		});

        $("#subcontenedor_registrar_loterias").toggle(1000);

		});

		let buttonsVisibleCambio = false;
		let buttonsVisibleFinal = false;


		$("#btn_final").dblclick(function () {
		buttonsVisibleFinal = !buttonsVisibleFinal;
		if (buttonsVisibleFinal) {
		$("#btn_modificar_registro_pda").hide();///15
		$("#btn_registar_pda").hide();//86
		} else {
		$("#btn_modificar_registro_pda").hide();
		$("#btn_registar_pda").hide();
		}
		});
		$("#btn_registar_pda").click(function ()
		{

		$("#btn_final").hide();
		});
		$("#btn_modificar_registro_pda").click(function ()
		{

			$("#btn_final").hide();
		});

		$("#btn_final").click(function () {
			var nivel = $("#nivel").val();
			console.log("Nivel 1:",nivel);
			if (nivel == 15)
		{   console.log("Nivel 2:",nivel);
			$("#btn_registar_pda").hide();
			$("#btn_cambio").hide();
			$("#btn_modificar_registro_pda").show();

		}  if (nivel == 86)
		{
			console.log("Nivel 3:",nivel);
			$("#btn_modificar_registro_pda").hide();
			$("#btn_registar_pda").show();
			$("#btn_cambio").hide();

		}
		});

		function miFuncionDeManejoDeCambio(event, dato, dato2)
		{
			// Accede a la informaciin del dato y al estado de la casilla de verificaciin
			console.log("Estado de la casilla:", event.target.checked);
			console.log("Datos de la loteria:", dato);
			console.log("Datos de la loteria:", dato2);
			// Puedes realizar otras acciones aqui segin tus necesidades
			var fechav=$("#fechav").val();
			const json =
			{
				con: "53.1",
				estado: ""+event.target.checked+"",
				id: dato,
				id2: dato2,
				fechav:fechav
			}

			$.ajax
			({
				url: ip_symfony+"datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers:

				{

					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend:function()
				{
					$(".cargando").show();
					$("#btn_final").show();
				},
				success:function(dato)
				{
					$(".cargando").hide();
					if(dato[0].estado == 0)
					{
						//$("#alert-confirmacion").show();
						alertify.success("Revision Actualizada");
					}
					else
					{
						alertify.error("No se pudo actualizar");
						//$("#alert-negado").show();
					}
		        }
			});

		}


		$("#btn_editar_pda").click(function ()
		{
			var fechav=$("#fechav").val();
			if (fechav === "" || fechav === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }
			const json = {
				con: 53.2,
				fechav: fechav,
				estado_reporte :1,

			}
			$.ajax({
				url: ip_symfony+"datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers:
				{
					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend: function()
				{

				$("#btn_final").hide();
				$("#btn_cambio").hide();
				$("#btn_modificar_registro_pda").hide();

				},
				success: function(respuesta)
				{
				if(respuesta[0].estado == 0)
				{
			    window.open(respuesta[0].ruta, "_blank", "title=PDF ESCRUTINIO");
			    //window.location.href = respuesta[0].ruta;
				///descargarArchivo(respuesta[0].ruta, "pdfEscrutinio");

				alertify.success("Se realiza Descargue de PDF");
				}
			    else
				alertify.error("No se realiza descarga de PDF");
				},
				error: function()
				{
				alertify.notify("Error de Servidor");
				}
			});
    });

		$("#btn_registar_pda").click(function ()
		{
			var nivel = $("#nivel").val();
			var fechav=$("#fechav").val();
			if (fechav === "" || fechav === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }
			console.log("Nivel Revision: 1",nivel);
			var nivel = $("#nivel").val();
			if (nivel == 15)
			{
                firma ="1";
				estado ="2"
			}
			else if (nivel == 86)
			{
				firma ="2";
				estado="1"
			}
			const json =
			{"con": 53.3,
			"estado": estado,
			"firma": firma,
			 fechav:fechav
		    }
            $.ajax({
                url: ip_symfony+"datos24",
                type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
                beforeSend: function () {
                    $(".cargando").show();
					$("#btn_editar_pda").show();
                    // Deshabilita los campos durante la solicitud AJAX

                },
                success: function (respuesta)
				{
                    $(".cargando").hide();


				if(respuesta[0].firma == 2)

				{
				alertify.success("Firma Existosa Coordinador Escrutinio ");


				}if(respuesta[0].firma == 1)
				{alertify.success("Firma Existosa Coordinador Escrutinio ");

				}if(respuesta[0].firma ==0)
				{
				  alertify.warning("Firma Ya registrada Coordinador Escrutinio ");
				  setTimeout(function()
				{
					alertify.notify("Continue con su Proceso");
				},2000);
				}
				},
				error: function()
				{
					alertify.notify("Error Servidor");//poner modal de error escribirle :: ERROR DEL SERVIDOR
				}

      });
    });
		///comentario ini
		$("#btn_comentario").click(function ()
		{
			comentario=$("#txt_comentario").val();
			const json =
			{"con": 53.5,
			"estado": 2,
			comentario: $("#txt_comentario").val()
	        }
            $.ajax({
                url: ip_symfony+"datos24",
                type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
                beforeSend: function () {
                    $(".cargando").show();
					$("#btn_final").hide();


                    // Deshabilita los campos durante la solicitud AJAX

                },
                success: function (respuesta)
				{
                    $(".cargando").hide();

                if(respuesta[0].estado == 1)
				{
					$("#txt_comentario").val("");
					alertify.success("Comentario Agregado Correctamente");

					$("#btn_final").show();

					$("#btn_comentario").hide();

				}else
				{
					alertify.error("Error al momento de Agregar ");
				}
				},
				error: function()
				{
					alertify.notify("Error Servidor");//poner modal de error escribirle :: ERROR DEL SERVIDOR
				}

      });
    });

		// comentario fin


		$("#btn_visualizar").click(function ()
		{
			$("#ModalResultados").trigger("click");
			$("#exampleModalResultados").show();
		});
		$("#btn_buscar_loteria").click(function ()
		{
        $("#btn_revision").hide();
        $("#fechas_revision").hide();

        var estado_reporte = $("input[name=radio_seg2]:checked").val();
        var nivel = $("#nivel").val();
        $("#fechas_valida").hide();
        var fechai=$("#fechai").val();
        if (fechai === "" || fechai === null)
          {
        alertify.error("Seleccione una Fecha");
        return false; // Detiene la ejecución de la solicitud AJAX
		    }
			    const json =
        {   "con": 53.4,
          fechai: fechai,
          estado_reporte :estado_reporte,
          estado:3
		    }
        $.ajax({
        url: ip_symfony+"datos24",
        type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
        beforeSend: function ()
        {
        $(".cargando").show();
				$("#btn_editar_pda").show();
                    // Deshabilita los campos durante la solicitud AJAX
					alertify.notify("Por favor espere , su solicitud tardara unos segundos ");
                    // Deshabilita los campos durante la solicitud AJAX
        },
        success: function (respuesta)
				{
          if(respuesta[0].revision == 0)
          {
            $("#fechas_revision").show();
            $("#btn_revision").hide();
            $(".cargando").hide();
          }
          if(respuesta[0].revision == 1)
          {
            if (nivel == 1)
            {
            $("#fechas_revision").show();
            $("#btn_revision").hide();
            setTimeout(function()
                      {
              alertify.error("No tiene acceso a realizar la confirmacion de los resultados.");
                },2000);

              $(".cargando").hide();
            }
            else
            {
              $("#fechas_revision").show();
              $("#btn_revision").show();
              $(".cargando").hide();
              alertify.success("Acceso a realizar la confirmacion de los resultados.");
            }

          }
          if(respuesta[0].estado == 0)
          {
            window.open(respuesta[0].ruta, "_blank", "title=PDF ESCRUTINIO");
            //window.location.href = respuesta[0].ruta;
          ///descargarArchivo(respuesta[0].ruta, "pdfEscrutinio");

          alertify.success("Se realiza Descargue de PDF");
          setTimeout(function()
          {
            alertify.notify("Descargue correcto");
            $(".cargando").hide();

          },2000);
          }else
          {
            alertify.error("No se realiza descarga de PDF");
            $("#btn_revision").hide();
          }
				},
				error: function()
				{
				alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
				$("#btn_revision").hide();
				}
      });
    });

		$("#btn_buscar_loteria_historico").click(function ()
		{
        $("#btn_revision").hide();
        $("#fechas_revision").hide();
        var nivel = $("#nivel").val();
        var estado_reporte = $("input[name=radio_seg2]:checked").val();
        $("#fechas_valida").hide();
        var fecha_his=$("#fecha_his").val();
        if (fecha_his === "" || fecha_his === null)
          {
        alertify.error("Seleccione una Fecha");
        return false; // Detiene la ejecución de la solicitud AJAX
          }
        const json =
        {"con": 53.4,
        fecha_his: fecha_his,
        estado_reporte:estado_reporte
		    }
        $.ajax({
        url: ip_symfony+"datos24",
        type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
                beforeSend: function () {
                    $(".cargando").show();
					$("#btn_editar_pda").show();

                    // Deshabilita los campos durante la solicitud AJAX
					alertify.notify("Por favor espere , su solicitud tardara unos segundos ");
                    // Deshabilita los campos durante la solicitud AJAX

                },
                success: function (respuesta)
				{
				if(respuesta[0].estado == 0)
				{
			    window.open(respuesta[0].ruta, "_blank", "title=PDF ESCRUTINIO");
			    //window.location.href = respuesta[0].ruta;
				///descargarArchivo(respuesta[0].ruta, "pdfEscrutinio");

				alertify.success("Se realiza Descargue de PDF");
				setTimeout(function()
				{
					alertify.notify("Descargue correcto");
					$(".cargando").hide();

				},2000);
				}else
				{
					alertify.error("No se realiza descarga de PDF");
					$("#btn_revision").hide();
				}
				},
				error: function()
				{
				alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
				$("#btn_revision").hide();
				}


      });
    });

		$("#btn_modificar_registro_pda").click(function ()
		{
			  var fechav=$("#fechav").val();
        if (fechav === "" || fechav === null)
          {
        alertify.error("Seleccione una Fecha");
        return false; // Detiene la ejecución de la solicitud AJAX
          }

        var nivel = $("#nivel").val();
        console.log("Nivel Revision 2:",nivel);
        if (nivel == 15)
        {
                  firma ="1";
          estado ="2";
        }
        else if (nivel == 86)
        {
          firma ="2";
          estado ="1";
        }
        const json =
        { "con": 53.3,
          "estado": estado,
          "firma": firma,
          fechav:fechav
		    }
        $.ajax({
        url: ip_symfony+"datos24",
        type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
        beforeSend: function () {
            $(".cargando").show();
            // Deshabilita los campos durante la solicitud AJAX
        },
        success: function (respuesta)
				{
          $(".cargando").hide();

				if(respuesta[0].firma == 0)
				{
					alertify.notify("Firma Ya Registrada en el Sistema");

					setTimeout(function()
						{
							alertify.success("Continue con su Proceso  ");
							$("#btn_editar_pda").show();
						},2000);

				}
				else if (respuesta[0].firma == 1)
				{
				alertify.success("Firma Existosa Director Escrutinio ");
				$("#btn_editar_pda").show();
				}
				else
				{
					alertify.error("Error Firma");
					$("#btn_editar_pda").hide();
				}
				},
				error: function()
				{
					alertify.error("Error Servidor");
					$("#btn_editar_pda").hide();//poner modal de error escribirle :: ERROR DEL SERVIDOR
				}
      });
    });

		$("#btn_revision").click(function ()
		{
            //AUXILIAR ESCRUTINIO = 83
			//DIRECTOR ESCRUTINIO = 15
			//COORDINADOR ESCRUTINIO = 86
			var fechai=$("#fechai").val();
			$("#btn_buscar_loteria_historico").hide();
			$("#fechas_historico").hide();

			if (fechai === "" || fechai === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }

			console.log("Nivel Revision: 5",nivel);

			if (nivel == 15)
			{
                firma ="1";
				estado = "3";
			}
			else if (nivel == 86)
			{
				firma ="2";
				estado = "3";
			}
			else if (nivel == 83)
			{
				firma ="3";
				estado = "3";
			}

			const json =
			{"con": 53.3,
			  "estado": estado,
        fechai: fechai,
        "firma": firma
      }
        $.ajax({
        url: ip_symfony+"datos24",
        type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
                beforeSend: function () {
                    $(".cargando").show();
					$("#btn_editar_pda").show();
                    // Deshabilita los campos durante la solicitud AJAX
                },
                success: function (respuesta)
				{
                if(respuesta[0].estado == 0)
				{
				alertify.notify("Ya se encuentra Registrada en el Sistema");
				$("#btn_editar_pda").show();
				}if(respuesta[0].estado == 2)
				{
				alertify.error("Error Estado de Revision");
					$("#btn_editar_pda").hide();
				}
				if(respuesta[0].estado == 1)
				{
				alertify.success("Revision Guardada Correctamente");
					$("#btn_editar_pda").hide();
					setTimeout(function()
                    {
				      document.location="reporte_escrutinio.php";
			        },1000);
				}
				if(respuesta[0].estado == 3)
				{
				alertify.error("No se Guardo Correctamente la Informacion");
					$("#btn_editar_pda").hide();
				}
				},
				error: function()
				{
					alertify.error("Error Servidor");
					$("#btn_editar_pda").hide();//poner modal de error escribirle :: ERROR DEL SERVIDOR
				}
      });
    });


		$("#btn_modal").click(function ()
		{
      $("#ingresa_pda").hide();
      $("#subcontenedor_registrar_loterias").hide();
      $("#btn_modificar_registro_pda").hide();
      $("#conte_regis_horarios").hide();
      $("#body_tabla_historial").hide();
      $("#tabla_registro").hide();
      $("#tabla_historial_wrapper").hide();
      $("#subconte_tabla_historial").hide();
      $("#tabla_registro_length").hide();
      $("#conte_tabla").hide();
      $("#btn_editar_pda").hide();


		});


		$("#btnEnvioreporte").click(function ()
    {
		  var selectedValue = $("input[name=radio_seg]:checked").val();
      $("#msm_sistemaError").hide();
      $("#msm_sistema").hide();
		  var fecha_r=$("#fecha_r").val();
		  var fecha_r2=$("#fecha_r2").val();

		  if (fecha_r === "" || fecha_r === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }
			if (fecha_r2 === "" || fecha_r2 === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }

		  const json =
		  {"con": 53.6,
		  fechai: fecha_r,
		  fechaf: fecha_r2,
		  selectedValue:selectedValue
		  }
		  $.ajax({
			  url: ip_symfony+"datos24",
			  type: "POST",
			  data: JSON.stringify(json),
			  headers: {
				  "Authentication": JSON.stringify(Authentication)
			  },
			  beforeSend: function () {
				  $(".cargando").show();
				  $("#btn_editar_pda").show();
				  // Deshabilita los campos durante la solicitud AJAX
				  alertify.success("Por favor espere, estamos procesando su solicitud");

			  },
			  success: function (respuesta)
			  {

			  if(respuesta[0].estado == 0)
			  {

			  setTimeout(function()
			  {
				  alertify.notify("Descargue Correcto");


			  },2200);
			  setTimeout(function()
			  {
				alertify.success("Descargue Realizado de Archivo");
				window.open(respuesta[0].datos,"_blank");
				$(".cargando").hide();

			  },2000);

			  }else
			  {
				alertify.notify("No se Descargo Correctamente el  Archivo ");
				  $("#btn_revision").hide();
				  $(".cargando").hide();
			  }
			  if(respuesta[0].estado == 4)
			  {
				alertify.error("No existen resultados de la fecha seleccionada");
				$(".cargando").hide();
			  }

			  },
			  error: function()
			  {
			  alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
			  $("#btn_revision").hide();
			  $(".cargando").hide();
			  }

		  });
    });

    document.getElementById("adj_seg_envio").addEventListener("change", function() {
      var file = this.files[0];
      if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
          var imagePreview = document.getElementById("imagePreview");
          imagePreview.src = e.target.result;
          imagePreview.style.display = "block";
        };
        reader.readAsDataURL(file);
      }
    });

    document.getElementById("formulario").addEventListener("submit", function(event)
    {
      event.preventDefault();

      var fecha_p1 = $("#fecha_p1").val();
      var fecha_p2 = $("#fecha_p2").val();

      if (fecha_p1 === "" || fecha_p1 === null) {
          alertify.error("Seleccione una Fecha");
          return false; // Detiene la ejecución de la solicitud AJAX
      }
      if (fecha_p2 === "" || fecha_p2 === null) {
          alertify.error("Seleccione una Fecha");
          return false; // Detiene la ejecución de la solicitud AJAX
      }

      var selectElement = document.getElementById("estado_p");
      var estado_p = selectElement.value;
      var nombre_p = $("#nombre_p").val();
      var archivo = $("#adj_seg_envio").prop("files")[0];

      if (!archivo) {
          alertify.error("Seleccione un archivo para subir.");
          return false;
      }

      if (!validateFileExtension2(archivo)) {
          alertify.error("Seleccione un Archivo .png");
          return false;
      } else {
          alertify.success("Extensión de Archivo Correcta");
      }

      // Validación de dimensiones de imagen
      var img = new Image();
      img.src = URL.createObjectURL(archivo);

      img.onload = function()
      {
          var width = img.width;
          var height = img.height;
          // if (width !== 469 || height !== 812) {
            if (!((width === 469 && height === 812) || (width === 1955 && height === 3385)))
              {
                alertify.error("La imagen debe tener 469px de ancho y 812px de alto, o bien 1955px de ancho y 3385px de alto.");
                alertify.error("La imagen cargada tiene " + width + "px de ancho y " + height + "px de alto.");
                alertify.error("Revise las políticas de mercadeo.");
              return false;
          }



          // Crear FormData y continuar con la solicitud AJAX
          var formData = new FormData();
          formData.append("file", archivo);
          formData.append("array_datos", JSON.stringify([{ con: 141, nombre_p: nombre_p, estado_p: estado_p, fecha_p1: fecha_p1, fecha_p2: fecha_p2 }]));

          const Authentication = {
              nickname: "'.$_SESSION['nickname'].'"
          };

          $.ajax({
              url: ip_symfony + "datos_10",
              type: "POST",
              data: formData,
              headers: {
                  "Authentication": JSON.stringify(Authentication)
              },
              contentType: false,
              processData: false,
              beforeSend: function() {
                  $(".cargando").show();
                  $("#btn_editar_pda").show();
                  alertify.notify("Por favor espere , su solicitud tardará unos segundos.");
                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , su solicitud tardará unos segundos.")
                  },4000);
                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , su solicitud tardará 60 segundos.")
                  },6000);

                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , no cierre la ventana, estamos procesando la solicitud.")
                  },10000);

                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , no cierre la ventana, estamos procesando la solicitud.")
                  },10000);

                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , no cierre la ventana, estamos procesando la solicitud.")
                  },10000);

                  setTimeout(function()
                  {
                    alertify.notify("Por favor espere , vamos a generar una plantilla con el diseño de la imagen.")
                  },15000);

              },
              success: function(respuesta) {
                  if (respuesta[0].estado == 1){
                    $(".cargando").hide();
                      alertify.success("Se realiza cargue de plantilla solicitada.");
                      alertify.notify("Actualice la página para visualizar los cambios en una plantilla por defecto, con la imagen cargada.");
                      window.open(respuesta[0].ruta, "_blank", "title=PDF Mercadeo");
                      setTimeout(function()
                      {
                        alertify.notify("Descargue correcto");
                        $(".cargando").show();
                        $("#exitModalPromocional").trigger("click");


                      },4000);
                      // document.body.removeChild(a);
                      $(".cargando").hide();

                      alertify.success("Se realiza Descargue de PNG solicitada.");
                      $("#exitModalPromocional").trigger("click");
                      // document.location="reporte_escrutinio.php";
                  }
                  if (respuesta[0].estado == 2)
                  {
                      alertify.error("Error al momento de generar el archivo");
                      $("#exitModalPromocional").trigger("click");

                  }
              },
              error: function() {
                  alertify.error("Error Servidor");

                  $("#exitModalPromocional").trigger("click");
              }
          });
      };

      img.onerror = function() {
          alertify.error("Error al cargar la imagen. Verifique el archivo.");
          return false;
      };
    });






		document.getElementById("formulario_imagen").addEventListener("submit", function(event)
    {   //alert("hola llegue");
			event.preventDefault();
			var fecha_imagen=$("#fecha_imagen").val();
			if (fecha_imagen === "" || fecha_imagen === null)
			{
				alertify.error("Seleccione una Fecha");
				return false; // Detiene la ejecución de la solicitud AJAX
			}

			var selectElement = document.getElementById("estado_p");
			estado_p=selectElement.value;
			nombre_i=$("#nombre_imagen").val()
			var myfileElement = document.getElementById("formulario");
			var archivo = $("#adj_seg_envio_imagen").prop("files")[0]
			if (!validateFileExtension2(archivo)) {
				alertify.error("Seleccione un Archivo .png");
				return false; // Cancel form submission
			  } else {
				alertify.success("Extension de Archivo Correcta");
			  }
			var formData = new FormData();
			formData.append("file", archivo);
			formData.append("array_datos",  JSON.stringify([{ con:143 ,nombre_i:nombre_i,estado_i:2,fecha_imagen:fecha_imagen,}]));
			const Authentication =
					{
						nickname :  "'.$_SESSION['nickname'].'"
					};
		  $.ajax
		  ({
			url: ip_symfony+"datos_10",
			type: "POST",
			data: formData,
			headers: {
				"Authentication": JSON.stringify(Authentication)
			},
			contentType: false,
			processData: false,
			beforeSend: function () {
				$(".cargando").show();
				$("#btn_editar_pda").show();
				// Deshabilita los campos durante la solicitud AJAX

			},
			success: function (respuesta)
			{

			if(respuesta[0][0]["estado"]==1)
			{
			alertify.success("Se realiza cargue de Archivo");

			$("#exitModal").trigger("click");
			$("#btn_editar_pda").show();

			setTimeout(function()
			{
				$("#exitModalPromocional").trigger("click");
				// document.location="reporte_escrutinio.php";
			},2000);
			}if(respuesta[0][0]["estado"]==2)
			{
			alertify.error("Error Estado de Revision");
				$("#btn_editar_pda").hide();
			}

			},
			error: function()
			{
				alertify.error("Error Servidor");
				$("#btn_editar_pda").hide();//poner modal de error escribirle :: ERROR DEL SERVIDOR
			}
	      });

		});

		function validateFileExtension(file)
		{
			const allowedExtensions = ["pdf"];
			const fileExtension = file.name.split(".").pop().toLowerCase();
			return allowedExtensions.includes(fileExtension);
		}
		function validateFileExtension2(file)
		{
			const allowedExtensions = ["png"];
			const fileExtension = file.name.split(".").pop().toLowerCase();
			return allowedExtensions.includes(fileExtension);
		}


		document.getElementById("formulario_control").addEventListener("submit", function(event)
    {
			event.preventDefault();

			// Get form data

			var fecha_con; // Declare the variable

			if ($("#fecha_con").val()) { // Check if #fecha_con has a value
			fecha_con = $("#fecha_con").val(); // Assign value from #fecha_con
			} else if ($("#fecha_cargue").val()) { // Check if #fecha_cargue has a value
			fecha_con = $("#fecha_cargue").val(); // Assign value from #fecha_cargue
			} else {
			// Handle the case where neither input has a value
			alertify.error("Error al intentar obtener la fecha seleccionada");
			}




			var nombre_a = $("#nombre_a").val();
			var archivo = $("#adj_seg_envio_a").prop("files")[0];

			// Validate file size
			if (archivo)
			{
				var fileSize = archivo.size;
				var fileSizeMB = fileSize / (1024 * 1024); // Convert to megabytes

				if (fileSizeMB > 1024) {
				  alertify.error("El archivo supera el límite de 1MB. Por favor, seleccione un archivo más pequeño.");
				  return false; // Cancel form submission
				}
			}

			// Validate file extension
			if (!validateFileExtension(archivo)) {
			  alertify.error("Seleccione un Archivo .pdf");
			  return false; // Cancel form submission
			} else {
			  alertify.success("Extension de Archivo Correcta");
			}

			// If both validations pass, proceed with AJAX request
			var formData = new FormData();
			formData.append("file", archivo);
			formData.append("array_datos", JSON.stringify([{
			  con: 142,
			  nombre_a: nombre_a,
			  fecha_con: fecha_con
			}]));

			const Authentication = {
			  nickname: "'.$_SESSION['nickname'].'"
			};

			$.ajax({
			  url: ip_symfony + "datos_10",
			  type: "POST",
			  data: formData,
			  headers: {
				"Authentication": JSON.stringify(Authentication)
			  },
			  contentType: false,
			  processData: false,
			  beforeSend: function() {
				$(".cargando").show();
				$("#btn_editar_pda").show();
				// Deshabilita los campos durante la solicitud AJAX
			  },
			  success: function(respuesta) {
				if (respuesta[0][0]["estado"] == 1) {
				  alertify.success("Se realiza cargue  de Archivo");

				  $("#exitModal").trigger("click");
				  $("#btn_editar_pda").show();
				  setTimeout(function()
				{
					$("#exitModalPromocional").trigger("click");
					document.location="reporte_escrutinio.php";
				},2000);
				} else if (respuesta[0][0]["estado"] == 2) {
				  alertify.error("Error Estado de Revision");
				  $("#btn_editar_pda").hide();
				}
			  },
			  error: function() {
				alertify.error("Error Servidor");
				$("#btn_editar_pda").hide(); //poner modal de error escribirle :: ERROR DEL SERVIDOR
			  }
			});
		});


		$("#formulario_balota").submit(function (event)
    {

			//alert("hola llegue");
			event.preventDefault();
			var selectElement = document.getElementById("txt_tipo_balota");
			estado_b=selectElement.value;
			numero_b=$("#numero_b").val()
			var myfileElement = document.getElementById("formulario_balota");
			var fecha_balota=$("#fecha_balota").val();
			if (fecha_balota === "" || fecha_balota === null)
		    {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		    }

			const json =
			{"con":53.8 ,
			"numero_b":numero_b,
			"estado_b":estado_b,
			fecha_balota:fecha_balota

			}
			$.ajax({
				url: ip_symfony+"datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
				contentType: false,
				processData: false,
				beforeSend: function () {
					$(".cargando").show();
					$("#btn_editar_pda").show();
					// Deshabilita los campos durante la solicitud AJAX

				},
				success: function (respuesta)
				{
					$(".cargando").hide();


				if(respuesta[0].estado == 1)

				{
				alertify.success("Balotas Agregadas");

				document.getElementById("exampleModalBaloto").remove();
				$("#exitModalBaloto").trigger("click");

				document.getElementById("formulario_balota").reset();
                 $("#exampleModalBaloto").modal("hide");
				 $("#msm_sistema").removeClass().addClass("sys_error").html("uardo Correctamente - Informacion Existente").show();


				}if(respuesta[0].estado == 2)
				{
					alertify.notify("Balotas NO Agregadas");

				}if(respuesta[0].estado ==3)
				{
					alertify.warning("Full Error");

				}
				},
				error: function()
				{
					alertify.notify("Error Servidor");//poner modal de error escribirle :: ERROR DEL SERVIDOR
				}


			});

		});

		function obtenerIcono(extension)
    {
          switch (extension.toLowerCase())
          {
            case "pdf":
              return "icono_pdf.png";
            case "doc":
            case "docx":
              return "icono_word.png";
            case "xls":
            case "xlsx":
              return "icono_excel.png";
            case "jpg":
            case "png":
              return "icono_imagen.png"
            // Agrega más extensiones y sus correspondientes íconos aquí según sea necesario
            default:
              return "icono_generico.png";
          }
    }

		$("input[name=radio_imagen]").change(function()
    {
			if ($(this).val() == "1") {

				$("#btn_publicar").show()
        $("#btn_descargar_png").hide();
			} else {
				$("#btn_publicar").hide()
        $("#btn_descargar_png").show();
			}
		});

		$("#btn_enviar").click(function ()
		{
			$("#staticBackdrop").show();
			var selectedImagen = $("input[name=radio_imagen]:checked").val();
			var fecha_resultado=$("#fecha_resultado").val();
			if (fecha_resultado === "" || fecha_resultado === null)
		    {
			alertify.error("Seleccione una Fecha");
			setTimeout(function()
				{
					$("#ModalResultadosCerrarPDF").trigger("click");
					document.location="reporte_escrutinio.php";
				},2000);
			return false; // Detiene la ejecución de la solicitud AJAX
			return false; // Detiene la ejecución de la solicitud AJAX
		    }

			const json =
			{"con": 53.7,
			estado: 1,
			fecha_resultado:fecha_resultado,
			estado_solicitud:1
			}

			$.ajax({
                url: ip_symfony+"datos24",
                type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
          beforeSend: function () {
          $(".cargando").show();
					$("#btn_enviar").attr("disabled","true");

                    // Deshabilita los campos durante la solicitud AJAX
					alertify.notify("Por favor espere , su solicitud tardara unos segundos.");

					setTimeout(function()
				   {
					alertify.notify("Por favor espere , estamos terminando de procesar su solicitud. ");

				  },2000);

                },
                success: function (respuesta)
				{
				if(respuesta[0].estado == 1)
				{

			    window.open(respuesta[0].ruta, "_blank", "title=PDF ESCRUTINIO");


				alertify.success("Se realiza Descargue de PDF");
				setTimeout(function()
				{
					alertify.notify("Descargue correcto");
					$("#btn_revision").show();
					// document.location="reporte_escrutinio.php";

				},4000);
        setTimeout(function()
				{
					$("#ModalResultadosCerrarPDF").trigger("click");
					document.location="reporte_escrutinio.php";
				},2000);
				}else
				{
					alertify.error("No se realiza descarga de PDF");
					$("#btn_revision").hide();
				}

				},
				error: function()
				{
				alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
				$("#btn_revision").hide();
				}

            });
		});

		$("#btn_imagen_servidor").click(function ()
    {
			$("#staticBackdrop").show();
			var fecha_resultado=$("#fecha_resultado").val();
			if (fecha_resultado === "" || fecha_resultado === null)
		    {
			alertify.error("Seleccione una Fecha");
			setTimeout(function()
				{
					$("#ModalResultadosCerrar").trigger("click");
					document.location="reporte_escrutinio.php";
				},2000);
			return false; // Detiene la ejecución de la solicitud AJAX
			return false; // Detiene la ejecución de la solicitud AJAX
		    }

			const json =
			{"con": 53.7,
			estado: 1,
			fecha_resultado:fecha_resultado,
			estado_solicitud:2
			}

			$.ajax({
                url: ip_symfony+"datos24",
                type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
                beforeSend: function () {
                    $(".cargando").show();
					$("#btn_enviar").attr("disabled","true");

                    // Deshabilita los campos durante la solicitud AJAX
					alertify.notify("Por favor espere , su solicitud tardara unos segundos.");

					setTimeout(function()
				   {
					alertify.notify("Por favor espere , estamos terminando de procesar su solicitud. ");

				  },2000);

                },
                success: function (respuesta)
				{

				if(respuesta[0].estado == 0)
				{

			    window.open(respuesta[0].ruta, "_blank", "title=PDF ESCRUTINIO");


				alertify.success("Se realiza Descargue de PDF");
				setTimeout(function()
				{
					alertify.notify("Descargue correcto");
					$("#btn_revision").show();


          $("#ModalResultadosCerrarPDF").trigger("click");


				},4000);
          document.location="reporte_escrutinio.php";
				}else
				{
					alertify.error("No se realiza descarga de PDF");
					$("#btn_revision").hide();
				}

				},
				error: function()
				{
				alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
				$("#btn_revision").hide();
				}

            });
		});

		$("#btn_tipo").click(function () {
			$("#exampleModalCategoria").show();
			$(".btn-close").trigger("click");
      $("#btn_descargar_png").hide();
      $("input[name=radio_imagen]").prop("checked", false);


		});

		$("#btn_descargar_png").click(function ()
    {
			// Hacer otra solicitud AJAX para obtener la ruta de la imagen PNG
			var selectedImagen = $("input[name=radio_imagen]:checked").val();
			var fecha_resultado2=$("#fecha_resultado2").val();
			if (fecha_resultado2 === "" || fecha_resultado2 === null)
		    {
			alertify.error("Seleccione una Fecha");
			setTimeout(function()
				{
					$("#ModalCategoriaSalir").trigger("click");

				},2000);
			return false; // Detiene la ejecución de la solicitud AJAX
		    }
			var selectElement = document.getElementById("txt_tipo_resultados");
            var valorSeleccionado = selectElement.value;
			tipo_resultado=valorSeleccionado;
			console.log("valorSeleccionado"+valorSeleccionado);
			console.log("tipo_resultado"+tipo_resultado);
			if (valorSeleccionado === "" || valorSeleccionado === null || valorSeleccionado === "Seleccione el tipo de Imagen")
			{
				alertify.error("Seleccione una opción de imagen.");
				return false; // Prevent Ajax request
			}
			if (!selectedImagen || selectedImagen === "")
				{
					alertify.error("Seleccione una opción de proceso.");
					return false; // Prevent Ajax request
				}
			//valorSeleccionado es el tipo imagen mañana tarde y noche
			var json =
			{
				"con": 53.7,
				"estado": 2,
			     valorSeleccionado:tipo_resultado,
				 fecha_resultado2:fecha_resultado2,
				 selectedImagen:selectedImagen

			}

			$.ajax({
				url: ip_symfony + "datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend: function ()
				{
					// Mostrar el mensaje antes de enviar la solicitud
					alertify.notify("Por favor espere , su solicitud tardará unos segundos.");
          $("#btn_descargar_png").hide();
          // $("#btn_descargar_png").attr("disabled","true");
				},
				success: function (respuesta)
			    {
				    alertify.success("Estamos terminando de procesar su solicitud.");
					  console.log(respuesta[0].estado);
            $("#btn_descargar_png").hide();

					if (respuesta[0].estado == 1)
					{
						// window.open(respuesta[0].ruta, "_blank","download");
            // descargarImagen(respuesta[0].ruta);
						var a = document.createElement("a");
						a.download = true;
						a.target = "_blank";
						// a.href= respuesta[0].ruta;
						a.href= "reporte_escrutinio_descargar.php?archivo=" + respuesta[0].ruta;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						alertify.success("Se realiza Descargue de PNG solicitada.");
						setTimeout(function()
						{
							$("#ModalCategoriaSalir").trigger("click");
							//document.location="reporte_escrutinio.php";
              $("#btn_descargar_png").show();
						},2000);
            $("#btn_descargar_png").css("display", "block"); // Muestra el botón
					}
          if (respuesta[0].estado == 2)
					{
						alertify.notify("No se logro procesar la peticion solicitada.");
						alertify.error("Comuniquese con el area de desarrollo.");

					}if (respuesta[0].estado == 3)
					{
							alertify.notify("Se movio la imagen de manera correcta a la carpeta.");

							setTimeout(function()
						    {
							alertify.success("Resvise la carpeta de Escrutinio.");
							$("#ModalCategoriaSalir").trigger("click");
							// document.location="reporte_escrutinio.php";
						    },2000);
					}

				},
        error: function()
				{
          alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
          $("#btn_descargar_png").hide();
				}
			});
		});

		function descargarImagen(urlImagen)
		{
			axios.get(urlImagen,
			{
			responseType: "blob",
			mode: "no-cors"
			})
			.then(function(response)
			{
				if (!response.data)
				{
					console.error("Error al descargar la imagen: No se recibió el Blob");
					console.error("Error al descargar la imagen: No se recibió el Blob");
					return;
				}

				var blob = response.data;
				var url = window.URL.createObjectURL(blob); // Obtener la URL del Blob
				var link = document.createElement("a"); // Crear un elemento de enlace

				link.href = url; // Establecer la URL del enlace
				link.download = "imagen.png"; // Establecer el nombre del archivo descargado
				link.click(); // Simular un clic en el enlace para iniciar la descarga

				window.URL.revokeObjectURL(url); // (Opcional) Eliminar la URL del Blob
			})
			.catch(function(error) {
			console.error("Error al descargar la imagen:", error);
      alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
			});
		}

		$("#btn_Examinar").click(function ()
    {
			// Simular clic en el input file
			// console.log("entre__1");
			// $("#inputFile").click();
			$("#adj_seg_envio").val("");
		});

		function handleCheckboxChange(event)
    {
        const checkbox = event.target;
        const loteriaId = checkbox.value;
        const isChecked = checkbox.checked;

        // Realiza una llamada AJAX aquí
        $.ajax({
          url: "tuscript.php", // Reemplaza con la URL de tu script PHP
          method: "POST",
          data: {
          loteria_id: loteriaId,
          checked: isChecked
          },
          success: function (response) {
          console.log("Respuesta del servidor:", response);
          // Maneja la respuesta del servidor aquí
          },
          error: function (xhr, status, error) {
          // console.error("Error en la llamada AJAX:", error);
          alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
          }
			});
		}

		$("#btn_comentario_modal").on("click", function ()
		{
      valores=$("#btn_comentario_modal").val()
      valores_split=valores.split(",");
      comentarios=$("#txt_comentario_loteria").val();
      //alert(valores_split[0]+" "+valores_split[1]);
      modificar_comentario_sorteo(valores_split[0],valores_split[1],comentarios);


		});

		$("#btn_comentario_modal2").on("click", function ()
		{
      valores=$("#btn_comentario_modal2").val()
      valores_split=valores.split(",");
      comentarios2=$("#txt_comentario_loteria_horarios").val();
      // alert(comentarios2);
      // alert(valores_split[0]+" "+valores_split[1]);
      modificar_comentario_sorteo_hor(valores_split[0],valores_split[1],comentarios2);


		});

		$("#btn_seguimiento").click(function()
		{

			var selectedValue = $("input[name=radio_seg]:checked").val();
      // alert(selectedValue);
			switch (selectedValue) {
			case "1":
				$("#fechas_container").show();
				$("#contenedor2").show();
				$("#fechas_archivo").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
				break;
			case "2":

				$("#fechas_container").show();
				$("#contenedor2").show();
				$("#fechas_archivo").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
				break;
			case "3":
				$("#fechas_container").show();
				$("#contenedor2").show();
				$("#fechas_archivo").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();

				break;
			case "4":
				$("#fechas_archivo").show();
				$("#contenedor2").show();
				$("#fechas_container").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();



				break;
			case "5":
        // alert("5");
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
				$("#btnCon").click();

        $("#fechas_container").hide();
				$("#selec_fecha_1").show();
				$("#fechas_archivo").hide();
				$("#conte_tabla3").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();

        break;
			case "6":
        mostrar_tabla_6()
        // alert("6");
        $("#tabla_registro3").hide();
        $("#conte_tabla3").hide();
        $("#tabla_registro3").hide();
        $("#conte_tabla3").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
        $("#fechas_container").hide();
        $("#fechas_archivo").hide();
        // $("#tabla_registro6").show();
        // $("#conte_tabla6").show();
				break;
      case "8":
        // mostrar_tabla_6()
        // alert("8");
        $("#modalCrearHorarioLoteria").show();
        $("#modalCrearHorarioLoteria").modal("show");
        $("#tabla_registro6").hide();
        $("#conte_tabla6").hide();
        $("#tabla_registro3").hide();
        $("#conte_tabla3").hide();
        $("#tabla_registro3").hide();
        $("#conte_tabla3").hide();
        $("#conte_tabla3").hide()
        $("#tabla_registro3").hide()
        $("#conte_tabla6").hide();
        $("#tabla_registro6").hide();
        $("#conte_tabla2").hide();
        $("#tabla_registro2").hide();
        $("#fechas_container").hide();
        $("#fechas_archivo").hide();

        // $("#conte_tabla6").show();
				break;
				default:
          alertify.error("Seleccione una opcion de Reporte");
          break;
			}
			// Store the selected value for the AJAX request
		});

		$("#btn_hist").click(function()
		{

			var selectedValue = $("input[name=radio_seg2]:checked").val();
			switch (selectedValue) {
			case "1":
				$("#fechas_revision").show();
				$("#contenedor3").show();
				$("#fechas_historico").hide();
				break;
			case "2":

				$("#fechas_historico").show();
				$("#contenedor3").show();
				$("#fechas_revision").hide();
				break;
				default:
                alertify.error("Seleccione una opcion de Reporte");

           break;
			}
			// Store the selected value for the AJAX request
		});

    $("#btn_ActualizarLoteria").click(function()
    {

      codigo_loteriaF=$("#txt_codigo_loteria").val();
      nombre_loteriasF=$("#txt_nombre_loterias").val();
      estado_cierreF=$("#txt_estado_cierre").val();
      estado_sorteoF=$("#txt_estado_sorteo").val();
      horario_cierreF=$("#txt_horario_cierre").val();
      horario_sorteoF=$("#txt_horario_sorteo").val();


      let regex = /^\d{2}:\d{2}:\d{2}$/;
      let regex1 = /^\d{2}:\d{2}:\d{2}$/;

        if (regex.test(horario_cierreF))
        {
            // El formato es correcto
            // console.log("Formato de horario correcto:", horario_cierreF);
            alertify.success("Formato de horario cierre correcto");
            // alert("horario ok");
            // Aquí puedes proceder a guardar los datos o realizar otra acción
        } else
        {

          alertify.error("El horario cierre debe estar en el formato HH:MM:SS, por ejemplo, 12:30:00.");
          return;
            // $("#txt_horario_cierre").focus(); // Focalizar el campo para corregir
        }
        if (regex1.test(horario_sorteoF))
          {
              // El formato es correcto
              // console.log("Formato de horario correcto:", horario_cierreF);
              alertify.success("Formato de horario sorteo correcto");
              // alert("horario ok");
              // Aquí puedes proceder a guardar los datos o realizar otra acción
        } else
        {
          alertify.error("El horario sorteo debe estar en el formato HH:MM:SS, por ejemplo, 12:30:00.");
            // $("#txt_horario_cierre").focus(); // Focalizar el campo para corregir
            return;
        }

      // alert("estado_sorteoF".estado_sorteoF);
      const json =
              {
                con: "53.9",
                id_codigoF: id_codigoF,
                codigo_loteriaF: codigo_loteriaF,
                nombre_loteriasF: nombre_loteriasF,
                estado_cierreF: estado_cierreF,
                estado_sorteoF: estado_sorteoF,
                horario_cierreF: horario_cierreF,
                horario_sorteoF: horario_sorteoF,
                estado_p: 9

              }



        alertify.confirm("¿Está seguro de que desea cambiar los horarios y estados?",
          function()
          {
              // El usuario aceptó, enviar la petición AJAX
              $.ajax({
                url: ip_symfony+"datos24",
                type: "POST",
                data: JSON.stringify(json),
                headers:

                {

                  "Authentication": JSON.stringify(Authentication)
                },
                beforeSend:function()
                {
                  $(".cargando").show();
                  $("#btn_final").show();
                },
                success:function(dato)
                {
                  $(".cargando").hide();
                  if(dato[0].estado == 0)
                  {
                    //$("#alert-confirmacion").show();
                    alertify.success("Revision Actualizada");
                    $("#exampleModalControlHorario2").trigger("click");
                    mostrar_tabla_6()
                  }

                  else
                  {
                    alertify.error("No se pudo actualizar");
                    //$("#alert-negado").show();
                  }
                }
            });
          },
          function() {
            // El usuario canceló, no se envía la petición
            alertify.error("No se han realizado cambios.");
            $("#exampleModalControlHorario2").trigger("click");
            mostrar_tabla_6()
        }
      ).setHeader("Mensaje de Confirmacion.");
    });

    $("#btn_GuardarHorarioLoteria").click(function() {

      let codigo_loteriaI = $("#txt_codigo_loteria_nuevo").val();
      let nombre_loteriasI = $("#txt_nombre_loterias_nuevo").val();
      let estado_cierreI = $("#txt_estado_cierre_nuevo").val();
      let estado_sorteoI = $("#txt_estado_sorteo_nuevo").val();
      let horario_cierreI = $("#txt_horario_cierre_nuevo").val();
      let horario_sorteoI = $("#txt_horario_sorteo_nuevo").val();


      // alert("estado_sorteoF".estado_sorteoF);
      // alertify.error("La informacion esta immpleta");
      if (!codigo_loteriaI) {
          alertify.error("La información está incompleta: Código de lotería.");
          return; // Evita que la petición se envíe
      }

      if (!nombre_loteriasI) {
          alertify.error("La información está incompleta: Nombre de la lotería.");
          return; // Evita que la petición se envíe
      }

      if (!estado_cierreI) {
          alertify.error("La información está incompleta: Estado de cierre.");
          return; // Evita que la petición se envíe
      }

      if (!estado_sorteoI) {
          alertify.error("La información está incompleta: Estado de sorteo.");
          return; // Evita que la petición se envíe
      }

      if (!horario_cierreI) {
          alertify.error("La información está incompleta: Horario de cierre.");
          return; // Evita que la petición se envíe
      }

      if (!horario_sorteoI) {
          alertify.error("La información está incompleta: Horario de sorteo.");
          return; // Evita que la petición se envíe
      }
      let regex = /^\d{2}:\d{2}:\d{2}$/;
      let regex1 = /^\d{2}:\d{2}:\d{2}$/;

        if (regex.test(horario_cierreI))
        {
            // El formato es correcto
            // console.log("Formato de horario correcto:", horario_cierreF);
            alertify.success("Formato de horario cierre correcto");
            // alert("horario ok");
            // Aquí puedes proceder a guardar los datos o realizar otra acción
        } else
        {

          alertify.error("El horario cierre debe estar en el formato HH:MM:SS, por ejemplo, 12:30:00.");
          return;
            // $("#txt_horario_cierre").focus(); // Focalizar el campo para corregir
        }
        if (regex1.test(horario_sorteoI))
          {
              // El formato es correcto
              // console.log("Formato de horario correcto:", horario_cierreF);
              alertify.success("Formato de horario sorteo correcto");
              // alert("horario ok");
              // Aquí puedes proceder a guardar los datos o realizar otra acción
        } else
        {

          alertify.error("El horario sorteo debe estar en el formato HH:MM:SS, por ejemplo, 12:30:00.");
            // $("#txt_horario_cierre").focus(); // Focalizar el campo para corregir
            return;
        }





      const json = {
          con: "53.9",
          codigo_loteriaI: codigo_loteriaI,
          nombre_loteriasI: nombre_loteriasI,
          estado_cierreI: estado_cierreI,
          estado_sorteoI: estado_sorteoI,
          horario_cierreI: horario_cierreI,
          horario_sorteoI: horario_sorteoI,
          estado_p: 10
      };

      $.ajax({
          url: ip_symfony + "datos24",
          type: "POST",
          data: JSON.stringify(json),
          headers: {
              "Authentication": JSON.stringify(Authentication)
          },
          beforeSend: function() {
              $(".cargando").show();
              $("#btn_final").show();
          },
          success: function(respuesta) {
              $(".cargando").hide();
              if (respuesta[0].estado == 0) {
                  alertify.success("Horario Creado");
                  $("#btn_CerrarCrearHorario").trigger("click");
                  mostrar_tabla_6();
              } if (respuesta[0].estado == 1){
                alertify.error("El codigo de loteria ya existe");
              }if (respuesta[0].estado == 2){
                alertify.error("Los horarios ya existen para este código de loteria");
              }
              if (respuesta[0].estado == 3){
                alertify.error("Error al ingresar informacion del horario.");
              }
          }
      });

    });


  }); // fin ready.
  function mostrar_tabla_1()
  {
  $("#tabla_registro").show();
  var fecha_con=$("#fecha_con").val();

  if (fecha_con === "" || fecha_con === null)
  {
    alertify.error("Seleccione una Fecha");
    return false; // Detiene la ejecución de la solicitud AJAX
  }

  const json =
  {
  con: 53.9,
  fecha_con:fecha_con,
  estado_p:1
  }

  $.ajax
  ({
  url: ip_symfony+"datos24",
  type: "POST",
  data: JSON.stringify(json),
  headers: {
    "Authentication": JSON.stringify(Authentication)
  },
  beforeSend:function()
  {
    $(".cargando").show();
    $("#tabla_registro2").hide();
    $("#conte_tabla2").hide()
  },
  success:function(respuesta)
  {
    $(".cargando").hide();

    if(respuesta[0]["estado"] == "1")
    {

    $("#valida_pda").hide();
    $("#conte_regis_horarios").hide(500);
    $("#btn_registar_pda").hide();

    //$("#tabla_registro").show();
    $("#btn_asignar").show();
    $("#conte_tabla").show();
    $(".cargando").hide();
    let  estado ="";

    let cont = $("#body_tabla_sorteos_puntos tr").length;

    if (cont > 0)
    {

      tabla_registro.destroy();

    }
      //obj = jQuery.parseJSON(datos[1]);

      $("#body_tabla_sorteos_puntos").empty();

      objeto = respuesta;

      // alert("entre por 2");
      // console.log("objeto"+objeto);

      // console.log("respuesta"+respuesta);

      // console.log("respuesta "+respuesta[0].respuesta)
      objeto = JSON.parse(respuesta[0].respuesta);
      console.log("objeto"+objeto);

      objeto.forEach(obj =>
      {


        fila=
        "<tr style=background-color:#ffffff>"+
        "<td><font color=#000000 size=2>"+obj.codigo_loteria+"</font></td>"+
        "<td><font color=#000000 size=2>"+obj.nombre_loteria+"</font></td>"+
        "<td><font color=#000000 size=2>"+obj.numero_loteria+"</font></td>"+
        "<td><font color=#000000 size=2>"+obj.fecha_sorteo+"</font></td>"+
        "<td><button onclick=modificar_comentario("+obj.codigo_loteria+") class=\'form-control btn-warning btn-xs\'  data-bs-toggle=modal data-bs-target=#exampleModalControl data-bs-whatever=@getbootstrap style= margin-left: 34px;width:38px;height:30px;><img src=lib/fonts/pencil.svg width=15px height=15px style=display:block;margin:auto;></button></td>"+

        ///  "<td id=revisadocheck><input type=checkbox  name=checkbox value=" + obj.codigo_loteria +  " onchange=\"miFuncionDeManejoDeCambio2(" +obj.codigo_loteria+  ",\'" +obj.fecha_sorteo+  "\')\"></td>"+
        "<td id=revisadocheck><input type=checkbox " + (obj.estado == "1" ? "checked" : "") + " name=checkbox value=" + obj.codigo_loteria + " onchange=\"miFuncionDeManejoDeCambio2(" +obj.codigo_loteria+  ",\'" +obj.fecha_sorteo+  "\')\"></td>"+


        "</tr>";

        $("#body_tabla_sorteos_puntos").append(fila);

      });
      // alert("Termine :");

      tabla_registro=$("#tabla_registro").DataTable({
      "language" :{ "url": "lib/datatables_1.12.1/lenguajes/es-ES.json" },
      scrollX: false,
      autoWidth: true,
      order: [[1, "asc"]],
              select: "single",

      })

      // data_table=new DataTable("#tabla_registro");
      $("#tabla_registro").show();
    }
    else if (respuesta[0]["estado"] == "2")
    {
    $("#valida_pda").show();
    $("#btn_modificar_1").focus();
    }
    else if (respuesta[0]["estado"] == "4")
    {
      alertify.error("No se pudo cargar la informacion");
    }
  },
  error: function()
      {
        alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");

      }
  });
  }





	$("#btn_revision_pda").click(function()
    {
		let rad_seg = document.querySelector("input[name=radio_seg]:checked");
		if(rad_seg!=null)
		{
		tipoSeg= rad_seg.value;
		label=document.querySelector("label[for=" + rad_seg.getAttribute("id") + "]");
		titulo_segui=label.innerText;
        }


	});

	function mostrar_tabla_2()
	{
		console.log("tabla")
		$("#tabla_registro2").show();

		  var fecha_hor=$("#fecha_hor").val();

		  if (fecha_hor === "" || fecha_hor === null) {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		  }

		  const json =
		  {
		  con: 53.9,
		  fecha_hor:fecha_hor,
		  estado_p:4
		  }

		  $.ajax
		  ({
		  url: ip_symfony+"datos24",
		  type: "POST",
		  data: JSON.stringify(json),
		  headers: {
			  "Authentication": JSON.stringify(Authentication)
		  },
		  beforeSend:function()
		  {
			  $(".cargando").show();
			  $("#tabla_registro").hide();
			  $("#conte_tabla").hide();

		  },
		  success:function(respuesta)
		  {
			  $(".cargando").hide();

			  if(respuesta[0]["estado"] == "1")
			  {

			  $("#valida_pda").hide();
			  $("#conte_regis_horarios").hide(500);
			  $("#btn_registar_pda").hide();

			  //$("#tabla_registro").show();
			  $("#btn_asignar").show();
			  $("#conte_tabla2").show();


			  $(".cargando").hide();
			  let  estado ="";

			  let cont = $("#body_tabla_sorteos_horarios tr").length;

			  if (cont > 0)
			  {

				  tabla_registro2.destroy();

			  }
				  //obj = jQuery.parseJSON(datos[1]);

				  $("#body_tabla_sorteos_horarios").empty();

				  objeto = respuesta;

				//   alert("entre por 2");
				//   console.log("objeto"+objeto);

				//   console.log("respuesta"+respuesta);

				//   console.log("respuesta "+respuesta[0].respuesta)
				  objeto = JSON.parse(respuesta[0].respuesta);
				  console.log("objeto"+objeto);

				  objeto.forEach(obj2 =>
				  {


					  fila=
					  "<tr style=background-color:#ffffff>"+
					  "<td><font color=#000000 size=2>"+obj2.codigo_loteria+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj2.nombre_loterias+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj2.hora_final+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj2.horario_cierre+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj2.fecha_sorteo+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj2.usuario+"</font></td>"+
					  "<td><button onclick=modificar_comentario_hor("+obj2.codigo_loteria+") class=\'form-control btn-info btn-xs\'  data-bs-toggle=modal data-bs-target=#exampleModalControlHorarios data-bs-whatever=@getbootstrap style= margin-left: 34px;width:38px;height:30px;><img src=lib/fonts/pencil.svg width=15px height=15px style=display:block;margin:auto;></button></td>"+

					  ///  "<td id=revisadocheck><input type=checkbox  name=checkbox value=" + obj.codigo_loteria +  " onchange=\"miFuncionDeManejoDeCambio2(" +obj.codigo_loteria+  ",\'" +obj.fecha_sorteo+  "\')\"></td>"+
					  "<td id=revisadocheck><input type=checkbox " + (obj2.estado == "1" ? "checked" : "") + " name=checkbox value=" + obj2.codigo_loteria + " onchange=\"miFuncionDeManejoDeCambio2_hor(" +obj2.codigo_loteria+  ",\'" +obj2.fecha_sorteo+  "\')\"></td>"+


					  "</tr>";

					  $("#body_tabla_sorteos_horarios").append(fila);

				  });
				  // alert("Termine :");

				  tabla_registro2=$("#tabla_registro2").DataTable({
				  "language" :{ "url": "lib/datatables_1.12.1/lenguajes/es-ES.json" },
				  scrollX: false,
				  autoWidth: true,
				  order: [[1, "asc"]],
                  select: "single"


				  })

				  // data_table=new DataTable("#tabla_registro");
				  $("#tabla_registro2").show();
			  }
			  else if (respuesta[0]["estado"] == "2")
			  {
			  $("#valida_pda").show();
			  $("#btn_modificar_1").focus();
			  alertify.error("No hay registros de horarios seleccionados al sistema ");

			  alertify.notify("La informacion debera ser solictada del dia actual");
			  }
			  else if (respuesta[0]["estado"] == "4")
			  {
				  alertify.error("No se pudo cargar la informacion");
			  }
		  },
        error: function()
				{
          alertify.notify("Error en el Servidor comuniquese con su Administrador de Sistema");
          $("#btn_descargar_png").hide();
				}
		  });
	}

	function descargar_adjunto(nombre)
	{
	  let ruta="";
	  if(carpeta_actual == "consuertepruebas")
	  {
		ruta=ip_symfony+"/uploads/escrutinio/archivo_control/dev/"+nombre;
	  }
	  else
	  {
		ruta=ip_symfony+"/uploads/escrutinio/archivo_control/prod/"+nombre;
	  }

	  window.open(ruta,"_blank");
	}

	function obtenerIcono(extension)
  {
    switch (extension.toLowerCase())
    {
        case "pdf":
            return "icono_pdf.png";
        case "doc":
        case "docx":
            return "icono_word.png";
        case "xls":
        case "xlsx":
            return "icono_excel.png";
        case "jpg":
        case "png":
            return "icono_imagen.png"
        // Agrega más extensiones y sus correspondientes íconos aquí según sea necesario
        default:
            return "icono_generico.png";
    }
  }

  var id_codigoF;
  function modificar_horario_pda(id_reg) {

    // id_codigoF = id_reg;
    // alert("id_codigoF".id_codigoF);
    // Mostrar el modal correctamente
    $("#modalModificarLoteria").show();
    // $("#tabla_registro6").hide();
    // $("#conte_tabla6").hide();

    $("#modalModificarLoteria").modal("show");


    // Encuentra el objeto con el id_reg y carga los datos en el formulario
    const valor = objeto6.find(obj6 => obj6.id_codigo === id_reg);

    if (valor) {
        // Cargar los valores en el modal
        id_codigoF = valor.id_codigo;
        // alert("id_codigoF"+id_codigoF);
        $("#txt_codigo_loteria").val(valor.codigo_loteria);
        $("#txt_nombre_loterias").val(valor.nombre_loterias);
        $("#txt_estado_cierre").val(valor.estado_cierre);
        $("#txt_estado_sorteo").val(valor.estado_sorteo);
        $("#txt_horario_cierre").val(valor.horario_cierre);
        $("#txt_horario_sorteo").val(valor.horario_sorteo);
        $("#txt_fecha").val(valor.fecha_sys);
    } else {
        alert("No se encontró la lotería con el código: " + id_reg);
    }
}




function mostrar_tabla_6() {
  $("#tabla_registro6").show();
  const json = {
      con: 53.9,
      estado_p:8
  };

  $.ajax({
      url: ip_symfony + "datos24",
      type: "POST",
      data: JSON.stringify(json),
      headers: {
          "Authentication": JSON.stringify(Authentication)
      },
      beforeSend: function() {
          $(".cargando").show();
      },
      success: function(respuesta) {
          $(".cargando").hide();

          if (respuesta[0]["estado"] == "1") {
              $("#conte_tabla6").show();

              if ($("#body_tabla_nombre_loterias tr").length > 0) {
                  tabla_registro6.destroy();
              }

              $("#body_tabla_nombre_loterias").empty();

              // Parsear el JSON y guardar el objeto en una variable global
              objeto6 = JSON.parse(respuesta[0].respuesta);
              console.log("objeto6: ", objeto6);

              objeto6.forEach(obj6 => {
                  let estadoCierreTexto = "";
                  let estadoCierreTexto1 = "";

                  // Asignar el texto correspondiente según el valor de estado_sorteo
                  switch (obj6.estado_sorteo) {
                      case 1:
                          estadoCierreTexto1 = "Lunes a Sabado."; // Sábados y entre semana
                          break;
                      case 2:
                          estadoCierreTexto1 = "Solo Sábados."; // Solo sábados
                          break;
                      case 3:
                          estadoCierreTexto1 = "Festivos y Domingos."; // Horario festivos y domingos
                          break;
                      case 6:
                          estadoCierreTexto1 = "Semana Completa."; // Semana Completa.
                          break;
                      default:
                          estadoCierreTexto1 = "Desconocido"; // Para valores no esperados
                  }
                  switch (obj6.estado_cierre) {
                      case 1:
                          estadoCierreTexto = "Lunes a Sabado."; // Sábados y entre semana
                          break;
                      case 2:
                          estadoCierreTexto = "Solo Sábados"; // Solo sábados
                          break;
                      case 3:
                          estadoCierreTexto = "Festivos y Domingos"; // Horario festivos y domingos
                          break;
                      case 6:
                          estadoCierreTexto = "Semana Completa."; // Semana Completa.
                          break;
                      default:
                          estadoCierreTexto = "Desconocido"; // Para valores no esperados
                  }

                  let fila = `
                      <tr style="background-color:#ffffff">
                          <td>
                              <button onclick="modificar_horario_pda(${obj6.id_codigo})"
                                  class="form-control btn-info btn-xs"
                                  data-toggle="tooltip" title="Modificar"
                                  data-placement="top" style="width:48px;height:35px;">
                                  <img src="lib/fonts/apple-carplay.svg" width="20px" ; height="25px;margin-top: -2px;"
                                  style="display:block;margin-top: -2px;margin-left: 2px;">
                              </button>
                          </td>
                          <td><font color="#000000" size="2">${obj6.codigo_loteria}</font></td>
                          <td><font color="#000000" size="2">${obj6.nombre_loterias}</font></td>
                          <td><font color="#000000" size="2">${estadoCierreTexto}</font></td>
                          <td><font color="#000000" size="2">${estadoCierreTexto1}</font></td>
                          <td><font color="#000000" size="2">${obj6.horario_cierre}</font></td>
                          <td><font color="#000000" size="2">${obj6.horario_sorteo}</font></td>
                          <td><font color="#000000" size="2">${obj6.fecha_sys}</font></td>
                          <td><font color="#000000" size="2">${obj6.usuario}</font></td>
                      </tr>`;

                  $("#body_tabla_nombre_loterias").append(fila);
              });

              tabla_registro6 = $("#tabla_registro6").DataTable({
                  "language": { "url": "lib/datatables_1.12.1/lenguajes/es-ES.json" },
                  scrollX: false,
                  autoWidth: true,
                  order: [[1, "asc"]],
                  select: "single"
                  // initComplete: function () {
                  //   this.api().columns().every(function () {
                  //     let column = this;

                  //     // Create select element
                  //     let select = document.createElement("select");
                  //     select.classList.add("form-select");
                  //     select.add(new Option(""));
                  //     column.footer().replaceChildren(select);

                  //     // Apply listener for user change in value
                  //     select.addEventListener("change", function () {
                  //       column.search(select.value, { exact: true }).draw();
                  //     });

                  //     // Add list of options
                  //     column.data().unique().sort().each(function (d, j) {
                  //       let tempElement = document.createElement("div");
                  //       tempElement.innerHTML = d;
                  //       let textValue = tempElement.innerText;
                  //       select.add(new Option(textValue));
                  //     });
                  //   });
                  // }
              });

              $("#tabla_registro6").show();
          } else if (respuesta[0]["estado"] == "2") {
              $("#valida_pda").show();
              $("#btn_modificar_1").focus();
          } else if (respuesta[0]["estado"] == "3") {
              $("#msgbox").fadeTo(200, 0.1, function() {
                  $(this).html("No tiene una sesión activa, salga y vuelva a ingresar.")
                         .addClass("messageboxerror").fadeTo(200, 1);
              });
              setTimeout(function() {
                  $("#msgbox").fadeOut(500);
              }, 3000);
          }
      },
      error: function(error) {
          console.log("Error: ", error);
          alertify.notify("Error en el Servidor, comuníquese con su Administrador de Sistema");
      }
  });
}





	function mostrar_tabla_3()
	{
		console.log("tabla")
		$("#tabla_registro3").show();

		  var fecha_archivo=$("#fecha_archivo").val();

		  if (fecha_archivo === "" || fecha_archivo === null) {
			alertify.error("Seleccione una Fecha");
			return false; // Detiene la ejecución de la solicitud AJAX
		  }

		  const json =
		  {
		  con: 53.9,
		  fecha_archivo:fecha_archivo,
		  estado_p:7
		  }

		  $.ajax
		  ({
		  url: ip_symfony+"datos24",
		  type: "POST",
		  data: JSON.stringify(json),
		  headers: {
			  "Authentication": JSON.stringify(Authentication)
		  },
		  beforeSend:function()
		  {
			  $(".cargando").show();
			  $("#tabla_registro").hide();
			  $("#conte_tabla").hide();

		  },
		  success:function(respuesta)
		  {
			  $(".cargando").hide();

			  if(respuesta[0]["estado"] == "1")
			  {

			  $("#valida_pda").hide();
			  $("#conte_regis_horarios").hide(500);
			  $("#btn_registar_pda").hide();

			  //$("#tabla_registro").show();
			  $("#btn_asignar").show();
			  $("#conte_tabla3").show();


			  $(".cargando").hide();
			  let  estado ="";

			  let cont = $("#body_tabla_sorteos_archivos tr").length;

			  if (cont > 0)
			  {

				  tabla_registro3.destroy();

			  }
				  //obj = jQuery.parseJSON(datos[1]);

				  $("#body_tabla_sorteos_archivos").empty();

				  objeto = respuesta;


				  objeto = JSON.parse(respuesta[0].respuesta);
				  console.log("objeto"+objeto);

				  objeto.forEach(obj3 =>
				  {
					  fila=
					  "<tr style=background-color:#ffffff>"+
					  "<td><font color=#000000 size=2>"+obj3.nombre_archivo+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj3.archivo+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj3.fecha_archivo+"</font></td>"+
					  "<td><font color=#000000 size=2>"+obj3.nombre+"</font></td>"+
					  "<td><font size=2>";
						if(obj3.archivo)
						{
							adjuntos = obj3.archivo.split(",");
							adjuntos.forEach(element =>
							{
							if(element)
							{
								extension = element.split(".").pop().toLowerCase();

								console.log("extension: "+extension);
								icono=obtenerIcono(extension)

								fila+="<button type=button class=\'btn btn-link\' width=50% onclick=descargar_adjunto(\'"+element+"\')><img src=images/"+icono+" height=25 width=25></button>";
							}
							});
							fila+="</font></td>";
						}
						else
						{
							fila+="</font></td>";
						}
						fila+="</tr>";


					  "</tr>";

					  $("#body_tabla_sorteos_archivos").append(fila);

				  });
				  // alert("Termine :");

				  tabla_registro3=$("#tabla_registro3").DataTable({
				  "language" :{ "url": "lib/datatables_1.12.1/lenguajes/es-ES.json" },
				  scrollX: false,
				  autoWidth: true,
				  order: [[1, "asc"]],
                  select: "single"


				  })

				  // data_table=new DataTable("#tabla_registro");
				  $("#tabla_registro2").show();
			  }
			  else if (respuesta[0]["estado"] == "2")
			  {
				alertify.error("No se realizo el proceso correctamente, comuniquese con el Administrador del Sistema ");
			  }
			  else if (respuesta[0]["estado"] == "4")
			  {
				  alertify.error("No hay registro de la fecha seleccionada");
			  }
		  },

		  });
	}

	function cambia_background(id)
	{
		$(".menus").css("background-color","");
		$("#"+id).css("background-color","#CAD2D5");
	}

	function miFuncionDeManejoDeCambio2(codigo_loteria, fecha_sorteo)
	{
		var checkbox = document.querySelector("input[name=\"checkbox\"][value=\"" + codigo_loteria + "\"]");
		let estado = 1;
		if (checkbox.checked) estado = 2;
			console.log("Estado de la casilla:", estado);
			console.log("Código de la lotería:", codigo_loteria);
			console.log("Fecha del sorteo:", fecha_sorteo);

			// Puedes realizar otras acciones aquí según tus necesidades
			const json = {
				con: "53.9",
				estado: estado,
				id: codigo_loteria,
				fecha_con: fecha_sorteo,
				estado_p:2
			};

			$.ajax({
				url: ip_symfony + "datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers: {
					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend: function() {
					$(".cargando").show();
					$("#btn_final").show();
				},
				success: function(dato) {
					$(".cargando").hide();
					if (dato[0].estado == 0) {
						alertify.success("Revisión Actualizada");
					} else {
						alertify.error("No se pudo actualizar");
					}
				},
				error: function(xhr, status, error) {
					console.error("Error en la petición AJAX:", error);
				}
			});
	}

	function miFuncionDeManejoDeCambio2_hor(codigo_loteria, fecha_sorteo)
	{
    var checkbox = document.querySelector("input[name=\"checkbox\"][value=\"" + codigo_loteria + "\"]");
    let estado = 1;
    if (checkbox.checked) estado = 2;
		console.log("Estado de la casilla:", estado);
		console.log("Código de la lotería:", codigo_loteria);
		console.log("Fecha del sorteo:", fecha_sorteo);

		// Puedes realizar otras acciones aquí según tus necesidades
		const json = {
			con: "53.9",
			estado: estado,
			id: codigo_loteria,
			fecha_hor: fecha_sorteo,
			estado_p:5
		};

		$.ajax({
			url: ip_symfony + "datos24",
			type: "POST",
			data: JSON.stringify(json),
			headers: {
				"Authentication": JSON.stringify(Authentication)
			},
			beforeSend: function() {
				$(".cargando").show();
				$("#btn_final").show();
			},
			success: function(dato) {
				$(".cargando").hide();
				if (dato[0].estado == 0) {
					alertify.success("Revisión Actualizada");
				} else {
					alertify.error("No se pudo actualizar");
				}
			},
			error: function(xhr, status, error) {
				console.error("Error en la petición AJAX:", error);
			}
		});
	}

	function modificar_comentario_sorteo(codigo_loteria,fecha_sorteo,comentario)
	{


		console.log(codigo_loteria, fecha_sorteo, comentario);


		// 3. Prepare AJAX request dat
		 const json =
			{
				con: "53.9",
				comentario: comentario,
				id: codigo_loteria,
				fecha_con: fecha_sorteo,
				estado_p: 3

			}

			$.ajax({
				url: ip_symfony+"datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers:

				{

					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend:function()
				{
					$(".cargando").show();
					$("#btn_final").show();
				},
				success:function(dato)
				{
					$(".cargando").hide();
					if(dato[0].estado == 0)
					{
						//$("#alert-confirmacion").show();
						alertify.success("Revision Actualizada");
						$("#exampleModalControl2").trigger("click");
						mostrar_tabla_1()
					}

					else
					{
						alertify.error("No se pudo actualizar");
						//$("#alert-negado").show();
					}
		        }
	            });


	}
	function modificar_comentario_sorteo_hor(codigo_loteria,fecha_sorteo,comentario2)
	{
		console.log(codigo_loteria, fecha_sorteo, comentario2);

		// 3. Prepare AJAX request dat
		 const json =
			{
				con: "53.9",
				comentario2: comentario2,
				id: codigo_loteria,
				fecha_hor: fecha_sorteo,
				estado_p: 6

			}

			$.ajax({
				url: ip_symfony+"datos24",
				type: "POST",
				data: JSON.stringify(json),
				headers:

				{

					"Authentication": JSON.stringify(Authentication)
				},
				beforeSend:function()
				{
					$(".cargando").show();
					$("#btn_final").show();
				},
				success:function(dato)
				{
					$(".cargando").hide();
					if(dato[0].estado == 0)
					{
						//$("#alert-confirmacion").show();
						alertify.success("Revision Actualizada Comentario de Horario");
						$("#exampleModalControlCerrar").trigger("click");
						mostrar_tabla_2()
					}

					else
					{
						alertify.error("No se pudo actualizar");
						//$("#alert-negado").show();
					}
		        }
	            });


	}




	function modificar_comentario(id_reg)
	{
		console.log(id_reg);
		console.log(objeto);
		const objetoEncontrado = objeto.find(obj => obj.codigo_loteria == id_reg);
		if (objetoEncontrado) {
			$("#txt_comentario_loteria").val(objetoEncontrado.comentario);
			valores=objetoEncontrado.codigo_loteria+"," +objetoEncontrado.fecha_sorteo;
			$("#btn_comentario_modal").val(valores);
			//modificar_comentario_sorteo(objetoEncontrado.codigo_loteria, objetoEncontrado.fecha_sorteo, objetoEncontrado.comentario);
		} else {
			console.log("Objeto no encontrado");
		}
	}






	function modificar_comentario_hor(id_reg)
	{
		console.log(id_reg);
		console.log(objeto);
    $("#exampleModalControlHorarios").removeAttr("style");

		const objetoEncontrado = objeto.find(obj2 => obj2.codigo_loteria == id_reg);
		if (objetoEncontrado) {

			$("#txt_comentario_loteria_horarios").val(objetoEncontrado.comentario);
			valores=objetoEncontrado.codigo_loteria+"," +objetoEncontrado.fecha_sorteo+"," +objetoEncontrado.comentario;
			console.log("valores2"+valores);
			$("#btn_comentario_modal2").val(valores);
			//modificar_comentario_sorteo(objetoEncontrado.codigo_loteria, objetoEncontrado.fecha_sorteo, objetoEncontrado.comentario);
		} else {
			console.log("Objeto no encontrado");
		}
	}

    function returnRefresh(returnVal)
	{
       window.document.reload();
    }

    function ajuste_reporte()
    {
    	document.location="reporte_codesa_comred_ajustes.php";
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

       tr.resultadostr:hover {
 		 background-color: #505050;
 		 font-weight: bold;

		}
		#revisadocheck {
			display: grid;
			place-items: center;
		}

		#container-buttons{
			place-items: center;
		}
/*	.dataTables_info{
		font-size: white;
	}
	.dataTables_length label {
		color: white;
	  }
	 .dataTables_filter label{
		color: white;
	}
	.dataTables_empty{
		background-color: #ffffff;
	}*/
	div.dataTables_wrapper {
        width: 800px;
        margin: 0 auto;
    }

	#msm_sistema {
        font-size: 16px; /* Cambiar el tamaio de la tipografia */
        font-weight: bold; /* Fuente en negrita o bold */
        color: #ffffff; /* Color del texto */
        border-radius: 5px; /* Borde del boton */
        letter-spacing: 2px; /* Espacio entre letras */
        padding: 18px 30px; /* Relleno del boton */
        transition: all 300ms ease 0ms;
        box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
        z-index: 99;
        width: 400px;
        }


    #msm_sistema:hover {
    background-color: #286090; /* Color de fondo al pasar el cursor */
    box-shadow: 0px 15px 20px rgba(0, 0, 0, 0.3);
    transform: translateY(-7px);
    }


    @media only screen and (max-width: 600px) {
     #msm_sistema {
        font-size: 14px;
        padding: 12px 20px;
        bottom: 20px;
        right: 20px;
      }
    }

	.sys_success
     {
       display:block;
       background-color: #5cb85c; /* Color de fondo */
       color: #ffffff; /* Color del texto */
     }

	 .sys_error
	 {
	   display:block;
       background-color: #c70000; /* Color de fondo */
       color: #ffffff; /* Color del texto */
	 }

	.overlay
	{
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0,0,0,0.7); /* fondo semitransparente */
          z-index: 10; /* asegura que el contenedor esti en la parte superior */
          display: flex;
          justify-content: center;
          align-items: center;
	}
	.modal-footer .btn {
     margin: 0 auto;
    }

	.text-center
	{
		text-align: center;
	}

	.d-flex justify-content-between
	{
		display: flex;
		justify-content: space-between;
	}
	.border-bottom {
		border-bottom: 1px solid black;
	  }

	  .divider {
		border-top: 1px solid #ccc; /* Estilo de la linea */
		margin: 10px 0; /* Espaciado superior e inferior */
	}

	h3 {
		font-family: "Arial, Helvetica, sans-serif";
		font-size: 20px;
	  }

	  .button {
		display: inline-block;
		border-radius: 4px;
		background-color: #0831bd;
		border: none;
		color: #FFFFFF;
		text-align: center;
		font-size: 28px;
		font-size: 22px;
        padding: 10x;
		transition: all 0.5;
		cursor: pointer;
		margin: 5px;
	  }

	  .button span {
		cursor: pointer;
		display: inline-block;
		position: relative;
		transition: 0.2;
	  }

	  .button span:after {
		content: 3333; /* Representa una flecha hacia la derecha */
		position: absolute;
		opacity: 0;
		top: 0;
		right: -20px;
		transition: 0.5;
	  }

	  .button:hover span {
		padding-right: 25px;
	  }

	  .button:hover span:after {
		opacity: 1;
		right: 0;
	  }



	.button_active {
		position: relative;
		width: 170px;
        height: 39px;
		background-color: #000;
		display: flex;
		align-items: center;
		color: white;
		flex-direction: column;
		justify-content: center;
		border: none;
		padding: 12px;
		gap: 12px;
		border-radius: 8px;
		cursor: pointer;
		margin-right:8%;
		margin-top : 29px
	}

	.button_active::before {
		content: "";
		position: absolute;
		inset: 0;
		left: 0px;
		top: -1px;
		margin: 35px
		width: 170px;
		height: 40px;
		border-radius: 10px;
		background: linear-gradient(-45deg, #0064ff 0%, #13a8e2 100% );
		z-index: -10;
		pointer-events: none;
		transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
	}

	.button_active::after {
		content: "";
		z-index: -1;
		position: absolute;
		inset: 0;
		background: linear-gradient(-45deg, #5e9dff  0%, #3639ff 100% );
		transform: translate3d(0, 0, 0) scale(0.95);
		filter: blur(20px);
	}

	.button_active:hover::after {
		filter: blur(30px);
	}

	.button_active:hover::before {
		transform: rotate(-180deg);
	}

	.button_active:active::before {
		scale: 0.7;
	}

/// boton es
	.button_repet {
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 6px 12px;
		gap: 8px;
		height: 34px;
		width: 112px;
		border: none;
		background: #ffffff;
		border-radius: 15px;
		cursor: pointer;

	}

	.lable {
		line-height: 20px;
		font-size: 17px;
		color: #212529;
		font-family: sans-serif;
		//font-family: "Arial, Helvetica, sans-serif";
		letter-spacing: 1px;



		width: 100px;
		height: 36px;
	}
	.button_repet {
		border-radius: 12px;
		background: #ffc20c;
  		width: 253px;
  		height: 43px;
		margin-top: 35px;

		margin-right: 39px
	}


	.button_repet:hover {
		background: #ff9b00eb;
	}

	.button_repet:hover .svg-icon {
		animation: spin 2s linear infinite;
	}

	@keyframes spin {
		0% {
		transform: rotate(0deg);
		}

		100% {
		transform: rotate(-360deg);
		}
	}

	.centrar-contenido
	{
		display: flex;
		flex-direction: column;
		align-items: center;
	  }

	.centrar-contenido label
	{
		margin-bottom: 0.5rem;
	}

	//check de validad
	.ui-bookmark {
		--icon-size: 24px;
		--icon-secondary-color: rgb(77, 77, 77);
		--icon-hover-color: rgb(97, 97, 97);
		--icon-primary-color: gold;
		--icon-circle-border: 1px solid var(--icon-primary-color);
		--icon-circle-size: 35px;
		--icon-anmt-duration: 0.3s;
	  }

	  .ui-bookmark input {
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		display: none;
	  }

	  .ui-bookmark .bookmark {
		width: var(--icon-size);
		height: auto;
		fill: var(--icon-secondary-color);
		cursor: pointer;
		-webkit-transition: 0.2s;
		-o-transition: 0.2s;
		transition: 0.2s;
		display: -webkit-box;
		display: -ms-flexbox;
		display: flex;
		-webkit-box-pack: center;
		-ms-flex-pack: center;
		justify-content: center;
		-webkit-box-align: center;
		-ms-flex-align: center;
		align-items: center;
		position: relative;
		-webkit-transform-origin: top;
		-ms-transform-origin: top;
		transform-origin: top;
	  }

	  .bookmark::after {
		content: "";
		position: absolute;
		width: 10px;
		height: 10px;
		-webkit-box-shadow: 0 30px 0 -4px var(--icon-primary-color),
		  30px 0 0 -4px var(--icon-primary-color),
		  0 -30px 0 -4px var(--icon-primary-color),
		  -30px 0 0 -4px var(--icon-primary-color),
		  -22px 22px 0 -4px var(--icon-primary-color),
		  -22px -22px 0 -4px var(--icon-primary-color),
		  22px -22px 0 -4px var(--icon-primary-color),
		  22px 22px 0 -4px var(--icon-primary-color);
		box-shadow: 0 30px 0 -4px var(--icon-primary-color),
		  30px 0 0 -4px var(--icon-primary-color),
		  0 -30px 0 -4px var(--icon-primary-color),
		  -30px 0 0 -4px var(--icon-primary-color),
		  -22px 22px 0 -4px var(--icon-primary-color),
		  -22px -22px 0 -4px var(--icon-primary-color),
		  22px -22px 0 -4px var(--icon-primary-color),
		  22px 22px 0 -4px var(--icon-primary-color);
		border-radius: 50%;
		-webkit-transform: scale(0);
		-ms-transform: scale(0);
		transform: scale(0);
	  }

	  .bookmark::before {
		content: "";
		position: absolute;
		border-radius: 50%;
		border: var(--icon-circle-border);
		opacity: 0;
	  }

	  /* actions */

	  .ui-bookmark:hover .bookmark {
		fill: var(--icon-hover-color);
	  }

	  .ui-bookmark input:checked + .bookmark::after {
		-webkit-animation: circles var(--icon-anmt-duration)
		  cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
		animation: circles var(--icon-anmt-duration)
		  cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
		-webkit-animation-delay: var(--icon-anmt-duration);
		animation-delay: var(--icon-anmt-duration);
	  }

	  .ui-bookmark input:checked + .bookmark {
		fill: var(--icon-primary-color);
		-webkit-animation: bookmark var(--icon-anmt-duration) forwards;
		animation: bookmark var(--icon-anmt-duration) forwards;
		-webkit-transition-delay: 0.3s;
		-o-transition-delay: 0.3s;
		transition-delay: 0.3s;
	  }

	  .ui-bookmark input:checked + .bookmark::before {
		-webkit-animation: circle var(--icon-anmt-duration)
		  cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
		animation: circle var(--icon-anmt-duration)
		  cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
		-webkit-animation-delay: var(--icon-anmt-duration);
		animation-delay: var(--icon-anmt-duration);
	  }

	  @-webkit-keyframes bookmark {
		50% {
		  -webkit-transform: scaleY(0.6);
		  transform: scaleY(0.6);
		}

		100% {
		  -webkit-transform: scaleY(1);
		  transform: scaleY(1);
		}
	  }

	  @keyframes bookmark {
		50% {
		  -webkit-transform: scaleY(0.6);
		  transform: scaleY(0.6);
		}

		100% {
		  -webkit-transform: scaleY(1);
		  transform: scaleY(1);
		}
	  }

	  @-webkit-keyframes circle {
		from {
		  width: 0;
		  height: 0;
		  opacity: 0;
		}

		90% {
		  width: var(--icon-circle-size);
		  height: var(--icon-circle-size);
		  opacity: 1;
		}

		to {
		  opacity: 0;
		}
	  }

	  @keyframes circle {
		from {
		  width: 0;
		  height: 0;
		  opacity: 0;
		}

		90% {
		  width: var(--icon-circle-size);
		  height: var(--icon-circle-size);
		  opacity: 1;
		}

		to {
		  opacity: 0;
		}
	  }

	  @-webkit-keyframes circles {
		from {
		  -webkit-transform: scale(0);
		  transform: scale(0);
		}

		40% {
		  opacity: 1;
		}

		to {
		  -webkit-transform: scale(0.8);
		  transform: scale(0.8);
		  opacity: 0;
		}
	  }

	  @keyframes circles {
		from {
		  -webkit-transform: scale(0);
		  transform: scale(0);
		}

		40% {
		  opacity: 1;
		}

		to {
		  -webkit-transform: scale(0.8);
		  transform: scale(0.8);
		  opacity: 0;
		}
	  }

	  .checkbox-container {
		display: inline-block;
		position: relative;
		padding-left: 35px;
		margin-bottom: 18px;
		cursor: pointer;
		font-size: 16px;
		user-select: none;
	  }

	  .custom-checkbox {
		position: absolute;
		opacity: 0;
		cursor: pointer;
		height: 0;
		width: 0;
	  }

	  .checkmark {
		position: absolute;
		top: 0;
		left: 0;
		height: 25px;
		width: 25px;
		background-color: #eee;
		border-radius: 4px;
		transition: background-color 0.3s;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
	  }

	  .checkmark:after {
		content: "";
		position: absolute;
		display: none;
		left: 9px;
		top: 5px;
		width: 5px;
		height: 10px;
		border: solid white;
		border-width: 0 3px 3px 0;
		transform: rotate(45deg);
	  }

	  .custom-checkbox:checked ~ .checkmark {
		background-color: #2196F3;
		box-shadow: 0 3px 7px rgba(33, 150, 243, 0.3);
	  }

	  .custom-checkbox:checked ~ .checkmark:after {
		display: block;
	  }

	  @keyframes checkAnim {
		0% {
		  height: 0;
		}

		100% {
		  height: 10px;
		}
	  }

	  .custom-checkbox:checked ~ .checkmark:after
	  {
		animation: checkAnim 0.2s forwards;
	  }
	  ///aca
	  .button_conf{
		border: 2px solid #24b4fb;
		background-color: #24b4fb;

		padding: 0.8em 1.2em 0.8em 1em;
		transition: all ease-in-out 0.2s;
		font-size: 16px;
		justify-content: center;
		align-items: center;

	   }

	   .button_conf span {
		display: flex;
		justify-content: center;
		align-items: center;
		color: #fff;
		font-weight: 600;
	   }

	   .button_conf:hover {
		background-color: #0071e2;
	   }
	   .button_conf{
	    border: 2px solid #24b4fb;
		background-color: #24b4fb;
		border-radius: 12px;

	   }
	   .button_conf{
	   justify-content: center;
		align-items: center;
	   }



	   .centered-button {

		left: 50%;
		transform: translateX(-50%);
	  }
	  .cargando {
		display: flex; /* Enable flexbox */
		justify-content: center; /* Center content horizontally */
		align-items: center; /* Center content vertically */
	  }




    </style>';
    }

  function menu()
  {
    global $cnn, $nivel;
    echo '<ul class="sf-menu">';


	$sql="SELECT mr.id,mn.nombre,mr.enlace,menup, mr.asociado, mr.orden, mr.suborden FROM menu_reportes mr, menu_reportes_nombres mn
      where mr.nombre=mn.id and permisos like'%,".$nivel.",%' order by mr.orden,mr.suborden ";

    $res=$cnn->Execute($sql);
	$num_rows = $res->RecordCount();


	$x=0;
	$aso="0";
	$aso1="";
	$x1="";
	$c=1;
	$x1[0]=0;
    while (!$res->EOF)
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
	/*echo '
	<link rel="stylesheet" href="lib/autocomplete jquery 1.13.1/jquery-ui.css">
    <script src="lib/autocomplete jquery 1.13.1/jquery-ui.js"></script>';*/

    global $nivel, $nickname, $cnn,$t_inv;
    $fechaini=date('Y-m-d');
    echo '
	<tr>
      	<td height="10" colspan="2"  class="text">
			<table width="90%" align="center" class="text">
				<tr>
					<td>

						<br>
						<input type=hidden id=tiemposesion name=tiemposesion >
						<input type=hidden id=estadotiempo name=estadotiempo value=0 >

						<input type=hidden id=usuario name=usuario value='.$nickname.'>
						<input type=hidden id=nivel name=nivel value='.$nivel.'>
						<input type=hidden id=t_inv name=t_inv value='.$t_inv.'>

						<center>
						  <div class="overlay" id="modal_msm" style="display:none;">
							<div class="sys_success" id="msm_sistema" href="#"></div>
						  </div>
						</center>



						<div align=center id=contenedor_registro_horarios style="display:none; ;">

							<h5 class="modal-title text-center container-fluid" style="font-size: 1.85rem; color: #fff; font-weight: bold; ;" id="exampleModalLabel">MODULO ESCRUTINIO ONLINE</h5>
								<div style="display:flex;width:52%;margin-top: 20px;margin-right: 75px;" id="container-buttons">
									<div style="display:flex;">
									  <button type="button" id="btn_programar_pda" class="btn btn-primary m-1" data-toggle="modal" data-target="#modal_programar_pda">
									    Validacion Loterias
									  </button>
									</div>
									<div style="display:flex;">
									  <button type="button" id="btn_consultar_pda" class="btn btn-danger m-1" data-toggle="modal" data-target="#modal_programar_pda">
									    Consultar Loterias
									  </button>
									</div>
									<div style="display:flex;">
									  <button type="button" id="btn_revision_pda" class="btn btn-warning m-1" data-toggle="modal" data-target="#modal_programar_pda">
									    Reporte Revision
									  </button>
									</div>
									<div style="display:flex;">
										<button type="button" id="btn_resultados_m" class="btn btn-success m-1" data-bs-toggle="modal" data-bs-target="#ModalResultados">
										Publicacion Resultados
										</button>
									</div>
									<div style="display:flex;">
									  <button type="button" id="btn_consultar_lot" class="btn btn-info m-1" data-toggle="modal" data-target="#accordionControl">
									    Control Interno
									  </button>
									</div>


								</div>

								<br>




								<div class="modal fade" id="exampleModalResultados" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
								<div class="modal-dialog">
									<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="staticBackdropLabel" >Visual de Plantilla Resultados PDF</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<div id="selec_fecha" >
											<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:10% with:70% ;color:black" >Seleccione una Fecha para visualizar </div>
											<label>Fecha Plantilla:</label>
											<input type="text" id="fecha_resultado" class="form-control" />
										</div>
										<hr/>
									</div>
									<div class="modal-footer">
										<button type="button"  id="ModalResultadosCerrarPDF" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
										<button type="button"  id="btn_enviar" class="btn btn-primary">Descargar Plantilla PDF</button>
									</div>
									</div>
								</div>
								</div>






								<div id="fechas_valida" style="display:none ;background-color: white; border-radius: 5px; padding-bottom: 1%; ">
									<div class="container">
									<h2 style="text-align: center;color:black; font-weight: bold;">Validacion de Resultados </h2>
							         <hr/>
										<div id="selec_fecha" >
									     <div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100 ;color:black" >Seleccione una Fecha para ver los resultados de las loterias.</div><br
										<label>Fecha Inicial:</label>
										<input type="text" id="fechav" class="form-control" />
									    </div>
										<hr/>
										<div class="form-group col-md-12">
											<center>
												<button type="button" id="btn_crear_loteria" class="btn btn-info">Visualizar Formulario</button>
											</center>
										</div>
										<hr/>
								    </div>
								</div>



								<div id="fechas_archivo" style="display:none ;background-color: white; border-radius: 5px; padding-bottom: 1%; ">
									<div class="container">
									<h2 style="text-align: center;color:black; font-weight: bold;">Revision Archivos Loterias</h2>
							         <hr/>
										<div id="selec_fecha" >
									     <div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100 ;color:black" >Seleccione una Fecha para ver los resultados Archivos.</div><br
										<label>Fecha Inicial:</label>
										<input type="text" id="fecha_archivo" class="form-control" />
									    </div>
										<hr/>
										<div class="form-group col-md-12">
											<center>
												<button type="button" id="btn_busca_archivo" class="btn btn-danger" onclick="mostrar_tabla_3()">Buscar Archivo</button>
											</center>
										</div>
										<hr/>
								    </div>
								</div>


								<div id="fechas_revision" style="display:none ;background-color: white; border-radius: 5px; padding-bottom: 1%;">
									<div class="container">
									<h2 style="text-align: center; font-weight: bold;color :black">Resultados Historico Loterias</h2>
							         <hr/>
										<div id="selec_fecha" >
									<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100 ; color :black;" >Seleccione una Fecha para ver los resultados de las loterias historica.</div><br
										<label>Fecha Inicial:</label>
										<input type="text" id="fechai" class="form-control" />
									    </div>
										<div class="form-group col-md-12">
											<center>
												<button type="button" id="btn_buscar_loteria" class="btn btn-success">Visualizar Historico</button>
											</center>
											</div>
											<hr/>
											<button class="button_conf" id="btn_revision" >
												<span>
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="30px" height="30px">
													<path fill="none" d="M0 0h24v24H0z"></path>
													<path fill="currentColor" d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"></path>
													</svg> Confirmar Revision
												</span>
											</button>
											<hr/>
										</div>
									</div>
								</div>

								<div id="fechas_historico" style="display:none ;background-color: white; border-radius: 5px; padding-bottom: 1%;">
									<div class="container">
									<h2 style="text-align: center; font-weight: bold;color :black">Resultados Loterias</h2>
							         <hr/>
										<div id="selec_fecha" >
									<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100 ; color :black;" >Seleccione una Fecha para ver los resultados de las loterias.</div><br
										<label>Fecha Inicial:</label>
										<input type="text" id="fecha_his" class="form-control" />
									    </div>
										<div class="form-group col-md-12">
											<center>
												<button type="button" style = "margin-top :20px" id="btn_buscar_loteria_historico" class="btn btn-primary">Visualizar</button>
											</center>
										</div>

										</div>
									</div>
								</div>














								<div id="fechas_container" style="display:none ;background-color: white;border-radius:5px;padding-bottom: 1%;">
									<div class="container">
										<h2 style="text-align: center; font-weight: bold;">Reporte de Revision</h2>
										<hr/>
										<div id="selec_fecha" >
									<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100" >Seleccione una Fecha  Incial para ver las revisiones realizadas.</div><br
										<label>Fecha Inicial:</label>
										<input type="text" id="fecha_r" class="form-control" />
									</div>
									<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100" >Seleccione una Fecha Final para ver las revisiones realizadas.</div><br
										<label>Fecha Final:</label>
										<input type="text" id="fecha_r2" class="form-control" />
									</div>
										<div class="form-group col-md-12">
										<hr/>
										<center>
												<button type="button" id="btnEnvioreporte" class="btn btn-primary">Buscar Revision</button>
										</center>
										</div>
										<hr/>
								</div>


								 <div id="conte_tabla2" style="margin-top:15px;background-color:#c0d9ff;padding-right:5%;padding-top:2%;border-radius: 5px;margin-left:-80px;margin-right:-80px;">
										<table id="tabla_registro2" class="table table-hover table-condense table-bordered" style="margin-top:15px;display:none;width:110%;table-layout: fixed; border-right-width: 5px; margin-left: 0;">
											<thead style="background-color:#03225f;">
													<tr>
														<th style="width: 4%;"><strong><b><font color="#ffffff" size="2">Codigo&nbsp; Sorteo</font></b></strong></th>
														<th style="width: 9%;"><strong><b><font color="#ffffff" size="2">Nombre&nbsp;Loteria</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Hora Bnet</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Hora Sistema</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Fecha Sorteo</font></b></strong></th>
														<th style="width: 9%;"><strong><b><font color="#ffffff" size="2">Usuario&nbsp;Horario</font></b></strong></th>
														<th style="width: 5%"><strong><b><font color="#ffffff" size="2">Comentario</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Revisado</font></b></strong></th>
													</tr>
											</thead>
											<tbody id="body_tabla_sorteos_horarios"></tbody>

										</table>


								 </div>

								 <div id="conte_tabla3" style="margin-top:15px;background-color:#c0d9ff;padding-right:5%;padding-top:2%;border-radius: 5px;margin-left:-80px;margin-right:-80px;">
										<table id="tabla_registro3" class="table table-hover table-condense table-bordered" style="margin-top:15px;display:none;width:110%;table-layout: fixed; border-right-width: 5px; margin-left: 0;">
											<thead style="background-color:#03225f;">
													<tr>
														<th style="width: 7%;"><strong><b><font color="#ffffff" size="2">Nombre&nbsp;Guardado</font></b></strong></th>
														<th style="width: 11%;"><strong><b><font color="#ffffff" size="2">Nombre&nbsp;Archivo</font></b></strong></th>
														<th style="width: 4%;"><strong><b><font color="#ffffff" size="2">Fecha Archivo</font></b></strong></th>
														<th style="width: 8%;"><strong><b><font color="#ffffff" size="2">Usuario&nbsp;Horario</font></b></strong></th>
														<th style="width: 5%"><strong><b><font color="#ffffff" size="2">Descargar</font></b></strong></th>

													</tr>
											</thead>
											<tbody id="body_tabla_sorteos_archivos"></tbody>

										</table>


								 </div>
                  <div id="conte_tabla6" style="background-color: rgb(156, 190, 255);padding:2%;border-radius: 5px; margin: auto;width: 110% ;margin-left: -40px;">
                  <table id="tabla_registro6" class="table table-hover table-condense table-bordered" style="width:100%; display:none; border-right-width: 5px;">
                    <thead style="background-color:#03225f">
                      <tr>
                        <th style="width: 10%;"><strong><b><font color="#ffffff" size="2">Editar</font></b></strong></th>
                        <th style="width: 15%;"><strong><b><font color="#ffffff" size="2">Código Lotería</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Nombre Lotería</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Estado de Cierre</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Estado de Sorteo</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Horario de Cierre</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Horario de Sorteo</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Fecha Actualizado</font></b></strong></th>
                        <th style="width: 20%;"><strong><b><font color="#ffffff" size="2">Usuario Actualiza</font></b></strong></th>
                      </tr>
                    </thead>
                    <tbody id="body_tabla_nombre_loterias">
                      <!-- Aquí se agregarán dinámicamente las filas -->
                    </tbody>
                  </table>
                </div>



								 <div id="conte_tabla" style="margin-top:15px;background-color:#c0d9ff;padding-right:5%;padding-top:2%;border-radius: 5px;margin-left:-80px;margin-right:-80px;">
										<table id="tabla_registro" class="table table-hover table-condense table-bordered" style="margin-top:15px;display:none;width:110%;table-layout: fixed; border-right-width: 5px; margin-left: 0;">
											<thead style="background-color:#03225f;">
													<tr>
														<th style="width: 3%;"><strong><b><font color="#ffffff" size="2">Codigo&nbsp; Sorteo</font></b></strong></th>
														<th style="width: 8%;"><strong><b><font color="#ffffff" size="2">Nombre&nbsp;Loteria</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Numero&nbsp; Sorteo</font></b></strong></th>
														<th style="width: 8%;"><strong><b><font color="#ffffff" size="2">Fecha Sorteo</font></b></strong></th>
														<th style="width: 5%"><strong><b><font color="#ffffff" size="2">Comentario</font></b></strong></th>
														<th style="width: 5%;"><strong><b><font color="#ffffff" size="2">Revisado</font></b></strong></th>
													</tr>
											</thead>
											<tbody id="body_tabla_sorteos_puntos"></tbody>
										</table>

										<center>
												<button type="button" id="btnCon" style="margin-top: 22px;margin-bottom: 20px;" data-bs-toggle="modal" data-bs-target="#ExampleModalAgregar" class="btn btn-primary">Adjuntar Documento</button>
										</center>
								 </div>







								<div class="d-flex align-items-center justify-content-center" style="height: 30px;">
									<input type="text" name="base64" id="base64" style="display: none">
									<canvas id="pdfCanvas" style="display: none"></canvas>
									<a id="downloadLink" download="resultado.png"></a>
								</div>

								<form action="" id="upload" enctype="multipart/form-data">
								<!-- Input file hidden -->

								<input type="submit" id="SubmitForm" value="Submitar" style="display:none">
								</form>
								<div class="modal fade" id="ModalResultados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
								<div class="modal-dialog modal-lg">
									<div class="modal-content"; style="width: 120% ; margin-left: -90px">
									<div class="modal-header">
										<h5 class="modal-title" id="exampleModalLabel">Publicacion de Resultados Redes Sociales</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
									Este modulo visualiza los Resultados de la loteria en el dia
									</div>
									<div class="modal-footer">
										<button type="button" id="btn_Examinar"  class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop">Subir Imagen</button>
										<button type="button"id="btn_bal" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModalBaloto">Agregar Balotas</button>

										<button type="button" id="btn_visualizar" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#exampleModalResultados">Visualizar Plantilla</button>
										<button type="button"  id="btn_tipo" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModalCategoria"> Descargar Imagen</button>
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
									</div>
									</div>
								</div>
								</div>

								<form id="formulario_control" enctype="multipart/form-data">
									<div class="modal fade" id="ExampleModalAgregar" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="staticBackdropLabel" style="text-align: center;  margin-left: 156px;">Control Interno</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
												</div>
												<div class="modal-body">

												<div class="container">
												<h2 style="text-align: center; font-weight: bold;">Agregar Archivo de Resultados</h2>
												<hr/>
												<div id="selec_fecha_1" >
													<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:15% with 100 ;color:black" >Seleccione una Fecha de Cargue.</div><br
													<label>Fecha Archivo:</label>
													<input type="text" id="fecha_cargue" class="form-control" />
												</div>
												<hr/>
												<div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
													<label for="inputCity">Nombre Archivo</label>
													<input type="text" class="form-control form-control-sm" id="nombre_i"  placeholder="" required >
												</div>
												<hr/>
												<input type="file" name="myfile" id="adj_seg_envio_a" enctype="multipart/form-data" class="form-control" accept=".pdf" multiple required></input>

												</div>
												<div class="modal-footer">
													<button id ="exitModalPromocional"type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
													<button type="submit" id="btn_agregar1" class="btn btn-warning">Agregar</button>
												</div>
												</div>
										    </div>
									    </div>
									</div>
								</form>

                <form id="formulario" enctype="multipart/form-data">
                  <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="staticBackdropLabel">Agregar Nueva Imagen de la Publicación</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="container">
                            <h2 style="text-align: center; font-weight: bold;">Asignacion de Horarios Promocional</h2>
                            <hr/>
                            <div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
                              <label for="inputCity">Nombre Promocional</label>
                              <input type="text" class="form-control form-control-sm" id="nombre_p" placeholder="EJM : PROMOCIONAL BALOTO" required>
                            </div>
                            <hr/>
                            <div class="col-md-4" style="margin-top: 25px; text-align: center;width:  450px;">
                              <label for="estado_p">Estado Promocional</label>
                              <select class="form-select form-select-sm" id="estado_p" required>
                                <option value="1">Activo</option>
                                <option value="2">Desactivado</option>
                              </select>
                            </div>
                            <hr/>
                            <div id="selec_fecha">
                              <div class="form-group col-md-6 col-md-offset-3 column centrar-contenido" style="padding-left:15% with 100">Seleccione una Fecha Inicial</div><br>
                              <label>Fecha Inicial Promocional :</label>
                              <input type="text" id="fecha_p1" class="form-control" required />
                            </div>
                            <div class="form-group col-md-6 col-md-offset-3 column centrar-contenido" style="padding-left:15% with 100">Seleccione una Fecha Final</div><br>
                            <label>Fecha Final Promocional:</label>
                            <input type="text" id="fecha_p2" class="form-control" required />
                            <hr/>
                            <input type="file" name="myfile" id="adj_seg_envio" class="form-control" accept=".png" required>

                            <!-- Vista previa de la imagen cargada -->
                            <div id="imagePreviewContainer" style="margin-top: 20px; text-align: center;">
                              <img id="imagePreview" src="" alt="Vista previa de la imagen" style="max-width: 100%; height: auto; display: none;" />
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button id="exitModalPromocional" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="btn_agregar" class="btn btn-warning">Agregar</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>



								<form id="formulario_balota" >
									<div class="modal fade" id="exampleModalBaloto" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
									<div class="modal-dialog">
										<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="exampleModalLabel">Agregar Balotas</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">

										<div id="selec_fecha" >
											<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido " style="padding-left:10% with:70% ;color:black" >Seleccione fecha de Registro de Balota </div>
											<hr/>
											<label>Fecha de Balota:</label>
											<input type="text" id="fecha_balota" class="form-control" />
										    <hr/>
										</div>


										<div class="modal-body">
													<select class="form-select" aria-label="Default select example" id="txt_tipo_balota" required>
														<option selected>Seleccione el tipo de Balota</option>
														<option value="1">Baloto</option>
														<option value="2">Baloto Revancha</option>
														<option value="3">Miloto</option>
                            <option value="4">Color-Loto</option>
													</select>
											</div>
											<hr/>
											<div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
											<label for="inputCity">Dia de Balota</label>
											<input type="text" class="form-control form-control-sm" id="dia_balota" required>
											</div>
											<hr/>
											<div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
											<label for="inputCity">Numero de Balota</label>
											<input type="text" class="form-control form-control-sm" id="numero_b"  placeholder="EJM : 09.11.21.24.41.06" required >
											</div>
											<hr/>

											</div>
											<div class="modal-footer">
												<button type="button"  id ="exitModalBaloto" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												<button type="submit"  id="btn_balota"  class="btn btn-primary">Guardar Cambios</button>
											</div>

										</div>
									</div>
									</div>
								</form>


								<div id="contenedor3" style="display: flex; background:white; flex-direction: column; align-items: center; border-radius: 15px; border: 2px solid #ccc; padding: 20px; width: 95%; margin: 0 auto;">
										<div style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 20px;">
											<div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg2" id="opcion1" value="1">
													<label class="form-check-label" for="opcion1" style="font-size: 1.2rem; ">
														Reporte Historico Resultados
													</label>
												</div>

											</div>
											<div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg2" id="opcion2" value="2">
													<label class="form-check-label" for="opcion4" style="font-size: 1.2rem; ">
													   Reporte Revision Resultados
													</label>
												</div>
											</div>
									</div>
									<button id="btn_hist" type="button" class="btn btn-danger" style="align-self: center;">Buscar Reporte</button>
								</div>

								<div class="modal fade" id="exampleModalCategoria" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
										<div class="modal-dialog modal-dialog-centered modal-dialog modal-lg">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="exampleModalLabel">Tipo de Resultados Redes Sociales</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
												</div>
												<div class="modal-body">
													<div id="selec_fecha">
														<div class="form-group col-md-6 col-md-offset-3 column centrar-contenido" style="padding-left:10px; color:black;">Seleccione una Fecha para visualizar</div>
														<hr/>
														<label>Fecha de Resultados RPA:</label>
														<input type="text" id="fecha_resultado2" class="form-control" />
														<hr/>
													</div>
													<select class="form-select" aria-label="Default select example" id="txt_tipo_resultados">
														<option selected>Seleccione el tipo de Imagen</option>
														<option value="1">Mañana</option>
														<option value="2">Tarde</option>
														<option value="3">Noche</option>
													</select>
													<hr/>
													<div class="form-check" style="padding-left: 60px;">
                              <div class="d-inline-block me-3">
                                  <input class="form-check-input" type="radio" name="radio_imagen" id="opcion54" value="1">
                                  <label class="form-check-label" for="opcion54" style="font-size: 1rem; margin-top: -12px;">
                                      Publicar Imagen Manual
                                  </label>
                              </div>
                              <div class="d-inline-block me-5">
                                  <input class="form-check-input" type="radio" name="radio_imagen" id="opcion6" value="3" style="margin-left: 8px;">
                                  <label class="form-check-label" for="opcion6" style="font-size: 1rem; margin-top: -12px; margin-left: 10px;">
                                      Publicar Imagen Automático
                                  </label>
                              </div>
                              <div class="d-inline-block me-5">
                                  <input class="form-check-input" type="radio" name="radio_imagen" id="opcion7" value="2" style="margin-left: 8px;">
                                  <label class="form-check-label" for="opcion7" style="font-size: 1rem; margin-top: -12px; margin-left: 16px;">
                                      Consultar Imagen
                                  </label>
                              </div>
                          </div>
													<button type="button" id="btn_publicar" style="margin-left: 310px;" class="btn btn-warning">Publicar Imagen</button>
												</div>
												<div class="modal-footer">
													<button type="button" id="ModalCategoriaSalir" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
													<button type="button" id="btn_descargar_png" class="btn btn-primary">Enviar y Descargar</button>
												</div>
											</div>
										</div>
									</div>

                  <div class="modal fade" id="modalModificarLoteria" tabindex="-1" aria-labelledby="modalModificarLoteriaLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="modalModificarLoteriaLabel">Modificar Lotería</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form>
                            <div class="form-group">
                              <label for="txt_codigo_loteria">Código Lotería</label>
                              <input type="text" class="form-control" id="txt_codigo_loteria" placeholder="Ingrese el código de la lotería" disabled>
                            </div>
                            <div class="form-group">
                              <label for="txt_nombre_loterias">Nombre Lotería</label>
                              <input type="text" class="form-control" id="txt_nombre_loterias" placeholder="Ingrese el nombre de la lotería "disabled>
                            </div>
                            <div class="form-group">
                                <label for="txt_estado_cierre">Estado de Cierre</label>
                                <select class="form-control" id="txt_estado_cierre">
                                    <option value="1">Lunes a Sabado.</option> <!-- Sábados y entre semana -->
                                    <option value="2">Solo Sábados</option> <!-- Solo sábados -->
                                    <option value="3">Festivos y Domingos</option> <!-- Horario festivos y domingos -->
                                    <option value="6">Semana Completa.</option> <!-- Semana Completa. -->
                                </select>

                            </div>

                            <div class="form-group">
                                <label for="txt_estado_sorteo">Estado de Sorteo</label>
                                <select class="form-control" id="txt_estado_sorteo">
                                    <option value="1">Lunes a Sabado.</option> <!-- Sábados y entre semana -->
                                    <option value="2">Solo Sábados</option> <!-- Solo sábados -->
                                    <option value="3">Festivos y Domingos</option> <!-- Horario festivos y domingos -->
                                    <option value="6">Semana Completa.</option> <!-- Semana Completa. -->
                                </select>

                            </div>
                            <div class="form-group">
                              <label for="txt_horario_cierre">Horario de Cierre</label>
                              <input type="text" class="form-control" id="txt_horario_cierre">
                            </div>
                            <div class="form-group">
                              <label for="txt_horario_sorteo">Horario del Sorteo</label>
                              <input type="text" class="form-control" id="txt_horario_sorteo">
                            </div>
                            <div class="form-group">
                              <label for="txt_fecha">Fecha</label>
                              <input type="text" class="form-control" id="txt_fecha" disabled>
                            </div>
                          </form>
                        </div>
                        <div class="modal-footer">

                          <button type="button" id="exampleModalControlHorario2" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          <button id="btn_ActualizarLoteria" type="button"  class="btn btn-primary">Guardar Horario</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="modal fade" id="modalCrearHorarioLoteria" tabindex="-1" aria-labelledby="modalCrearHorarioLoteriaLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalCrearHorarioLoteriaLabel">Crear Horario Lotería</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form>
                                    <div class="form-group">
                                        <label for="txt_codigo_loteria_nuevo">Código Lotería</label>
                                        <input type="text" class="form-control" id="txt_codigo_loteria_nuevo" placeholder="Ingrese el código de la lotería">
                                    </div>
                                    <div class="form-group">
                                        <label for="txt_nombre_loterias_nuevo">Nombre Lotería</label>
                                        <input type="text" class="form-control" id="txt_nombre_loterias_nuevo" placeholder="Ingrese el nombre de la lotería">
                                    </div>
                                    <div class="form-group">
                                        <label for="txt_estado_cierre_nuevo">Estado de Cierre</label>
                                        <select class="form-control" id="txt_estado_cierre_nuevo">
                                            <option value="1">L-M-M-J-V-S</option> <!-- Sábados y entre semana -->
                                            <option value="2">Solo Sábados</option> <!-- Solo sábados -->
                                            <option value="3">Festivos y Domingos</option> <!-- Horario festivos y domingos -->
                                            <option value="6">Semana Completa.</option> <!-- Semana Completa. -->
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="txt_estado_sorteo_nuevo">Estado de Sorteo</label>
                                        <select class="form-control" id="txt_estado_sorteo_nuevo">
                                            <option value="1">L-M-M-J-V-S</option> <!-- Sábados y entre semana -->
                                            <option value="2">Solo Sábados</option> <!-- Solo sábados -->
                                            <option value="3">Festivos y Domingos</option> <!-- Horario festivos y domingos -->
                                            <option value="6">Semana Completa.</option> <!-- Semana Completa. -->
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="txt_horario_cierre_nuevo">Horario de Cierre</label>
                                        <input type="text" class="form-control" id="txt_horario_cierre_nuevo">
                                    </div>
                                    <div class="form-group">
                                        <label for="txt_horario_sorteo_nuevo">Horario del Sorteo</label>
                                        <input type="text" class="form-control" id="txt_horario_sorteo_nuevo">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="btn_CerrarCrearHorario" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button id="btn_GuardarHorarioLoteria" type="button" class="btn btn-primary">Crear Horario</button>
                            </div>
                        </div>
                    </div>
                </div>








									<form id="formulario_imagen" enctype="multipart/form-data">
										<div class="modal fade" id="exampleModalImagenServidor" tabindex="-1" aria-labelledby="exampleModalImagenLabel" aria-hidden="true">
											<div class="modal-dialog modal-dialog-centered">
												<div class="modal-content">
													<div class="modal-header">
														<h5 class="modal-title" id="exampleModalImagenLabel">Imagen en Servidor</h5>
														<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
													</div>
													<div class="modal-body">
														<div id="selec_fecha_imagen">
															<div class="form-group col-md-12 col-md-offset-3 column centrar-contenido" style="padding-left: 15px;">
																<label>Seleccione una Fecha Inicial</label><br>
																<label>Fecha Inicial Promocional:</label>
																<input type="text" id="fecha_imagen" class="form-control" required />
															</div>
															<hr/>
														</div>
														<div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
															<label for="inputCity">Nombre Imagen</label>
															<input type="text" class="form-control form-control-sm" id="nombre_imagen" placeholder="" required>
														</div>
														<hr/>
														<div class="col-md-4" style="margin-top: 25px; text-align: center; width: 450px;">
															<input type="file" name="myfile" id="adj_seg_envio_imagen" class="form-control" accept=".png" multiple required>
														</div>
													</div>
													<div class="modal-footer">
														<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
														<button  type="submit" id ="btn_publicar_imagen"type="submit" class="btn btn-primary">Guardar cambios</button>
													</div>
												</div>
											</div>
										</div>
									</form>



									<div class="modal fade" id="exampleModalControl" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
										<div class="modal-dialog">
											<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="exampleModalLabel">Enviar Comentario</h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
											</div>
											<div class="modal-body">
												<form>
												<div class="mb-3">
													<label for="message-text" class="col-form-label">Comentario:</label>
													<textarea class="form-control" id="txt_comentario_loteria"></textarea>
												</div>
												</form>
											</div>
											<div class="modal-footer">
												<button type="button" id ="exampleModalControl" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												<button type="button" id="btn_comentario_modal"  class="btn btn-primary">Guardar Comentario</button>

											</div>
											</div>
										</div>
									</div>

                  <div class="modal fade" id="exampleModalControlHorarios" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="exampleModalLabel">Enviar Comentario</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form>
                          <div class="mb-3">
                            <label for="message-text" class="col-form-label">Comentario Horario:</label>
                            <textarea class="form-control" id="txt_comentario_loteria_horarios"></textarea>
                          </div>
                          </form>
                        </div>
                        <div class="modal-footer">
                          <button type="button" id ="exampleModalControlCerrar" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                          <button type="button" id="btn_comentario_modal2"  class="btn btn-primary">Guardar Comentario</button>

                        </div>
                      </div>
                    </div>
                  </div>

									<div id="contenedor2" style="display: flex; background:white; flex-direction: column; align-items: center; border-radius: 15px; border: 2px solid #ccc; padding: 20px; width: 95%; margin: 0 auto;">
										<div style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 20px;">
											<div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion22" value="1">
													<label class="form-check-label" for="opcion22" style="font-size: 1.2rem; ">
														Reporte Revisones Escrutinio.
													</label>
												</div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion21" value="2">
													<label class="form-check-label" for="opcion21" style="font-size: 1.2rem; ">
														Reporte Revisones Control Interno Horario.
													</label>
												</div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion23"  for="opcion2" value="2">
													<label class="form-check-label" for="opcion23" style="font-size: 1.2rem; ">
														Reporte Revisones Redes Sociales.
													</label>
												</div>
                        <div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion24"  for="opcion2" value="8">
													<label class="form-check-label" for="opcion24" style="font-size: 1.2rem; ">
														Crear Horarios de Loterias
													</label>
												</div>
											</div>
											<div>
												<div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion44"  for="opcion4" value="3">
													<label class="form-check-label" for="opcion44" style="font-size: 1.2rem; ">
													 Reporte Revisones Control Interno Resultados.
													</label>
												</div>
											    <div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion33" for="opcion3" value="4">
													<label class="form-check-label" for="opcion33" style="font-size: 1.2rem; ">
												     Archivo Control Interno Resultados.
													</label>
										  		</div>
												  <div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion51"  for="opcion5"value="5">
													<label class="form-check-label" id="opcion51" for="opcion51" style="font-size: 1.2rem; ">
												     Agregar Archivo Control Interno Resultados.
													</label>
										  		</div>
                          <div class="form-check" style="padding-left: 30px;">
													<input class="form-check-input" type="radio" name="radio_seg" id="opcion16"  for="opcion16" value="6">
													<label class="form-check-label" id="opcion16" for="opcion16" style="font-size: 1.2rem; ">
												     Modificar Horarios de Loterias.
													</label>
										  		</div
										    </div>
										</div>
										     <button id="btn_seguimiento" type="button" class="btn btn-primary" style="align-self: center;">Buscar Reporte</button>
									</div>

									</div>
									<div class="accordion" id="accordionControl">
									<div class="accordion-item">
										<h2 class="accordion-header" id="accordionControl_1">
										<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
											Horario y Cierre de Sorteos-Loteria
										</button>
										</h2>
										<div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionControl">
										<div class="accordion-body">
											<div id="fechas_horarios" style="background-color: white;border-radius:5px;padding-bottom: 1%;">
											<div class="container">
												<h2 style="text-align: center; font-weight: bold;">Consultar Fecha de Horarios</h2>
												<hr/>
												<div class="form-group">
												<label for="fecha_hor" class="form-label">Fecha Horario:</label>
												<input type="text" class="form-control" id="fecha_hor" />

												</div>
												<center><div class="d-grid gap-2" style="width: 250px ;display:flex margin-top: 19px">
												<button type="button" id="btn_asignar4" class="button" style="vertical-align:middle" onclick="mostrar_tabla_2()"><span>Buscar Horarios</span></button>
												</div></center>
											</div>
											</div>
										</div>
										</div>
									</div>
									<div class="accordion-item">
										<h2 class="accordion-header" id="accordionControl_2">
										<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
											Resultados de Sorteos-Loteria
										</button>
										</h2>
										<div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionControl">
										<div class="accordion-body">
											<div id="fechas_control" style="background-color: white;border-radius:5px;padding-bottom: 1%;">
											<div class="container">
												<h2 style="text-align: center; font-weight: bold;">Consultar Fecha de Sorteo</h2>
												<hr/>
												<div class="form-group">
												<label for="fecha_con" class="form-label">Fecha Sorteo:</label>
												<input type="text" class="form-control" id="fecha_con" />
												</div>

												<center><div class="d-grid gap-2" style="width: 250px ;display:flex margin-top: 19px">
												<button type="button" id="btn_asignar" class="button" style="vertical-align:middle" onclick="mostrar_tabla_1()"><span>Buscar Sorteo</span></button>
												</div></center>
											</div>
											</div>
										</div>
										</div>
									</div>
									</div>



								<div id="subcontenedor_registrar_loterias" style="display:none ; background-color: white;border-radius:5px;padding-bottom: 1%;">

								<div>
									<table class="table table-hover table-bordered centered-table table-primary " id ="tabla_resultados" style="display:none">
									<h2 id ="titulo_resultados" style="text-align: center; font-weight: bold;color :black">Resultados Loterias </h2>
										<thead style="background-color: grey;">
											<tr>
												<th>SORTEO/LOTERIA</th>
												<th>NUMERO</th>
												<th>NOMBRE</th>
												<th>VALIDACION</th>
												<th>NOMBRE</th>
												<th>VALIDACION</th>
												<th>NOMBRE</th>
												<th>VALIDACION</th>
												<th>NOMBRE</th>
												<th>VALIDACION ESCRUTINIO</th>
											</tr>
										</thead>
										<tbody  id="body_tabla_resultados">
											<!-- <tr>
												<td style="text-align: center;">BOGOTA</td>
												<td style="text-align: center;">1325</td>
												<td style="text-align: center;">CANAL UNO</td>
												<td><input type="checkbox" class="form-check" style="margin:auto;"></td>
												<td style="text-align: center;">GANA GANA</td>
												<td><input type="checkbox" class="form-check" style="margin:auto;"></td>
												<td style="text-align: center;">PAGA TOOD</td>
												<td><input type="checkbox" class="form-check" style="margin:auto;"></td>
												<td style="text-align: center;">APOSTAR</td>
												<td><input type="checkbox" class="form-check" style="margin:auto;"></td>
											<!</tr> -->
										</tbody>
									</table>

								</div>







								<div id="botones_check" style="display: flex;justify-content: space-around;margin: 37px auto; width: 190px; height: 45px;background-color: #f7eded;border-radius: 12px;padding: 10px;text-align: center; box-sizing: border-box;">
									<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Agregar Comentario</label>
										<label class="checkbox-container">
										<input class="custom-checkbox"id ="check_comentario" checked="" type="checkbox">
										<span class="checkmark"></span>
									</label>
									</div>
								</div>
								<hr/>

								<div id="botones_observacion" style="display: flex; justify-content: space-around; margin-top: 37px; width: 10;height: 25px;">
									<div class="form-floating">
									<textarea class="form-control" style="width: 50pc; display: flex " placeholder="Leave a comment here" id="txt_comentario"></textarea>
									<label for="floatingTextarea">Observacion :</label>
									</div>
									<hr/>
								</div>
								        



								<div id="botones_container" style="display: flex; justify-content: space-around; margin-top: 57px;">
								    <hr/>
                                    <button class="button_repet centered-button"  id="btn_final" >
									<svg class="svg-icon" fill="none" height="23" viewBox="0 0 20 20" width="25" xmlns="http://www.w3.org/2000/svg"><g stroke="#ff342b" stroke-linecap="round" stroke-width="1.5" style="display:none margin-top: 17px; z-index: 1 ;-"><path d="m3.33337 10.8333c0 3.6819 2.98477 6.6667 6.66663 6.6667 3.682 0 6.6667-2.9848 6.6667-6.6667 0-3.68188-2.9847-6.66664-6.6667-6.66664-1.29938 0-2.51191.37174-3.5371 1.01468"></path><path d="m7.69867 1.58163-1.44987 3.28435c-.18587.42104.00478.91303.42582 1.0989l3.28438 1.44986"></path></g></svg>
									<span class="lable">Finalizar Revision</span>
									</button>
									<button id="btn_registar_pda" type="button" class="btn btn-success centered-button" style="display:none margin-top: 28px; z-index: 1 ;width:185px ; height:60px ;;margin:19px">Firma Cordinador Escrutunio</button>
                  <button id="btn_modificar_registro_pda" onclick="" type="button" class="btn btn-danger" style="display:none ;margin-right:115px; z-index: 1 ;width:185px ; height:63px ;margin:19px">Firma Director Escrutunio</button>
									<button id="btn_editar_pda" type="button" class="button_active" style="display:none ; z-index: 1; ">Descargar PDF</button>
									<button id="btn_comentario" type="button" class="btn btn-dark centered-button " style="display:none margin-top: 35px; z-index: 1 ;width:185px ; height:43px ;margin-right: 112px;">Guardar Comentario</button>

								</div>



                    <!-- Separaciin con un <br> -->
                    <br>

                    <div id="alert-confirmacion"  class="alert alert-success" role="alert">Actualizado!</div>
                    <div id="alert-negado"  class="alert alert-danger" role="alert">No Actualizado</div>
                    <div id="alert-consulta"  class="alert alert-warning" role="alert">Error en Consulta</div>

                    <span id="msgbox1" style="display:none; "></span>
								</div>
								<br>
								<div class="cargando" style="display:none ;">
									<div class="spinner-grow text-primary " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-secondary " role="status" style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-success " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-danger " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-warning " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-info " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-light " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
									<div class="spinner-grow text-dark " role="status"  style="width: 1.5rem; height: 1.5rem;">
									<span class="sr-only"></span>
									</div>
								</div>





	';
								echo '








									<br>
									<span id="msgbox" style="display:none;"></span>






						<div id=contenedor_reporte style="display:none;">
							<p class="title" align=center> <strong><B><font color=#ffffff>REPORTE PDA SIN REGISTROS DE HORARIO</font></B></strong></p>
							<table width=100% border=0 cellspacing=0 cellpadding=0  >
								<tr>
									<td align=center><input type=button name=btngenerar id=btngenerar value="Generar" style="margin-left:-10px; height:23px" onclick=generar(1); >
									<span id="loading"> <img src="images/loader.gif" border="0" width="15" height="15" > </span> <span id="msgbox2" style="display:none"></span> </td>
								</tr>
							</table>
							<br>
							<p class="title" align=center> <strong><B><font color=#ffffff>REPORTE PDA CON HORARIOS PROGRAMADOS</font></B></strong></p>
							<table width=100% border=0 cellspacing=0 cellpadding=0 >
								<tr>
									<td align=center><input type=button name=btngenerar_2 id=btngenerar_2 value="Generar" style="margin-left:-10px; height:23px" onclick=generar(2); >
									<span id="loading2"> <img src="images/loader.gif" border="0" width="15" height="15" > </span> <span id="msgbox3" style="display:none"></span> </td>
								</tr>
							</table>
							<br>
						</div>
					</td>
				</tr>
			</table>
	  	</td>
    </tr>
	';

  }
   include($plantilla5);
    /*echo '<link rel="stylesheet" href="lib/Bootstrap4.6.1/bootstrap4.6.1.min.css">';
	echo '<script src="lib/Bootstrap4.6.1/bootstrap4.6.1.bundle.min.js"></script>';
	echo '<script src="lib/datatables_1.12.1/js/jquery.dataTables.min.js"></script>';
	echo '<script src="lib/datatables_1.12.1/js/dataTables.bootstrap4.min.js"></script>';
	echo '<link rel="stylesheet" href="css/w3.css">';*/


	echo '<link href="lib/bootstrap-5.0.2/css/bootstrap.min.css" rel="stylesheet"/>';
	//echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"/>';

	echo '<link href="lib/select2-4.1.0/dist/css/select2.min.css" rel="stylesheet" />';
	//echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">';
	//echo '<link rel="stylesheet" href="lib/datatables_1.12.1/css/dataTables.bootstrap4.min.css">';

	//echo '<link rel="stylesheet" href="lib/datatables_1.12.1/css/dataTables.bootstrap4.min.css">';


	echo '<script src="lib/bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>';
	//echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';

	//echo '<script src="lib/datatables_1.12.1/js/jquery.dataTables.min.js"></script>';
	//echo '<script src="lib/datatables_1.12.1/js/dataTables.bootstrap4.min.js"></script>';


	//echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" />';
	echo '<link href="lib/datatables_1.13.6/css/dataTables.bootstrap5.min.css" />';
	//echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>';
	echo '<script src="lib/select2-4.1.0/dist/js/select2.min.js"></script>';


    echo '<script src="lib/datatables_1.13.6/js/jquery.dataTables.min.js"></script>';
	echo '<script src="lib/datatables_1.13.6/js/dataTables.bootstrap5.min.js"></script>';
	echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>';





   /* echo '<link href="lib/select2-4.1.0/dist/css/select2.min.css" rel="stylesheet" />';
   echo '<script src="lib/select2-4.1.0/dist/js/select2.min.js"></script>'; */
?>
