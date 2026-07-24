import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useUnitsList, useUpdateUnit } from '@/src/hooks/useCatalog';
import { useToast } from '@/src/toast/ToastContext';

export default function EditUnitScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const unitId = Number(id);
  const query = useUnitsList();
  const mutation = useUpdateUnit(unitId);
  const toast = useToast();
  const unit = query.data?.find((item) => item.id === unitId);
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');

  useEffect(() => {
    if (unit) {
      setName(unit.name);
      setSymbol(unit.symbol);
    }
  }, [unit]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({ name: name.trim(), symbol: symbol.trim() });
        toast.show('Unit updated');
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update unit.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (query.isLoading) {
    return <LoadingState />;
  }

  if (!unit) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit unit' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Input autoCapitalize="none" label="Symbol" value={symbol} onChangeText={setSymbol} />
        <Button label="Save changes" loading={mutation.isPending} onPress={handleSubmit} />
      </FormScreen>
    </>
  );
}
