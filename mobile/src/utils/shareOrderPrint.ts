import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

import { getApiBaseUrl } from '@/src/api/config';
import * as authStorage from '@/src/auth/storage';

export async function shareOrderPrintHtml(
  path: string,
  filename: string,
  organizationId: number,
): Promise<void> {
  const headers: Record<string, string> = {
    Accept: 'text/html',
  };

  const token = await authStorage.getAccessToken();
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  headers['X-Organization-Id'] = String(organizationId);

  const response = await fetch(`${getApiBaseUrl()}${path}`, { headers });

  if (!response.ok) {
    throw new Error('Could not load printable order.');
  }

  const html = await response.text();
  const fileUri = `${FileSystem.cacheDirectory}${filename}`;

  await FileSystem.writeAsStringAsync(fileUri, html, {
    encoding: FileSystem.EncodingType.UTF8,
  });

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(fileUri, {
      mimeType: 'text/html',
      dialogTitle: 'Share order',
    });
  }
}
