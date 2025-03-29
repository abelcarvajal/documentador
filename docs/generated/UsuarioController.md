<!-- filepath: C:\git_consuerte\serversoap_recaudos\src\Controller\UsuarioController.php -->
# **TICKET:** ##
### **Fecha de creación/modificación:** 2025-03-29
## **Nombre Aplicativo:** UsuarioController.php

### **Descripcion Aplicativo:**
```

```

### **Librerias**
* App\Entity\Psgc\Sesion
* App\Entity\Psgc\Usuario
* Doctrine\ORM\EntityManagerInterface
* Doctrine\Persistence\ManagerRegistry
* Symfony\Bundle\FrameworkBundle\Controller\AbstractController
* Symfony\Component\HttpFoundation\JsonResponse
* Symfony\Component\HttpFoundation\Request
* Symfony\Component\HttpFoundation\Response
* Symfony\Component\Routing\Annotation\Route
* Symfony\Component\Validator\Constraints\Email
* Symfony\Component\Validator\Validation

### **Servicios**
*Sin servicios*

### **Lenguaje de programación utilizado:**
PHP

### **URL**
* UsuarioController.php -> [Ruta archivo: UsuarioController.php](../../src/Controller/UsuarioController.php)

### **URL / .md Documentación Archivos Implementados**
*  -> [Ruta archivo: ](../../public/guia_programador/)

# **PHP:**

### **Listado de Variables Globales**
*Sin variables globales*

### **Listado de Variables**
```
* cnn
* em
* h_c
* http_client
* ip
* log
* nickname
* php_auth_pw
* php_auth_user
* prm
* ruta
```
### **Tablas Base de datos consultadas / Entidad relacion**
* del
* usuarios

### **Tipo de Conector Bases de Datos**
* cnn->query('0')
* cnn->query('19')
* cnn->query('2')

## **Conexión a base de datos**
* app consuerte 10.1.1.10
* gamble 70 10.67.34.70
* producion Sgc 10.1.1.4

### **Funciones**
* __construct
* __construct
* controlados_x_controlador
* controlados_x_controlador
* convert_from_latin1_to_utf8_recursively
* create
* create
* edit
* edit
* email_prueba
* email_prueba
* index
* index
* initialize
* initialize
* login
* login
* login_decode
* login_decode
* login_decode_App
* login_decode_App
* modificarUsuariosConsuertePay
* modificarUsuariosConsuertePay

## Rutas
* app_usuario:
  - **Path:** `/usuario`
  - **Methods:**  (GET)
  - **Controller:** `PHPParser::index`
* app_usuario_register:
  - **Path:** `/register`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::create`
* app_usuario_login:
  - **Path:** `/login`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::login`
* app_usuario_edit:
  - **Path:** `/user/edit`
  - **Methods:**  (PUT)
  - **Controller:** `PHPParser::edit`
* app_usuario_decode:
  - **Path:** `/login_decode`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::login_decode`
* app_usuario_decodeApp:
  - **Path:** `/login_decode_App`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::login_decode_App`
* email_prueba:
  - **Path:** `/email_prueba`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::email_prueba`
* controlados_x_controlador:
  - **Path:** `/controlados_x_controlador`
  - **Methods:**  (POST)
  - **Controller:** `PHPParser::controlados_x_controlador`

### **Realizado por:**
José Abel Carvajal
