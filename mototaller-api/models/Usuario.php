<?php
/**
 * Modelo: Usuario
 *
 * Gestiona las operaciones de base de datos para la tabla `usuarios`.
 * Es la única capa que accede directamente a la BD.
 *
 * @package    MotoTallerAPI
 * @subpackage Models
 */

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    /** @var PDO Conexión activa */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Métodos de consulta ───────────────────────────────────

    /**
     * Busca un usuario por su email.
     *
     * @param  string     $email Email a buscar
     * @return array|null Datos del usuario o null si no existe
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql  = "SELECT * FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Verifica si un email ya está registrado.
     *
     * @param  string $email Email a verificar
     * @return bool   true si ya existe
     */
    public function emailExiste(string $email): bool
    {
        $sql  = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Registra un nuevo usuario en la base de datos.
     * La contraseña se encripta con password_hash (bcrypt).
     *
     * @param  string $nombre   Nombre completo
     * @param  string $email    Correo electrónico
     * @param  string $password Contraseña en texto plano (se encripta aquí)
     * @param  string $rol      Rol del usuario
     * @return bool  true si se registró correctamente
     */
    public function registrar(
        string $nombre,
        string $email,
        string $password,
        string $rol = 'tecnico'
    ): bool {
        // Encriptar contraseña con bcrypt (costo 12)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol)
                VALUES (:nombre, :email, :password_hash, :rol)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre',        $nombre,       PDO::PARAM_STR);
        $stmt->bindParam(':email',         $email,        PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':rol',           $rol,          PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Actualiza el campo ultimo_acceso del usuario tras login exitoso.
     *
     * @param  int  $id ID del usuario
     * @return void
     */
    public function actualizarUltimoAcceso(int $id): void
    {
        $sql  = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
