import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as teamApi from '@/src/api/team';
import type { OrganizationMemberPayload, RolePayload } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';

export function useTeamMembers() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['team-members', organizationId],
    enabled: organizationId !== null,
    queryFn: () => teamApi.fetchTeamMembers(organizationId),
  });
}

export function useCreateTeamMember() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: OrganizationMemberPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.createTeamMember(payload, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['team-members'] });
    },
  });
}

export function useUpdateTeamMemberRole() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: ({ userId, role }: { userId: number; role: string }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.updateTeamMemberRole(userId, role, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['team-members'] });
    },
  });
}

export function useDeleteTeamMember() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (userId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.deleteTeamMember(userId, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['team-members'] });
    },
  });
}

export function useRoles() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['roles', organizationId],
    enabled: organizationId !== null,
    queryFn: () => teamApi.fetchRoles(organizationId),
  });
}

export function usePermissionGroups() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['permission-groups', organizationId],
    enabled: organizationId !== null,
    queryFn: () => teamApi.fetchPermissionGroups(organizationId),
  });
}

export function useCreateRole() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: RolePayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.createRole(payload, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['roles'] });
    },
  });
}

export function useUpdateRole(roleId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: RolePayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.updateRole(roleId, payload, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['roles'] });
    },
  });
}

export function useDeleteRole() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (roleId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return teamApi.deleteRole(roleId, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['roles'] });
    },
  });
}
