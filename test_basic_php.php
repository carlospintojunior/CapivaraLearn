<?php
// Teste básico de PHP
echo "PHP está funcionando!<br>";
echo "Versão: " . PHP_VERSION . "<br>";
echo "Data: " . date('Y-m-d H:i:s') . "<br>";

// Testar se o autoload funciona
echo "Testando autoload...<br>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php existe<br>";
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoload carregado com sucesso<br>";
    } catch (Exception $e) {
        echo "❌ Erro no autoload: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php NÃO EXISTE<br>";
}

// Testar se conseguimos incluir arquivos
echo "Testando includes...<br>";
if (file_exists('includes/config.php')) {
    echo "✅ includes/config.php existe<br>";
} else {
    echo "❌ includes/config.php NÃO EXISTE<br>";
}

phpinfo();
?>
