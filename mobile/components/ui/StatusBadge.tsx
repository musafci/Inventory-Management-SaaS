import { StyleSheet, Text, View } from 'react-native';

import { theme } from '@/src/theme';

type StatusTone = 'default' | 'success' | 'warning' | 'danger' | 'info';

type StatusBadgeProps = {
  label: string;
  tone?: StatusTone;
};

const toneStyles: Record<StatusTone, { bg: string; text: string; border: string }> = {
  default: { bg: theme.colors.surfaceMuted, text: theme.colors.textSecondary, border: 'rgba(100,116,139,0.10)' },
  success: { bg: theme.colors.successSoft, text: theme.colors.success, border: 'rgba(5,150,105,0.10)' },
  warning: { bg: theme.colors.warningSoft, text: theme.colors.warning, border: 'rgba(245,158,11,0.10)' },
  danger: { bg: theme.colors.dangerSoft, text: theme.colors.danger, border: 'rgba(225,29,72,0.10)' },
  info: { bg: theme.colors.primarySoft, text: theme.colors.primary, border: 'rgba(2,132,199,0.10)' },
};

export function StatusBadge({ label, tone = 'default' }: StatusBadgeProps) {
  const colors = toneStyles[tone];

  return (
    <View style={[styles.badge, { backgroundColor: colors.bg, borderColor: colors.border }]}>
      <Text style={[styles.label, { color: colors.text }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  label: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
});
