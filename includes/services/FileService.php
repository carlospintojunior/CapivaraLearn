<?php
require_once __DIR__ . '/../config.php';

/**
 * Serviço para gerenciamento de arquivos
 */
class FileService {
/**
 * Serviço para gerenciamento de uploads de arquivos
 */
class FileService {
    private $db;
    private static $instance = null;
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../../public/assets/uploads/';
        $this->allowedTypes = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar'
        ];
        $this->maxFileSize = 50 * 1024 * 1024; // 50MB
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Processa o upload de um arquivo
     * @param array $file Array $_FILES do arquivo
     * @param string $subdir Subdiretório opcional para organizar uploads
     * @return array Informações do arquivo processado
     * @throws Exception
     */
    public function processUpload($file, $subdir = '') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Validar tamanho
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("Arquivo muito grande. Tamanho máximo permitido: 50MB");
        }

        // Validar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($this->allowedTypes[$mimeType])) {
            throw new Exception("Tipo de arquivo não permitido");
        }

        // Criar subdiretório se necessário
        $targetDir = $this->uploadDir;
        if ($subdir) {
            $targetDir .= trim($subdir, '/') . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        // Gerar nome único
        $extension = $this->allowedTypes[$mimeType];
        $fileName = uniqid('file_') . '.' . $extension;
        $targetPath = $targetDir . $fileName;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Erro ao salvar arquivo");
        }

        // Registrar upload no banco
        $relativePath = 'assets/uploads/' . ($subdir ? $subdir . '/' : '') . $fileName;
        $fileId = $this->registerFile([
            'nome_original' => $file['name'],
            'nome_arquivo' => $fileName,
            'caminho' => $relativePath,
            'tipo' => $mimeType,
            'tamanho' => $file['size']
        ]);

        return [
            'id' => $fileId,
            'original_name' => $file['name'],
            'file_name' => $fileName,
            'path' => $relativePath,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }

    /**
     * Registra informações do arquivo no banco
     */
    private function registerFile($data) {
        $sql = "INSERT INTO arquivos (nome_original, nome_arquivo, caminho, tipo, tamanho, data_upload) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $this->db->execute($sql, [
            $data['nome_original'],
            $data['nome_arquivo'],
            $data['caminho'],
            $data['tipo'],
            $data['tamanho']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Retorna mensagem de erro de upload
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "O arquivo excede o tamanho máximo permitido pelo PHP";
            case UPLOAD_ERR_FORM_SIZE:
                return "O arquivo excede o tamanho máximo permitido pelo formulário";
            case UPLOAD_ERR_PARTIAL:
                return "O upload do arquivo foi feito parcialmente";
            case UPLOAD_ERR_NO_FILE:
                return "Nenhum arquivo foi enviado";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Pasta temporária ausente";
            case UPLOAD_ERR_CANT_WRITE:
                return "Falha ao gravar arquivo em disco";
            case UPLOAD_ERR_EXTENSION:
                return "Uma extensão PHP interrompeu o upload do arquivo";
            default:
                return "Erro desconhecido no upload";
        }
    }

    /**
     * Remove um arquivo
     */
    public function deleteFile($fileId) {
        $file = $this->db->select(
            "SELECT * FROM arquivos WHERE id = ?",
            [$fileId]
        );

        if (!empty($file)) {
            $file = $file[0];
            $fullPath = __DIR__ . '/../../public/' . $file['caminho'];
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return $this->db->execute(
                "DELETE FROM arquivos WHERE id = ?",
                [$fileId]
            );
        }

        return false;
    }
}
