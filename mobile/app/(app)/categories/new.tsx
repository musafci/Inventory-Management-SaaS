import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateCategory } from '@/src/hooks/useCatalog';

export default function NewCategoryScreen() {
  const router = useRouter();
  const mutation = useCreateCategory();
  const [name, setName] = useState('');

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({ name: name.trim() });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not create category.';
        Alert.alert('Create failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'New category' }} />
      <FormScreen>
        <Input
          label="Name"
          placeholder="Category name"
          value={name}
          onChangeText={setName}
        />
        <Button
          label="Create category"
          loading={mutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}
