<?php
/**
 * FinancialService - Manages community contributions and sustainability
 * 
 * This service handles all community operations including:
 * - Community contribution tracking
 * - Voluntary contribution management
 * - User eligibility for contribution requests
 * - Community sustainability logging
 * - Contribution notifications
 * 
 * @author CapivaraLearn Team
 * @version 0.7.0 - Community Model
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
     * Initialize community contribution tracking for a new user
     * 
     * @param int $userId User ID
     * @return array Result with success status and tracking data
     */
    public function initializeUserContribution($userId)
    {
        try {
            // Get community plan
            $communityPlan = $this->database->select('subscription_plans', '*', [
                'plan_code' => 'COMMUNITY_FREE',
                'is_active' => 1
            ]);

            if (!$communityPlan || empty($communityPlan)) {
                // Create community plan if it doesn't exist
                $this->database->insert('subscription_plans', [
                    'plan_name' => 'Community Free',
                    'plan_code' => 'COMMUNITY_FREE',
                    'description' => '100% free access with optional voluntary contributions',
                    'price_usd' => 0.00,
                    'billing_cycle' => 'voluntary',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Check if it's a DatabaseConnection wrapper or direct Medoo instance
                if (method_exists($this->database, 'getConnection')) {
                    $planId = $this->database->getConnection()->lastInsertId();
                } else {
                    $planId = $this->database->id();
                }
            } else {
                $planId = $communityPlan[0]['id'];
            }

            // Check if tracking already exists
            $existingTracking = $this->database->select('user_subscriptions', '*', [
                'user_id' => $userId,
                'plan_id' => $planId
            ]);

            if ($existingTracking && !empty($existingTracking)) {
                return [
                    'success' => true,
                    'message' => 'Community tracking already exists',
                    'tracking' => $existingTracking[0]
                ];
            }

            // Create community tracking
            $this->database->insert('user_subscriptions', [
                'user_id' => $userId,
                'plan_id' => $planId,
                'status' => 'free_access',
                'registration_date' => date('Y-m-d H:i:s'),
                'grace_period_end' => null,
                'next_payment_due' => null,
                'amount_due_usd' => 0.00,
                'payment_attempts' => 0
            ]);
            
            // Check if it's a DatabaseConnection wrapper or direct Medoo instance
            if (method_exists($this->database, 'getConnection')) {
                $trackingId = $this->database->getConnection()->lastInsertId();
            } else {
                $trackingId = $this->database->id();
            }

            // Log community event
            $this->logCommunityEvent($userId, $trackingId, 'registration', 
                'User joined CapivaraLearn community - 100% free access');

            // Get tracking data to return
            $tracking = $this->database->select('user_subscriptions', '*', [
                'id' => $trackingId
            ]);
            $tracking = $tracking ? $tracking[0] : null;

            $this->log('info', 'User community tracking initialized', [
                'user_id' => $userId,
                'tracking_id' => $trackingId
            ]);

            return [
                'success' => true,
                'message' => 'Community tracking initialized successfully',
                'tracking' => $tracking
            ];

        } catch (Exception $e) {
            $this->log('error', 'Failed to initialize user community tracking', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initialize community tracking: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initialize subscription for a new user (legacy compatibility)
     * 
     * @param int $userId User ID
     * @return array Result with success status and subscription data
     */
    public function initializeUserSubscription($userId)
    {
        try {
            // Get default plan
            $defaultPlan = $this->database->select('subscription_plans', '*', [
                'plan_code' => 'ANNUAL_BASIC',
                'is_active' => 1
            ]);

            if (!$defaultPlan || empty($defaultPlan)) {
                throw new Exception('Default subscription plan not found');
            }
            
            $defaultPlan = $defaultPlan[0]; // Get first result

            // Calculate grace period end date
            $gracePeriodEnd = date('Y-m-d', strtotime('+365 days'));
            $nextPaymentDue = $gracePeriodEnd;

            // Check if subscription already exists
            $existingSubscription = $this->database->select('user_subscriptions', '*', [
                'user_id' => $userId,
                'plan_id' => $defaultPlan['id']
            ]);

            if ($existingSubscription && !empty($existingSubscription)) {
                return [
                    'success' => true,
                    'message' => 'Subscription already exists',
                    'subscription' => $existingSubscription[0]
                ];
            }

            // Create subscription (don't manage transaction here, let caller handle it)
            $this->database->insert('user_subscriptions', [
                'user_id' => $userId,
                'plan_id' => $defaultPlan['id'],
                'status' => 'grace_period',
                'registration_date' => date('Y-m-d H:i:s'),
                'grace_period_end' => $gracePeriodEnd,
                'next_payment_due' => $nextPaymentDue,
                'amount_due_usd' => $defaultPlan['price_usd'],
                'payment_attempts' => 0
            ]);
            
            // Check if it's a DatabaseConnection wrapper or direct Medoo instance
            if (method_exists($this->database, 'getConnection')) {
                $subscriptionId = $this->database->getConnection()->lastInsertId();
            } else {
                $subscriptionId = $this->database->id();
            }

            // Log billing event
            $this->logBillingEvent($userId, $subscriptionId, 'registration', 
                'User registered with 365-day grace period', $defaultPlan['price_usd']);

            // Schedule grace period ending notification (30 days before)
            $this->scheduleNotification($userId, $subscriptionId, 'grace_period_ending', 
                date('Y-m-d', strtotime($gracePeriodEnd . ' -30 days')));

            // Get subscription data to return (don't commit here, let caller handle it)
            $subscription = $this->database->select('user_subscriptions', '*', [
                'id' => $subscriptionId
            ]);
            $subscription = $subscription ? $subscription[0] : null;

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
            // Don't manage transaction here, just log and return error
            // Let the caller handle the transaction rollback
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
     * Get user community status
     * 
     * @param int $userId User ID
     * @return array|null Community tracking data or null if not found
     */
    public function getUserCommunityStatus($userId)
    {
        // Use Medoo's join syntax instead of raw SQL
        $tracking = $this->database->select('user_subscriptions', [
            '[>]subscription_plans' => ['plan_id' => 'id']
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

        return $tracking && !empty($tracking) ? $tracking[0] : null;
    }

    /**
     * Check if user should see contribution request
     * 
     * @param int $userId User ID
     * @return bool True if eligible for contribution request
     */
    public function shouldShowContributionRequest($userId)
    {
        $tracking = $this->getUserCommunityStatus($userId);
        
        if (!$tracking) {
            return false;
        }

        $registrationDate = new DateTime($tracking['registration_date']);
        $today = new DateTime();
        $daysSinceRegistration = $today->diff($registrationDate)->days;

        // Show contribution request after 1 year (365 days)
        return $daysSinceRegistration >= 365;
    }

    /**
     * Record a voluntary contribution
     * 
     * @param int $userId User ID
     * @param float $amount Contribution amount
     * @param string $method Payment method
     * @param string $message Optional message from contributor
     * @return array Result with success status
     */
    public function recordContribution($userId, $amount, $method = 'voluntary', $message = '')
    {
        try {
            $tracking = $this->getUserCommunityStatus($userId);
            
            if (!$tracking) {
                return [
                    'success' => false,
                    'message' => 'User community tracking not found'
                ];
            }

            // Record contribution transaction
            $this->database->insert('payment_transactions', [
                'user_id' => $userId,
                'subscription_id' => $tracking['id'],
                'transaction_type' => 'voluntary_contribution',
                'amount_usd' => $amount,
                'payment_method' => $method,
                'status' => 'completed',
                'transaction_reference' => 'VOLUNTARY_' . time(),
                'gateway_response' => json_encode(['message' => $message]),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log community event
            $this->logCommunityEvent($userId, $tracking['id'], 'voluntary_contribution', 
                "Voluntary contribution received: $" . number_format($amount, 2), $amount);

            // Update tracking status if first contribution
            if ($tracking['status'] === 'free_access') {
                $this->database->update('user_subscriptions', [
                    'status' => 'active_contributor',
                    'last_payment_date' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ], [
                    'id' => $tracking['id']
                ]);
            }

            $this->log('info', 'Voluntary contribution recorded', [
                'user_id' => $userId,
                'amount' => $amount,
                'method' => $method
            ]);

            return [
                'success' => true,
                'message' => 'Contribution recorded successfully'
            ];

        } catch (Exception $e) {
            $this->log('error', 'Failed to record voluntary contribution', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record contribution: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get contribution history for user
     * 
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Contribution transactions
     */
    public function getContributionHistory($userId, $limit = 10)
    {
        return $this->database->select('payment_transactions', '*', [
            'user_id' => $userId,
            'transaction_type' => 'voluntary_contribution',
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => $limit
        ]);
    }

    /**
     * Get user subscription status (legacy compatibility)
     * 
     * @param int $userId User ID
     * @return array|null Subscription data or null if not found
     */
    public function getUserSubscription($userId)
    {
        // For compatibility, return community status
        return $this->getUserCommunityStatus($userId);
    }

    /**
     * Check if user subscription needs status update (legacy compatibility)
     * 
     * @param int $userId User ID
     * @return bool True if status was updated
     */
    public function updateSubscriptionStatus($userId)
    {
        try {
            $tracking = $this->getUserCommunityStatus($userId);
            
            if (!$tracking) {
                return false;
            }

            // For community model, we don't need to update status based on payments
            // Just return true for compatibility
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
     * Get grace period days remaining (legacy compatibility)
     * 
     * @param int $userId User ID
     * @return int|null Days remaining or null if not in grace period
     */
    public function getGracePeriodDaysRemaining($userId)
    {
        $tracking = $this->getUserCommunityStatus($userId);
        
        if (!$tracking) {
            return null;
        }

        $today = new DateTime();
        $registrationDate = new DateTime($tracking['registration_date']);
        $oneYearAfter = clone $registrationDate;
        $oneYearAfter->add(new DateInterval('P1Y'));

        // If less than one year, return days remaining until eligibility
        if ($today < $oneYearAfter) {
            $diff = $today->diff($oneYearAfter);
            return $diff->days;
        }

        return 0; // More than one year, no "grace period"
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
     * Log community event
     * 
     * @param int $userId User ID
     * @param int $trackingId Tracking ID
     * @param string $eventType Event type
     * @param string $description Event description
     * @param float $amount Amount (optional)
     */
    private function logCommunityEvent($userId, $trackingId, $eventType, $description, $amount = null)
    {
        $this->database->insert('billing_events', [
            'user_id' => $userId,
            'subscription_id' => $trackingId,
            'event_type' => $eventType,
            'event_description' => $description,
            'amount_usd' => $amount,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log billing event (legacy compatibility)
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
        $existing = $this->database->select('payment_notifications', 'id', [
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'notification_type' => $notificationType,
            'status' => 'pending'
        ]);

        if (!$existing || empty($existing)) {
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
            // Count by status using Medoo syntax
            $statusCounts = $this->database->select('user_subscriptions', [
                'status',
                'count' => Medoo\Medoo::raw('COUNT(*)')
            ], [
                'GROUP' => 'status'
            ]);

            foreach ($statusCounts as $status) {
                $stats['status_' . $status['status']] = $status['count'];
            }

            // Users in grace period ending soon (next 30 days)
            $gracePeriodEndingSoon = $this->database->count('user_subscriptions', [
                'status' => 'grace_period',
                'grace_period_end[<>]' => [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]
            ]);
            $stats['grace_period_ending_soon'] = $gracePeriodEndingSoon;

            // Total revenue using Medoo syntax
            $revenue = $this->database->sum('payment_transactions', 'amount_usd', [
                'status' => 'completed'
            ]);
            $stats['total_revenue_usd'] = $revenue ?? 0;

            return $stats;

        } catch (Exception $e) {
            $this->log('error', 'Failed to get subscription statistics', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}
