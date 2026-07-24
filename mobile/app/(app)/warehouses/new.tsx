import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import {
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
import { useCreateWarehouse } from '@/src/hooks/useInventory';

export default function NewWarehouseScreen() {
  const router = useRouter();
  const mutation = useCreateWarehouse();
  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [isDefault, setIsDefault] = useState(false);

  return (
    <>
      <Stack.Screen options={{ title: 'New warehouse' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput
          value={name}
          onChangeText={setName}
          placeholder="Warehouse name"
          style={styles.input}
        />

        <Text style={styles.label}>Address</Text>
        <TextInput
          value={address}
          onChangeText={setAddress}
          placeholder="Optional address"
          style={styles.input}
        />

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
                const message = error instanceof ApiError ? error.message : 'Could not create warehouse.';
                Alert.alert('Create failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Create warehouse'}</Text>
        </Pressable>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
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
