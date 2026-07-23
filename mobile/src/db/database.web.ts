import { clearOrganizationMemoryCache } from '@/src/db/memoryStore.web';

export async function getDatabase(): Promise<null> {
  return null;
}

export async function clearOrganizationCache(organizationId: number): Promise<void> {
  clearOrganizationMemoryCache(organizationId);
}
