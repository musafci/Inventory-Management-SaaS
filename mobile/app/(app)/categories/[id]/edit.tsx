import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCategoriesList, useUpdateCategory } from '@/src/hooks/useCatalog';

export default function EditCategoryScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const categoryId = Number(id);
  const query = useCategoriesList();
  const mutation = useUpdateCategory(categoryId);
  const category = query.data?.find((item) => item.id === categoryId);
  const [name, setName] = useState('');

  useEffect(() => {
    if (category) {
      setName(category.name);
    }
  }, [category]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({ name: name.trim() });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update category.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (query.isLoading) {
    return <LoadingState />;
  }

  if (!category) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit category' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Button
          label="Save changes"
          loading={mutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}
