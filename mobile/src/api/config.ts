const DEFAULT_API_URL = 'http://localhost:8000/api';

export function getApiBaseUrl(): string {
  const configured = process.env.EXPO_PUBLIC_API_URL?.trim();

  return configured && configured.length > 0
    ? configured.replace(/\/$/, '')
    : DEFAULT_API_URL;
}
