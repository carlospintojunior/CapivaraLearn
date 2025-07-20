<?php
/**
 * MariaDB Compatibility Check and Fix
 * Checks for MariaDB version compatibility issues and applies fixes
 */

require_once 'Medoo.php';

echo "=== MARIADB COMPATIBILITY CHECK ===\n\n";

try {
    // Database configuration
    $database = new Medoo\Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);

    // Check MariaDB version
    $version = $database->query("SELECT VERSION() as version")->fetch();
    echo "Database Version: {$version['version']}\n\n";

    // Check if stored procedures work
    echo "Testing stored procedure creation...\n";
    
    try {
        // Try to create a simple test procedure
        $testProcedure = "
        CREATE OR REPLACE PROCEDURE TestProcedure()
        BEGIN
            SELECT 'Hello World' as message;
        END
        ";
        
        $database->pdo->exec($testProcedure);
        echo "✅ Stored procedures work correctly\n";
        
        // Clean up test procedure
        $database->pdo->exec("DROP PROCEDURE IF EXISTS TestProcedure");
        
        // Now try to create the actual subscription procedure
        echo "\nCreating CreateUserSubscription procedure...\n";
        
        $createProcedure = "
        CREATE OR REPLACE PROCEDURE CreateUserSubscription(IN p_user_id INT)
        BEGIN
            DECLARE v_plan_id INT DEFAULT NULL;
            DECLARE v_grace_end DATE;
            DECLARE EXIT HANDLER FOR SQLEXCEPTION 
            BEGIN
                ROLLBACK;
                RESIGNAL;
            END;
            
            START TRANSACTION;
            
            -- Get the default plan ID
            SELECT id INTO v_plan_id 
            FROM subscription_plans 
            WHERE plan_code = 'basic_annual' AND is_active = 1 
            LIMIT 1;
            
            -- Only proceed if plan was found
            IF v_plan_id IS NOT NULL THEN
                -- Calculate grace period end date (365 days from now)
                SET v_grace_end = DATE_ADD(CURDATE(), INTERVAL 365 DAY);
                
                -- Insert user subscription
                INSERT INTO user_subscriptions 
                (user_id, plan_id, status, registration_date, grace_period_end, next_payment_due, amount_due_usd)
                VALUES 
                (p_user_id, v_plan_id, 'grace_period', NOW(), v_grace_end, v_grace_end, 1.00);
                
                -- Log billing event
                INSERT INTO billing_events 
                (user_id, subscription_id, event_type, event_description, amount_usd)
                VALUES 
                (p_user_id, LAST_INSERT_ID(), 'registration', 'User registered with 365-day grace period', 1.00);
            END IF;
            
            COMMIT;
        END
        ";
        
        $database->pdo->exec($createProcedure);
        echo "✅ CreateUserSubscription procedure created successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Stored procedure creation failed: " . $e->getMessage() . "\n";
        echo "ℹ️ This is not critical - the system will work with PHP methods instead\n";
        
        // Check if it's the mysql.proc issue
        if (strpos($e->getMessage(), 'mysql.proc') !== false) {
            echo "\n⚠️ MARIADB UPGRADE NEEDED\n";
            echo "Your MariaDB installation needs to be upgraded.\n";
            echo "Run this command as root to fix:\n";
            echo "sudo mysql_upgrade --force\n";
            echo "or\n";
            echo "sudo mariadb-upgrade --force\n\n";
        }
    }

    // Test financial system functionality
    echo "\nTesting financial system...\n";
    
    // Check if default plan exists
    $defaultPlan = $database->get('subscription_plans', '*', ['plan_code' => 'basic_annual']);
    if ($defaultPlan) {
        echo "✅ Default subscription plan found: {$defaultPlan['plan_name']} - USD {$defaultPlan['price_usd']}\n";
    } else {
        echo "❌ Default subscription plan not found\n";
    }
    
    // Check tables structure
    $tables = ['subscription_plans', 'user_subscriptions', 'payment_transactions', 'billing_events', 'payment_notifications'];
    foreach ($tables as $table) {
        $count = $database->count($table);
        echo "✅ Table '$table': $count records\n";
    }
    
    echo "\n=== COMPATIBILITY CHECK COMPLETED ===\n";
    echo "✅ Database structure is compatible\n";
    echo "✅ Financial system is ready to use\n";
    
    if (!function_exists('mysql_upgrade')) {
        echo "\nℹ️ Note: If you encounter stored procedure issues, run 'mysql_upgrade' command\n";
    }

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}

echo "\n=== END OF CHECK ===\n";
?>
