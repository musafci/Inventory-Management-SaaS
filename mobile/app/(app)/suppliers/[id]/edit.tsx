import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useSuppliers, useSuppliersList, useUpdateSupplier } from '@/src/hooks/usePartners';

export default function EditSupplierScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const supplierId = Number(id);
  const query = useSuppliers('');
  const mutation = useUpdateSupplier(supplierId);
  const supplier = useSuppliersList('').find((item) => item.id === supplierId);
  const [name, setName] = useState('');
  const [contactPerson, setContactPerson] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  useEffect(() => {
    if (supplier) {
      setName(supplier.name);
      setContactPerson(supplier.contact_person ?? '');
      setEmail(supplier.email ?? '');
      setPhone(supplier.phone ?? '');
      setAddress(supplier.address ?? '');
    }
  }, [supplier]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          contact_person: contactPerson.trim() || null,
          email: email.trim() || null,
          phone: phone.trim() || null,
          address: address.trim() || null,
        });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update supplier.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (query.isLoading) {
    return <LoadingState />;
  }

  if (!supplier) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit supplier' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Input label="Contact person" value={contactPerson} onChangeText={setContactPerson} />
        <Input
          autoCapitalize="none"
          keyboardType="email-address"
          label="Email"
          value={email}
          onChangeText={setEmail}
        />
        <Input keyboardType="phone-pad" label="Phone" value={phone} onChangeText={setPhone} />
        <Input label="Address" multiline value={address} onChangeText={setAddress} />
        <Button label="Save changes" loading={mutation.isPending} onPress={handleSubmit} />
      </FormScreen>
    </>
  );
}
