<?php
class LessonService {
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
    
    public function createLesson($topicId, $title, $description, $deadline, $maxScore = 10.00) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO lessons (topic_id, title, description, deadline, max_score, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->bind_param("isssdi", $topicId, $title, $description, $deadline, $maxScore, $userId);
            
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
    
    public function getLesson($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT l.*, t.title as topic_title, t.code as topic_code,
                       u.name as creator_name
                FROM lessons l
                LEFT JOIN topics t ON l.topic_id = t.id
                LEFT JOIN users u ON l.created_by = u.id
                WHERE l.id = ?
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
    
    public function updateLesson($id, $title = null, $description = null, $deadline = null, $maxScore = null) {
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
            
            if ($deadline !== null) {
                $updates[] = "deadline = ?";
                $types .= "s";
                $params[] = $deadline;
            }
            
            if ($maxScore !== null) {
                $updates[] = "max_score = ?";
                $types .= "d";
                $params[] = $maxScore;
            }
            
            if (empty($updates)) {
                return true;
            }
            
            $sql = "UPDATE lessons SET " . implode(", ", $updates) . " WHERE id = ?";
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
    
    public function deleteLesson($id) {
        try {
            // Primeiro, excluímos todas as submissões
            $stmt = $this->conn->prepare("DELETE FROM lesson_submissions WHERE lesson_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Agora excluímos a aula
            $stmt = $this->conn->prepare("DELETE FROM lessons WHERE id = ?");
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
    
    public function getLessonsByTopic($topicId, $status = 'active') {
        try {
            $sql = "
                SELECT l.*, u.name as creator_name
                FROM lessons l
                LEFT JOIN users u ON l.created_by = u.id
                WHERE l.topic_id = ? AND l.status = ?
                ORDER BY l.deadline ASC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $topicId, $status);
            
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
    
    public function submitLesson($lessonId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO lesson_submissions (lesson_id, user_id, submission_date, status)
                VALUES (?, ?, NOW(), 'submitted')
                ON DUPLICATE KEY UPDATE
                submission_date = NOW(),
                status = 'submitted'
            ");
            
            $stmt->bind_param("ii", $lessonId, $userId);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id ?: true;
            }
            
            $this->lastError = $stmt->error;
            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function gradeSubmission($lessonId, $userId, $score, $feedback = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE lesson_submissions
                SET score = ?,
                    feedback = ?,
                    status = 'graded'
                WHERE lesson_id = ? AND user_id = ?
            ");
            
            $stmt->bind_param("dsii", $score, $feedback, $lessonId, $userId);
            
            if ($stmt->execute()) {
                return true;
            }
            
            $this->lastError = $stmt->error;
            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function getStudentProgress($userId, $topicId = null) {
        try {
            $sql = "
                SELECT l.id as lesson_id, l.title, l.deadline, l.max_score,
                       ls.submission_date, ls.score, ls.status,
                       CASE
                           WHEN ls.submission_date IS NULL AND NOW() > l.deadline THEN 'late'
                           WHEN ls.submission_date IS NULL THEN 'pending'
                           WHEN ls.submission_date > l.deadline THEN 'submitted_late'
                           ELSE ls.status
                       END as current_status
                FROM lessons l
                LEFT JOIN lesson_submissions ls ON l.id = ls.lesson_id AND ls.user_id = ?
                WHERE l.status = 'active'
            " . ($topicId ? " AND l.topic_id = ?" : "") . "
                ORDER BY l.deadline ASC";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($topicId) {
                $stmt->bind_param("ii", $userId, $topicId);
            } else {
                $stmt->bind_param("i", $userId);
            }
            
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
