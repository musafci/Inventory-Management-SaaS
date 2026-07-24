import { apiRequest } from '@/src/api/client';
import type {
  OrganizationDataExport,
  OrganizationDetail,
  OrganizationPayload,
} from '@/src/api/types';

export async function fetchOrganization(
  organizationId?: number | null,
): Promise<OrganizationDetail> {
  return apiRequest<OrganizationDetail>('/v1/organization', { organizationId });
}

export async function updateOrganization(
  payload: OrganizationPayload,
  organizationId?: number | null,
): Promise<OrganizationDetail> {
  return apiRequest<OrganizationDetail>('/v1/organization', {
    method: 'PATCH',
    body: payload,
    organizationId,
  });
}

export async function requestOrganizationDeletion(
  organizationId?: number | null,
): Promise<OrganizationDetail> {
  return apiRequest<OrganizationDetail>('/v1/organization/request-deletion', {
    method: 'POST',
    organizationId,
  });
}

export async function cancelOrganizationDeletion(
  organizationId?: number | null,
): Promise<OrganizationDetail> {
  return apiRequest<OrganizationDetail>('/v1/organization/cancel-deletion', {
    method: 'POST',
    organizationId,
  });
}

export async function queueOrganizationExport(
  organizationId?: number | null,
): Promise<OrganizationDataExport> {
  return apiRequest<OrganizationDataExport>('/v1/organization/export', {
    method: 'POST',
    organizationId,
  });
}
