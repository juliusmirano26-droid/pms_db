<?php
// core/Services/AuthService.php
require_once __DIR__ . '/User.php';

class AuthService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function login(string $email, string $password): ?User {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        // Verify plain text match or hashed match (using plain text here to correspond with seed data)
        if ($row && $password === $row['password']) {
            // Instantiate the correct object type using polymorphism based on role
            switch ($row['role']) {
                case 'Admin': return new Admin($row['id'], $row['name'], $row['email'], $row['role']);
                case 'Project Manager': return new ProjectManager($row['id'], $row['name'], $row['email'], $row['role']);
                case 'Team Member': return new TeamMember($row['id'], $row['name'], $row['email'], $row['role']);
                case 'Client': return new Client($row['id'], $row['name'], $row['email'], $row['role']);
            }
        }
        return null;
    }
}