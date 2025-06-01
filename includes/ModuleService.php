<?php
require_once "config.php";

class ModuleService {
    private static $instance = null;
    private $conn;
    private $lastError = '';
    
    private function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createModule($courseId, $title, $description, $icon = null, $color = null, $orderIndex = 0) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO modules (course_id, title, description, icon, color, order_index, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->bind_param("issssii", $courseId, $title, $description, $icon, $color, $orderIndex, $userId);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            } else {
                $this->lastError = $stmt->error;
                return false;
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function updateModule($id, $title, $description, $icon = null, $color = null, $orderIndex = null) {
        try {
            $updates = [];
            $types = "";
            $params = [];
            
            if ($title !== null) {
                $updates[] = "title = ?";
                $types .= "s";
                $params[] = $title;
            }
            
            if ($description !== null) {
                $updates[] = "description = ?";
                $types .= "s";
                $params[] = $description;
            }
            
            if ($icon !== null) {
                $updates[] = "icon = ?";
                $types .= "s";
                $params[] = $icon;
            }
            
            if ($color !== null) {
                $updates[] = "color = ?";
                $types .= "s";
                $params[] = $color;
            }
            
            if ($orderIndex !== null) {
                $updates[] = "order_index = ?";
                $types .= "i";
                $params[] = $orderIndex;
            }
            
            if (empty($updates)) {
                return true; // Nada para atualizar
            }
            
            $sql = "UPDATE modules SET " . implode(", ", $updates) . " WHERE id = ?";
            $types .= "i";
            $params[] = $id;
            
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    return true;
                }
            }
            
            $this->lastError = $stmt ? $stmt->error : $this->conn->error;
            return false;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function deleteModule($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM modules WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return $stmt->affected_rows > 0;
            }
            
            $this->lastError = $stmt->error;
            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function getModule($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*, u.name as creator_name 
                FROM modules m 
                LEFT JOIN users u ON m.created_by = u.id 
                WHERE m.id = ?
            ");
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                return $result->fetch_assoc();
            }
            
            $this->lastError = $stmt->error;
            return null;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
    
    public function getAllModules($status = 'active', $orderBy = 'order_index') {
        try {
            $sql = "
                SELECT m.*, u.name as creator_name 
                FROM modules m 
                LEFT JOIN users u ON m.created_by = u.id 
                WHERE m.status = ?
                ORDER BY m.$orderBy ASC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $status);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            $this->lastError = $stmt->error;
            return [];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}
?>
