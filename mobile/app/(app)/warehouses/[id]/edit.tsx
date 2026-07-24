import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import { useUpdateWarehouse, useWarehouses } from '@/src/hooks/useInventory';

export default function EditWarehouseScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const warehouseId = Number(id);
  const query = useWarehouses();
  const mutation = useUpdateWarehouse(warehouseId);
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

  if (query.isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!warehouse) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit warehouse' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput value={name} onChangeText={setName} style={styles.input} />

        <Text style={styles.label}>Address</Text>
        <TextInput value={address} onChangeText={setAddress} style={styles.input} />

        <View style={styles.switchRow}>
          <Text style={styles.labelInline}>Default warehouse</Text>
          <Switch value={isDefault} onValueChange={setIsDefault} />
        </View>

        <Pressable
          disabled={mutation.isPending}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({
                  name: name.trim(),
                  address: address.trim() || null,
                  is_default: isDefault,
                });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not update warehouse.';
                Alert.alert('Update failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Save changes'}</Text>
        </Pressable>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  loading: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
  },
  container: {
    padding: 16,
  },
  label: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginTop: 12,
  },
  labelInline: {
    color: '#334155',
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
  input: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    marginTop: 16,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    marginTop: 24,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
});
