<!-- filepath: C:\Users\auxsenadesarrollo\Desktop\ABEL\Documentador_Simfony\archivos_testing\AppController.php -->
# **TICKET:** ##
### **Fecha de creación/modificación:** 2025-03-21
## **Nombre Aplicativo:** AppController.php

### **Descripcion Aplicativo:**
```

```

### **Librerias**
* Symfony\Bundle\FrameworkBundle\Controller\AbstractController
* Symfony\Component\HttpFoundation\JsonResponse
* Symfony\Component\HttpFoundation\Request
* Symfony\Component\HttpFoundation\Response
* Symfony\Component\Routing\Annotation\Route

### **Servicios**
* App\Services\Conexion (Servicio interno)
* App\Services\ConsultaParametro (Servicio interno)
* App\Services\Herramientas (Servicio interno)
* App\Services\Log (Servicio interno)
* Symfony\Component\HttpClient\HttpClient
* Symfony\Contracts\HttpClient\ChunkInterface
* Symfony\Contracts\HttpClient\HttpClientInterface

### **Lenguaje de programación utilizado:**
PHP

### **URL**
* AppController.php -> [Ruta archivo: AppController.php](..\..\..\archivos_testing\AppController.php\AppController.php)

### **URL / .md Documentación Archivos Implementados**
* AppController.md -> [Ruta archivo: AppController.md](documentador\docs\generated\AppController.md)

# **PHP:**

### **Listado de Variables Globales**
* $GLOBALS

### **Listado de Variables**
```
* VERSION_CODE
* _SERVER
* ano
* ape_cli
* apellidos
* app_ws
* arr_llano_ws
* arr_msm
* array
* bandera
* base64
* body_top
* c_v
* cabeceras
* campoventa
* cantidad
* carpeta
* cc
* cc_apostador
* ced_cli
* cedula
* celu
* cliente
* cnn
* cnn2
* cnn2_
* cnn_
* cnn_app
* cnn_app_
* cntrl
* cntrl_12
* codebar
* codigo
* com_ltn
* com_ltp
* compara
* complemento
* con
* cont
* cont_val
* contenido
* contenido_ma
* contentType
* control
* control2
* control_
* control_nic
* control_saldo
* control_usuario_tipo
* convertedArray
* cor_cli
* correo
* countDatos
* cuerpo
* dat
* dat_ws
* dat_ws2
* data
* dato
* datos
* datos_validar_tipo_user
* device_after
* dia
* dig_cli
* dispo_v
* dt
* ean_bio
* ean_llano
* elementos
* encoded_attach
* especial
* estado
* estado0_bio
* estado0_llano
* estado1_bio
* estado1_llano
* estado2
* extras
* fac_bio
* fac_llano
* fact
* factura
* facturaid
* fec_ini2
* fecha
* fecha_act
* fecha_actual
* fecha_bio
* fecha_dian
* fecha_ex
* fecha_exp
* fecha_explo
* fecha_fin
* fecha_i
* fecha_imp
* fecha_llano
* fecha_mas_minutos
* fecha_r
* fecha_r_hh
* fecha_re_hh
* fecha_rel
* fecha_trans
* fecha_trans_aux
* fecha_v
* fechaf
* fechai
* fechapeticion
* fechar
* file
* fnacio
* gru_v
* h_c
* hh_mas_cinco
* hh_peticion_recaudo
* homol
* homol2
* homologa
* hora
* hora1
* hora2
* hora_act
* hora_obt
* hora_recaudo
* horapeticion
* horarios
* html
* httpClient
* http_client
* huella
* huella_cs10
* id_apost
* id_consulta
* id_conve_sgc
* id_convenio
* id_fac_bio
* id_fac_llan
* id_homo
* id_homologo
* id_huella
* id_llanogas_homo
* id_mod
* id_ope
* id_pago_pago_bio
* id_paq
* id_pro
* id_r
* id_recaudo
* id_recaudo_bio
* id_reg_ll_bio
* id_reg_llano
* id_reg_pago
* id_regis
* id_regist
* id_trans
* id_user
* imei
* imei_after
* imeis
* inf_device
* inf_ws
* info
* infoFE
* infoUrlFE
* inser
* inser_app
* insert_huella
* ips
* ips_after
* item
* iva
* iva_comi
* json
* key
* link
* llan_ws
* llano_arreglo
* log
* mac
* mac_gamble
* mail
* max_rec
* max_ret
* mes
* message
* message2
* metodo_imp
* min_rec
* min_ret
* minutos
* msm
* msm_login
* name_device
* name_plan
* name_ref
* nickname
* nickname_cliente
* nom_cer
* nom_cli
* nom_pro
* nombre
* nombres
* nrm_frm
* nro_fact
* num_f
* num_i
* operador
* operador_sql
* p1
* p2
* pa
* pagados
* paq
* parametro1
* parametro2
* params
* pass
* pass_1
* pass_2
* pass_act
* path
* pdf_generado
* pdv_r
* permiso
* permisos
* pin
* pin_retiro
* pinretiro
* placa
* pos
* prefi
* prm
* producto
* pv
* pventa_u
* pvt
* r
* r_finger
* r_id_huell
* r_p
* re_v
* rec
* recaudador
* reeimprime
* reg_cli
* request
* res
* res0
* res1
* res2
* res_
* res_2
* res_autoriza
* res_bille
* res_horario
* res_man
* res_up
* res_usu
* res_val_usu
* res_ver
* res_vs
* resb
* resp
* resp2
* response
* response_sgc
* respuesta
* rest
* resu_dian
* result
* resultados
* row
* rr
* rs2
* run
* sAsunto
* sDe
* sPara
* sTexto
* sTexto2
* sacar_iva
* saldo
* se_res
* se_val
* segundos
* select_s
* ser
* seri
* serv
* server_addr
* sesion
* sesion_mobil
* sq2
* sq_control
* sq_control_
* sq_hist
* sq_horario
* sql
* sql0
* sql1
* sql2
* sql_autoriza
* sql_con
* sql_gamble
* sql_hist
* sql_homo
* sql_homo2
* sql_id
* sql_impri
* sql_mac
* sql_pago
* sql_sesion
* sql_up
* sql_update
* sql_v
* sql_val
* sql_valida_sesion
* sql_valores
* sql_vta
* sqlb
* sqlec
* sqlinser
* sqmant
* st
* status
* statusCode
* succes
* tabla
* tabla_recaudo
* tele
* telefo
* tercero
* this
* tiempo_segundos
* tip_doc
* tipo
* tipoLogin
* tipo_co
* tipo_conv
* tipo_impo
* tipo_login
* tipo_r
* tipo_tra
* tipo_trans
* tipo_u
* tipo_user
* tipou
* tipouser
* tipouser_validar
* titular
* token
* token_sesion
* token_ws
* tope
* tope1
* total
* total_al_bloquearse
* total_g
* total_gen
* total_lf
* totalgasint
* totalint
* totalvista
* upd
* upd_user
* update_usu
* url
* use
* use_upd
* user
* user_tipo
* usu_env
* usuario
* usuario_rec
* v_70
* v_80
* va_ent
* val
* val_billetera
* val_bio
* val_comi
* val_llano
* val_rec
* val_status_gamble
* val_transa
* vali
* valida_factura
* validacion
* valor
* valor_dif_redon
* valor_homo
* valor_llanogas
* valor_llanogas_homo
* valor_redon_valida
* valor_redondeado
* valor_sum_recaudo_llano_homo
* valorbioint
* valorbiovista
* valorgi
* valorre
* value
* valueBille
* vence
* ventas
* ver_ini
* veri_bio
* veri_llano
* vers
* vista_valor
* vs
* vta_actual
* vta_calc
* vta_calc_1
* vta_calc_2
* ws_app
* x
* zo
```
### **Tablas Base de datos consultadas / Entidad relacion**
* acueducto_recaudado
* app_bemovil_recaudos
* app_modulos
* app_pagos_recaudos
* app_version
* bemovil_hvehicular_recaudado
* bemovil_pines_recaudado
* bemovil_recargas_recaudado
* bemovil_resolucion_dian
* bemovil_tipo_operador
* bioagricola_recaudado
* cem_control_recaudos
* cem_recaudado
* cem_regis_detalle
* congente_recaudado
* consolidadoventaservicios
* controlhorariopersonas
* datos
* detalleincentivos
* detallevtasotrosproductos
* edesa_recaudado
* emsa_asignacion_punto
* emsa_recaudado
* equivalenciasproductos
* firebaseid
* formularios
* gamble_otrosservicios
* hist_formularios_tat
* hist_transacciones
* llanogas_recaudado
* llanogas_recaudado_ws
* llanogas_regis_detalle_ws
* mac_punto_venta
* mngmcn_acum3
* mod_convenios
* otrosdias
* parametros_pagos
* personas
* porcentaje_comision_empresa
* premiospersonaproveedor
* productos
* saldos_ventas_tat_sgc
* token
* users
* users_activacion
* users_huellas
* users_parametros_recaudos
* users_sesion
* usuarios
* v_billetera_usuario
* v_totalventasnegocio
* vista_recaudos_sgc

### **Tipo de Conector Bases de Datos**
* cnn->query('0')
* cnn->query('13')
* cnn->query('19')
* cnn->query('2')
* cnn->query('5')
* gamble70

## **Conexión a base de datos**
* app consuerte 10.1.1.10
* gamble 70 10.67.34.70
* gamble 80 10.72.34.80
* manager 50 10.195.53.50
* producion Sgc 10.1.1.4

### **Funciones**
* __construct
* __construct
* analizar_bloqueo
* analizar_bloqueo
* convertArrayKeysToUtf8
* convertArrayKeysToUtf8
* dec_codigo_barra
* dec_codigo_barra
* doAuthenticate
* doAuthenticate
* envioActivacionmail
* envioActivacionmail
* horas_permitidas
* index
* index
* mailActivarusuario
* mailActivarusuario
* metodos
* metodos
* metodos_web
* metodos_web
* newTokenFirebase
* newTokenFirebase
* test_registro
* test_registro
* utf8_converter
* utf8_converter
* validarTransaccionBeMovil
* validarTransaccionBeMovil
* validar_estado_usuario
* validar_estado_usuario
* validar_logueo
* validar_logueo
* validar_maquina
* validar_maquina
* validatelogin
* validatelogin
* verificacion_transaccion
* verificacion_transaccion

## Rutas
* app_app:
  - **Path:** `/app`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::index`
* app_app_validatelogin:
  - **Path:** `/app/validatelogin`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::validatelogin`
* app_app_newTokenFirebase:
  - **Path:** `/app/newTokenFirebase`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::newTokenFirebase`
* app_app_metodos:
  - **Path:** `/app/metodos`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::metodos`
* app_app_metodos_web:
  - **Path:** `/app/metodos_web`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::metodos_web`
* app_app_validar_maquina:
  - **Path:** `/app/validar_maquina`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::validar_maquina`
* app_app_test_registro:
  - **Path:** `/app/test_registro`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::test_registro`
* app_app_verificacion_transaccion_bemovil:
  - **Path:** `/app/verificacion_transaccion_bemovil`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::verificacion_transaccion`

### **Realizado por:**
José Abel Carvajal
