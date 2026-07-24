import { Link } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { EmptyState } from '@/components/ui';
import { theme } from '@/src/theme';

type EmptyWarehousesPromptProps = {
  message: string;
  canCreate?: boolean;
};

export function EmptyWarehousesPrompt({ message, canCreate = true }: EmptyWarehousesPromptProps) {
  return (
    <View style={styles.container}>
      <EmptyState body={message} title="No warehouses yet" />
      {canCreate ? (
        <Link href="/(app)/warehouses/new" style={styles.link}>
          Add warehouse
        </Link>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: theme.spacing.xxl,
  },
  link: {
    color: theme.colors.primary,
    fontSize: 16,
    fontWeight: '600',
    marginTop: theme.spacing.lg,
  },
});
