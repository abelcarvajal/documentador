
# **TICKET:** #61268
## **Nombre Aplicativo:** Modificación Venta del Dia ConsuertePay.

### **Lenguaje de programacion utilizado**
* Php

### **Descripcion Aplicativo:**
```
Se incluyen los productos de gamble en el reporte.
```

### **URL**
* AppController.php -> [Ruta Archivo: AppController.php](../../src/Controller/AppController.php)

### **URL / .md Documentación Archivos Implementados**
* http://10.1.1.12:8094/app/verificacion_transaccion

### **Listado de Variables**
* $log
* $cnn
* $prm
* $ruta
* $php_auth_user
* $php_auth_pw
* $http_client
* $h_c
* $ip
* $server_addr
* $user
* $pass
* $nickname:
* $fecha
* $sqmant
* $res_man
* $permiso
* $codigo
* $tercero
* $val
* $id_regis
* $total
* $msm
* $id_huella
* $fecha_act
* $tipo
* $id_pro
* $valor
* $array

### **Tablas Base de datos consultadas / Entidad relacion**
* users
* users_sesion
* users_parametros_recaudos
* emsa_recaudado
* edesa_recaudado
* llanogas_recaudado
* acueducto_recaudado
* congente_recaudado
* cem_control_recaudos
* cem_recaudado
* v_billetera_usuario
* app_modulos
* app_version
* llanogas_regis_detalle
* acueducto_regis_detalle
* edesa_regis_detalle
* bioagricola_regis_detalle
* parametros_pagos

### **Tipo de Conector Bases de Datos**
* cnn

### **Funciones**
* __construct(Log $log, Conexion $cnn, ConsultaParametro $prm, HttpClientInterface $http_client, Herramientas $h_c)
* public function doAuthenticate()
* public function validar_logueo($user)
* public function dec_codigo_barra($codigo, $tercero)
* public function utf8_converter($array)
* public function convertArrayKeysToUtf8(array $array)
* private function analizar_bloqueo($nickname, $total, $tipo=0)
* private function validar_estado_usuario($nickname)
* private function validarTransaccionBeMovil($valor, $telefo, $total, $nickname, $fecha_act, $id_ope, $placa, $id_pro, $tipo_r)
* private function mailActivarusuario($mail, $token, $fecha_exp)
* private function envioActivacionmail($mail)
* public function validatelogin(Request $request)
* public function metodos(Request $request)
* public function validar_maquina(Request $request)
* public function verificacion_transaccion(Request $request)
* public function horas_permitidas($log)
* public function newTokenFirebase(Request $request)
* public function test_registro(Request $request)
* public function envioActivacionmail($mail)
* public function mailActivarusuario($mail, $token, $fecha_exp)

### **Conexion a base de datos**
* psgc

### **Menu de para los modulos**

### **Elaborado  Por**

José Abel Carvajal
___________________________________________________________

# **TICKET:** #835159
## **Nombre Aplicativo:** Modificación Funcion Verificacion para Maquina Automatica Kiosko.

### **Lenguaje de programacion utilizado**
* Php

### **Descripcion Aplicativo:**
```
Se le agrega a la función verficacion_transaccion las validaciones para los recaudos de convenios y betplay, 
para que la maquina kiosko pueda validar si al momento de realizar la transacción no hay respuesta o un timeout, 
por medio de esta se pueda consultar si quedo registrada en bd la operación de la transacción.
```

### **URL**
* AppController.php -> [Ruta Archivo: AppController.php](../../src/Controller/AppController.php)

### **URL / .md Documentaci�n Archivos Implementados**
* http://10.1.1.12:8094/app/verificacion_transaccion

### **Listado de Variables**
* nickname
* tercero
* facturaid
* valorre
* fecha_re_hh
* fecha_explo
* tipo
* valor
* id_apost
* mac
* pin_retiro
* segundos
* fecha_mas_minutos

### **Tablas Base de datos consultadas / Entidad relacion**
* vista_recaudos_sgc
* cem_recaudado
* cem_regis_detalle
* territorios
* bemovil_resolucion_dian

### **Tipo de Conector Bases de Datos**
* cnn

### **Funciones**
* public funtion verificacion_transaccion

### **Conexion a base de datos**
* psgc

### **Menu de para los modulos**

### **Elaborado  Por**
Brayan Fabiani Rodriguez


____________________________________________________________________________________________________________________________



# **TICKET:** #832713
## **Nombre Aplicativo:** WebService Parametrización Recaudos Pagina-Web Bemovil.

### **Lenguaje de programacion utilizado**
* Php

### **Descripcion Aplicativo:**
```
Al módulo se le agrega la nueva función verificacion_transaccion para que las transacciones de Bemovil si al momento 
de realizar la transacción no hay respuesta o un timeout, esta pueda validar si quedo registrada en bd la operación 
de la transacción.
```

### **URL**
* AppController.php -> [Ruta Archivo: AppController.php](../../src/Controller/AppController.php)

### **URL / .md Documentaci�n Archivos Implementados**
* http://10.1.1.12:8094/app/verificacion_transaccion
* http://10.1.1.4/consuerteinventarios/bemovil_ws.php
* http://10.1.1.12/consuertepruebas/bemovil_ws.php

### **Listado de Variables**
* nickname
* id_ope
* total
* telefo
* id_paq
* id_prod
* tipo_r
* tipou
* placa
* imei
* fecha_r_hh

### **Tablas Base de datos consultadas / Entidad relacion**
* bemovil_recargas_recaudado
* bemovil_hvehicular_recaudado
* bemovil_pines_recaudado
* bemovil_tipo_paquete
* bemovil_paquetes
* bemovil_tipo_operador
* territorios
* bemovil_resolucion_dian
* bemovil_hvehicular

### **Tipo de Conector Bases de Datos**
* cnn

### **Funciones**
* public funtion verificacion_transaccion

### **Conexion a base de datos**
* psgc

### **Menu de para los modulos**

### **Elaborado  Por**
Brayan Fabiani Rodriguez


____________________________________________________________________________________________________________________________



# **TICKET:** #794978
## **Nombre Aplicativo:** Modificacion Webservice Maquina Automatica Kiosko


### **Lenguaje de programacion utilizado**
* Php


### **Descripcion Aplicativo:**
```
Al módulo se le crearon y se modificaron métodos para su uso en el aplicativo de app_pruebas utilizado habitualmente 
para los usuarios tipo tat de maquina portable y cuenta con funciones para Betplay, ConsuertePay, CEM, Kiosko y webservice.
```


### **URL**
* AppController.php -> [Ruta Archivo: AppController.php](../../src/Controller/AppController.php)


### **URL / .md Documentaci�n Archivos Implementados**
* http://10.1.1.12:8094/app/metodos
* http://10.1.1.4/consuerteinventarios/datos.php
* http://10.1.1.12/consuertepruebas/datos.php
* http://10.1.1.4/consuerteinventarios/cem_ws.php
* http://10.1.1.12/consuertepruebas/cem_ws.php


### **Listado de Variables**
* private $log;
* private $cnn;
* private $prm;
* private $ruta;
* private $php_auth_user; 
* private $php_auth_pw;
* private $http_client;
* nickname
* user
* fecha
* fechai
* saldo
* valor
* total
* token
* tipouser
* factura


### **Tablas Base de datos consultadas / Entidad relacion**
* emsa_recaudado
* edesa_recaudado
* bioagricola_recaudado
* congente_recaudado
* acueducto_recaudado
* llanogas_recaudado_ws
* llanogas_recaudado
* cem_recaudado
* consignacion_usuarios_betplay
* cem_control_recaudos


### **Tipo de Conector Bases de Datos**
* cnn


### **Funciones**
* public function __construct
* public function doAuthenticate()
* public function validar_logueo
* public function dec_codigo_barra
* public function utf8_converter
* public function convertArrayKeysToUtf8
* private function analizar_bloqueo
* private function validarTransaccionBeMovil
* public function mailActivarusuario
* public function envioActivacionmail
* public function index
* function horas_permitidas
* public function validatelogin
* public function newTokenFirebase
* public function metodos
* public function metodos_web
* public function validar_maquina
* public function test_registro

### **Conexion a base de datos**
* psgc
* gamble70
* sqmant

### **Menu de para los modulos**

### **Elaborado  Por**
Rodolfo Díaz.


_______________________________________________________________________________________________________________________________________________________________________________

# **TICKET:**#817577 
## **Nombre Aplicativo:** Modificación validaciones usuarios TAT.


### **Lenguaje de programacion utilizado:**
*	 Php


### **Descripcion Aplicativo:**
```
Se implementó un método a las consultas de las validaciones TAT en todos los módulos y aplicativos para reducir los tiempos que se requieren en el proceso.
```


### **URL:**
* http://10.1.1.12:8094/app/metodos
* http://10.1.1.4/consuerteinventarios/datos.php
* http://10.1.1.12/consuertepruebas/datos.php
* http://10.1.1.4/consuerteinventarios/cem_ws.php
* http://10.1.1.12/consuertepruebas/cem_ws.php


### **URL / .md Documentación Archivos Implementados:**
* AppController.md -> [Ruta Archivo: PtatController.md](../guia_programador/AppController.md)


### **Listado de Variables:**
* $nickname
* $total
* $tipo
* $fecha_act
* $hora_obt
* $val
* $va_ent
* $sq_control_
* $control_
* $fechai
* $fechaf
* $saldo
* $sq_control
* $control
* $min_rec
* $max_rec
* $min_ret
* $max_ret
* $tope
* $dias
* $fechas
* $sql_valores
* $cntrl
* $vta_calc_1
* $sql_gamble
* $cntrl_12


### **Conexion a base de datos:**
* psgc
* gamble70


### **Tablas Base de datos consultadas / Entidad relacion:**
* cem_control_recaudos
* users_parametros_recaudos
* emsa_recaudado
* emsa_regis_detalle
* edesa_recaudado
* edesa_regis_detalle
* bioagricola_recaudado
* bioagricola_regis_detalle
* congente_recaudado
* congente_regis_detalle
* acueducto_recaudado
* acueducto_regis_detalle
* llanogas_recaudado_ws
* llanogas_regis_detalle_ws
* llanogas_recaudado
* llanogas_regis_detalle
* bemovil_recargas_recaudado
* bemovil_tipo_operador
* bemovil_hvehicular_recaudado
* bemovil_pines_recaudado
* cem_recaudado
* consignacion_usuarios_betplay
* formularios
* gamble.hist_formularios_tat
* detalleincentivos
* equivalenciasproductos
* productos
* gamble_otrosservicios
* consolidadoventaservicios
* contratosventa
* proveedorservicios
* detallevtasotrosproductos


### **Tipo de Conector Bases de Datos:**
* cnn


### **Funciones:**
analizar_bloqueo($nickname,$total,$tipo=0)


### **Elaborado  Por:**
Johan Estaban Gutierrez.

_______________________________________________________________________________________________________________________________________________________________________________

# **TICKET:**#817581 
## **Nombre Aplicativo:** Modificación Control TAT.


### **Lenguaje de programacion utilizado:**
*	 Php


### **Descripcion Aplicativo:**
```
Se implementó un método a las consultas de las validaciones TAT en todos los módulos y aplicativos para realizar un control efectivo de los saldos de los usuarios.
```


### **URL:**
* http://10.1.1.12:8094/app/metodos
* http://10.1.1.4/consuerteinventarios/datos.php
* http://10.1.1.12/consuertepruebas/datos.php
* http://10.1.1.4/consuerteinventarios/cem_ws.php
* http://10.1.1.12/consuertepruebas/cem_ws.php


### **URL / .md Documentación Archivos Implementados:**
* AppController.md -> [Ruta Archivo: PtatController.md](../guia_programador/AppController.md)


### **Listado de Variables:**
* $nickname
* $total
* $tipo
* $fecha_act
* $hora_obt
* $val
* $va_ent
* $sq_control_
* $control_
* $fechai
* $fechaf
* $saldo
* $sq_control
* $control
* $min_rec
* $max_rec
* $min_ret
* $max_ret
* $tope
* $dias
* $fechas
* $sql_valores
* $cntrl
* $vta_calc_1
* $sql_gamble
* $cntrl_12


### **Conexion a base de datos:**
* psgc
* gamble70


### **Tablas Base de datos consultadas / Entidad relacion:**
* cem_control_recaudos
* users_parametros_recaudos
* emsa_recaudado
* emsa_regis_detalle
* edesa_recaudado
* edesa_regis_detalle
* bioagricola_recaudado
* bioagricola_regis_detalle
* congente_recaudado
* congente_regis_detalle
* acueducto_recaudado
* acueducto_regis_detalle
* llanogas_recaudado_ws
* llanogas_regis_detalle_ws
* llanogas_recaudado
* llanogas_regis_detalle
* bemovil_recargas_recaudado
* bemovil_tipo_operador
* bemovil_hvehicular_recaudado
* bemovil_pines_recaudado
* cem_recaudado
* consignacion_usuarios_betplay
* formularios
* gamble.hist_formularios_tat
* detalleincentivos
* equivalenciasproductos
* productos
* gamble_otrosservicios
* consolidadoventaservicios
* contratosventa
* proveedorservicios
* detallevtasotrosproductos


### **Tipo de Conector Bases de Datos:**
* cnn


### **Funciones:**
analizar_bloqueo($nickname,$total,$tipo=0)


### **Elaborado  Por:**
Johan Estaban Gutierrez.

_________________________________________________________________________________________________________________________________________________________________________________

# **TICKET:**#832733 
## **Nombre Aplicativo:** Mejoramiento de Controles por Mantenimiento.


### **Lenguaje de programacion utilizado:**
*	 Php


### **Descripcion Aplicativo:**
```
Se implementó un nuevo desarrollo en Consuertepay que permite mejorar el control de versiones y el control de bloqueo por mantenimiento, obligando a los usuarios a cerrar sesión cuando alguno de los dos está en proceso solicitado.
```


### **URL:**
* http://10.1.1.12:8094/app/metodos
* http://10.1.1.4/consuerteinventarios/datos.php
* http://10.1.1.12/consuertepruebas/datos.php
* http://10.1.1.4/consuerteinventarios/cem_ws.php
* http://10.1.1.12/consuertepruebas/cem_ws.php


### **URL / .md Documentación Archivos Implementados:**
* AppController.md -> [Ruta Archivo: PtatController.md](../guia_programador/AppController.md)


### **Listado de Variables:**
* $this->log
* $params
* $con
* $array
* $VERSION_CODE
* $usu_env
* $this->php_auth_user
* $this->php_auth_pw
* $dt
* $tipouser_validar
* $datos_validar_tipo_user
* $data
* $sql_con
* $res_ver
* $vers
* $sql
* $sqmant
* $res_man
* $val
* $dispo_v


### **Conexion a base de datos:**
* psgc
* gamble70


### **Tablas Base de datos consultadas / Entidad relacion:**
* app_version
* datos


### **Tipo de Conector Bases de Datos:**
* cnn


### **Funciones:**
* new Log('app_sgc', $dt[0], $this->ruta)
* array_push($val, array("status" => 1, "version" => $vers))
* array_push($val, array("status" => 0, "msm" => "Se debe Actualizar"))
* array_push($val, array("status" => 0, "msm" => "No se pudo validar la version,por el momento sigue usando la misma."))
* return new JsonResponse(array($data))


### **Elaborado  Por:**
Johan Estaban Gutierrez.