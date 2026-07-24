import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Switch, Text, View } from 'react-native';

import { Button, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useUpdateWarehouse, useWarehouses } from '@/src/hooks/useInventory';
import { useToast } from '@/src/toast/ToastContext';
import { theme } from '@/src/theme';

export default function EditWarehouseScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const warehouseId = Number(id);
  const query = useWarehouses();
  const mutation = useUpdateWarehouse(warehouseId);
  const toast = useToast();
  const warehouse = query.data?.find((item) => item.id === warehouseId);
  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [isDefault, setIsDefault] = useState(false);

  useEffect(() => {
    if (warehouse) {
      setName(warehouse.name);
      setAddress(warehouse.address ?? '');
      setIsDefault(warehouse.is_default);
    }
  }, [warehouse]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          address: address.trim() || null,
          is_default: isDefault,
        });
        toast.show('Warehouse updated');
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update warehouse.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (query.isLoading) {
    return <LoadingState />;
  }

  if (!warehouse) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit warehouse' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Input label="Address" value={address} onChangeText={setAddress} />
        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>Default warehouse</Text>
          <Switch value={isDefault} onValueChange={setIsDefault} />
        </View>
        <Button label="Save changes" loading={mutation.isPending} onPress={handleSubmit} />
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
