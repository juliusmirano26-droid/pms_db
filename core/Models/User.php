<?php
// core/Models/User.php
abstract class User {
    public int $id;
    public string $name;
    public string $email;
    public string $role;

    public function __construct(int $id, string $name, string $email, string $role) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }

    abstract public function getPermissions(): array;
}

class Admin extends User {
    public function getPermissions(): array { return ['all']; }
}

class ProjectManager extends User {
    public function getPermissions(): array { return ['manage_projects', 'assign_tasks']; }
}

class TeamMember extends User {
    public function getPermissions(): array { return ['view_tasks', 'update_task']; }
}

class Client extends User {
    public function getPermissions(): array { return ['view_outputs']; }
}