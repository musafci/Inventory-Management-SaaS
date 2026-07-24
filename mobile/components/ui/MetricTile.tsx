import { StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { shadow, theme, type AccentTone, accentTones } from '@/src/theme';
import { appIcon, type AppIcon } from '@/src/theme/icons';

type MetricTileProps = {
  label: string;
  value: string;
  meta?: string;
  tone?: AccentTone;
  icon?: AppIcon;
};

export function MetricTile({
  label,
  value,
  meta,
  tone = 'indigo',
  icon,
}: MetricTileProps) {
  const accent = accentTones[tone];

  return (
    <View style={[styles.tile, shadow('md')]}>
      <View style={styles.topRow}>
        <View style={[styles.iconWrap, { backgroundColor: accent.soft }]}>
          {icon ? (
            <SymbolView name={appIcon(icon)} size={18} tintColor={accent.solid} />
          ) : null}
        </View>
        <Text style={styles.label}>{label}</Text>
      </View>
      <Text style={styles.value}>{value}</Text>
      {meta ? <Text style={styles.meta}>{meta}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  tile: {
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    flex: 1,
    minWidth: '46%',
    padding: theme.spacing.lg,
  },
  topRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.sm,
  },
  iconWrap: {
    alignItems: 'center',
    borderRadius: theme.radius.sm,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  label: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    flex: 1,
  },
  value: {
    ...theme.typography.metric,
    color: theme.colors.text,
    fontSize: 24,
    marginTop: theme.spacing.md,
  },
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
  },
});
