import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, TextInput } from 'react-native';

import { ApiError } from '@/src/api/client';
import { useCreateUnit } from '@/src/hooks/useCatalog';

export default function NewUnitScreen() {
  const router = useRouter();
  const mutation = useCreateUnit();
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');

  return (
    <>
      <Stack.Screen options={{ title: 'New unit' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput value={name} onChangeText={setName} placeholder="Piece" style={styles.input} />

        <Text style={styles.label}>Symbol</Text>
        <TextInput value={symbol} onChangeText={setSymbol} placeholder="pcs" style={styles.input} autoCapitalize="none" />

        <Pressable
          disabled={mutation.isPending}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({ name: name.trim(), symbol: symbol.trim() });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not create unit.';
                Alert.alert('Create failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Create unit'}</Text>
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
  input: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
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
