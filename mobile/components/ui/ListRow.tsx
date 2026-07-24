import { type ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { type Href } from 'expo-router';
import { SymbolView } from 'expo-symbols';

import { AnimatedPressable } from './AnimatedPressable';
import { NavPressable } from './NavPressable';
import { palette, shadow, theme } from '@/src/theme';

type ListRowProps = {
  title: string;
  subtitle?: string;
  meta?: string;
  href?: Href;
  onPress?: () => void;
  right?: ReactNode;
  showChevron?: boolean;
  testID?: string;
};

export function ListRow({
  title,
  subtitle,
  meta,
  href,
  onPress,
  right,
  showChevron = true,
  testID,
}: ListRowProps) {
  const content = (
    <>
      <View style={styles.body}>
        <Text style={styles.title} numberOfLines={1}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle} numberOfLines={2}>{subtitle}</Text> : null}
      </View>
      {meta ? <Text style={styles.meta}>{meta}</Text> : null}
      {right}
      {showChevron && (href || onPress) ? (
        <SymbolView
          name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' }}
          size={16}
          tintColor={theme.colors.textMuted}
        />
      ) : null}
    </>
  );

  if (href) {
    return (
      <NavPressable href={href} style={styles.row} testID={testID}>
        {content}
      </NavPressable>
    );
  }

  if (onPress) {
    return (
      <AnimatedPressable onPress={onPress} style={styles.row} testID={testID}>
        {content}
      </AnimatedPressable>
    );
  }

  return <View style={styles.row}>{content}</View>;
}

const styles = StyleSheet.create({
  row: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: `${palette.slate900}0D`,
    borderRadius: theme.radius.md,
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
    marginHorizontal: theme.spacing.lg,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 14,
    ...shadow('sm'),
  },
  body: {
    flex: 1,
    minWidth: 0,
  },
  title: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  subtitle: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
  meta: {
    color: theme.colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
});
