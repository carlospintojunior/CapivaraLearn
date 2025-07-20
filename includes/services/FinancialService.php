<?php
/**
 * FinancialService - Manages subscription and payment operations
 * 
 * This service handles all financial operations including:
 * - Subscription management
 * - Payment processing
 * - Grace period tracking
 * - Billing event logging
 * - Payment notifications
 * 
 * @author CapivaraLearn Team
 * @version 1.1
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
            // Get default plan
            $defaultPlan = $this->database->get('subscription_plans', '*', [
                'plan_code' => 'ANNUAL_BASIC',
                'is_active' => 1
            ]);

            if (!$defaultPlan) {
                throw new Exception('Default subscription plan not found');
            }

            // Calculate grace period end date
            $gracePeriodEnd = date('Y-m-d', strtotime('+365 days'));
            $nextPaymentDue = $gracePeriodEnd;

            // Check if subscription already exists
            $existingSubscription = $this->database->get('user_subscriptions', '*', [
                'user_id' => $userId,
                'plan_id' => $defaultPlan['id']
            ]);

            if ($existingSubscription) {
                return [
                    'success' => true,
                    'message' => 'Subscription already exists',
                    'subscription' => $existingSubscription
                ];
            }

            // Start transaction
            $this->database->pdo->beginTransaction();

            // Create subscription
            $subscriptionId = $this->database->insert('user_subscriptions', [
                'user_id' => $userId,
                'plan_id' => $defaultPlan['id'],
                'status' => 'grace_period',
                'registration_date' => date('Y-m-d H:i:s'),
                'grace_period_end' => $gracePeriodEnd,
                'next_payment_due' => $nextPaymentDue,
                'amount_due_usd' => $defaultPlan['price_usd'],
                'payment_attempts' => 0
            ]);

            // Log billing event
            $this->logBillingEvent($userId, $subscriptionId, 'registration', 
                'User registered with 365-day grace period', $defaultPlan['price_usd']);

            // Schedule grace period ending notification (30 days before)
            $this->scheduleNotification($userId, $subscriptionId, 'grace_period_ending', 
                date('Y-m-d', strtotime($gracePeriodEnd . ' -30 days')));

            // Commit transaction
            $this->database->pdo->commit();

            $subscription = $this->database->get('user_subscriptions', '*', ['id' => $subscriptionId]);

            $this->log('info', 'User subscription initialized', [
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'grace_period_end' => $gracePeriodEnd
            ]);

            return [
                'success' => true,
                'message' => 'Subscription initialized successfully',
                'subscription' => $subscription
            ];

        } catch (Exception $e) {
            if ($this->database->pdo->inTransaction()) {
                $this->database->pdo->rollback();
            }

            $this->log('error', 'Failed to initialize user subscription', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initialize subscription: ' . $e->getMessage()
            ];
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
        $subscription = $this->database->get('user_subscriptions', [
            '[><]subscription_plans' => ['plan_id' => 'id']
        ], [
            'user_subscriptions.id',
            'user_subscriptions.status',
            'user_subscriptions.registration_date',
            'user_subscriptions.grace_period_end',
            'user_subscriptions.next_payment_due',
            'user_subscriptions.last_payment_date',
            'user_subscriptions.amount_due_usd',
            'user_subscriptions.payment_attempts',
            'subscription_plans.plan_name',
            'subscription_plans.plan_code',
            'subscription_plans.description',
            'subscription_plans.price_usd',
            'subscription_plans.billing_cycle'
        ], [
            'user_subscriptions.user_id' => $userId
        ]);

        return $subscription;
    }

    /**
     * Check if user subscription needs status update
     * 
     * @param int $userId User ID
     * @return bool True if status was updated
     */
    public function updateSubscriptionStatus($userId)
    {
        try {
            $subscription = $this->getUserSubscription($userId);
            
            if (!$subscription) {
                return false;
            }

            $today = date('Y-m-d');
            $gracePeriodEnd = $subscription['grace_period_end'];
            $nextPaymentDue = $subscription['next_payment_due'];
            $currentStatus = $subscription['status'];
            $newStatus = $currentStatus;

            // Determine new status based on dates
            if ($currentStatus === 'grace_period' && $today > $gracePeriodEnd) {
                $newStatus = 'payment_due';
            } elseif ($currentStatus === 'payment_due' && $today > date('Y-m-d', strtotime($nextPaymentDue . ' +30 days'))) {
                $newStatus = 'overdue';
            } elseif ($currentStatus === 'overdue' && $today > date('Y-m-d', strtotime($nextPaymentDue . ' +60 days'))) {
                $newStatus = 'suspended';
            }

            // Update status if changed
            if ($newStatus !== $currentStatus) {
                $this->database->update('user_subscriptions', [
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ], [
                    'id' => $subscription['id']
                ]);

                // Log status change
                $this->logBillingEvent($userId, $subscription['id'], 
                    $newStatus === 'suspended' ? 'account_suspended' : 'payment_due',
                    "Status changed from {$currentStatus} to {$newStatus}",
                    $subscription['amount_due_usd']);

                $this->log('info', 'Subscription status updated', [
                    'user_id' => $userId,
                    'subscription_id' => $subscription['id'],
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->log('error', 'Failed to update subscription status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get days remaining in grace period
     * 
     * @param int $userId User ID
     * @return int|null Days remaining or null if not in grace period
     */
    public function getGracePeriodDaysRemaining($userId)
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription || $subscription['status'] !== 'grace_period') {
            return null;
        }

        $today = new DateTime();
        $gracePeriodEnd = new DateTime($subscription['grace_period_end']);
        $diff = $today->diff($gracePeriodEnd);

        return $gracePeriodEnd > $today ? $diff->days : 0;
    }

    /**
     * Get payment history for user
     * 
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Payment transactions
     */
    public function getPaymentHistory($userId, $limit = 10)
    {
        return $this->database->select('payment_transactions', '*', [
            'user_id' => $userId,
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => $limit
        ]);
    }

    /**
     * Log billing event
     * 
     * @param int $userId User ID
     * @param int $subscriptionId Subscription ID
     * @param string $eventType Event type
     * @param string $description Event description
     * @param float $amount Amount (optional)
     */
    private function logBillingEvent($userId, $subscriptionId, $eventType, $description, $amount = null)
    {
        $this->database->insert('billing_events', [
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'event_type' => $eventType,
            'event_description' => $description,
            'amount_usd' => $amount,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Schedule notification
     * 
     * @param int $userId User ID
     * @param int $subscriptionId Subscription ID
     * @param string $notificationType Notification type
     * @param string $scheduledDate When to send notification
     */
    private function scheduleNotification($userId, $subscriptionId, $notificationType, $scheduledDate)
    {
        // Check if notification already exists
        $existing = $this->database->get('payment_notifications', 'id', [
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'notification_type' => $notificationType,
            'status' => 'pending'
        ]);

        if (!$existing) {
            $this->database->insert('payment_notifications', [
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'notification_type' => $notificationType,
                'scheduled_date' => $scheduledDate,
                'status' => 'pending',
                'notification_channel' => 'email'
            ]);
        }
    }

    /**
     * Log message
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function log($level, $message, $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Get subscription statistics
     * 
     * @return array Statistics data
     */
    public function getSubscriptionStatistics()
    {
        try {
            $stats = [];

            // Total users
            $stats['total_users'] = $this->database->count('usuarios');

            // Subscription status breakdown
            $statusCounts = $this->database->select('user_subscriptions', [
                'status',
                'COUNT(*)' => 'count'
            ], [
                'GROUP' => 'status'
            ]);

            foreach ($statusCounts as $status) {
                $stats['status_' . $status['status']] = $status['count'];
            }

            // Users in grace period ending soon (next 30 days)
            $stats['grace_period_ending_soon'] = $this->database->count('user_subscriptions', [
                'status' => 'grace_period',
                'grace_period_end[<>]' => [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]
            ]);

            // Total revenue
            $revenue = $this->database->get('payment_transactions', [
                'SUM(amount_usd)' => 'total'
            ], [
                'status' => 'completed'
            ]);
            $stats['total_revenue_usd'] = $revenue['total'] ?? 0;

            return $stats;

        } catch (Exception $e) {
            $this->log('error', 'Failed to get subscription statistics', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}
