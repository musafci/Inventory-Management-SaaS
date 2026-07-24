import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateCustomer } from '@/src/hooks/usePartners';

export default function NewCustomerScreen() {
  const router = useRouter();
  const mutation = useCreateCustomer();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          email: email.trim() || null,
          phone: phone.trim() || null,
          address: address.trim() || null,
        });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not create customer.';
        Alert.alert('Create failed', message);
      }
    })();
  };

  return (
    <>
      <Stack.Screen options={{ title: 'New customer' }} />
      <FormScreen>
        <Input
          label="Name"
          placeholder="Customer name"
          value={name}
          onChangeText={setName}
        />
        <Input
          autoCapitalize="none"
          keyboardType="email-address"
          label="Email"
          placeholder="Optional"
          value={email}
          onChangeText={setEmail}
        />
        <Input
          keyboardType="phone-pad"
          label="Phone"
          placeholder="Optional"
          value={phone}
          onChangeText={setPhone}
        />
        <Input
          label="Address"
          multiline
          placeholder="Optional"
          value={address}
          onChangeText={setAddress}
        />
        <Button label="Create customer" loading={mutation.isPending} onPress={handleSubmit} />
      </FormScreen>
    </>
  );
}
