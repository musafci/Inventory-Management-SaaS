import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';

import * as devicesApi from '@/src/api/devices';
import * as authStorage from '@/src/auth/storage';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

export async function obtainExpoPushToken(): Promise<string | null> {
  if (!Device.isDevice) {
    return null;
  }

  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;

  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    return null;
  }

  const projectId = Constants.expoConfig?.extra?.eas?.projectId
    ?? Constants.easConfig?.projectId;

  const token = await Notifications.getExpoPushTokenAsync(
    projectId ? { projectId } : undefined,
  );

  return token.data;
}

export async function registerDevicePushToken(organizationId: number | null): Promise<void> {
  if (organizationId === null) {
    return;
  }

  const expoPushToken = await obtainExpoPushToken();

  if (!expoPushToken) {
    return;
  }

  const storedToken = await authStorage.getPushToken();

  if (storedToken === expoPushToken) {
    return;
  }

  const platform: 'ios' | 'android' = Device.osName === 'iOS' ? 'ios' : 'android';

  await devicesApi.registerPushToken({
    expo_push_token: expoPushToken,
    platform,
    device_name: Device.modelName ?? undefined,
    organization_id: organizationId,
  });

  await authStorage.savePushToken(expoPushToken);
}

export async function unregisterDevicePushToken(): Promise<void> {
  const expoPushToken = await authStorage.getPushToken();

  if (!expoPushToken) {
    return;
  }

  try {
    await devicesApi.unregisterPushToken(expoPushToken);
  } finally {
    await authStorage.clearPushToken();
  }
}
