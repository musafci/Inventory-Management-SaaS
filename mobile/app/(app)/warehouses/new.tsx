import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, StyleSheet, Switch, Text, View } from 'react-native';

import { Button, FormScreen, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateWarehouse } from '@/src/hooks/useInventory';
import { useToast } from '@/src/toast/ToastContext';
import { theme } from '@/src/theme';

export default function NewWarehouseScreen() {
  const router = useRouter();
  const mutation = useCreateWarehouse();
  const toast = useToast();
  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [isDefault, setIsDefault] = useState(false);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          address: address.trim() || null,
          is_default: isDefault,
        });
        toast.show('Warehouse created');
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not create warehouse.';
        Alert.alert('Create failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'New warehouse' }} />
      <FormScreen>
        <Input
          label="Name"
          placeholder="Warehouse name"
          value={name}
          onChangeText={setName}
        />
        <Input
          label="Address"
          placeholder="Optional address"
          value={address}
          onChangeText={setAddress}
        />
        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>Default warehouse</Text>
          <Switch value={isDefault} onValueChange={setIsDefault} />
        </View>
        <Button label="Create warehouse" loading={mutation.isPending} onPress={handleSubmit} />
      </FormScreen>
    </>
  );
}

const styles = StyleSheet.create({
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.md,
    marginTop: theme.spacing.sm,
  },
  switchLabel: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    flex: 1,
    fontWeight: '600',
  },
});
