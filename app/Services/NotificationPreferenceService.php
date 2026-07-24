<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    /**
     * @return list<string>
     */
    public function eventKeys(): array
    {
        return [
            'low_stock',
            'sales_order_status',
            'purchase_order_status',
            'trial_ending',
            'payment_past_due',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function preferencesFor(User $user, int $organizationId): array
    {
        $stored = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->pluck('enabled', 'event_key');

        $preferences = [];

        foreach ($this->eventKeys() as $eventKey) {
            $preferences[$eventKey] = (bool) ($stored[$eventKey] ?? true);
        }

        return $preferences;
    }

    /**
     * @param  array<string, bool>  $preferences
     * @return array<string, bool>
     */
    public function updateFor(User $user, int $organizationId, array $preferences): array
    {
        foreach ($preferences as $eventKey => $enabled) {
            if (! in_array($eventKey, $this->eventKeys(), true)) {
                continue;
            }

            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'organization_id' => $organizationId,
                    'event_key' => $eventKey,
                ],
                ['enabled' => (bool) $enabled],
            );
        }

        return $this->preferencesFor($user, $organizationId);
    }

    public function isEnabled(User $user, int $organizationId, string $eventKey): bool
    {
        if (! in_array($eventKey, $this->eventKeys(), true)) {
            return true;
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->where('event_key', $eventKey)
            ->value('enabled');

        return $preference ?? true;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function filterEnabledRecipients($users, int $organizationId, string $eventKey)
    {
        return $users->filter(
            fn (User $user): bool => $this->isEnabled($user, $organizationId, $eventKey),
        );
    }
}
