import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as authApi from '@/src/api/auth';
import { useAuth } from '@/src/auth/AuthContext';

export function useSessions() {
  const { isAuthenticated } = useAuth();

  return useQuery({
    queryKey: ['auth-sessions'],
    enabled: isAuthenticated,
    queryFn: () => authApi.fetchSessions(),
  });
}

export function useRevokeSession() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (tokenId: string) => authApi.revokeSession(tokenId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['auth-sessions'] });
    },
  });
}
