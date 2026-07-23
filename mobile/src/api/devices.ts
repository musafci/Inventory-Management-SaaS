import { apiRequest } from '@/src/api/client';

export type DevicePushTokenPayload = {
  expo_push_token: string;
  platform: 'ios' | 'android';
  device_name?: string | null;
  organization_id?: number | null;
};

export type DevicePushTokenResponse = {
  id: number;
  user_id: number;
  organization_id: number | null;
  expo_push_token: string;
  platform: string;
  device_name: string | null;
  last_used_at: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export async function registerPushToken(
  payload: DevicePushTokenPayload,
): Promise<DevicePushTokenResponse> {
  return apiRequest<DevicePushTokenResponse>('/v1/devices/push-token', {
    method: 'POST',
    body: payload,
    organizationId: payload.organization_id,
  });
}

export async function unregisterPushToken(expoPushToken: string): Promise<void> {
  await apiRequest<void>('/v1/devices/push-token', {
    method: 'DELETE',
    body: { expo_push_token: expoPushToken },
  });
}
