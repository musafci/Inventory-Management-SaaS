import { StyleSheet, Text, View } from 'react-native';

import { theme } from '@/src/theme';

type DetailRowProps = {
  label: string;
  value: string;
};

export function DetailRow({ label, value }: DetailRowProps) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
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
});
