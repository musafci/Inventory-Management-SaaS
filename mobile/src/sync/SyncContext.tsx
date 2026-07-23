import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';

import { apiRequest } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';
import { clearOrganizationCache, getDatabase } from '@/src/db/database';
import {
  countPendingMutations,
  listPendingMutations,
  removeMutation,
  updateMutationStatus,
} from '@/src/db/outbox';
import { getLastSyncedAt } from '@/src/db/syncMetadata';
import { useNetwork } from '@/src/network/NetworkContext';
import { pullAllCaches } from '@/src/sync/pullInventory';

type SyncContextValue = {
  isReady: boolean;
  isSyncing: boolean;
  pendingOutboxCount: number;
  lastSyncedAt: string | null;
  syncNow: () => Promise<void>;
};

const SyncContext = createContext<SyncContextValue | null>(null);

async function processOutbox(organizationId: number): Promise<void> {
  const pending = await listPendingMutations(organizationId);

  for (const mutation of pending) {
    await updateMutationStatus(mutation.id, 'processing');

    try {
      await apiRequest(mutation.path, {
        method: mutation.method,
        body: mutation.body ? JSON.parse(mutation.body) : undefined,
        organizationId,
        idempotencyKey: mutation.idempotency_key ?? undefined,
      });

      await removeMutation(mutation.id);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Sync failed.';
      await updateMutationStatus(mutation.id, 'failed', message);
      break;
    }
  }
}

export function SyncProvider({ children }: { children: ReactNode }) {
  const { organizationId, isAuthenticated } = useAuth();
  const { isConnected } = useNetwork();
  const [isReady, setIsReady] = useState(false);
  const [isSyncing, setIsSyncing] = useState(false);
  const [pendingOutboxCount, setPendingOutboxCount] = useState(0);
  const [lastSyncedAt, setLastSyncedAt] = useState<string | null>(null);
  const previousOrganizationId = useRef<number | null>(null);
  const syncInFlight = useRef(false);

  useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        await getDatabase();
      } finally {
        if (mounted) {
          setIsReady(true);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  const refreshSyncState = useCallback(async (orgId: number) => {
    const [pendingCount, productsSyncedAt] = await Promise.all([
      countPendingMutations(orgId),
      getLastSyncedAt(orgId, 'products'),
    ]);

    setPendingOutboxCount(pendingCount);
    setLastSyncedAt(productsSyncedAt);
  }, []);

  useEffect(() => {
    if (!isReady || !isAuthenticated || organizationId === null) {
      return;
    }

    if (
      previousOrganizationId.current !== null
      && previousOrganizationId.current !== organizationId
    ) {
      void clearOrganizationCache(previousOrganizationId.current);
    }

    previousOrganizationId.current = organizationId;
    void refreshSyncState(organizationId);
  }, [isAuthenticated, isReady, organizationId, refreshSyncState]);

  const syncNow = useCallback(async () => {
    if (!isReady || organizationId === null || syncInFlight.current) {
      return;
    }

    syncInFlight.current = true;
    setIsSyncing(true);

    try {
      if (isConnected) {
        await processOutbox(organizationId);
        await pullAllCaches(organizationId);
      }

      await refreshSyncState(organizationId);
    } finally {
      syncInFlight.current = false;
      setIsSyncing(false);
    }
  }, [isConnected, isReady, organizationId, refreshSyncState]);

  useEffect(() => {
    if (!isReady || !isAuthenticated || organizationId === null || !isConnected) {
      return;
    }

    void syncNow();
  }, [isAuthenticated, isConnected, isReady, organizationId, syncNow]);

  const value = useMemo<SyncContextValue>(
    () => ({
      isReady,
      isSyncing,
      pendingOutboxCount,
      lastSyncedAt,
      syncNow,
    }),
    [isReady, isSyncing, lastSyncedAt, pendingOutboxCount, syncNow],
  );

  return <SyncContext.Provider value={value}>{children}</SyncContext.Provider>;
}

export function useSync(): SyncContextValue {
  const context = useContext(SyncContext);

  if (context === null) {
    throw new Error('useSync must be used within SyncProvider');
  }

  return context;
}
