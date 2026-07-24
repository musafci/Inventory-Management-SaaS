import * as BackgroundFetch from 'expo-background-fetch';
import * as TaskManager from 'expo-task-manager';

import * as authStorage from '@/src/auth/storage';
import { processOutbox } from '@/src/sync/processOutbox';
import { pullAllCaches } from '@/src/sync/pullInventory';

export const BACKGROUND_SYNC_TASK = 'inventory-background-sync';

TaskManager.defineTask(BACKGROUND_SYNC_TASK, async () => {
  const organizationId = await authStorage.getOrganizationId();
  const accessToken = await authStorage.getAccessToken();

  if (organizationId === null || !accessToken) {
    return BackgroundFetch.BackgroundFetchResult.NoData;
  }

  try {
    await processOutbox(organizationId);
    await pullAllCaches(organizationId);

    return BackgroundFetch.BackgroundFetchResult.NewData;
  } catch {
    return BackgroundFetch.BackgroundFetchResult.Failed;
  }
});

export async function registerBackgroundSync(): Promise<void> {
  const isRegistered = await TaskManager.isTaskRegisteredAsync(BACKGROUND_SYNC_TASK);

  if (!isRegistered) {
    await BackgroundFetch.registerTaskAsync(BACKGROUND_SYNC_TASK, {
      minimumInterval: 15 * 60,
      stopOnTerminate: false,
      startOnBoot: true,
    });
  }
}

export async function unregisterBackgroundSync(): Promise<void> {
  const isRegistered = await TaskManager.isTaskRegisteredAsync(BACKGROUND_SYNC_TASK);

  if (isRegistered) {
    await BackgroundFetch.unregisterTaskAsync(BACKGROUND_SYNC_TASK);
  }
}
