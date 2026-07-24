import { Stack } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet } from 'react-native';

import {
  Button,
  Card,
  DetailRow,
  ErrorState,
  Input,
  LoadingState,
  ScreenContainer,
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

  if (query.isLoading) {
    return (
      <ScreenContainer>
        <Stack.Screen options={{ title: 'Organization' }} />
        <LoadingState />
      </ScreenContainer>
    );
  }

  if (query.isError) {
    return (
      <ScreenContainer>
        <Stack.Screen options={{ title: 'Organization' }} />
        <ErrorState message="Could not load organization." />
      </ScreenContainer>
    );
  }

  if (!org) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Organization' }} />
      <ScreenScrollView>
        <Card style={styles.card}>
          <DetailRow label="Plan" value={org.plan} />
          <DetailRow label="Status" value={org.status} />
          {org.slug ? <DetailRow label="Slug" value={org.slug} /> : null}
          {org.users_count !== undefined ? (
            <DetailRow label="Team members" value={String(org.users_count)} />
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
    </>
  );
}

const styles = StyleSheet.create({
  card: {
    marginBottom: theme.spacing.lg,
  },
});
