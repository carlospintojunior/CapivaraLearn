<?php
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

return [
    'logger' => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $loggerSettings = $settings['logger'];
        
        $logger = new Logger($loggerSettings['name']);
        
        // Handler para logs rotativos
        $rotatingHandler = new RotatingFileHandler(
            $loggerSettings['path'],
            0,
            $loggerSettings['level']
        );
        
        $formatter = new LineFormatter(null, null, false, true);
        $rotatingHandler->setFormatter($formatter);
        $logger->pushHandler($rotatingHandler);
        
        // Handler para erros
        $errorHandler = new StreamHandler(
            __DIR__ . '/../logs/php_errors.log',
            Logger::ERROR
        );
        $errorHandler->setFormatter($formatter);
        $logger->pushHandler($errorHandler);
        
        return $logger;
    },
    
    'db' => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $dbSettings = $settings['db'];
        
        $dsn = "mysql:host={$dbSettings['host']};port={$dbSettings['port']};dbname={$dbSettings['database']};charset={$dbSettings['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            return new PDO($dsn, $dbSettings['username'], $dbSettings['password'], $options);
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    },
    
    'view' => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $viewSettings = $settings['view'];
        
        $loader = new \Twig\Loader\FilesystemLoader($viewSettings['template_path']);
        $twig = new \Twig\Environment($loader, [
            'cache' => $viewSettings['cache_path'],
            'debug' => $settings['displayErrorDetails'],
        ]);
        
        // Adicionar extensões se necessário
        if ($settings['displayErrorDetails']) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }
        
        return $twig;
    },
    
    'csrf' => function (ContainerInterface $container) {
        return new \Slim\Csrf\Guard();
    },
    
    'flash' => function (ContainerInterface $container) {
        return new \Slim\Flash\Messages();
    },
    
    'session' => function (ContainerInterface $container) {
        return new \SlimSession\Helper();
    },
    
    // Serviços da aplicação
    'UniversityService' => function (ContainerInterface $container) {
        return new \App\Services\UniversityService($container->get('db'), $container->get('logger'));
    },
    
    'CourseService' => function (ContainerInterface $container) {
        return new \App\Services\CourseService($container->get('db'), $container->get('logger'));
    },
    
    'ModuleService' => function (ContainerInterface $container) {
        return new \App\Services\ModuleService($container->get('db'), $container->get('logger'));
    },
    
    'TopicService' => function (ContainerInterface $container) {
        return new \App\Services\TopicService($container->get('db'), $container->get('logger'));
    },
    
    'EnrollmentService' => function (ContainerInterface $container) {
        return new \App\Services\EnrollmentService($container->get('db'), $container->get('logger'));
    },
    
    'AuthService' => function (ContainerInterface $container) {
        return new \App\Services\AuthService($container->get('db'), $container->get('logger'));
    },
];
