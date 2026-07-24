import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import type {
  OrganizationMember,
  OrganizationMemberPayload,
  PermissionGroups,
  Role,
  RolePayload,
} from '@/src/api/types';

export async function fetchTeamMembers(
  organizationId?: number | null,
): Promise<OrganizationMember[]> {
  const response = await apiRequestPaginated<OrganizationMember[]>('/v1/users', {
    organizationId,
  });

  return response.data;
}

export async function createTeamMember(
  payload: OrganizationMemberPayload,
  organizationId?: number | null,
): Promise<OrganizationMember> {
  return apiRequest<OrganizationMember>('/v1/users', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateTeamMemberRole(
  userId: number,
  role: string,
  organizationId?: number | null,
): Promise<OrganizationMember> {
  return apiRequest<OrganizationMember>(`/v1/users/${userId}`, {
    method: 'PATCH',
    body: { role },
    organizationId,
  });
}

export async function deleteTeamMember(
  userId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/users/${userId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function fetchRoles(organizationId?: number | null): Promise<Role[]> {
  return apiRequest<Role[]>('/v1/roles', { organizationId });
}

export async function fetchPermissionGroups(
  organizationId?: number | null,
): Promise<PermissionGroups> {
  return apiRequest<PermissionGroups>('/v1/roles/permissions', { organizationId });
}

export async function createRole(
  payload: RolePayload,
  organizationId?: number | null,
): Promise<Role> {
  return apiRequest<Role>('/v1/roles', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateRole(
  roleId: number,
  payload: RolePayload,
  organizationId?: number | null,
): Promise<Role> {
  return apiRequest<Role>(`/v1/roles/${roleId}`, {
    method: 'PATCH',
    body: payload,
    organizationId,
  });
}

export async function deleteRole(
  roleId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/roles/${roleId}`, {
    method: 'DELETE',
    organizationId,
  });
}
