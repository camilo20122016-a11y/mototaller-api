<?php
/**
 * Router principal — MotoTaller API REST v2.0
 *
 * Endpoints disponibles:
 * ┌──────────────────────────────────────────────────────────┐
 * │ AUTH                                                     │
 * │  POST  ?ruta=registro       → Registrar usuario          │
 * │  POST  ?ruta=login          → Iniciar sesión             │
 * ├──────────────────────────────────────────────────────────┤
 * │ CLIENTES                                                 │
 * │  GET   ?ruta=clientes       → Listar todos               │
 * │  GET   ?ruta=clientes&id=N  → Obtener uno por ID         │
 * │  POST  ?ruta=clientes       → Crear cliente              │
 * │  PUT   ?ruta=clientes&id=N  → Actualizar cliente         │
 * │  DELETE ?ruta=clientes&id=N → Eliminar cliente           │
 * └──────────────────────────────────────────────────────────┘
 *
 * @package MotoTallerAPI
 * @version 2.0
 */

// ── Headers ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

// ── Cargar controladores ──────────────────────────────────────
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ClienteController.php';

// ── Leer parámetros ───────────────────────────────────────────
$ruta   = strtolower(trim($_GET['ruta'] ?? ''));
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$metodo = $_SERVER['REQUEST_METHOD'];

// ── Enrutamiento ──────────────────────────────────────────────
switch ($ruta) {

    // ── AUTH: Registro ────────────────────────────────────────
    case 'registro':
        if ($metodo !== 'POST') { metodoNoPermitido(); break; }
        (new AuthController())->registrar();
        break;

    // ── AUTH: Login ───────────────────────────────────────────
    case 'login':
        if ($metodo !== 'POST') { metodoNoPermitido(); break; }
        (new AuthController())->login();
        break;

    // ── CLIENTES: CRUD completo ───────────────────────────────
    case 'clientes':
        $ctrl = new ClienteController();
        switch ($metodo) {
            case 'GET':
                // GET con ID → obtener uno / sin ID → listar todos
                $id !== null ? $ctrl->obtener($id) : $ctrl->listar();
                break;
            case 'POST':
                $ctrl->crear();
                break;
            case 'PUT':
                if ($id === null) {
                    responderError(400, 'Especifica el ID: ?ruta=clientes&id=N');
                    break;
                }
                $ctrl->actualizar($id);
                break;
            case 'DELETE':
                if ($id === null) {
                    responderError(400, 'Especifica el ID: ?ruta=clientes&id=N');
                    break;
                }
                $ctrl->eliminar($id);
                break;
            default:
                metodoNoPermitido();
        }
        break;

    // ── Info general de la API ────────────────────────────────
    case '':
    default:
        if ($ruta === '') {
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'api'     => 'MotoTaller API REST',
                'version' => '2.0.0',
                'endpoints' => [
                    'POST ?ruta=registro'         => 'Registrar usuario',
                    'POST ?ruta=login'            => 'Iniciar sesión',
                    'GET  ?ruta=clientes'         => 'Listar todos los clientes',
                    'GET  ?ruta=clientes&id=N'    => 'Obtener cliente por ID',
                    'POST ?ruta=clientes'         => 'Crear cliente',
                    'PUT  ?ruta=clientes&id=N'    => 'Actualizar cliente',
                    'DELETE ?ruta=clientes&id=N'  => 'Eliminar cliente',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            responderError(404, "Endpoint '$ruta' no encontrado.");
        }
        break;
}

// ── Funciones auxiliares ──────────────────────────────────────
function metodoNoPermitido(): void {
    http_response_code(405);
    echo json_encode(['status'=>'error','codigo'=>405,
        'mensaje'=>'Método HTTP no permitido para este endpoint.'],
        JSON_UNESCAPED_UNICODE);
}

function responderError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['status'=>'error','codigo'=>$code,'mensaje'=>$msg],
        JSON_UNESCAPED_UNICODE);
}
