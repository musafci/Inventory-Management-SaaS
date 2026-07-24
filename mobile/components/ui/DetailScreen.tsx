import { type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { Button } from './Button';
import { Card } from './Card';
import { LoadingState } from './ScreenState';
import { ScreenScrollView } from './ScreenScrollView';
import { theme } from '@/src/theme';

type DetailScreenProps = {
  children: ReactNode;
  loading?: boolean;
  deleteLabel?: string;
  deleteLoading?: boolean;
  onDelete?: () => void;
  showDelete?: boolean;
};

export function DetailScreen({
  children,
  loading = false,
  deleteLabel = 'Delete',
  deleteLoading = false,
  onDelete,
  showDelete = false,
}: DetailScreenProps) {
  if (loading) {
    return <LoadingState />;
  }

  return (
    <ScreenScrollView contentContainerStyle={styles.content}>
      <Card>{children}</Card>
      {showDelete && onDelete ? (
        <View style={styles.footer}>
          <Button
            label={deleteLoading ? 'Deleting…' : deleteLabel}
            loading={deleteLoading}
            onPress={onDelete}
            variant="danger"
          />
        </View>
      ) : null}
    </ScreenScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    paddingTop: theme.spacing.lg,
  },
  footer: {
    marginTop: theme.spacing.sm,
  },
});
