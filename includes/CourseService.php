<?php
class CourseService {
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
    
    public function createCourse($code, $title, $description) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO courses (code, title, description, created_by)
                VALUES (?, ?, ?, ?)
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->bind_param("sssi", $code, $title, $description, $userId);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            
            $this->lastError = $stmt->error;
            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function getCourse($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as module_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?
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
    
    public function updateCourse($id, $code = null, $title = null, $description = null, $status = null) {
        try {
            $updates = [];
            $types = "";
            $params = [];
            
            if ($code !== null) {
                $updates[] = "code = ?";
                $types .= "s";
                $params[] = $code;
            }
            
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
            
            if ($status !== null) {
                $updates[] = "status = ?";
                $types .= "s";
                $params[] = $status;
            }
            
            if (empty($updates)) {
                return true;
            }
            
            $sql = "UPDATE courses SET " . implode(", ", $updates) . " WHERE id = ?";
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
    
    public function deleteCourse($id) {
        try {
            // Primeiro, verificamos se há módulos
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM modules WHERE course_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $this->lastError = "Não é possível excluir o curso pois existem módulos vinculados";
                return false;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM courses WHERE id = ?");
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
    
    public function getAllCourses($status = 'active', $orderBy = 'code') {
        try {
            $sql = "
                SELECT c.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as module_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.status = ?
                ORDER BY c.$orderBy ASC
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
