import * as WebBrowser from 'expo-web-browser';
import { Stack } from 'expo-router';
import { Alert, StyleSheet, Text, View } from 'react-native';

import {
  Button,
  Card,
  DetailRow,
  ErrorState,
  LoadingState,
  ScreenContainer,
  ScreenScrollView,
  SectionHeader,
} from '@/components/ui';
import { ApiError } from '@/src/api/client';
import {
  useBillingOverview,
  useBillingPortalSession,
  useCheckoutSession,
} from '@/src/hooks/useBilling';
import { theme } from '@/src/theme';

export default function BillingSettingsScreen() {
  const query = useBillingOverview();
  const checkoutMutation = useCheckoutSession();
  const portalMutation = useBillingPortalSession();
  const billing = query.data;
  const subscription = billing?.subscription;
  const currentPlan = subscription?.plan;

  const openCheckout = (planSlug: string, interval: 'monthly' | 'annual') => {
    void (async () => {
      try {
        const session = await checkoutMutation.mutateAsync({
          planSlug,
          interval,
        });
        await WebBrowser.openBrowserAsync(session.url);
        void query.refetch();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not start checkout.';
        Alert.alert('Checkout failed', message);
      }
    })();
  };

  const openPortal = () => {
    void (async () => {
      try {
        const session = await portalMutation.mutateAsync();
        await WebBrowser.openBrowserAsync(session.url);
        void query.refetch();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not open billing portal.';
        Alert.alert('Portal failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Billing' }} />
      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : query.isError ? (
        <ScreenContainer><ErrorState message="Could not load billing information." /></ScreenContainer>
      ) : billing ? (
        <ScreenScrollView>
          <Card>
            <Text style={styles.cardLabel}>Current subscription</Text>
            {subscription ? (
              <>
                <Text style={styles.cardValue}>{currentPlan?.name ?? 'Active plan'}</Text>
                <DetailRow label="Status" value={subscription.status} />
                {subscription.billing_interval ? (
                  <DetailRow label="Interval" value={subscription.billing_interval} />
                ) : null}
                {subscription.current_period_ends_at ? (
                  <DetailRow label="Renews" value={subscription.current_period_ends_at} />
                ) : null}
                {subscription.trial_ends_at ? (
                  <DetailRow label="Trial ends" value={subscription.trial_ends_at} />
                ) : null}
              </>
            ) : (
              <Text style={styles.meta}>No active subscription.</Text>
            )}
          </Card>

          {!billing.stripe_configured ? (
            <Card muted>
              <Text style={styles.meta}>Stripe billing is not configured for this environment.</Text>
            </Card>
          ) : null}

          {subscription && billing.stripe_configured ? (
            <Button
              disabled={portalMutation.isPending}
              label={portalMutation.isPending ? 'Opening…' : 'Manage subscription'}
              loading={portalMutation.isPending}
              variant="secondary"
              onPress={openPortal}
            />
          ) : null}

          <SectionHeader title="Available plans" />
          {billing.available_plans.map((plan) => (
            <Card key={plan.id} style={styles.planCard}>
              <Text style={styles.planName}>{plan.name}</Text>
              <Text style={styles.planPrice}>
                {plan.price_monthly}/mo · {plan.price_annual}/yr
                {plan.is_custom ? ' · Custom' : ''}
              </Text>
              {billing.stripe_configured && !plan.is_custom ? (
                <View style={styles.planActions}>
                  <Button
                    disabled={checkoutMutation.isPending}
                    label={currentPlan?.slug === plan.slug ? 'Current (monthly)' : 'Monthly'}
                    onPress={() => openCheckout(plan.slug, 'monthly')}
                    style={styles.planButton}
                  />
                  <Button
                    disabled={checkoutMutation.isPending}
                    label={currentPlan?.slug === plan.slug ? 'Current (yearly)' : 'Yearly'}
                    variant="secondary"
                    onPress={() => openCheckout(plan.slug, 'annual')}
                    style={styles.planButton}
                  />
                </View>
              ) : null}
            </Card>
          ))}
        </ScreenScrollView>
      ) : null}
    </>
  );
}

const styles = StyleSheet.create({
  cardLabel: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.sm,
  },
  cardValue: {
    ...theme.typography.heading,
    color: theme.colors.text,
    marginBottom: theme.spacing.md,
  },
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
  },
  planCard: {
    marginBottom: theme.spacing.md,
  },
  planName: {
    ...theme.typography.heading,
    color: theme.colors.text,
  },
  planPrice: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
  },
  planActions: {
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginTop: theme.spacing.md,
  },
  planButton: {
    flex: 1,
    minHeight: 44,
  },
});
