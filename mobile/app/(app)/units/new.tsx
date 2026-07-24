import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateUnit } from '@/src/hooks/useCatalog';

export default function NewUnitScreen() {
  const router = useRouter();
  const mutation = useCreateUnit();
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({ name: name.trim(), symbol: symbol.trim() });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not create unit.';
        Alert.alert('Create failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'New unit' }} />
      <FormScreen>
        <Input label="Name" placeholder="Piece" value={name} onChangeText={setName} />
        <Input
          autoCapitalize="none"
          label="Symbol"
          placeholder="pcs"
          value={symbol}
          onChangeText={setSymbol}
        />
        <Button label="Create unit" loading={mutation.isPending} onPress={handleSubmit} />
      </FormScreen>
    </>
  );
}
