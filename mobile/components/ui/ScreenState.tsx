import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { EmptyState } from './EmptyState';
import { theme } from '@/src/theme';

type ScreenStateProps = {
  message?: string;
};

export function LoadingState({ message }: ScreenStateProps) {
  return (
    <View style={styles.centered}>
      <ActivityIndicator color={theme.colors.primary} size="large" />
      {message ? <Text style={styles.message}>{message}</Text> : null}
    </View>
  );
}

export function ErrorState({ message = 'Something went wrong.' }: ScreenStateProps) {
  return (
    <View style={styles.centered}>
      <EmptyState body="Pull to refresh or try again later." title={message} />
    </View>
  );
}

const styles = StyleSheet.create({
  centered: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: theme.spacing.xxxl,
  },
  message: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.md,
  },
});
