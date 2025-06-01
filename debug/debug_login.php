<?php
/**
 * CapivaraLearn - Debug Login
 * Este arquivo captura erros mais detalhados durante o processo de login/registro
 */

// Função para formatar exceções de forma amigável
function formatError(Exception $e) {
    $error = array(
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    );
    
    // Tentar identificar o tipo de erro
    if (strpos($error['message'], 'SQLSTATE') !== false) {
        if (strpos($error['message'], '23000') !== false) {
            return "⚠️ Erro de Banco de Dados: Email já cadastrado ou violação de chave única.";
        }
        if (strpos($error['message'], '42S02') !== false) {
            return "⚠️ Erro de Banco de Dados: Tabela não encontrada. Execute o install.php primeiro.";
        }
        return "⚠️ Erro de Banco de Dados: " . $error['message'];
    }
    
    if (strpos($error['message'], 'connect') !== false) {
        return "⚠️ Erro de Conexão: Não foi possível conectar ao banco de dados. Verifique as configurações.";
    }
    
    if (strpos($error['file'], 'MailService') !== false) {
        return "⚠️ Erro no Serviço de Email: Sistema criou sua conta mas houve um problema ao enviar o email de confirmação. Entre em contato com o suporte.";
    }
    
    // Se não conseguir identificar o tipo específico, retorna uma mensagem genérica com mais detalhes
    return "⚠️ Erro Interno: {$error['message']} (em {$error['file']}:{$error['line']})";
}

// Função para registrar o erro no log
function logLoginError(Exception $e, $context = array()) {
    $timestamp = date('Y-m-d H:i:s');
    $errorLog = "[{$timestamp}] " . formatError($e) . "\n";
    $errorLog .= "Context: " . json_encode($context) . "\n";
    $errorLog .= "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    
    error_log($errorLog, 3, __DIR__ . '/logs/login_errors.log');
    
    return formatError($e);
}