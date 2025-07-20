<?php
/**
 * FinancialService - Simplified version for CapivaraLearn
 * 
 * @author CapivaraLearn Team
 * @version 1.2
 * @since 2025-07-20
 */

class FinancialService
{
    private $database;
    private $logger;

    public function __construct($database, $logger = null)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Initialize subscription for a new user
     * 
     * @param int $userId User ID
     * @return array Result with success status and subscription data
     */
    public function initializeUserSubscription($userId)
    {
        try {
            // Simple approach: just log and return success
            // This prevents blocking user registration if financial system has issues
            
            $this->log('info', 'Financial subscription initialization requested', [
                'user_id' => $userId
            ]);

            // Try to get default plan if exists
            try {
                $defaultPlan = $this->database->select(
                    "SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1 LIMIT 1",
                    ['ANNUAL_BASIC']
                );

                if ($defaultPlan && !empty($defaultPlan)) {
                    $plan = $defaultPlan[0];
                    
                    // Try to create subscription record
                    $result = $this->database->execute("
                        INSERT INTO user_subscriptions (
                            user_id, plan_id, status, registration_date, 
                            grace_period_end, next_payment_due, amount_due_usd, payment_attempts
                        ) VALUES (?, ?, 'grace_period', NOW(), ?, ?, ?, 0)
                    ", [
                        $userId, 
                        $plan['id'], 
                        date('Y-m-d', strtotime('+365 days')), 
                        date('Y-m-d', strtotime('+365 days')), 
                        $plan['price_usd']
                    ]);

                    if ($result) {
                        $this->log('success', 'Financial subscription initialized successfully', [
                            'user_id' => $userId,
                            'plan_id' => $plan['id']
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Log but don't fail
                $this->log('warning', 'Financial subscription initialization failed, but user registration continues', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }

            // Always return success to not block user registration
            return [
                'success' => true,
                'message' => 'User registered successfully'
            ];

        } catch (Exception $e) {
            // Log error but still return success to not block registration
            $this->log('error', 'Financial service error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true,
                'message' => 'User registered successfully (financial system error logged)'
            ];
        }
    }

    /**
     * Simple logging method
     */
    private function log($level, $message, $context = [])
    {
        if (function_exists('log_sistema')) {
            $logMessage = $message;
            if (!empty($context)) {
                $logMessage .= ' | Context: ' . json_encode($context);
            }
            log_sistema($logMessage, strtoupper($level));
        }
    }

    /**
     * Get user subscription status
     * 
     * @param int $userId User ID
     * @return array|null Subscription data or null if not found
     */
    public function getUserSubscription($userId)
    {
        try {
            $subscription = $this->database->select("
                SELECT 
                    us.*,
                    sp.plan_name,
                    sp.plan_code,
                    sp.description,
                    sp.price_usd,
                    sp.billing_cycle
                FROM user_subscriptions us
                LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
                WHERE us.user_id = ? 
                LIMIT 1
            ", [$userId]);

            return $subscription && !empty($subscription) ? $subscription[0] : null;
        } catch (Exception $e) {
            $this->log('error', 'Failed to get user subscription', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update subscription status (simplified version)
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function updateSubscriptionStatus($userId)
    {
        try {
            // Simple approach: just log that it was called
            $this->log('info', 'Subscription status update requested', [
                'user_id' => $userId
            ]);
            
            // Check if grace period ended and update if needed
            $subscription = $this->getUserSubscription($userId);
            if ($subscription && $subscription['grace_period_end'] < date('Y-m-d')) {
                $this->database->execute(
                    "UPDATE user_subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'grace_period'",
                    [$userId]
                );
                $this->log('info', 'Grace period expired, status updated to expired', [
                    'user_id' => $userId
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            $this->log('error', 'Failed to update subscription status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get grace period days remaining
     * 
     * @param int $userId User ID
     * @return int Days remaining (0 if expired or no subscription)
     */
    public function getGracePeriodDaysRemaining($userId)
    {
        try {
            $subscription = $this->getUserSubscription($userId);
            if (!$subscription || !$subscription['grace_period_end']) {
                return 0;
            }

            $gracePeriodEnd = new DateTime($subscription['grace_period_end']);
            $today = new DateTime();
            $diff = $today->diff($gracePeriodEnd);

            if ($gracePeriodEnd < $today) {
                return 0; // Expired
            }

            return $diff->days;
        } catch (Exception $e) {
            $this->log('error', 'Failed to calculate grace period days', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get payment history
     * 
     * @param int $userId User ID
     * @return array Payment history
     */
    public function getPaymentHistory($userId)
    {
        try {
            $payments = $this->database->select("
                SELECT 
                    pt.*,
                    us.status as subscription_status
                FROM payment_transactions pt
                LEFT JOIN user_subscriptions us ON pt.subscription_id = us.id
                WHERE pt.user_id = ?
                ORDER BY pt.transaction_date DESC
                LIMIT 50
            ", [$userId]);

            return $payments ?: [];
        } catch (Exception $e) {
            $this->log('error', 'Failed to get payment history', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get subscription statistics (for admin dashboard)
     * 
     * @return array Statistics
     */
    public function getSubscriptionStatistics()
    {
        try {
            $stats = [];

            // Total users
            $result = $this->database->select("SELECT COUNT(*) as count FROM usuarios");
            $stats['total_users'] = $result[0]['count'] ?? 0;

            // Active subscriptions
            $result = $this->database->select("SELECT COUNT(*) as count FROM user_subscriptions WHERE status = 'active'");
            $stats['active_subscriptions'] = $result[0]['count'] ?? 0;

            // Grace period users
            $result = $this->database->select("SELECT COUNT(*) as count FROM user_subscriptions WHERE status = 'grace_period'");
            $stats['grace_period_users'] = $result[0]['count'] ?? 0;

            // Total revenue
            $result = $this->database->select("SELECT SUM(amount_usd) as total FROM payment_transactions WHERE status = 'completed'");
            $stats['total_revenue_usd'] = $result[0]['total'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            $this->log('error', 'Failed to get subscription statistics', [
                'error' => $e->getMessage()
            ]);
            return [
                'total_users' => 0,
                'active_subscriptions' => 0,
                'grace_period_users' => 0,
                'total_revenue_usd' => 0
            ];
        }
    }
}
