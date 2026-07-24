import { type ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { theme } from '@/src/theme';

type DetailRowProps = {
  label: string;
  value: ReactNode;
};

export function DetailRow({ label, value }: DetailRowProps) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      {typeof value === 'string' || typeof value === 'number' ? (
        <Text style={styles.value}>{value}</Text>
      ) : (
        <View style={styles.valueWrap}>{value}</View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    backgroundColor: theme.colors.surfaceMuted,
    borderRadius: theme.radius.md,
    marginBottom: theme.spacing.sm,
    padding: theme.spacing.lg,
  },
  label: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    marginBottom: 6,
  },
  value: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
    fontSize: 16,
  },
  valueWrap: {
    marginTop: 2,
  },
});
