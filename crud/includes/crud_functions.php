<?php
/**
 * Funções auxiliares para os CRUDs
 */

/**
 * Sanitiza dados de entrada
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Valida se um campo obrigatório não está vazio
 */
function validate_required($value, $field_name) {
    if (empty(trim($value))) {
        throw new Exception("O campo '{$field_name}' é obrigatório.");
    }
}

/**
 * Exibe mensagem de sucesso
 */
function show_success_message($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Exibe mensagem de erro
 */
function show_error_message($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Gera token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redireciona com mensagem
 */
function redirect_with_message($url, $type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
}

/**
 * Exibe mensagem flash
 */
function show_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        if ($flash['type'] === 'success') {
            return show_success_message($flash['message']);
        } else {
            return show_error_message($flash['message']);
        }
    }
    return '';
}

/**
 * Formata data para exibição
 */
function format_date($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Trunca texto
 */
function truncate_text($text, $length = 50) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
