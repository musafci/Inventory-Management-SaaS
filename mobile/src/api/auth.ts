import { apiRequest } from '@/src/api/client';
import type { LoginResponse, MeResponse } from '@/src/api/types';

export async function login(email: string, password: string): Promise<LoginResponse> {
  return apiRequest<LoginResponse>('/v1/auth/login', {
    method: 'POST',
    body: { email, password },
  });
}

export async function refresh(refreshToken: string): Promise<LoginResponse> {
  return apiRequest<LoginResponse>('/v1/auth/refresh', {
    method: 'POST',
    body: { refresh_token: refreshToken },
  });
}

export async function fetchMe(organizationId?: number | null): Promise<MeResponse> {
  return apiRequest<MeResponse>('/v1/auth/me', {
    organizationId: organizationId ?? undefined,
  });
}

export async function logout(accessToken: string): Promise<void> {
  await apiRequest<void>('/v1/auth/logout', {
    method: 'POST',
    accessToken,
  });
}
