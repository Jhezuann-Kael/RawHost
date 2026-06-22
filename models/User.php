<?php
class User
{
    private $conn;
    public $id;
    public $username;
    public $email;
    public $password;

    public function __construct($db)
    {
        $this->conn = $db->connect();
    }

    public function register($username, $email, $password)
    {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function login($username, $password)
    {
        // Find user by username or email
        $query = "SELECT id, username, password, is_superuser FROM users WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username); // Check both
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $_SESSION['is_superuser'] = $row['is_superuser'];
                return true;
            }
        }
        return false;
    }

    public function exists($username, $email)
    {
        $query = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
