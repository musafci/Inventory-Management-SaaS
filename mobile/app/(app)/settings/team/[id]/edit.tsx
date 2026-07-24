import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert } from 'react-native';

import { Button, ChipSelect, DetailRow, FormScreen, LoadingState } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useTeamMembers, useUpdateTeamMemberRole, useRoles } from '@/src/hooks/useTeam';

export default function EditTeamMemberScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const userId = Number(id);
  const membersQuery = useTeamMembers();
  const rolesQuery = useRoles();
  const mutation = useUpdateTeamMemberRole();
  const member = membersQuery.data?.find((item) => item.id === userId);
  const roles = rolesQuery.data ?? [];
  const [role, setRole] = useState('');

  useEffect(() => {
    if (member?.role) {
      setRole(member.role);
    }
  }, [member?.role]);

  const handleSubmit = () => {
    void (async () => {
      try {
        await mutation.mutateAsync({ userId, role });
        router.back();
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not update member.';
        Alert.alert('Update failed', message);
      }
    })();
  };

  if (membersQuery.isLoading) {
    return <LoadingState />;
  }

  if (!member) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit team member' }} />
      <FormScreen>
        <DetailRow label="Name" value={member.name} />
        <DetailRow label="Email" value={member.email} />
        <ChipSelect
          label="Role"
          options={roles.map((item) => ({ label: item.name, value: item.name }))}
          value={role}
          onChange={setRole}
        />
        <Button
          disabled={!role}
          label="Save changes"
          loading={mutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}
