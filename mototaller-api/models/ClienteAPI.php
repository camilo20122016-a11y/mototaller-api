<?php
/**
 * Modelo: ClienteAPI
 *
 * Gestiona todas las operaciones CRUD sobre la tabla `clientes`
 * para ser consumidas por la API REST de MotoTaller.
 *
 * @package    MotoTallerAPI
 * @subpackage Models
 */

require_once __DIR__ . '/../config/database.php';

class ClienteAPI
{
    /** @var PDO Conexión activa a la base de datos */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── READ ALL ──────────────────────────────────────────────

    /**
     * Obtiene todos los clientes ordenados por nombre.
     *
     * @return array Lista de clientes
     */
    public function obtenerTodos(): array
    {
        $sql  = "SELECT id, nombre, telefono, direccion, email,
                        DATE_FORMAT(fecha_registro, '%Y-%m-%d %H:%i:%s') AS fecha_registro
                 FROM clientes
                 ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un cliente por su ID.
     *
     * @param  int        $id ID del cliente
     * @return array|null Datos del cliente o null
     */
    public function obtenerUno(int $id): ?array
    {
        $sql  = "SELECT id, nombre, telefono, direccion, email,
                        DATE_FORMAT(fecha_registro, '%Y-%m-%d %H:%i:%s') AS fecha_registro
                 FROM clientes WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Verifica si un email ya existe (para validar duplicados).
     *
     * @param  string   $email     Email a verificar
     * @param  int|null $excluirId ID a excluir (para updates)
     * @return bool
     */
    public function emailExiste(string $email, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $sql  = "SELECT COUNT(*) FROM clientes WHERE email = :email AND id != :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':id',    $excluirId, PDO::PARAM_INT);
        } else {
            $sql  = "SELECT COUNT(*) FROM clientes WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── CREATE ────────────────────────────────────────────────

    /**
     * Inserta un nuevo cliente.
     *
     * @param  string $nombre
     * @param  string $telefono
     * @param  string $direccion
     * @param  string $email
     * @return int    ID del cliente creado
     */
    public function crear(
        string $nombre,
        string $telefono,
        string $direccion,
        string $email
    ): int {
        $sql  = "INSERT INTO clientes (nombre, telefono, direccion, email)
                 VALUES (:nombre, :telefono, :direccion, :email)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre',    $nombre,    PDO::PARAM_STR);
        $stmt->bindParam(':telefono',  $telefono,  PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        $stmt->bindParam(':email',     $email,     PDO::PARAM_STR);
        $stmt->execute();
        // Retornar el ID del registro insertado
        return (int)$this->db->lastInsertId();
    }

    // ── UPDATE ────────────────────────────────────────────────

    /**
     * Actualiza los datos de un cliente existente.
     *
     * @param  int    $id
     * @param  string $nombre
     * @param  string $telefono
     * @param  string $direccion
     * @param  string $email
     * @return bool
     */
    public function actualizar(
        int    $id,
        string $nombre,
        string $telefono,
        string $direccion,
        string $email
    ): bool {
        $sql  = "UPDATE clientes
                 SET nombre = :nombre, telefono = :telefono,
                     direccion = :direccion, email = :email
                 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre',    $nombre,    PDO::PARAM_STR);
        $stmt->bindParam(':telefono',  $telefono,  PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        $stmt->bindParam(':email',     $email,     PDO::PARAM_STR);
        $stmt->bindParam(':id',        $id,        PDO::PARAM_INT);
        $stmt->execute();
        // Verificar que se afectó al menos una fila
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ────────────────────────────────────────────────

    /**
     * Elimina un cliente por su ID.
     *
     * @param  int  $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $sql  = "DELETE FROM clientes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Cuenta el total de clientes registrados.
     *
     * @return int
     */
    public function contarTodos(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
