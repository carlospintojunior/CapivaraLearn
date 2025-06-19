<?php
/**
 * Sistema de Logs CapivaraLearn
 * Sistema robusto para geração de logs com diferentes níveis
 */

class Logger {
    private static $instance = null;
    private $logFile;
    private $logDir;
    
    private function __construct() {
        $this->logDir = __DIR__ . '/../logs';
        $this->logFile = $this->logDir . '/capivaralearn.log';
        $this->ensureLogDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureLogDirectory() {
        // Criar diretório se não existir
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
            chmod($this->logDir, 0777);
        }
        
        // Criar arquivo se não existir
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
        }
    }
    
    private function writeLog($level, $message, $context = []) {
        $this->ensureLogDirectory();
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'ip' => $ip,
            'message' => $message
        ];
        
        if (!empty($context)) {
            $logEntry['context'] = $context;
        }
        
        // Formato legível
        $logLine = sprintf(
            "[%s] %s | IP: %s | %s",
            $timestamp,
            strtoupper($level),
            $ip,
            $message
        );
        
        if (!empty($context)) {
            $logLine .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logLine .= PHP_EOL;
        
        // Escrever no arquivo
        $result = file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Fallback: tentar escrever diretamente no error_log do PHP
        if ($result === false) {
            error_log("Logger FALHOU - $level: $message");
        }
        
        return $result !== false;
    }
    
    public function debug($message, $context = []) {
        return $this->writeLog('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        return $this->writeLog('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        return $this->writeLog('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        return $this->writeLog('ERROR', $message, $context);
    }
    
    public function critical($message, $context = []) {
        return $this->writeLog('CRITICAL', $message, $context);
    }
    
    public function emailError($email, $error, $config = []) {
        return $this->error("FALHA ENVIO EMAIL", [
            'destinatario' => $email,
            'erro' => $error,
            'config_smtp' => $config,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function emailSuccess($email, $subject) {
        return $this->info("EMAIL ENVIADO", [
            'destinatario' => $email,
            'assunto' => $subject,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getLogFile() {
        return $this->logFile;
    }
    
    public function getLastLines($lines = 50) {
        if (!file_exists($this->logFile)) {
            return "Arquivo de log não existe ainda.";
        }
        
        $content = file_get_contents($this->logFile);
        $allLines = explode(PHP_EOL, $content);
        $lastLines = array_slice($allLines, -$lines);
        
        return implode(PHP_EOL, $lastLines);
    }
    
    public function clearLog() {
        if (file_exists($this->logFile)) {
            return file_put_contents($this->logFile, '') !== false;
        }
        return true;
    }
    
    // Método de teste para verificar se o sistema está funcionando
    public function test() {
        $testMessage = "TESTE DE LOG - " . date('Y-m-d H:i:s');
        $result = $this->info($testMessage, ['teste' => true]);
        
        if ($result) {
            return "✅ Sistema de logs funcionando! Arquivo: " . $this->logFile;
        } else {
            return "❌ Falha no sistema de logs!";
        }
    }
}

// Função helper global para facilitar o uso
function logger() {
    return Logger::getInstance();
}

// Funções helper globais
function logInfo($message, $context = []) {
    return Logger::getInstance()->info($message, $context);
}

function logDebug($message, $context = []) {
    return Logger::getInstance()->debug($message, $context);
}

function logWarning($message, $context = []) {
    return Logger::getInstance()->warning($message, $context);
}
?>
