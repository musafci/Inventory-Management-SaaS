import { apiRequest } from '@/src/api/client';
import type {
  AuthSession,
  LoginResponse,
  MeResponse,
  RegisterPayload,
} from '@/src/api/types';

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

export async function register(payload: RegisterPayload): Promise<LoginResponse> {
  return apiRequest<LoginResponse>('/v1/auth/register', {
    method: 'POST',
    body: payload,
  });
}

export async function forgotPassword(email: string): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/v1/auth/forgot-password', {
    method: 'POST',
    body: { email },
  });
}

export async function resetPassword(payload: {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/v1/auth/reset-password', {
    method: 'POST',
    body: payload,
  });
}

export async function fetchSessions(): Promise<AuthSession[]> {
  return apiRequest<AuthSession[]>('/v1/auth/sessions');
}

export async function revokeSession(tokenId: string): Promise<void> {
  await apiRequest<void>(`/v1/auth/sessions/${tokenId}`, {
    method: 'DELETE',
  });
}

export async function exitImpersonation(): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/v1/auth/impersonation/exit', {
    method: 'POST',
  });
}
