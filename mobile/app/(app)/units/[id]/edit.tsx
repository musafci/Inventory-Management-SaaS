import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import { ApiError } from '@/src/api/client';
import { useUnitsList, useUpdateUnit } from '@/src/hooks/useCatalog';

export default function EditUnitScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const unitId = Number(id);
  const query = useUnitsList();
  const mutation = useUpdateUnit(unitId);
  const unit = query.data?.find((item) => item.id === unitId);
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');

  useEffect(() => {
    if (unit) {
      setName(unit.name);
      setSymbol(unit.symbol);
    }
  }, [unit]);

  if (query.isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!unit) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit unit' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput value={name} onChangeText={setName} style={styles.input} />

        <Text style={styles.label}>Symbol</Text>
        <TextInput value={symbol} onChangeText={setSymbol} style={styles.input} autoCapitalize="none" />

        <Pressable
          disabled={mutation.isPending}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({ name: name.trim(), symbol: symbol.trim() });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not update unit.';
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
