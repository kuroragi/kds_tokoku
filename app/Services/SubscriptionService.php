<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\BusinessUnit;

class SubscriptionService
{
    /**
     * Create a new subscription for a user.
     */
    public function createSubscription(
        User $user,
        Plan $plan,
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?string $voucherCode = null,
        ?int $durationDays = null
    ): Subscription {
        // Cancel any existing active subscription
        $user->subscriptions()
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $duration = $durationDays ?? $plan->duration_days;

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays($duration)->toDateString(),
            'status' => 'active',
            'amount_paid' => $voucherCode ? 0 : $plan->price,
            'payment_method' => $paymentMethod ?? ($voucherCode ? 'voucher' : 'trial'),
            'payment_reference' => $paymentReference,
            'voucher_code' => $voucherCode,
        ]);
    }

    /**
     * Get the currently active subscription for a user.
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->with('plan.features')
            ->active()
            ->notExpired()
            ->latest('ends_at')
            ->first();
    }

    /**
     * Get the current plan for a user.
     */
    public function getCurrentPlan(User $user): ?Plan
    {
        return $this->getActiveSubscription($user)?->plan;
    }

    /**
     * Check if a user has an active subscription.
     */
    public function hasActiveSubscription(User $user): bool
    {
        return $this->getActiveSubscription($user) !== null;
    }

    /**
     * Check if a specific feature is available for the user's current plan.
     */
    public function hasFeature(User $user, string $featureKey): bool
    {
        // Superadmin bypasses all feature checks
        if ($user->hasRole('superadmin')) {
            return true;
        }

        $plan = $this->getCurrentPlan($user);
        if (!$plan) {
            return false;
        }

        return $plan->hasFeature($featureKey);
    }

    /**
     * Check if the user can add more users under their subscription.
     */
    public function canAddUser(User $owner): bool
    {
        $plan = $this->getCurrentPlan($owner);
        if (!$plan) {
            return false;
        }

        // Unlimited users
        if ($plan->max_users === 0) {
            return true;
        }

        $currentUserCount = $this->getUserCount($owner);
        return $currentUserCount < $plan->max_users;
    }

    /**
     * Get the number of users under the same business unit(s) as the owner.
     */
    public function getUserCount(User $owner): int
    {
        if ($owner->business_unit_id) {
            return User::where('business_unit_id', $owner->business_unit_id)
                ->whereNull('deleted_at')
                ->count();
        }

        return 1; // Only the owner themselves
    }

    /**
     * Get remaining user slots.
     */
    public function getRemainingUserSlots(User $owner): int|string
    {
        $plan = $this->getCurrentPlan($owner);
        if (!$plan) {
            return 0;
        }

        if ($plan->max_users === 0) {
            return 'unlimited';
        }

        return max(0, $plan->max_users - $this->getUserCount($owner));
    }

    /**
     * Check if the user can add more business units.
     */
    public function canAddBusinessUnit(User $owner): bool
    {
        $plan = $this->getCurrentPlan($owner);
        if (!$plan) {
            return false;
        }

        // Unlimited
        if ($plan->max_business_units === 0) {
            return true;
        }

        $currentUnitCount = $this->getBusinessUnitCount($owner);
        return $currentUnitCount < $plan->max_business_units;
    }

    /**
     * Get the number of business units owned by the user.
     */
    public function getBusinessUnitCount(User $owner): int
    {
        // Count units associated with the user
        if ($owner->business_unit_id) {
            return BusinessUnit::where('id', $owner->business_unit_id)
                ->whereNull('deleted_at')
                ->count();
        }

        return 0;
    }

    /**
     * Get remaining business unit slots.
     */
    public function getRemainingUnitSlots(User $owner): int|string
    {
        $plan = $this->getCurrentPlan($owner);
        if (!$plan) {
            return 0;
        }

        if ($plan->max_business_units === 0) {
            return 'unlimited';
        }

        return max(0, $plan->max_business_units - $this->getBusinessUnitCount($owner));
    }

    /**
     * Check and update subscription statuses (expired/grace).
     * Should be called via scheduled command daily.
     */
    public function processExpirations(): int
    {
        $count = 0;

        // Active → Grace (expired today, give 3 day grace)
        $count += Subscription::where('status', 'active')
            ->where('ends_at', '<', now()->toDateString())
            ->update(['status' => 'grace']);

        // Grace → Expired (after 3 day grace period)
        $count += Subscription::where('status', 'grace')
            ->where('ends_at', '<', now()->subDays(3)->toDateString())
            ->update(['status' => 'expired']);

        return $count;
    }

    /**
     * Get subscription summary for dashboard display.
     */
    public function getSubscriptionSummary(User $user): array
    {
        $subscription = $this->getActiveSubscription($user);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'plan_name' => 'Tidak Ada',
                'days_remaining' => 0,
                'user_slots' => '0/0',
                'unit_slots' => '0/0',
            ];
        }

        $plan = $subscription->plan;

        return [
            'has_subscription' => true,
            'plan_name' => $plan->name,
            'plan_slug' => $plan->slug,
            'days_remaining' => $subscription->daysRemaining(),
            'ends_at' => $subscription->ends_at->format('d M Y'),
            'user_slots' => $plan->max_users === 0
                ? $this->getUserCount($user) . '/∞'
                : $this->getUserCount($user) . '/' . $plan->max_users,
            'unit_slots' => $plan->max_business_units === 0
                ? $this->getBusinessUnitCount($user) . '/∞'
                : $this->getBusinessUnitCount($user) . '/' . $plan->max_business_units,
            'status' => $subscription->status,
        ];
    }

    /**
     * Get all feature keys available for the user's current plan.
     */
    public function getAvailableFeatures(User $user): array
    {
        $plan = $this->getCurrentPlan($user);
        if (!$plan) {
            return [];
        }

        return $plan->features()
            ->where('is_enabled', true)
            ->pluck('feature_key')
            ->toArray();
    }
}
