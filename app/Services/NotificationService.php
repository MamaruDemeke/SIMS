<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

/**
 * NotificationService — a small helper that centralizes how in-app
 * notifications are created and delivered.
 *
 * The app has its own custom `notifications` table (NOT Laravel's database
 * notification tables). Each row targets ONE user via `user_id`, records who
 * sent it via `sender_id`, and has a `type` used to pick the icon in the UI.
 *
 * This service adds convenience methods to notify every user holding a given
 * role, which the Purchase workflow uses to route status updates to the right
 * people (e.g. "purchase submitted" → all inventory managers).
 */
class NotificationService
{
    /**
     * Send a notification to every active user that holds one of the given role slugs.
     *
     * @param array|string $roleSlugs e.g. 'inventory-manager' or ['finance', 'admin']
     * @param string       $type      icon/type code shown in the UI (see notifications/index.blade.php)
     * @param string       $title     short headline
     * @param string       $message   body text
     * @param int|null     $productId linked product (optional)
     * @param int|null     $purchaseId linked purchase, so workflow alerts can be
     *                                cleaned up once the purchase is approved (optional)
     * @param int|null     $saleId    linked sale (optional)
     * @return int how many notifications were created
     */
    public static function notifyRole(array|string $roleSlugs, string $type, string $title, string $message, ?int $productId = null, ?int $purchaseId = null, ?int $saleId = null): int
    {
        $slugs = is_array($roleSlugs) ? $roleSlugs : [$roleSlugs];

        $roleIds = Role::whereIn('slug', $slugs)->pluck('id')->all();

        // Only notify active users belonging to those roles.
        $userIds = User::whereIn('role_id', $roleIds)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        return static::notifyUsers($userIds, $type, $title, $message, $productId, $purchaseId, $saleId);
    }

    /**
     * Send a notification to one specific user (e.g. the purchase officer who
     * created an order).
     *
     * @param int|null $userId
     * @param int|null $purchaseId
     * @param int|null $saleId
     */
    public static function notifyUser(?int $userId, string $type, string $title, string $message, ?int $productId = null, ?int $purchaseId = null, ?int $saleId = null): int
    {
        if (!$userId) {
            return 0;
        }

        return static::notifyUsers([$userId], $type, $title, $message, $productId, $purchaseId, $saleId);
    }

    /**
     * Create one notification row per target user.
     */
    private static function notifyUsers(array $userIds, string $type, string $title, string $message, ?int $productId = null, ?int $purchaseId = null, ?int $saleId = null): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;
        foreach ($userIds as $userId) {
            Notification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'product_id' => $productId,
                'purchase_id' => $purchaseId,
                'sale_id' => $saleId,
                'user_id' => $userId,
                'sender_id' => auth()->id(),
                'is_read' => false,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Delete all workflow notifications linked to a given purchase.
     * Used to clean up the temporary status alerts once a purchase is approved.
     */
    public static function deleteForPurchase(int $purchaseId): void
    {
        Notification::where('purchase_id', $purchaseId)->delete();
    }
}
