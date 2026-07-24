import { apiRequest } from '@/src/api/client';
import type { BillingOverview, CheckoutSession } from '@/src/api/types';

export async function fetchBillingOverview(
  organizationId?: number | null,
): Promise<BillingOverview> {
  return apiRequest<BillingOverview>('/v1/billing', { organizationId });
}

export async function createCheckoutSession(
  planSlug: string,
  interval: 'monthly' | 'annual',
  organizationId?: number | null,
): Promise<CheckoutSession> {
  return apiRequest<CheckoutSession>('/v1/billing/checkout', {
    method: 'POST',
    body: { plan_slug: planSlug, interval },
    organizationId,
  });
}

export async function createBillingPortalSession(
  organizationId?: number | null,
): Promise<CheckoutSession> {
  return apiRequest<CheckoutSession>('/v1/billing/portal', {
    method: 'POST',
    organizationId,
  });
}
