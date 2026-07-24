import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

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
      <View style={styles.errorIcon}>
        <SymbolView
          name={{ ios: 'exclamationmark.triangle.fill', android: 'error', web: 'error' }}
          size={28}
          tintColor={theme.colors.danger}
        />
      </View>
      <Text style={styles.errorTitle}>{message}</Text>
      <Text style={styles.errorBody}>Pull to refresh or try again later.</Text>
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
  errorIcon: {
    alignItems: 'center',
    backgroundColor: theme.colors.dangerSoft,
    borderRadius: theme.radius.xl,
    height: 56,
    justifyContent: 'center',
    marginBottom: theme.spacing.lg,
    width: 56,
  },
  errorTitle: {
    ...theme.typography.heading,
    color: theme.colors.text,
    textAlign: 'center',
  },
  errorBody: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
    textAlign: 'center',
  },
});
