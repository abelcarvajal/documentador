<!-- filepath: C:\Users\auxsenadesarrollo\Desktop\ABEL\Documentador_Simfony\archivos_testing\AppController.php -->
# **TICKET:** ##
### **Fecha de creación/modificación:** 2025-03-20
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
* AppController.php -> [Ruta archivo: AppController.php](../../src/Controller/AppController.php)

### **URL / .md Documentación Archivos Implementados**
*  -> [Ruta archivo: ](../../public/guia_programador/)

# **PHP:**

### **Listado de Variables Globales**
*Sin variables globales*

### **Listado de Variables**
```
*Sin variables locales*
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
* **app_app** - /app (POST) - PHPParser::index
* **app_app_validatelogin** - /app/validatelogin (POST) - PHPParser::validatelogin
* **app_app_newTokenFirebase** - /app/newTokenFirebase (POST) - PHPParser::newTokenFirebase
* **app_app_metodos** - /app/metodos (POST) - PHPParser::metodos
* **app_app_metodos_web** - /app/metodos_web (POST) - PHPParser::metodos_web
* **app_app_validar_maquina** - /app/validar_maquina (POST) - PHPParser::validar_maquina
* **app_app_test_registro** - /app/test_registro (POST) - PHPParser::test_registro
* **app_app_verificacion_transaccion_bemovil** - /app/verificacion_transaccion_bemovil (POST) - PHPParser::verificacion_transaccion

### **Realizado por:**
José Abel Carvajal
