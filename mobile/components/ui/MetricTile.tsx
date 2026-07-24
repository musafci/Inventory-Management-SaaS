import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { shadow, theme, palette, accentFor, resolveAccentTone, type LegacyAccentTone, type AccentTone } from '@/src/theme';
import { appIcon, type AppIcon } from '@/src/theme/icons';

const accentBarColors: Record<AccentTone, readonly [string, string]> = {
  sky: ['#38bdf8', '#0284c7'] as const,
  emerald: ['#34d399', '#059669'] as const,
  amber: ['#fbbf24', '#d97706'] as const,
  cyan: ['#22d3ee', '#0891b2'] as const,
  violet: ['#a78bfa', '#7c3aed'] as const,
  rose: ['#fb7185', '#e11d48'] as const,
};

const accentCircleColors: Record<AccentTone, string> = {
  sky: 'rgba(14,165,233,0.07)',
  emerald: 'rgba(16,185,129,0.07)',
  amber: 'rgba(245,158,11,0.07)',
  cyan: 'rgba(6,182,212,0.07)',
  violet: 'rgba(139,92,246,0.07)',
  rose: 'rgba(244,63,94,0.07)',
};

type MetricTileProps = {
  label: string;
  value: string;
  meta?: string;
  tone?: LegacyAccentTone;
  icon?: AppIcon;
};

export function MetricTile({
  label,
  value,
  meta,
  tone = 'sky',
  icon,
}: MetricTileProps) {
  const accent = accentFor(tone);
  const resolvedTone = resolveAccentTone(tone);

  return (
    <View style={[styles.tile, shadow('md')]}>
      <LinearGradient
        colors={[...accentBarColors[resolvedTone]]}
        end={{ x: 1, y: 0 }}
        start={{ x: 0, y: 0 }}
        style={styles.accentBar}
      />
      <View style={[styles.cornerCircle, { backgroundColor: accentCircleColors[resolvedTone] }]} />
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
    borderColor: `${palette.slate900}0D`,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    flex: 1,
    minWidth: '46%',
    overflow: 'hidden',
    padding: theme.spacing.lg,
  },
  accentBar: {
    height: 4,
    marginHorizontal: -theme.spacing.lg,
    marginTop: -theme.spacing.lg,
  },
  cornerCircle: {
    position: 'absolute',
    right: -24,
    top: -24,
    width: 96,
    height: 96,
    borderRadius: 48,
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
