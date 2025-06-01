<?php
class TopicService {
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
    
    public function createTopic($moduleId, $code, $title, $description, $startDate, $endDate, $orderIndex = 0) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO topics (module_id, code, title, description, start_date, end_date, order_index, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->bind_param("isssssii", $moduleId, $code, $title, $description, $startDate, $endDate, $orderIndex, $userId);
            
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
    
    public function getTopic($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM lessons WHERE topic_id = t.id) as lesson_count
                FROM topics t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.id = ?
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
    
    public function updateTopic($id, $code = null, $title = null, $description = null, $startDate = null, $endDate = null, $orderIndex = null) {
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
            
            if ($startDate !== null) {
                $updates[] = "start_date = ?";
                $types .= "s";
                $params[] = $startDate;
            }
            
            if ($endDate !== null) {
                $updates[] = "end_date = ?";
                $types .= "s";
                $params[] = $endDate;
            }
            
            if ($orderIndex !== null) {
                $updates[] = "order_index = ?";
                $types .= "i";
                $params[] = $orderIndex;
            }
            
            if (empty($updates)) {
                return true;
            }
            
            $sql = "UPDATE topics SET " . implode(", ", $updates) . " WHERE id = ?";
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
    
    public function deleteTopic($id) {
        try {
            // Primeiro, verificamos se há aulas
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM lessons WHERE topic_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $this->lastError = "Não é possível excluir o tópico pois existem aulas vinculadas";
                return false;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM topics WHERE id = ?");
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
    
    public function getTopicsByModule($moduleId, $status = 'active') {
        try {
            $sql = "
                SELECT t.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM lessons WHERE topic_id = t.id) as lesson_count
                FROM topics t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.module_id = ? AND t.status = ?
                ORDER BY t.start_date ASC, t.order_index ASC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $moduleId, $status);
            
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
