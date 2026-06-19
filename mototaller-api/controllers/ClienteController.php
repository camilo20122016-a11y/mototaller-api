<?php
/**
 * Controlador: ClienteController (API REST)
 *
 * Gestiona los endpoints REST del módulo de Clientes.
 * Implementa las operaciones CRUD completas:
 *
 * GET    /api/clientes        → listar todos
 * GET    /api/clientes/{id}   → obtener uno
 * POST   /api/clientes        → crear
 * PUT    /api/clientes/{id}   → actualizar
 * DELETE /api/clientes/{id}   → eliminar
 *
 * @package    MotoTallerAPI
 * @subpackage Controllers
 */

require_once __DIR__ . '/../models/ClienteAPI.php';

class ClienteController
{
    /** @var ClienteAPI Instancia del modelo */
    private ClienteAPI $modelo;

    public function __construct()
    {
        $this->modelo = new ClienteAPI();
    }

    // ── GET /api/clientes ─────────────────────────────────────

    /**
     * Lista todos los clientes registrados.
     * Método: GET
     *
     * @return void JSON con array de clientes
     */
    public function listar(): void
    {
        $clientes = $this->modelo->obtenerTodos();
        $this->responder(200, 'success',
            'Clientes obtenidos correctamente.', [
                'total'    => count($clientes),
                'clientes' => $clientes,
            ]
        );
    }

    // ── GET /api/clientes/{id} ────────────────────────────────

    /**
     * Obtiene un cliente específico por ID.
     * Método: GET
     *
     * @param  int  $id ID del cliente
     * @return void JSON con datos del cliente
     */
    public function obtener(int $id): void
    {
        // Validar que el ID sea positivo
        if ($id <= 0) {
            $this->responder(400, 'error', 'ID de cliente inválido.');
            return;
        }

        $cliente = $this->modelo->obtenerUno($id);

        if ($cliente === null) {
            $this->responder(404, 'error',
                "No se encontró ningún cliente con ID $id.");
            return;
        }

        $this->responder(200, 'success', 'Cliente encontrado.', [
            'cliente' => $cliente,
        ]);
    }

    // ── POST /api/clientes ────────────────────────────────────

    /**
     * Crea un nuevo cliente.
     * Método: POST
     *
     * @return void JSON con datos del cliente creado
     */
    public function crear(): void
    {
        // Leer body JSON
        $body = json_decode(file_get_contents('php://input'), true);

        if ($body === null) {
            $this->responder(400, 'error', 'El body debe ser JSON válido.');
            return;
        }

        // Extraer y sanear campos
        $nombre    = $this->sanear($body['nombre']    ?? '');
        $telefono  = $this->sanear($body['telefono']  ?? '');
        $direccion = $this->sanear($body['direccion'] ?? '');
        $email     = $this->sanear($body['email']     ?? '');

        // Validar campos obligatorios
        if (empty($nombre) || empty($telefono) || empty($direccion) || empty($email)) {
            $this->responder(400, 'error',
                'Todos los campos son obligatorios: nombre, telefono, direccion, email.');
            return;
        }

        // Validar formato email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, 'error',
                'El formato del correo electrónico no es válido.');
            return;
        }

        // Validar longitudes máximas
        if (strlen($nombre) > 100 || strlen($telefono) > 20 ||
            strlen($direccion) > 200 || strlen($email) > 100) {
            $this->responder(400, 'error',
                'Uno o más campos exceden la longitud máxima permitida.');
            return;
        }

        // Verificar email duplicado
        if ($this->modelo->emailExiste($email)) {
            $this->responder(409, 'error',
                'El correo electrónico ya está registrado por otro cliente.');
            return;
        }

        // Crear cliente y obtener ID generado
        $nuevoId = $this->modelo->crear($nombre, $telefono, $direccion, $email);

        if ($nuevoId > 0) {
            // Recuperar el cliente recién creado para devolverlo completo
            $clienteCreado = $this->modelo->obtenerUno($nuevoId);
            $this->responder(201, 'success', 'Cliente creado exitosamente.', [
                'cliente' => $clienteCreado,
            ]);
        } else {
            $this->responder(500, 'error', 'Error al crear el cliente.');
        }
    }

    // ── PUT /api/clientes/{id} ────────────────────────────────

    /**
     * Actualiza los datos de un cliente existente.
     * Método: PUT
     *
     * @param  int  $id ID del cliente a actualizar
     * @return void JSON con confirmación
     */
    public function actualizar(int $id): void
    {
        if ($id <= 0) {
            $this->responder(400, 'error', 'ID de cliente inválido.');
            return;
        }

        // Verificar que el cliente existe
        if ($this->modelo->obtenerUno($id) === null) {
            $this->responder(404, 'error',
                "No se encontró ningún cliente con ID $id.");
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if ($body === null) {
            $this->responder(400, 'error', 'El body debe ser JSON válido.');
            return;
        }

        $nombre    = $this->sanear($body['nombre']    ?? '');
        $telefono  = $this->sanear($body['telefono']  ?? '');
        $direccion = $this->sanear($body['direccion'] ?? '');
        $email     = $this->sanear($body['email']     ?? '');

        if (empty($nombre) || empty($telefono) || empty($direccion) || empty($email)) {
            $this->responder(400, 'error',
                'Todos los campos son obligatorios: nombre, telefono, direccion, email.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, 'error',
                'El formato del correo electrónico no es válido.');
            return;
        }

        // Verificar email duplicado en OTRO cliente (excluir el actual)
        if ($this->modelo->emailExiste($email, $id)) {
            $this->responder(409, 'error',
                'El correo ya está en uso por otro cliente.');
            return;
        }

        if ($this->modelo->actualizar($id, $nombre, $telefono, $direccion, $email)) {
            $clienteActualizado = $this->modelo->obtenerUno($id);
            $this->responder(200, 'success', 'Cliente actualizado correctamente.', [
                'cliente' => $clienteActualizado,
            ]);
        } else {
            $this->responder(500, 'error', 'Error al actualizar el cliente.');
        }
    }

    // ── DELETE /api/clientes/{id} ─────────────────────────────

    /**
     * Elimina un cliente por su ID.
     * Método: DELETE
     *
     * @param  int  $id ID del cliente a eliminar
     * @return void JSON con confirmación
     */
    public function eliminar(int $id): void
    {
        if ($id <= 0) {
            $this->responder(400, 'error', 'ID de cliente inválido.');
            return;
        }

        // Verificar que el cliente existe antes de eliminar
        $cliente = $this->modelo->obtenerUno($id);
        if ($cliente === null) {
            $this->responder(404, 'error',
                "No se encontró ningún cliente con ID $id.");
            return;
        }

        if ($this->modelo->eliminar($id)) {
            $this->responder(200, 'success',
                "Cliente '{$cliente['nombre']}' eliminado correctamente.", [
                    'id_eliminado' => $id,
                ]
            );
        } else {
            $this->responder(500, 'error', 'Error al eliminar el cliente.');
        }
    }

    // ── Métodos auxiliares ────────────────────────────────────

    /**
     * Envía respuesta JSON estándar.
     *
     * @param int    $code    HTTP status code
     * @param string $status  success | error
     * @param string $mensaje Mensaje descriptivo
     * @param array  $data    Datos adicionales
     */
    private function responder(
        int $code, string $status, string $mensaje, array $data = []
    ): void {
        http_response_code($code);
        $resp = ['status' => $status, 'codigo' => $code, 'mensaje' => $mensaje];
        if (!empty($data)) $resp['data'] = $data;
        echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Sanea una cadena eliminando HTML y espacios peligrosos.
     *
     * @param  string $valor
     * @return string
     */
    private function sanear(string $valor): string
    {
        return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
    }
}
