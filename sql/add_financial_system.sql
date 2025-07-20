-- CapivaraLearn Financial System Migration
-- Version: 1.1
-- Date: 2025-07-20
-- Description: Adds monetization and subscription management tables

-- Subscription Plans Table
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    plan_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    price_usd DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'yearly', 'one_time') DEFAULT 'yearly',
    grace_period_days INT DEFAULT 365,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_plan_code (plan_code),
    INDEX idx_is_active (is_active)
);

-- User Subscriptions Table
CREATE TABLE user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'grace_period', 'payment_due', 'overdue', 'suspended') DEFAULT 'grace_period',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grace_period_end DATE,
    next_payment_due DATE,
    last_payment_date TIMESTAMP NULL,
    amount_due_usd DECIMAL(10,2) DEFAULT 0.00,
    payment_attempts INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    UNIQUE KEY unique_user_plan (user_id, plan_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_grace_period_end (grace_period_end),
    INDEX idx_next_payment_due (next_payment_due)
);

-- Payment Transactions Table
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT NOT NULL,
    transaction_type ENUM('payment', 'refund', 'adjustment') DEFAULT 'payment',
    amount_usd DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'crypto', 'other') NULL,
    payment_gateway VARCHAR(100),
    gateway_transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    failure_reason TEXT,
    gateway_response JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_gateway_transaction_id (gateway_transaction_id)
);

-- Billing Events Table
CREATE TABLE billing_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT,
    event_type ENUM('registration', 'grace_period_start', 'payment_due', 'payment_completed', 'payment_failed', 'account_suspended', 'account_reactivated') NOT NULL,
    event_description TEXT,
    amount_usd DECIMAL(10,2) NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);

-- Payment Notifications Table
CREATE TABLE payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT NOT NULL,
    notification_type ENUM('grace_period_ending', 'payment_due', 'payment_overdue', 'final_notice') NOT NULL,
    scheduled_date DATE NOT NULL,
    sent_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    notification_channel ENUM('email', 'sms', 'in_app') DEFAULT 'email',
    message_content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
);

-- Insert default subscription plan
INSERT INTO subscription_plans (
    plan_name, 
    plan_code, 
    description, 
    price_usd, 
    billing_cycle, 
    grace_period_days, 
    is_active
) VALUES (
    'Annual Operational Contribution',
    'ANNUAL_BASIC',
    'Annual operational expense reimbursement to help maintain the platform infrastructure and development.',
    1.00,
    'yearly',
    365,
    1
);

-- Create stored procedure to initialize user subscription on registration
DELIMITER //
CREATE PROCEDURE CreateUserSubscription(IN p_user_id INT)
BEGIN
    DECLARE v_plan_id INT;
    DECLARE v_grace_end DATE;
    
    -- Get the default plan
    SELECT id INTO v_plan_id 
    FROM subscription_plans 
    WHERE plan_code = 'ANNUAL_BASIC' AND is_active = 1 
    LIMIT 1;
    
    -- Calculate grace period end (1 year from now)
    SET v_grace_end = DATE_ADD(CURDATE(), INTERVAL 365 DAY);
    
    -- Insert user subscription
    INSERT INTO user_subscriptions (
        user_id, 
        plan_id, 
        status, 
        registration_date, 
        grace_period_end,
        next_payment_due,
        amount_due_usd
    ) VALUES (
        p_user_id,
        v_plan_id,
        'grace_period',
        NOW(),
        v_grace_end,
        v_grace_end,
        1.00
    );
    
    -- Log the registration event
    INSERT INTO billing_events (
        user_id,
        subscription_id,
        event_type,
        event_description,
        amount_usd
    ) VALUES (
        p_user_id,
        LAST_INSERT_ID(),
        'registration',
        'User registered with 365-day grace period',
        1.00
    );
END //
DELIMITER ;

-- Migration completed successfully
SELECT 'Financial system tables created successfully' AS Status;
