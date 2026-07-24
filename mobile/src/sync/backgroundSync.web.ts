import * as BackgroundFetch from 'expo-background-fetch';
import * as TaskManager from 'expo-task-manager';

export const BACKGROUND_SYNC_TASK = 'inventory-background-sync';

export async function registerBackgroundSync(): Promise<void> {
  // Background fetch is not supported on web preview.
}

export async function unregisterBackgroundSync(): Promise<void> {
  // Background fetch is not supported on web preview.
}

// Keep task name available for native module without registering on web.
void BackgroundFetch;
void TaskManager;
