<?php
// Arquivo para forçar um erro e testar o log
echo "Testando erro...<br>";
// Vamos forçar um erro fatal
$resultado = funcao_que_nao_existe();
echo "Este texto não deve aparecer";
?>
