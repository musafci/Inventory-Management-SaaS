import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { Button, ChipSelect, FormScreen, Input, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateTeamMember, useRoles } from '@/src/hooks/useTeam';

export default function NewTeamMemberScreen() {
  const router = useRouter();
  const rolesQuery = useRoles();
  const mutation = useCreateTeamMember();
  const roles = rolesQuery.data ?? [];

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState('');

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({
          name: name.trim(),
          email: email.trim(),
          password,
          phone: phone.trim() || null,
          role,
        });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not add team member.';
        Alert.alert('Create failed', message);
      }
    })();
  };

  if (rolesQuery.isLoading) {
    return <LoadingState />;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Add team member' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Input
          autoCapitalize="none"
          keyboardType="email-address"
          label="Email"
          value={email}
          onChangeText={setEmail}
        />
        <Input
          autoCapitalize="none"
          label="Password"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
        />
        <Input
          keyboardType="phone-pad"
          label="Phone"
          value={phone}
          onChangeText={setPhone}
        />
        <ChipSelect
          label="Role"
          options={roles.map((item) => ({ label: item.name, value: item.name }))}
          value={role}
          onChange={setRole}
        />
        <Button
          disabled={!name.trim() || !email.trim() || !password.trim() || !role}
          label="Add member"
          loading={mutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}
