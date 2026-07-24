import { StyleSheet, Text, View } from 'react-native';

import { theme } from '@/src/theme';

type StatusTone = 'default' | 'success' | 'warning' | 'danger' | 'info';

type StatusBadgeProps = {
  label: string;
  tone?: StatusTone;
};

const toneStyles: Record<StatusTone, { bg: string; text: string }> = {
  default: { bg: theme.colors.surfaceMuted, text: theme.colors.textSecondary },
  success: { bg: theme.colors.successSoft, text: theme.colors.success },
  warning: { bg: theme.colors.warningSoft, text: theme.colors.warning },
  danger: { bg: theme.colors.dangerSoft, text: theme.colors.danger },
  info: { bg: theme.colors.infoSoft, text: theme.colors.info },
};

export function StatusBadge({ label, tone = 'default' }: StatusBadgeProps) {
  const colors = toneStyles[tone];

  return (
    <View style={[styles.badge, { backgroundColor: colors.bg }]}>
      <Text style={[styles.label, { color: colors.text }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    borderRadius: theme.radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  label: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
});
