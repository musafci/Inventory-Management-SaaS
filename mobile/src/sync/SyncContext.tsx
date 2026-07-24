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

import { useAuth } from '@/src/auth/AuthContext';
import { clearOrganizationCache, getDatabase } from '@/src/db/database';
import {
  countPendingMutations,
  listFailedMutations,
  removeMutation,
  retryFailedMutation,
} from '@/src/db/outbox';
import { getLastSyncedAt } from '@/src/db/syncMetadata';
import type { OutboxMutation } from '@/src/db/types';
import { useNetwork } from '@/src/network/NetworkContext';
import { processOutbox } from '@/src/sync/processOutbox';
import { pullAllCaches } from '@/src/sync/pullInventory';
import { registerBackgroundSync, unregisterBackgroundSync } from '@/src/sync/backgroundSync';

type SyncContextValue = {
  isReady: boolean;
  isSyncing: boolean;
  pendingOutboxCount: number;
  failedMutations: OutboxMutation[];
  lastSyncedAt: string | null;
  syncNow: () => Promise<void>;
  retryMutation: (id: number) => Promise<void>;
  dismissMutation: (id: number) => Promise<void>;
};

const SyncContext = createContext<SyncContextValue | null>(null);

export function SyncProvider({ children }: { children: ReactNode }) {
  const { organizationId, isAuthenticated } = useAuth();
  const { isConnected } = useNetwork();
  const [isReady, setIsReady] = useState(false);
  const [isSyncing, setIsSyncing] = useState(false);
  const [pendingOutboxCount, setPendingOutboxCount] = useState(0);
  const [failedMutations, setFailedMutations] = useState<OutboxMutation[]>([]);
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
    const [pendingCount, failed, productsSyncedAt] = await Promise.all([
      countPendingMutations(orgId),
      listFailedMutations(orgId),
      getLastSyncedAt(orgId, 'products'),
    ]);

    setPendingOutboxCount(pendingCount);
    setFailedMutations(failed);
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

  useEffect(() => {
    if (!isAuthenticated) {
      void unregisterBackgroundSync();
      return;
    }

    void registerBackgroundSync();
  }, [isAuthenticated]);

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

  const retryMutation = useCallback(async (id: number) => {
    await retryFailedMutation(id);

    if (organizationId !== null) {
      await refreshSyncState(organizationId);
      await syncNow();
    }
  }, [organizationId, refreshSyncState, syncNow]);

  const dismissMutation = useCallback(async (id: number) => {
    await removeMutation(id);

    if (organizationId !== null) {
      await refreshSyncState(organizationId);
    }
  }, [organizationId, refreshSyncState]);

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
      failedMutations,
      lastSyncedAt,
      syncNow,
      retryMutation,
      dismissMutation,
    }),
    [
      dismissMutation,
      failedMutations,
      isReady,
      isSyncing,
      lastSyncedAt,
      pendingOutboxCount,
      retryMutation,
      syncNow,
    ],
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
