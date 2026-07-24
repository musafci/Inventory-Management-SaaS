<?php

namespace App\Http\Requests\Notification;

use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $allowed = app(NotificationPreferenceService::class)->eventKeys();
        $filtered = [];

        foreach ($validated['preferences'] as $eventKey => $enabled) {
            if (in_array($eventKey, $allowed, true)) {
                $filtered[$eventKey] = (bool) $enabled;
            }
        }

        return ['preferences' => $filtered];
    }
}
