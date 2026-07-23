const ACCESS_TOKEN_KEY = 'oneapp_access_token';
const REFRESH_TOKEN_KEY = 'oneapp_refresh_token';
const ORGANIZATION_ID_KEY = 'oneapp_organization_id';
const PUSH_TOKEN_KEY = 'oneapp_push_token';

function canUseLocalStorage(): boolean {
  return typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
}

async function getItem(key: string): Promise<string | null> {
  if (!canUseLocalStorage()) {
    return null;
  }

  return window.localStorage.getItem(key);
}

async function setItem(key: string, value: string): Promise<void> {
  if (!canUseLocalStorage()) {
    return;
  }

  window.localStorage.setItem(key, value);
}

async function removeItem(key: string): Promise<void> {
  if (!canUseLocalStorage()) {
    return;
  }

  window.localStorage.removeItem(key);
}

export async function getAccessToken(): Promise<string | null> {
  return getItem(ACCESS_TOKEN_KEY);
}

export async function getRefreshToken(): Promise<string | null> {
  return getItem(REFRESH_TOKEN_KEY);
}

export async function getOrganizationId(): Promise<number | null> {
  const value = await getItem(ORGANIZATION_ID_KEY);

  if (value === null || value === '') {
    return null;
  }

  const parsed = Number.parseInt(value, 10);

  return Number.isNaN(parsed) ? null : parsed;
}

export async function saveTokens(accessToken: string, refreshToken: string): Promise<void> {
  await setItem(ACCESS_TOKEN_KEY, accessToken);
  await setItem(REFRESH_TOKEN_KEY, refreshToken);
}

export async function saveOrganizationId(organizationId: number): Promise<void> {
  await setItem(ORGANIZATION_ID_KEY, String(organizationId));
}

export async function getPushToken(): Promise<string | null> {
  return getItem(PUSH_TOKEN_KEY);
}

export async function savePushToken(pushToken: string): Promise<void> {
  await setItem(PUSH_TOKEN_KEY, pushToken);
}

export async function clearPushToken(): Promise<void> {
  await removeItem(PUSH_TOKEN_KEY);
}

export async function clearAuthStorage(): Promise<void> {
  await removeItem(ACCESS_TOKEN_KEY);
  await removeItem(REFRESH_TOKEN_KEY);
  await removeItem(ORGANIZATION_ID_KEY);
  await removeItem(PUSH_TOKEN_KEY);
}
