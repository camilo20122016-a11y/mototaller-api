# 🏍️ MotoTaller — API REST de Autenticación
## Evidencia GA7-220501096-AA5-EV01

---

## Descripción
API REST en PHP puro para gestión de autenticación del sistema MotoTaller.
Proporciona dos servicios web: **registro** e **inicio de sesión**.

## Tecnología
- **Backend:** PHP 8+ (sin frameworks)
- **Base de datos:** MySQL con PDO
- **Formato:** JSON
- **Servidor:** XAMPP (Apache + MySQL)

## Estructura del Proyecto
```
mototaller-api/
├── config/
│   └── database.php          ← Conexión PDO Singleton
├── models/
│   └── Usuario.php           ← Modelo de usuarios
├── controllers/
│   └── AuthController.php    ← Servicios registro y login
├── database/
│   └── mototaller_api.sql    ← Script SQL
├── .htaccess                 ← Reescritura de URLs
├── index.php                 ← Router principal
└── README.md
```

---

## Endpoints

### POST /api/registro
Registra un nuevo usuario en el sistema.

**Body:**
```json
{
  "nombre":   "Camilo Rios",
  "email":    "camilo@mototaller.com",
  "password": "MiClave123",
  "rol":      "tecnico"
}
```

**Respuesta exitosa (201):**
```json
{
  "status": "success",
  "codigo": 201,
  "mensaje": "Usuario registrado exitosamente.",
  "data": {
    "nombre": "Camilo Rios",
    "email":  "camilo@mototaller.com",
    "rol":    "tecnico"
  }
}
```

**Respuesta error (400):**
```json
{
  "status":  "error",
  "codigo":  400,
  "mensaje": "Los campos nombre, email y password son obligatorios."
}
```

---

### POST /api/login
Inicia sesión y devuelve datos del usuario autenticado.

**Body:**
```json
{
  "email":    "camilo@mototaller.com",
  "password": "MiClave123"
}
```

**Respuesta exitosa (200):**
```json
{
  "status":  "success",
  "codigo":  200,
  "mensaje": "Autenticación satisfactoria.",
  "data": {
    "usuario": {
      "id":     1,
      "nombre": "Camilo Rios",
      "email":  "camilo@mototaller.com",
      "rol":    "tecnico"
    },
    "token":         "base64token...",
    "ultimo_acceso": "2025-01-15 10:30:00"
  }
}
```

**Respuesta error (401):**
```json
{
  "status":  "error",
  "codigo":  401,
  "mensaje": "Error en la autenticación. Credenciales incorrectas."
}
```

---

## Instalación en XAMPP
1. Copiar carpeta `mototaller-api/` a `C:\xampp\htdocs\`
2. Importar `database/mototaller_api.sql` en phpMyAdmin
3. Probar con Postman o Thunder Client en VS Code

## Repositorio
https://github.com/camilo20122016-a11y/mototaller-ga7
