import * as SecureStore from 'expo-secure-store';

const ACCESS_TOKEN_KEY = 'oneapp_access_token';
const REFRESH_TOKEN_KEY = 'oneapp_refresh_token';
const ORGANIZATION_ID_KEY = 'oneapp_organization_id';

export async function getAccessToken(): Promise<string | null> {
  return SecureStore.getItemAsync(ACCESS_TOKEN_KEY);
}

export async function getRefreshToken(): Promise<string | null> {
  return SecureStore.getItemAsync(REFRESH_TOKEN_KEY);
}

export async function getOrganizationId(): Promise<number | null> {
  const value = await SecureStore.getItemAsync(ORGANIZATION_ID_KEY);

  if (value === null || value === '') {
    return null;
  }

  const parsed = Number.parseInt(value, 10);

  return Number.isNaN(parsed) ? null : parsed;
}

export async function saveTokens(accessToken: string, refreshToken: string): Promise<void> {
  await SecureStore.setItemAsync(ACCESS_TOKEN_KEY, accessToken);
  await SecureStore.setItemAsync(REFRESH_TOKEN_KEY, refreshToken);
}

export async function saveOrganizationId(organizationId: number): Promise<void> {
  await SecureStore.setItemAsync(ORGANIZATION_ID_KEY, String(organizationId));
}

export async function clearAuthStorage(): Promise<void> {
  await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
  await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
  await SecureStore.deleteItemAsync(ORGANIZATION_ID_KEY);
}
