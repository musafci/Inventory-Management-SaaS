export async function registerDevicePushToken(_organizationId: number | null): Promise<void> {
  // Push notifications are not supported in the web preview.
}

export async function unregisterDevicePushToken(): Promise<void> {
  // Push notifications are not supported in the web preview.
}

export async function obtainExpoPushToken(): Promise<string | null> {
  return null;
}
