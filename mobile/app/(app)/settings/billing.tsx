import * as WebBrowser from 'expo-web-browser';
import { Stack } from 'expo-router';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import {
  useBillingOverview,
  useBillingPortalSession,
  useCheckoutSession,
} from '@/src/hooks/useBilling';

export default function BillingSettingsScreen() {
  const query = useBillingOverview();
  const checkoutMutation = useCheckoutSession();
  const portalMutation = useBillingPortalSession();
  const billing = query.data;
  const subscription = billing?.subscription;
  const currentPlan = subscription?.plan;

  const openCheckout = (planSlug: string) => {
    void (async () => {
      try {
        const session = await checkoutMutation.mutateAsync({
          planSlug,
          interval: 'monthly',
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
      <ScrollView contentContainerStyle={styles.container}>
        {query.isLoading ? (
          <ActivityIndicator size="large" style={styles.loader} />
        ) : query.isError ? (
          <Text style={styles.error}>Could not load billing information.</Text>
        ) : billing ? (
          <>
            <View style={styles.card}>
              <Text style={styles.cardLabel}>Current subscription</Text>
              {subscription ? (
                <>
                  <Text style={styles.cardValue}>{currentPlan?.name ?? 'Active plan'}</Text>
                  <Text style={styles.meta}>Status: {subscription.status}</Text>
                  {subscription.billing_interval ? (
                    <Text style={styles.meta}>Interval: {subscription.billing_interval}</Text>
                  ) : null}
                  {subscription.current_period_ends_at ? (
                    <Text style={styles.meta}>
                      Renews: {subscription.current_period_ends_at}
                    </Text>
                  ) : null}
                  {subscription.trial_ends_at ? (
                    <Text style={styles.meta}>Trial ends: {subscription.trial_ends_at}</Text>
                  ) : null}
                </>
              ) : (
                <Text style={styles.meta}>No active subscription.</Text>
              )}
            </View>

            {!billing.stripe_configured ? (
              <View style={styles.card}>
                <Text style={styles.meta}>Stripe billing is not configured for this environment.</Text>
              </View>
            ) : null}

            {subscription && billing.stripe_configured ? (
              <Pressable
                disabled={portalMutation.isPending}
                onPress={openPortal}
                style={[styles.button, styles.secondaryButton]}>
                <Text style={styles.secondaryButtonText}>
                  {portalMutation.isPending ? 'Opening…' : 'Manage subscription'}
                </Text>
              </Pressable>
            ) : null}

            <Text style={styles.sectionTitle}>Available plans</Text>
            {billing.available_plans.map((plan) => (
              <View key={plan.id} style={styles.planCard}>
                <Text style={styles.planName}>{plan.name}</Text>
                <Text style={styles.planPrice}>
                  {plan.price_monthly}/mo
                  {plan.is_custom ? ' · Custom' : ''}
                </Text>
                {billing.stripe_configured && !plan.is_custom ? (
                  <Pressable
                    disabled={checkoutMutation.isPending}
                    onPress={() => openCheckout(plan.slug)}
                    style={styles.upgradeButton}>
                    <Text style={styles.upgradeButtonText}>
                      {currentPlan?.slug === plan.slug ? 'Current plan' : 'Upgrade (monthly)'}
                    </Text>
                  </Pressable>
                ) : null}
              </View>
            ))}
          </>
        ) : null}
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 16,
    paddingBottom: 40,
  },
  loader: {
    marginTop: 32,
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
    padding: 16,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    padding: 16,
  },
  cardLabel: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  cardValue: {
    color: '#0f172a',
    fontSize: 20,
    fontWeight: '700',
    marginTop: 8,
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
  sectionTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 12,
    marginTop: 8,
  },
  planCard: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  planName: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  planPrice: {
    color: '#64748b',
    fontSize: 14,
    marginTop: 6,
  },
  button: {
    alignItems: 'center',
    borderRadius: 10,
    marginBottom: 16,
    paddingVertical: 14,
  },
  secondaryButton: {
    backgroundColor: '#fff',
    borderColor: '#2563eb',
    borderWidth: 1,
  },
  secondaryButtonText: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '700',
  },
  upgradeButton: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 8,
    marginTop: 12,
    paddingVertical: 10,
  },
  upgradeButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
  },
});
