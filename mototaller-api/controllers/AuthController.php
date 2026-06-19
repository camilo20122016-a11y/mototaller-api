<?php
/**
 * Controlador: AuthController
 *
 * Gestiona los servicios web de autenticación:
 * - registrar(): servicio de registro de nuevos usuarios
 * - login():     servicio de inicio de sesión
 *
 * Todos los métodos devuelven JSON con la estructura:
 * {
 *   "status":  "success" | "error",
 *   "mensaje": "Descripción del resultado",
 *   "data":    { ... } // Solo en respuestas exitosas
 * }
 *
 * @package    MotoTallerAPI
 * @subpackage Controllers
 */

require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    /** @var Usuario Instancia del modelo de usuarios */
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    // ── SERVICIO 1: Registro ──────────────────────────────────

    /**
     * Servicio web de registro de usuario.
     *
     * Método HTTP: POST
     * Endpoint:    /api/registro
     *
     * Body JSON esperado:
     * {
     *   "nombre":   "Camilo Rios",
     *   "email":    "camilo@mototaller.com",
     *   "password": "MiClave123*",
     *   "rol":      "tecnico"  (opcional)
     * }
     *
     * Respuestas posibles:
     * - 201 Created:  registro exitoso
     * - 400 Bad Request: datos inválidos
     * - 409 Conflict: email ya registrado
     * - 500 Error:    fallo del servidor
     *
     * @return void Imprime JSON
     */
    public function registrar(): void
    {
        // 1. Leer y decodificar el cuerpo JSON de la petición
        $body = json_decode(file_get_contents('php://input'), true);

        // 2. Verificar que el JSON sea válido
        if ($body === null) {
            $this->responder(400, 'error', 'El cuerpo de la petición debe ser JSON válido.');
            return;
        }

        // 3. Extraer y sanear campos
        $nombre   = $this->sanear($body['nombre']   ?? '');
        $email    = $this->sanear($body['email']     ?? '');
        $password = trim($body['password']           ?? '');
        $rol      = $this->sanear($body['rol']       ?? 'tecnico');

        // 4. Validaciones de campos obligatorios
        if (empty($nombre) || empty($email) || empty($password)) {
            $this->responder(400, 'error', 'Los campos nombre, email y password son obligatorios.');
            return;
        }

        // 5. Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, 'error', 'El formato del correo electrónico no es válido.');
            return;
        }

        // 6. Validar longitud mínima de contraseña
        if (strlen($password) < 8) {
            $this->responder(400, 'error', 'La contraseña debe tener mínimo 8 caracteres.');
            return;
        }

        // 7. Validar fortaleza de contraseña
        if (!preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password)) {
            $this->responder(400, 'error',
                'La contraseña debe contener al menos una mayúscula y un número.');
            return;
        }

        // 8. Validar rol permitido
        $rolesPermitidos = ['admin', 'tecnico', 'recepcion'];
        if (!in_array($rol, $rolesPermitidos)) {
            $this->responder(400, 'error',
                'Rol inválido. Valores permitidos: admin, tecnico, recepcion.');
            return;
        }

        // 9. Verificar si el email ya está registrado
        if ($this->usuarioModel->emailExiste($email)) {
            $this->responder(409, 'error',
                'El correo electrónico ya está registrado en el sistema.');
            return;
        }

        // 10. Registrar usuario en la BD
        if ($this->usuarioModel->registrar($nombre, $email, $password, $rol)) {
            $this->responder(201, 'success', 'Usuario registrado exitosamente.', [
                'nombre' => $nombre,
                'email'  => $email,
                'rol'    => $rol,
            ]);
        } else {
            $this->responder(500, 'error', 'Error al registrar el usuario. Intente de nuevo.');
        }
    }

    // ── SERVICIO 2: Login ─────────────────────────────────────

    /**
     * Servicio web de inicio de sesión.
     *
     * Método HTTP: POST
     * Endpoint:    /api/login
     *
     * Body JSON esperado:
     * {
     *   "email":    "camilo@mototaller.com",
     *   "password": "MiClave123*"
     * }
     *
     * Respuestas posibles:
     * - 200 OK:           autenticación satisfactoria
     * - 400 Bad Request:  datos faltantes o inválidos
     * - 401 Unauthorized: credenciales incorrectas
     *
     * @return void Imprime JSON
     */
    public function login(): void
    {
        // 1. Leer cuerpo JSON
        $body = json_decode(file_get_contents('php://input'), true);

        if ($body === null) {
            $this->responder(400, 'error', 'El cuerpo de la petición debe ser JSON válido.');
            return;
        }

        // 2. Extraer campos
        $email    = $this->sanear($body['email']    ?? '');
        $password = trim($body['password']          ?? '');

        // 3. Validar campos obligatorios
        if (empty($email) || empty($password)) {
            $this->responder(400, 'error', 'Los campos email y password son obligatorios.');
            return;
        }

        // 4. Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, 'error', 'El formato del correo electrónico no es válido.');
            return;
        }

        // 5. Buscar usuario en BD por email
        $usuario = $this->usuarioModel->buscarPorEmail($email);

        // 6. Verificar existencia del usuario
        // IMPORTANTE: no revelar si el email existe o no (seguridad)
        if ($usuario === null) {
            $this->responder(401, 'error', 'Error en la autenticación. Credenciales incorrectas.');
            return;
        }

        // 7. Verificar contraseña con password_verify (bcrypt)
        // password_verify compara la contraseña en texto plano
        // contra el hash almacenado de forma segura
        if (!password_verify($password, $usuario['password_hash'])) {
            $this->responder(401, 'error', 'Error en la autenticación. Credenciales incorrectas.');
            return;
        }

        // 8. Autenticación exitosa — actualizar último acceso
        $this->usuarioModel->actualizarUltimoAcceso($usuario['id']);

        // 9. Generar token de sesión simple (en producción usar JWT)
        $token = base64_encode($usuario['id'] . ':' . time() . ':' . bin2hex(random_bytes(16)));

        // 10. Responder con datos del usuario (SIN incluir la contraseña)
        $this->responder(200, 'success', 'Autenticación satisfactoria.', [
            'usuario' => [
                'id'     => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
            ],
            'token'         => $token,
            'ultimo_acceso' => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Métodos auxiliares privados ───────────────────────────

    /**
     * Envía una respuesta JSON con el código HTTP y estructura estándar.
     *
     * @param  int    $httpCode Código de respuesta HTTP (200, 201, 400, etc.)
     * @param  string $status   'success' o 'error'
     * @param  string $mensaje  Mensaje descriptivo del resultado
     * @param  array  $data     Datos adicionales (solo en éxito)
     * @return void
     */
    private function responder(
        int    $httpCode,
        string $status,
        string $mensaje,
        array  $data = []
    ): void {
        http_response_code($httpCode);
        $respuesta = [
            'status'  => $status,
            'codigo'  => $httpCode,
            'mensaje' => $mensaje,
        ];
        if (!empty($data)) {
            $respuesta['data'] = $data;
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Sanea una cadena eliminando espacios y caracteres peligrosos.
     *
     * @param  string $valor Valor a sanear
     * @return string Valor limpio
     */
    private function sanear(string $valor): string
    {
        return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    }
}
