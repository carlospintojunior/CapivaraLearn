<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Monolog\Logger;

require __DIR__ . '/../vendor/autoload.php';

// Configuração da sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Carregar configurações
$settings = require __DIR__ . '/../config/settings.php';

// Criar container DI
$container = new Container();

// Registrar configurações no container
$container->set('settings', $settings['settings']);

// Registrar dependências
$dependencies = require __DIR__ . '/../config/dependencies.php';
foreach ($dependencies as $key => $value) {
    $container->set($key, $value);
}

// Criar aplicação Slim
AppFactory::setContainer($container);
$app = AppFactory::create();

// Middleware de tratamento de erros
$app->addErrorMiddleware(
    $container->get('settings')['displayErrorDetails'],
    true,
    true
);

// Middleware para parsing do corpo da requisição
$app->addBodyParsingMiddleware();

// Middleware personalizado para logging
$app->add(function (Request $request, $handler) use ($container) {
    $logger = $container->get('logger');
    
    $logger->info('Request', [
        'method' => $request->getMethod(),
        'uri' => (string) $request->getUri(),
        'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $request->getHeaderLine('User-Agent')
    ]);
    
    $response = $handler->handle($request);
    
    $logger->info('Response', [
        'status' => $response->getStatusCode(),
        'uri' => (string) $request->getUri()
    ]);
    
    return $response;
});

// Middleware para autenticação
$authMiddleware = function (Request $request, $handler) use ($container) {
    $logger = $container->get('logger');
    $authService = $container->get('AuthService');
    
    if (!$authService->isAuthenticated()) {
        $logger->warning('Unauthorized access attempt', [
            'uri' => (string) $request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
    
    return $handler->handle($request);
};

// Rota principal - redireciona para login ou dashboard
$app->get('/', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $authService = $container->get('AuthService');
    
    $logger->info('Root access');
    
    if ($authService->isAuthenticated()) {
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    } else {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
});

// Rota de login (GET)
$app->get('/login', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $authService = $container->get('AuthService');
    
    $logger->info('Login page accessed');
    
    if ($authService->isAuthenticated()) {
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
    
    // Carregar template de login
    $loginHtml = file_get_contents(__DIR__ . '/../templates/login.html');
    $response->getBody()->write($loginHtml);
    return $response->withHeader('Content-Type', 'text/html');
});

// Rota de login (POST)
$app->post('/login', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $authService = $container->get('AuthService');
    
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    try {
        $user = $authService->authenticate($email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } else {
            $response->getBody()->write(json_encode(['error' => 'Credenciais inválidas']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    } catch (Exception $e) {
        $logger->error('Login error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $response->getBody()->write(json_encode(['error' => 'Erro interno do servidor']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Rota de logout
$app->get('/logout', function (Request $request, Response $response) use ($container) {
    $authService = $container->get('AuthService');
    
    $authService->logout();
    
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// Rota do dashboard (protegida)
$app->get('/dashboard', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $authService = $container->get('AuthService');
    $universityService = $container->get('UniversityService');
    
    $currentUser = $authService->getCurrentUser();
    $logger->info('Dashboard accessed', ['user_id' => $currentUser['id']]);
    
    try {
        $db = $container->get('db');
        
        // Buscar estatísticas
        $stats = [];
        
        // Total de universidades
        $stmt = $db->query("SELECT COUNT(*) as total FROM universidades WHERE ativo = 1");
        $stats['universidades'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de cursos
        $stmt = $db->query("SELECT COUNT(*) as total FROM cursos WHERE ativo = 1");
        $stats['cursos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de módulos
        $stmt = $db->query("SELECT COUNT(*) as total FROM modulos WHERE ativo = 1");
        $stats['modulos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de tópicos
        $stmt = $db->query("SELECT COUNT(*) as total FROM topicos WHERE ativo = 1");
        $stats['topicos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de inscrições
        $stmt = $db->query("SELECT COUNT(*) as total FROM inscricoes WHERE ativo = 1");
        $stats['inscricoes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Carregar template do dashboard
        $dashboardHtml = file_get_contents(__DIR__ . '/../templates/dashboard.html');
        
        // Substituir variáveis no template
        $dashboardHtml = str_replace('{{user_name}}', $currentUser['name'], $dashboardHtml);
        $dashboardHtml = str_replace('{{total_universidades}}', $stats['universidades'], $dashboardHtml);
        $dashboardHtml = str_replace('{{total_cursos}}', $stats['cursos'], $dashboardHtml);
        $dashboardHtml = str_replace('{{total_modulos}}', $stats['modulos'], $dashboardHtml);
        $dashboardHtml = str_replace('{{total_topicos}}', $stats['topicos'], $dashboardHtml);
        $dashboardHtml = str_replace('{{total_inscricoes}}', $stats['inscricoes'], $dashboardHtml);
        
        $response->getBody()->write($dashboardHtml);
        return $response->withHeader('Content-Type', 'text/html');
        
    } catch (Exception $e) {
        $logger->error('Dashboard error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $currentUser['id']
        ]);
        
        $response->getBody()->write('Erro interno do servidor');
        return $response->withStatus(500);
    }
})->add($authMiddleware);

// Rotas para CRUDs
$app->group('/crud', function ($group) use ($container) {
    
    // Universidades
    $group->get('/universities', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Universities CRUD accessed', ['user_id' => $currentUser['id']]);
        
        $html = file_get_contents(__DIR__ . '/../templates/crud/universities.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Cursos
    $group->get('/courses', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Courses CRUD accessed', ['user_id' => $currentUser['id']]);
        
        $html = file_get_contents(__DIR__ . '/../templates/crud/courses.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Módulos
    $group->get('/modules', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Modules CRUD accessed', ['user_id' => $currentUser['id']]);
        
        $html = file_get_contents(__DIR__ . '/../templates/crud/modules.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Tópicos
    $group->get('/topics', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Topics CRUD accessed', ['user_id' => $currentUser['id']]);
        
        $html = file_get_contents(__DIR__ . '/../templates/crud/topics.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Inscrições
    $group->get('/enrollments', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Enrollments CRUD accessed', ['user_id' => $currentUser['id']]);
        
        $html = file_get_contents(__DIR__ . '/../templates/crud/enrollments.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
})->add($authMiddleware);

// APIs para universidades
$app->group('/api/universities', function ($group) use ($container) {
    
    // Listar universidades
    $group->get('', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        $universityService = $container->get('UniversityService');
        
        $currentUser = $authService->getCurrentUser();
        $logger->info('Universities API accessed', ['user_id' => $currentUser['id']]);
        
        try {
            $universities = $universityService->getAll();
            
            $response->getBody()->write(json_encode($universities));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $logger->error('Universities API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $response->getBody()->write(json_encode(['error' => 'Erro interno do servidor']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
    
    // Criar universidade
    $group->post('', function (Request $request, Response $response) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        $universityService = $container->get('UniversityService');
        
        $currentUser = $authService->getCurrentUser();
        $data = $request->getParsedBody();
        
        try {
            $university = $universityService->create($data);
            
            // Registrar atividade
            $authService->logActivity($currentUser['id'], 'university_create', 'Universidade criada: ' . $university['nome']);
            
            $response->getBody()->write(json_encode($university));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (Exception $e) {
            $logger->error('University creation error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    });
    
    // Atualizar universidade
    $group->put('/{id}', function (Request $request, Response $response, $args) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        $universityService = $container->get('UniversityService');
        
        $currentUser = $authService->getCurrentUser();
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        try {
            $university = $universityService->update($id, $data);
            
            // Registrar atividade
            $authService->logActivity($currentUser['id'], 'university_update', 'Universidade atualizada: ' . $university['nome']);
            
            $response->getBody()->write(json_encode($university));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $logger->error('University update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    });
    
    // Excluir universidade
    $group->delete('/{id}', function (Request $request, Response $response, $args) use ($container) {
        $logger = $container->get('logger');
        $authService = $container->get('AuthService');
        $universityService = $container->get('UniversityService');
        
        $currentUser = $authService->getCurrentUser();
        $id = (int) $args['id'];
        
        try {
            $university = $universityService->getById($id);
            $universityService->deactivate($id);
            
            // Registrar atividade
            $authService->logActivity($currentUser['id'], 'university_delete', 'Universidade desativada: ' . $university['nome']);
            
            $response->getBody()->write(json_encode(['message' => 'Universidade desativada com sucesso']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $logger->error('University deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    });
    
})->add($authMiddleware);

// Executar aplicação
$app->run();
