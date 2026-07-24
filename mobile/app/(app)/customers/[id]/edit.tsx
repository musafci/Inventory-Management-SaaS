import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert } from 'react-native';

import { Button, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCustomers, useCustomersList, useUpdateCustomer } from '@/src/hooks/usePartners';
import { useToast } from '@/src/toast/ToastContext';

export default function EditCustomerScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const customerId = Number(id);
  const query = useCustomers('');
  const mutation = useUpdateCustomer(customerId);
  const toast = useToast();
  const customer = useCustomersList('').find((item) => item.id === customerId);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  useEffect(() => {
    if (customer) {
      setName(customer.name);
      setEmail(customer.email ?? '');
      setPhone(customer.phone ?? '');
      setAddress(customer.address ?? '');
    }
  }, [customer]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          email: email.trim() || null,
          phone: phone.trim() || null,
          address: address.trim() || null,
        });
        toast.show('Customer updated');
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update customer.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (query.isLoading) {
    return <LoadingState />;
  }

  if (!customer) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit customer' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
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
