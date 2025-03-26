<!-- filepath: C:\git_consuerte\serversoap_recaudos\src\Controller\ComercialController.php -->
# **TICKET:** ##
### **Fecha de creación/modificación:** 2025-03-21
## **Nombre Aplicativo:** ComercialController.php

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
* Symfony\Contracts\HttpClient\HttpClientInterface

### **Lenguaje de programación utilizado:**
PHP

### **URL**
* ComercialController.php -> [Ruta archivo: ComercialController.php](..\..\..\..\..\..\..\..\git_consuerte\serversoap_recaudos\src\Controller\ComercialController.php\ComercialController.php)

### **URL / .md Documentación Archivos Implementados**
* ComercialController.md -> [Ruta archivo: ComercialController.md](documentador\docs\generated\ComercialController.md)

# **PHP:**

### **Listado de Variables Globales**
*Sin variables globales*

### **Listado de Variables**
```
* apellido
* apellido1
* archivo
* archivo2
* archivo3
* auth
* auxTipob
* bono
* campoventa
* cant
* cantidad
* cc
* cedula
* cnn
* com_ltn
* com_ltp
* comision1
* compara
* compara_anticipo
* content
* convenio
* convenios
* data_2
* dir
* enlace
* est
* estado
* estadoNombre
* f
* f_p
* fecha_act
* fecha_imp
* fecha_impresion
* fechasys
* fechav
* form
* gru_v
* h_c
* http_client
* id
* ip_address
* json
* jsonContent
* key
* largTotBon
* largo
* largo1
* largo2
* largo3
* largo4
* link
* log
* n_r
* nickname
* nm_ser
* nom_pro
* nombres
* obj
* opciones
* p_d
* params
* path
* poliza
* prm
* producto
* pv
* pvt
* request
* res
* res0
* res1
* res11
* res13
* res1_t
* res2
* res5
* res6
* res61
* res7
* res71
* res9
* res_bonos
* res_tvo
* resb
* reslt
* response
* respuesta
* row
* rs2
* ruta_arch
* s_e
* sacar_iva
* ser
* serv
* sq2
* sql
* sql0
* sql1
* sql11
* sql13
* sql1_abonostat
* sql2
* sql5
* sql6
* sql61
* sql7
* sql71
* sqlReversoTAT
* sql_bmovil
* sql_bonos
* sql_cultura
* sql_devol_tvo
* sqlb
* sqllt
* tabla
* tabla_recaudo
* tercero
* tipo
* tipo2
* tipo_bet
* tipob
* tipodire
* tipoter
* total
* total2
* totalB
* total_dona
* total_lf
* usuario
* v_70
* v_80
* v_r
* valor
* valor1
* valor2
* valorB
* valorTotBono
* x
* x1
* y
* z
* zo
```
### **Tablas Base de datos consultadas / Entidad relacion**
* bases_pventa
* bemovil_colpatria_depositos_retiros_recaudado
* bemovil_hvehicular_recaudado
* bemovil_pines_recaudado
* bemovil_recargas_recaudado
* bemovil_tipo_operador
* boletas_convenio_inv
* bonos_regalo_entregados
* bonos_regalo_genericos_redimidos
* cedellanos_recaudado_usuario_nuevo
* cierres_asesoras
* cofrem_consulta_ws
* consignacion_usuarios_betplay
* consignacion_usuarios_betplay_reverso
* controlhorariopersonas
* credibanco_asignar_usuario
* credibanco_regis_detalle
* cultura_recaudado
* detallevtasotrosproductos
* directv_pre_afi_devolucion
* donaciones_covid_19
* donaciones_covid_19_entrega
* donaciones_recaudado
* emsa_asignacion_punto
* formularios
* liquidadores_vendedor
* llanogas_recaudado
* llanogas_recaudado_ws
* loteria_fisica_vendida
* moya_osorio_afiliaciones
* otrosdias
* personas
* porcentaje_comision_empresa
* premiospersonaproveedor
* recaudos_sgc
* sem_lista_convenios
* tablas_recaudos_facturas
* terceros_inv
* traslados_vendedores
* tvo_comunicaciones_devoluciones
* v_totalventasnegocio
* venta_producto_inv
* vista_recaudos_sgc

### **Tipo de Conector Bases de Datos**
* cnn->query('0')
* cnn->query('13')
* cnn->query('2')
* gamble70

## **Conexión a base de datos**
* gamble 70 10.67.34.70
* gamble 80 10.72.34.80
* producion Sgc 10.1.1.4

### **Funciones**
* __construct
* __construct
* cargarConveniosSupervisores
* cargarConveniosSupervisores
* imprimir_reporte_venta_vendedor
* imprimir_reporte_venta_vendedor
* index
* index
* modificarConveniosSupervisores
* modificarConveniosSupervisores
* registrarConveniosSupervisores
* registrarConveniosSupervisores

## Rutas
* app_comercial:
  - **Path:** `/comercial`
  - **Methods:**  (GET)
  - **Controller:** `PHPParser::index`
* comercial_imprimir_reporte_venta_vendedor:
  - **Path:** `/comercial/imprimir_reporte_venta_vendedor`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::imprimir_reporte_venta_vendedor`

### **Realizado por:**
José Abel Carvajal


