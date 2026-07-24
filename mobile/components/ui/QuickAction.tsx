import { StyleSheet, Text } from 'react-native';
import { type Href } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { NavPressable } from './NavPressable';
import { shadow, theme, type AccentTone, accentTones } from '@/src/theme';
import { appIcon, type AppIcon } from '@/src/theme/icons';

type QuickActionProps = {
  label: string;
  href: Href;
  tone?: AccentTone;
  icon?: AppIcon;
};

export function QuickAction({ label, href, tone = 'indigo', icon }: QuickActionProps) {
  const accent = accentTones[tone];

  return (
    <NavPressable href={href} style={[styles.chip, shadow('sm'), { backgroundColor: accent.soft }]}>
      {icon ? (
        <SymbolView name={appIcon(icon)} size={16} tintColor={accent.solid} />
      ) : null}
      <Text style={[styles.label, { color: accent.text }]} numberOfLines={1}>{label}</Text>
    </NavPressable>
  );
}

const styles = StyleSheet.create({
  chip: {
    alignItems: 'center',
    borderColor: 'transparent',
    borderRadius: theme.radius.pill,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 11,
  },
  label: {
    fontSize: 14,
    fontWeight: '700',
  },
});
