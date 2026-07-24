import { apiRequest } from '@/src/api/client';

export type NotificationPreferencesResponse = {
  events: string[];
  preferences: Record<string, boolean>;
};

export async function fetchNotificationPreferences(
  organizationId?: number | null,
): Promise<NotificationPreferencesResponse> {
  return apiRequest<NotificationPreferencesResponse>('/v1/notifications/preferences', {
    organizationId,
  });
}

export async function updateNotificationPreferences(
  preferences: Record<string, boolean>,
  organizationId?: number | null,
): Promise<NotificationPreferencesResponse> {
  return apiRequest<NotificationPreferencesResponse>('/v1/notifications/preferences', {
    method: 'PATCH',
    body: { preferences },
    organizationId,
  });
}
