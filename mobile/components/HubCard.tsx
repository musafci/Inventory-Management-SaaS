import { type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { NavPressable } from '@/components/ui/NavPressable';
import { shadow, theme, accentFor, type LegacyAccentTone } from '@/src/theme';
import { appIcon, type AppIcon } from '@/src/theme/icons';

type HubCardProps = {
  href: Href;
  title: string;
  body: string;
  testID?: string;
  tone?: LegacyAccentTone;
  icon?: AppIcon;
};

export function HubCard({
  href,
  title,
  body,
  testID,
  tone = 'sky',
  icon,
}: HubCardProps) {
  const accent = accentFor(tone);

  return (
    <NavPressable
      accessibilityHint={`Opens ${title}`}
      accessibilityLabel={title}
      accessibilityRole="button"
      href={href}
      style={[styles.card, shadow('md')]}
      testID={testID}>
      <View style={[styles.iconWrap, { backgroundColor: accent.soft }]}>
        {icon ? (
          <SymbolView name={appIcon(icon)} size={22} tintColor={accent.solid} />
        ) : (
          <SymbolView
            name={{ ios: 'square.grid.2x2.fill', android: 'apps', web: 'apps' }}
            size={22}
            tintColor={accent.solid}
          />
        )}
      </View>
      <View style={styles.body}>
        <Text style={styles.cardTitle}>{title}</Text>
        <Text style={styles.cardBody}>{body}</Text>
      </View>
      <SymbolView
        name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' }}
        size={18}
        tintColor={theme.colors.textMuted}
      />
    </NavPressable>
  );
}

const styles = StyleSheet.create({
  card: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.md,
    padding: theme.spacing.lg,
  },
  iconWrap: {
    alignItems: 'center',
    borderRadius: theme.radius.md,
    height: 48,
    justifyContent: 'center',
    width: 48,
  },
  body: {
    flex: 1,
    minWidth: 0,
  },
  cardTitle: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
    fontSize: 17,
  },
  cardBody: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
});
