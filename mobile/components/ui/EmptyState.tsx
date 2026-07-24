import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { theme, type AccentTone } from '@/src/theme';
import { appIcon, type AppIcon } from '@/src/theme/icons';

type EmptyStateProps = {
  title: string;
  body?: string;
  icon?: AppIcon;
  tone?: AccentTone;
};

const toneConfig: Record<AccentTone, { gradient: readonly [string, string]; tint: string }> = {
  sky: { gradient: ['#e0f2fe', '#f0f9ff'], tint: '#0ea5e9' },
  emerald: { gradient: ['#d1fae5', '#ecfdf5'], tint: '#10b981' },
  amber: { gradient: ['#fef3c7', '#fffbeb'], tint: '#f59e0b' },
  cyan: { gradient: ['#cffafe', '#ecfeff'], tint: '#06b6d4' },
  violet: { gradient: ['#ede9fe', '#f5f3ff'], tint: '#8b5cf6' },
  rose: { gradient: ['#ffe4e6', '#fff1f2'], tint: '#f43f5e' },
};

export function EmptyState({ title, body, icon, tone = 'sky' }: EmptyStateProps) {
  const config = toneConfig[tone];

  return (
    <View style={styles.wrap}>
      {icon ? (
        <View style={styles.iconContainer}>
          <LinearGradient
            colors={[...config.gradient]}
            style={styles.iconCircle}>
            <SymbolView name={appIcon(icon)} size={28} tintColor={config.tint} />
          </LinearGradient>
        </View>
      ) : null}
      <Text style={[styles.title, icon ? styles.titleWithIcon : null]}>{title}</Text>
      {body ? <Text style={styles.body}>{body}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    alignItems: 'center',
    paddingHorizontal: theme.spacing.xl,
    paddingVertical: theme.spacing.xxxl,
  },
  iconContainer: {
    marginBottom: theme.spacing.lg,
  },
  iconCircle: {
    alignItems: 'center',
    borderRadius: theme.radius.xl,
    height: 64,
    justifyContent: 'center',
    width: 64,
  },
  title: {
    ...theme.typography.bodyStrong,
    color: theme.colors.textSecondary,
    textAlign: 'center',
  },
  titleWithIcon: {
    ...theme.typography.heading,
    color: theme.colors.text,
  },
  body: {
    ...theme.typography.caption,
    color: theme.colors.textMuted,
    marginTop: theme.spacing.sm,
    textAlign: 'center',
  },
});
