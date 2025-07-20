
<?php
session_start();

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Carregar dependências
require_once 'Medoo.php';
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/services/FinancialService.php';
require_once __DIR__ . '/includes/config.php';  // Para ter acesso ao sistema de logs
require_once 'includes/log_sistema.php';

// Função para limpar dados recursivamente, removendo objetos e recursos
function clean_data_for_insert($data) {
    if (is_array($data)) {
        $clean = [];
        foreach ($data as $key => $value) {
            // Verificar se a chave também é válida
            if (is_string($key) || is_numeric($key)) {
                $clean_value = clean_data_for_insert($value);
                if ($clean_value !== null) {
                    $clean[$key] = $clean_value;
                }
            }
        }
        return $clean;
    } elseif (is_object($data) || is_resource($data)) {
        return null; // Remove objetos e recursos
    } elseif (is_scalar($data) || is_null($data)) {
        return $data; // Manter apenas dados escalares e null
    } else {
        return null; // Remove qualquer outro tipo
    }
}

// Função para garantir que apenas dados primitivos sejam enviados ao banco
function sanitize_for_database($data) {
    if (is_array($data)) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($key) || is_numeric($key)) {
                $clean_value = sanitize_for_database($value);
                if ($clean_value !== null) {
                    $sanitized[$key] = $clean_value;
                }
            }
        }
        return $sanitized;
    } elseif (is_string($data) || is_numeric($data) || is_bool($data) || is_null($data)) {
        return $data;
    } else {
        // Converter qualquer coisa não primitiva para string 
        return (string) $data;
    }
}

// Função para forçar conversão de dados para tipos MySQL seguros
function force_mysql_safe($data) {
    if (is_array($data)) {
        $safe = [];
        foreach ($data as $key => $value) {
            $safe_key = (string) $key;
            $safe_value = force_mysql_safe($value);
            if ($safe_value !== null) {
                $safe[$safe_key] = $safe_value;
            }
        }
        return $safe;
    } elseif (is_bool($data)) {
        return $data ? 1 : 0;
    } elseif (is_string($data) || is_int($data) || is_float($data)) {
        return $data;
    } elseif (is_null($data)) {
        return null;
    } else {
        // Converter tudo mais para string
        return (string) $data;
    }
}  // Para ter acesso à função log_sistema

// Configuração do banco
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$import_stats = [];

// Função para enviar progresso via SSE (Server-Sent Events)
function send_progress($message, $step = null, $total = null) {
    if (ob_get_level()) {
        ob_end_flush();
    }
    
    $data = [
        'message' => $message,
        'timestamp' => date('H:i:s'),
        'step' => $step,
        'total' => $total
    ];
    
    echo "data: " . json_encode($data) . "\n\n";
    
    if (ob_get_level()) {
        ob_start();
    }
    flush();
    
    // Log também para o sistema
    log_sistema($message, 'INFO');
}

// Se for requisição de progresso (SSE)
if (isset($_GET['progress']) && $_GET['progress'] === 'stream') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    
    send_progress('🔄 Iniciando sistema de progresso...');
    exit;
}

// Log do acesso à página
log_sistema('Página de restauração de backup acessada - User ID: ' . $user_id . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'INFO');

// Processar upload do arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    // Para processo interativo, definir headers apropriados
    if (isset($_POST['interactive']) && $_POST['interactive'] === '1') {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        send_progress('🚀 Iniciando processo de restauração interativo...');
    }
    
    log_sistema('Iniciando processo de restauração de backup - User ID: ' . $user_id . ' - Filename: ' . ($_FILES['backup_file']['name'] ?? 'unknown'), 'INFO');
    
    try {
        send_progress('📁 Verificando arquivo enviado...');
        
        // Verificar se o arquivo foi enviado corretamente
        if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo.');
        }

        // Verificar o tipo do arquivo
        $file_info = pathinfo($_FILES['backup_file']['name']);
        if (strtolower($file_info['extension']) !== 'json') {
            throw new Exception('Apenas arquivos JSON são aceitos.');
        }

        send_progress('📖 Lendo conteúdo do arquivo...');
        
        // Ler o conteúdo do arquivo
        $json_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        if ($json_content === false) {
            throw new Exception('Não foi possível ler o arquivo.');
        }

        send_progress('🔍 Decodificando JSON...');
        
        // Decodificar JSON
        $backup_data = json_decode($json_content, true);
        if ($backup_data === null) {
            throw new Exception('Arquivo JSON inválido.');
        }

        send_progress('✅ Validando estrutura do backup...');
        
        // Debug - registrar estrutura do backup
        log_sistema('DEBUG: Estrutura do backup encontrada - Keys: ' . implode(', ', array_keys($backup_data)), 'INFO');
        if (isset($backup_data['universities']) && is_array($backup_data['universities'])) {
            log_sistema('DEBUG: Universidades encontradas: ' . count($backup_data['universities']), 'INFO');
            send_progress('📊 Encontradas ' . count($backup_data['universities']) . ' universidades no backup');
            
            if (count($backup_data['universities']) > 0) {
                $first_uni = $backup_data['universities'][0];
                log_sistema('DEBUG: Primeira universidade - Keys: ' . implode(', ', array_keys($first_uni)), 'INFO');
                foreach ($first_uni as $key => $value) {
                    log_sistema('DEBUG: Uni[' . $key . '] = ' . gettype($value) . (is_object($value) ? ' (CLASS: ' . get_class($value) . ')' : ''), 'INFO');
                }
            }
        }

        // Verificar estrutura do backup
        if (!isset($backup_data['backup_info']) || !isset($backup_data['backup_info']['type']) || 
            $backup_data['backup_info']['type'] !== 'complete_user_data') {
            throw new Exception('Este não é um arquivo de backup de dados de usuário válido.');
        }

        log_sistema('Backup válido encontrado - User ID: ' . $user_id . ' - Version: ' . ($backup_data['backup_info']['version'] ?? 'unknown') . ' - Created: ' . ($backup_data['backup_info']['created_at'] ?? 'unknown'), 'INFO');

        send_progress('💾 Conectando com banco de dados...');
        
        // Iniciar transação (Medoo way)
        $pdo = $database->pdo;
        if (!$pdo) {
            throw new Exception('Não foi possível estabelecer conexão com o banco de dados.');
        }
        
        send_progress('🔒 Iniciando transação de banco...');
        log_sistema('DEBUG: Iniciando transação de banco de dados', 'INFO');
        $pdo->beginTransaction();
        log_sistema('DEBUG: Transação iniciada com sucesso', 'INFO');
        send_progress('✅ Transação iniciada com sucesso');

        $counters = [
            'universidades' => 0,
            'cursos' => 0,
            'disciplinas' => 0,
            'topicos' => 0,
            'unidades' => 0,
            'matriculas' => 0,
            'skipped' => 0
        ];

        // Mapear IDs antigos para novos
        $id_mapping = [
            'universidades' => [],
            'cursos' => [],
            'disciplinas' => [],
            'topicos' => []
        ];

        // Importar universidades
        if (isset($backup_data['universities']) && is_array($backup_data['universities'])) {
            $total_unis = count($backup_data['universities']);
            send_progress('🏛️ Importando ' . $total_unis . ' universidade(s)...', 0, $total_unis);
            
            log_sistema('DEBUG: Iniciando importação de universidades', 'INFO');
            foreach ($backup_data['universities'] as $index => $uni) {
                try {
                    send_progress('📝 Processando: ' . ($uni['nome'] ?? 'unknown'), $index + 1, $total_unis);
                    log_sistema('DEBUG: Processando universidade: ' . ($uni['nome'] ?? 'unknown'), 'INFO');
                    
                    // Limpar dados para evitar problemas de serialização
                    $uni_data = clean_data_for_insert($uni);
                    
                    // Debug - verificar se ainda há objetos
                    foreach ($uni_data as $key => $value) {
                        if (is_object($value) || is_resource($value)) {
                            log_sistema('OBJETO DETECTADO em universidades - Key: ' . $key . ' - Type: ' . gettype($value), 'ERROR');
                        }
                    }
                    
                    // Verificar se já existe
                    $existing = $database->get("universidades", "id", [
                        "nome" => $uni_data['nome'],
                        "usuario_id" => $user_id
                    ]);
                    
                    log_sistema('DEBUG: Verificação de universidade existente - Result: ' . ($existing ? 'EXISTS' : 'NEW'), 'INFO');

                    if (!$existing) {
                        $old_id = $uni_data['id'];
                        unset($uni_data['id']);
                        $uni_data['usuario_id'] = $user_id;
                        $uni_data['data_criacao'] = date('Y-m-d H:i:s');
                        $uni_data['data_atualizacao'] = date('Y-m-d H:i:s');

                        // Sanitização final antes da inserção
                        $uni_data = sanitize_for_database($uni_data);
                        $uni_data = force_mysql_safe($uni_data);

                        send_progress('💾 Inserindo universidade no banco...');
                        log_sistema('DEBUG: Tentando inserir universidade no banco', 'INFO');
                        log_sistema('DEBUG: Dados da universidade: ' . json_encode($uni_data), 'INFO');
                        
                        // Verificar estrutura da tabela antes da inserção
                        try {
                            $table_info = $database->query("DESCRIBE universidades")->fetchAll();
                            log_sistema('DEBUG: Estrutura da tabela universidades: ' . json_encode($table_info), 'INFO');
                        } catch (Exception $desc_error) {
                            log_sistema('ERROR: Não foi possível descrever tabela universidades: ' . $desc_error->getMessage(), 'ERROR');
                        }
                        
                        // Verificar se a conexão com o banco está ativa
                        try {
                            $test_query = $database->query("SELECT 1")->fetchAll();
                            log_sistema('DEBUG: Conexão com banco testada: ' . (count($test_query) > 0 ? 'OK' : 'FALHA'), 'INFO');
                        } catch (Exception $conn_error) {
                            log_sistema('ERROR: Falha na conexão com banco: ' . $conn_error->getMessage(), 'ERROR');
                        }
                        
                        // Debug dos dados antes da inserção
                        log_sistema('DEBUG: Dados finais para inserção: ' . json_encode($uni_data), 'INFO');
                        log_sistema('DEBUG: Verificando tipos dos dados...', 'INFO');
                        foreach ($uni_data as $key => $value) {
                            log_sistema("DEBUG: Campo {$key}: " . gettype($value) . " = " . var_export($value, true), 'INFO');
                        }
                        
                        try {
                            // Tentar inserção com captura detalhada de erro
                            log_sistema('DEBUG: Executando insert...', 'INFO');
                            log_sistema('DEBUG: Comando: $database->insert("universidades", $uni_data)', 'INFO');
                            
                            $new_id = $database->insert("universidades", $uni_data);
                            
                            log_sistema('DEBUG: Insert executado - Result: ' . var_export($new_id, true), 'INFO');
                            log_sistema('DEBUG: Tipo do resultado: ' . gettype($new_id), 'INFO');
                            
                            // Obter informações de erro do Medoo (método correto)
                            $pdo_info = $database->info();
                            $last_query = isset($database->last) ? $database->last : 'N/A';
                            log_sistema('DEBUG: PDO info após insert: ' . json_encode($pdo_info), 'INFO');
                            log_sistema('DEBUG: Last query: ' . $last_query, 'INFO');
                            
                            // O Medoo retorna um PDOStatement em caso de sucesso, não um ID
                            // Precisamos verificar se é um objeto PDOStatement
                            if (!($new_id instanceof PDOStatement)) {
                                log_sistema('ERROR: Insert não retornou PDOStatement - Result: ' . var_export($new_id, true), 'ERROR');
                                log_sistema('ERROR: PDO Info: ' . json_encode($pdo_info), 'ERROR');
                                send_progress('❌ Falha na inserção: Resultado inesperado');
                                throw new Exception('Falha na inserção da universidade: Resultado inesperado');
                            }
                            
                            // Obter o ID da inserção usando o método correto do Medoo
                            $actual_new_id = $database->id();
                            log_sistema('DEBUG: ID obtido via database->id(): ' . var_export($actual_new_id, true), 'INFO');
                            
                            if (!$actual_new_id || $actual_new_id <= 0) {
                                log_sistema('ERROR: ID inválido retornado: ' . var_export($actual_new_id, true), 'ERROR');
                                send_progress('❌ Falha ao obter ID da universidade inserida');
                                throw new Exception('Falha ao obter ID da universidade inserida');
                            }
                            
                            $new_id = $actual_new_id;
                            
                            log_sistema('DEBUG: Universidade inserida com ID: ' . $new_id, 'INFO');
                            send_progress('✅ Universidade inserida com ID: ' . $new_id);
                            
                        } catch (PDOException $pdo_error) {
                            log_sistema('ERROR: PDO Exception na inserção: ' . $pdo_error->getMessage(), 'ERROR');
                            log_sistema('ERROR: PDO Error Code: ' . $pdo_error->getCode(), 'ERROR');
                            $error_info = $pdo_error->errorInfo ?? [];
                            log_sistema('ERROR: PDO Error Info: ' . json_encode($error_info), 'ERROR');
                            send_progress('❌ Erro PDO: ' . $pdo_error->getMessage());
                            throw $pdo_error;
                        } catch (Exception $insert_error) {
                            log_sistema('ERROR: Exceção na inserção da universidade: ' . $insert_error->getMessage(), 'ERROR');
                            log_sistema('ERROR: Error Code: ' . $insert_error->getCode(), 'ERROR');
                            log_sistema('ERROR: Trace: ' . $insert_error->getTraceAsString(), 'ERROR');
                            send_progress('❌ Erro na inserção: ' . $insert_error->getMessage());
                            throw $insert_error;
                        } catch (Error $fatal_error) {
                            log_sistema('ERROR: Fatal Error na inserção: ' . $fatal_error->getMessage(), 'ERROR');
                            log_sistema('ERROR: Fatal Error File: ' . $fatal_error->getFile() . ':' . $fatal_error->getLine(), 'ERROR');
                            log_sistema('ERROR: Fatal Error Trace: ' . $fatal_error->getTraceAsString(), 'ERROR');
                            send_progress('❌ Erro fatal: ' . $fatal_error->getMessage());
                            
                            // Para erros fatais, não relançar imediatamente, tentar continuar
                            log_sistema('ERROR: Tentando continuar após erro fatal...', 'ERROR');
                            send_progress('⚠️ Erro detectado, tentando continuar...');
                            $counters['skipped']++;
                            continue; // Pula esta universidade e continua com as outras
                        }
                        
                        $id_mapping['universidades'][$old_id] = $new_id;
                        $counters['universidades']++;
                    } else {
                        send_progress('⚠️ Universidade já existe, pulando...');
                        $id_mapping['universidades'][$uni_data['id']] = $existing;
                        $counters['skipped']++;
                    }
                } catch (Exception $uni_error) {
                    // Log do erro específico da universidade, mas continue
                    log_sistema('Erro ao importar universidade - User ID: ' . $user_id . ' - Error: ' . $uni_error->getMessage() . ' - University: ' . ($uni_data['nome'] ?? 'unknown'), 'ERROR');
                    $counters['skipped']++;
                }
            }
        }

        // Importar cursos
        if (isset($backup_data['courses']) && is_array($backup_data['courses'])) {
            foreach ($backup_data['courses'] as $curso) {
                // Limpar dados
                $curso = clean_data_for_insert($curso);
                
                // Debug - verificar se ainda há objetos
                foreach ($curso as $key => $value) {
                    if (is_object($value) || is_resource($value)) {
                        log_sistema('OBJETO DETECTADO em cursos - Key: ' . $key . ' - Type: ' . gettype($value), 'ERROR');
                    }
                }
                
                // Verificar se já existe
                $existing = $database->get("cursos", "id", [
                    "nome" => $curso['nome'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $curso['id'];
                    $old_uni_id = $curso['universidade_id'];
                    
                    unset($curso['id']);
                    $curso['usuario_id'] = $user_id;
                    $curso['universidade_id'] = $id_mapping['universidades'][$old_uni_id] ?? $old_uni_id;
                    $curso['data_criacao'] = date('Y-m-d H:i:s');
                    $curso['data_atualizacao'] = date('Y-m-d H:i:s');

                    // Sanitização final antes da inserção
                    $curso = sanitize_for_database($curso);
                    $curso = force_mysql_safe($curso);

                    $new_id = $database->insert("cursos", $curso);
                    $id_mapping['cursos'][$old_id] = $new_id;
                    $counters['cursos']++;
                } else {
                    $id_mapping['cursos'][$curso['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar disciplinas
        if (isset($backup_data['subjects']) && is_array($backup_data['subjects'])) {
            foreach ($backup_data['subjects'] as $disc) {
                // Limpar dados
                $disc = clean_data_for_insert($disc);
                
                // Verificar se já existe
                $existing = $database->get("disciplinas", "id", [
                    "nome" => $disc['nome'],
                    "curso_id" => $id_mapping['cursos'][$disc['curso_id']] ?? $disc['curso_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $disc['id'];
                    $old_curso_id = $disc['curso_id'];
                    
                    unset($disc['id']);
                    $disc['usuario_id'] = $user_id;
                    $disc['curso_id'] = $id_mapping['cursos'][$old_curso_id] ?? $old_curso_id;
                    $disc['data_criacao'] = date('Y-m-d H:i:s');
                    $disc['data_atualizacao'] = date('Y-m-d H:i:s');

                    // Sanitização final antes da inserção
                    $disc = sanitize_for_database($disc);
                    $disc = force_mysql_safe($disc);

                    $new_id = $database->insert("disciplinas", $disc);
                    $id_mapping['disciplinas'][$old_id] = $new_id;
                    $counters['disciplinas']++;
                } else {
                    $id_mapping['disciplinas'][$disc['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar tópicos
        if (isset($backup_data['topics']) && is_array($backup_data['topics'])) {
            foreach ($backup_data['topics'] as $topico) {
                // Limpar dados
                $topico = clean_data_for_insert($topico);
                
                // Verificar se já existe
                $existing = $database->get("topicos", "id", [
                    "nome" => $topico['nome'],
                    "disciplina_id" => $id_mapping['disciplinas'][$topico['disciplina_id']] ?? $topico['disciplina_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_id = $topico['id'];
                    $old_disc_id = $topico['disciplina_id'];
                    
                    unset($topico['id']);
                    $topico['usuario_id'] = $user_id;
                    $topico['disciplina_id'] = $id_mapping['disciplinas'][$old_disc_id] ?? $old_disc_id;
                    $topico['data_criacao'] = date('Y-m-d H:i:s');
                    $topico['data_atualizacao'] = date('Y-m-d H:i:s');

                    // Sanitização final antes da inserção
                    $topico = sanitize_for_database($topico);
                    $topico = force_mysql_safe($topico);

                    $new_id = $database->insert("topicos", $topico);
                    $id_mapping['topicos'][$old_id] = $new_id;
                    $counters['topicos']++;
                } else {
                    $id_mapping['topicos'][$topico['id']] = $existing;
                    $counters['skipped']++;
                }
            }
        }

        // Importar unidades de aprendizagem
        if (isset($backup_data['learning_units']) && is_array($backup_data['learning_units'])) {
            foreach ($backup_data['learning_units'] as $unidade) {
                // Limpar dados
                $unidade = clean_data_for_insert($unidade);
                
                // Verificar se já existe
                $existing = $database->get("unidades_aprendizagem", "id", [
                    "nome" => $unidade['nome'],
                    "topico_id" => $id_mapping['topicos'][$unidade['topico_id']] ?? $unidade['topico_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_topico_id = $unidade['topico_id'];
                    
                    unset($unidade['id']);
                    $unidade['usuario_id'] = $user_id;
                    $unidade['topico_id'] = $id_mapping['topicos'][$old_topico_id] ?? $old_topico_id;
                    $unidade['data_criacao'] = date('Y-m-d H:i:s');
                    $unidade['data_atualizacao'] = date('Y-m-d H:i:s');

                    // Sanitização final antes da inserção
                    $unidade = sanitize_for_database($unidade);
                    $unidade = force_mysql_safe($unidade);

                    $database->insert("unidades_aprendizagem", $unidade);
                    $counters['unidades']++;
                } else {
                    $counters['skipped']++;
                }
            }
        }

        // Importar matrículas
        if (isset($backup_data['enrollments']) && is_array($backup_data['enrollments'])) {
            foreach ($backup_data['enrollments'] as $matricula) {
                // Limpar dados
                $matricula = clean_data_for_insert($matricula);
                
                // Verificar se já existe
                $existing = $database->get("matriculas", "id", [
                    "curso_id" => $id_mapping['cursos'][$matricula['curso_id']] ?? $matricula['curso_id'],
                    "usuario_id" => $user_id
                ]);

                if (!$existing) {
                    $old_curso_id = $matricula['curso_id'];
                    
                    unset($matricula['id']);
                    $matricula['usuario_id'] = $user_id;
                    $matricula['curso_id'] = $id_mapping['cursos'][$old_curso_id] ?? $old_curso_id;
                    $matricula['data_criacao'] = date('Y-m-d H:i:s');
                    $matricula['data_atualizacao'] = date('Y-m-d H:i:s');

                    // Sanitização final antes da inserção
                    $matricula = sanitize_for_database($matricula);
                    $matricula = force_mysql_safe($matricula);

                    $database->insert("matriculas", $matricula);
                    $counters['matriculas']++;
                } else {
                    $counters['skipped']++;
                }
            }
        }

        log_sistema('DEBUG: Finalizando importação de dados - Counters: ' . json_encode($counters), 'INFO');

        // Initialize financial subscription for restored user ANTES do commit
        log_sistema('DEBUG: Iniciando configuração do sistema financeiro...', 'INFO');
        try {
            $financialService = new FinancialService($database);
            log_sistema('DEBUG: FinancialService instanciado com sucesso', 'INFO');
            
            // Check if user already has a subscription
            $existingSubscription = $financialService->getUserSubscription($user_id);
            log_sistema('DEBUG: Verificação de subscription existente - Result: ' . ($existingSubscription ? 'EXISTS' : 'NEW'), 'INFO');
            
            if (!$existingSubscription) {
                log_sistema('DEBUG: Criando nova subscription financeira...', 'INFO');
                $result = $financialService->initializeUserSubscription($user_id);
                log_sistema('DEBUG: Resultado da criação: ' . json_encode($result), 'INFO');
                
                if ($result['success']) {
                    $counters['financial_subscription'] = 1;
                    log_sistema('Financial subscription initialized for restored user - User ID: ' . $user_id, 'SUCCESS');
                } else {
                    log_sistema('Failed to initialize financial subscription for restored user - User ID: ' . $user_id . ' - Error: ' . ($result['error'] ?? 'Unknown error'), 'WARNING');
                }
            } else {
                $counters['financial_subscription'] = 0; // Already exists
                log_sistema('User already has financial subscription - User ID: ' . $user_id, 'INFO');
            }
            log_sistema('DEBUG: Sistema financeiro configurado com sucesso', 'INFO');
        } catch (Exception $e) {
            log_sistema('ERROR: Falha no sistema financeiro - Error: ' . $e->getMessage(), 'ERROR');
            log_sistema('ERROR: Financial Service File: ' . $e->getFile() . ':' . $e->getLine(), 'ERROR');
            log_sistema('ERROR: Financial Service Trace: ' . $e->getTraceAsString(), 'ERROR');
            // Don't fail the restore process for financial subscription errors
            log_sistema('WARNING: Continuando restauração sem sistema financeiro...', 'WARNING');
        }

        // Commit da transação APÓS configurar sistema financeiro
        log_sistema('DEBUG: Fazendo commit da transação', 'INFO');
        $pdo->commit();
        log_sistema('DEBUG: Commit realizado com sucesso', 'INFO');

        $import_stats = $counters;
        
        log_sistema('Backup restaurado com sucesso - User ID: ' . $user_id . ' - Import stats: ' . json_encode($import_stats), 'SUCCESS');
        
        $success_message = "Backup restaurado com sucesso!";

    } catch (Exception $e) {
        // Rollback em caso de erro
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        log_sistema('Erro ao restaurar backup - User ID: ' . $user_id . ' - Error: ' . $e->getMessage() . ' - File: ' . $e->getFile() . ' - Line: ' . $e->getLine(), 'ERROR');
        
        $error_message = "Erro ao restaurar backup: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Dados do Usuário - CapivaraLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .upload-area {
            border: 2px dashed #28a745;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #20c997;
            background: linear-gradient(135deg, #f0fff0 0%, #d4edda 100%);
        }
        .upload-area.dragover {
            border-color: #007bff;
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        }
        .stats-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            border-radius: 10px;
            padding: 1rem;
            color: white;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card card-custom">
                    <div class="card-body text-center bg-gradient-success text-white">
                        <h1 class="display-6 mb-3">
                            <i class="fas fa-upload me-3"></i>
                            Restaurar Dados do Usuário
                        </h1>
                        <p class="lead mb-0">Importar backup completo dos seus dados acadêmicos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Restaurar Dados</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($import_stats)): ?>
        <!-- Estatísticas de Importação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados da Restauração</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['universidades']; ?></h4>
                                    <small>Universidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['cursos']; ?></h4>
                                    <small>Cursos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['disciplinas']; ?></h4>
                                    <small>Disciplinas</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['topicos']; ?></h4>
                                    <small>Tópicos</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['unidades']; ?></h4>
                                    <small>Unidades</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="stats-success text-center">
                                    <h4><?php echo $import_stats['matriculas']; ?></h4>
                                    <small>Matrículas</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Financial System Status -->
                        <?php if (isset($import_stats['financial_subscription'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-<?php echo $import_stats['financial_subscription'] ? 'success' : 'info'; ?>">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    <strong>Sistema Financeiro:</strong>
                                    <?php if ($import_stats['financial_subscription']): ?>
                                        Subscription criada com período de graça de 365 dias
                                    <?php else: ?>
                                        Subscription já existia (mantida)
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($import_stats['skipped'] > 0): ?>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo $import_stats['skipped']; ?> itens foram ignorados por já existirem.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulário de Upload -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Selecionar Arquivo de Backup</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-success mb-3"></i>
                                <h4 class="mb-3">Arraste o arquivo aqui ou clique para selecionar</h4>
                                <input type="file" name="backup_file" id="backup_file" accept=".json" class="d-none" required>
                                <input type="hidden" name="interactive" value="1">
                                <p class="text-muted mb-3">Apenas arquivos JSON de backup são aceitos</p>
                                <button type="button" class="btn btn-outline-success" onclick="document.getElementById('backup_file').click()">
                                    <i class="fas fa-folder-open me-2"></i>Escolher Arquivo
                                </button>
                            </div>
                            
                            <div id="fileInfo" class="mt-4 d-none">
                                <div class="alert alert-info">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Arquivo selecionado: <span id="fileName"></span>
                                </div>
                            </div>

                            <!-- Progress Area -->
                            <div id="progressArea" class="mt-4 d-none">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-cog fa-spin me-2"></i>
                                            Progresso da Restauração
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress mb-3">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 id="progressBar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div id="progressMessages" style="max-height: 300px; overflow-y: auto;">
                                            <!-- Messages will be added here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn" disabled>
                                    <i class="fas fa-upload me-2"></i>Restaurar Backup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avisos Importantes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Avisos Importantes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger">⚠️ Atenção</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Itens duplicados serão ignorados</small></li>
                                    <li><small>• Processo pode demorar alguns segundos</small></li>
                                    <li><small>• Mantenha conexão estável durante importação</small></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">ℹ️ Compatibilidade</h6>
                                <ul class="list-unstyled">
                                    <li><small>• Apenas backups do CapivaraLearn v1.1+</small></li>
                                    <li><small>• Arquivos devem estar em formato JSON</small></li>
                                    <li><small>• Estrutura será validada automaticamente</small></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('backup_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressArea = document.getElementById('progressArea');
        const progressBar = document.getElementById('progressBar');
        const progressMessages = document.getElementById('progressMessages');

        // Progress management
        let currentStep = 0;
        let totalSteps = 100;

        function addProgressMessage(message, timestamp, isError = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert ${isError ? 'alert-danger' : 'alert-light'} py-2 mb-2`;
            messageDiv.innerHTML = `
                <small class="text-muted">[${timestamp}]</small> 
                <span>${message}</span>
            `;
            progressMessages.appendChild(messageDiv);
            progressMessages.scrollTop = progressMessages.scrollHeight;
        }

        function updateProgress(step, total) {
            if (total && total > 0) {
                totalSteps = total;
                currentStep = step;
                const percentage = Math.round((step / total) * 100);
                progressBar.style.width = percentage + '%';
                progressBar.textContent = percentage + '%';
            }
        }

        // Form submission with progress
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!fileInput.files[0]) {
                alert('Por favor, selecione um arquivo primeiro.');
                return;
            }

            // Show progress area
            progressArea.classList.remove('d-none');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
            
            // Create FormData and submit
            const formData = new FormData(uploadForm);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                function readProgress() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            addProgressMessage('✅ Processo finalizado!', new Date().toLocaleTimeString());
                            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Concluído';
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                            return;
                        }
                        
                        const text = decoder.decode(value);
                        const lines = text.split('\n');
                        
                        lines.forEach(line => {
                            if (line.startsWith('data: ')) {
                                try {
                                    const data = JSON.parse(line.substring(6));
                                    
                                    // Detectar se é uma mensagem de erro
                                    const isError = data.message.includes('❌') || 
                                                   data.message.includes('Erro') || 
                                                   data.message.includes('ERROR') ||
                                                   data.message.includes('Fatal');
                                    
                                    addProgressMessage(data.message, data.timestamp, isError);
                                    
                                    if (data.step && data.total) {
                                        updateProgress(data.step, data.total);
                                    }
                                } catch (e) {
                                    console.log('Error parsing progress data:', e);
                                }
                            }
                        });
                        
                        return readProgress();
                    });
                }
                
                return readProgress();
            })
            .catch(error => {
                console.error('Error:', error);
                addProgressMessage('❌ Erro: ' + error.message, new Date().toLocaleTimeString(), true);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Restaurar Backup';
            });
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileInfo(e.target.files[0]);
            }
        });

        function updateFileInfo(file) {
            if (file.type === 'application/json' || file.name.endsWith('.json')) {
                fileName.textContent = file.name;
                fileInfo.classList.remove('d-none');
                submitBtn.disabled = false;
            } else {
                alert('Por favor, selecione apenas arquivos JSON.');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                submitBtn.disabled = true;
            }
        }
    </script>
</body>
</html>
