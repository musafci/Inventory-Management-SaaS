import { Stack } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Text } from 'react-native';

import {
  Button,
  Card,
  DetailRow,
  ErrorState,
  Input,
  LoadingState,
  ScreenScrollView,
} from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';
import { useOrganization, useUpdateOrganization } from '@/src/hooks/useOrganization';
import { canUpdateOrganization } from '@/src/permissions';
import { theme } from '@/src/theme';

export default function OrganizationSettingsScreen() {
  const { permissions } = useAuth();
  const query = useOrganization();
  const updateMutation = useUpdateOrganization();
  const org = query.data;
  const canEdit = canUpdateOrganization(permissions);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');

  useEffect(() => {
    if (org) {
      setName(org.name);
      setEmail(org.email);
      setPhone(org.phone ?? '');
    }
  }, [org]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await updateMutation.mutateAsync({
          name: name.trim(),
          email: email.trim(),
          phone: phone.trim() || null,
        });
        Alert.alert('Saved', 'Organization updated.');
      } catch (error) {
        const message = error instanceof ApiError
          ? error.message
          : 'Could not update organization.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Organization' }} />
      {query.isLoading ? (
        <LoadingState />
      ) : query.isError ? (
        <ErrorState message="Could not load organization." />
      ) : org ? (
        <ScreenScrollView>
          <Card style={styles.card}>
            <DetailRow label="Plan" value={org.plan} />
            <Text style={styles.meta}>Status: {org.status}</Text>
            {org.slug ? <Text style={styles.meta}>Slug: {org.slug}</Text> : null}
            {org.users_count !== undefined ? (
              <Text style={styles.meta}>{org.users_count} team members</Text>
            ) : null}
          </Card>

          {canEdit ? (
            <Card style={styles.card}>
              <Input label="Name" value={name} onChangeText={setName} />
              <Input
                autoCapitalize="none"
                keyboardType="email-address"
                label="Email"
                value={email}
                onChangeText={setEmail}
              />
              <Input
                keyboardType="phone-pad"
                label="Phone"
                value={phone}
                onChangeText={setPhone}
              />
              <Button
                label="Save changes"
                loading={updateMutation.isPending}
                onPress={handleSubmit}
              />
            </Card>
          ) : (
            <Card style={styles.card}>
              <DetailRow label="Name" value={org.name} />
              <DetailRow label="Email" value={org.email} />
              {org.phone ? <DetailRow label="Phone" value={org.phone} /> : null}
            </Card>
          )}
        </ScreenScrollView>
      ) : null}
    </>
  );
}

const styles = StyleSheet.create({
  card: {
    marginBottom: theme.spacing.lg,
  },
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
  },
});
