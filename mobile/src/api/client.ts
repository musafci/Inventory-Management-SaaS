import { getApiBaseUrl } from '@/src/api/config';
import type { ApiEnvelope, ApiErrorBody, PaginatedResponse } from '@/src/api/types';
import * as authStorage from '@/src/auth/storage';

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

type RequestOptions = {
  method?: string;
  body?: unknown;
  organizationId?: number | null;
  accessToken?: string | null;
  idempotencyKey?: string;
};

export async function apiRequest<T>(
  path: string,
  {
    method = 'GET',
    body,
    organizationId,
    accessToken,
    idempotencyKey,
  }: RequestOptions = {},
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const token = accessToken ?? (await authStorage.getAccessToken());
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const orgId = organizationId ?? (await authStorage.getOrganizationId());
  if (orgId !== null && orgId !== undefined) {
    headers['X-Organization-Id'] = String(orgId);
  }

  if (idempotencyKey) {
    headers['Idempotency-Key'] = idempotencyKey;
  }

  const response = await fetch(`${getApiBaseUrl()}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = (await response.json()) as ApiEnvelope<T> | ApiErrorBody;

  if (!response.ok) {
    const errorPayload = payload as ApiErrorBody;

    throw new ApiError(
      errorPayload.message ?? 'Request failed.',
      response.status,
      errorPayload.errors ?? {},
    );
  }

  return (payload as ApiEnvelope<T>).data;
}

export async function apiRequestPaginated<T>(
  path: string,
  options: RequestOptions = {},
): Promise<PaginatedResponse<T>> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  const { method = 'GET', body, organizationId, accessToken, idempotencyKey } = options;

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const token = accessToken ?? (await authStorage.getAccessToken());
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const orgId = organizationId ?? (await authStorage.getOrganizationId());
  if (orgId !== null && orgId !== undefined) {
    headers['X-Organization-Id'] = String(orgId);
  }

  if (idempotencyKey) {
    headers['Idempotency-Key'] = idempotencyKey;
  }

  const response = await fetch(`${getApiBaseUrl()}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const payload = (await response.json()) as ApiEnvelope<T> | ApiErrorBody;

  if (!response.ok) {
    const errorPayload = payload as ApiErrorBody;

    throw new ApiError(
      errorPayload.message ?? 'Request failed.',
      response.status,
      errorPayload.errors ?? {},
    );
  }

  const envelope = payload as ApiEnvelope<T>;
  const pagination = envelope.meta?.pagination;

  return {
    data: envelope.data,
    meta: {
      pagination: pagination ?? {
        current_page: 1,
        per_page: 0,
        total: 0,
        last_page: 1,
      },
    },
  };
}
