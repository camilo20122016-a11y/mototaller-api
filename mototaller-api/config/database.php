<?php
/**
 * Configuración de conexión a la base de datos.
 * Utiliza PDO con patrón Singleton.
 *
 * @package    MotoTallerAPI
 * @subpackage Config
 */

// ── Constantes de conexión ────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'mototaller_api');
define('DB_USER',    'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Clase Database — Conexión PDO Singleton.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            // Registrar error sin exponer detalles al cliente
            error_log('DB Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'mensaje' => 'Error de conexión al servidor.']);
            exit;
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
