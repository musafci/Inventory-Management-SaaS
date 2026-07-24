import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as notificationsApi from '@/src/api/notifications';
import { useAuth } from '@/src/auth/AuthContext';

export function useNotificationPreferences() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['notification-preferences', organizationId],
    enabled: organizationId !== null,
    queryFn: () => notificationsApi.fetchNotificationPreferences(organizationId),
  });
}

export function useUpdateNotificationPreferences() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (preferences: Record<string, boolean>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return notificationsApi.updateNotificationPreferences(preferences, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['notification-preferences'] });
    },
  });
}
