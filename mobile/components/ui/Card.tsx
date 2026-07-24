import { type ReactNode } from 'react';
import { Platform, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { palette, shadow, theme } from '@/src/theme';

type CardProps = {
  children: ReactNode;
  style?: StyleProp<ViewStyle>;
  elevated?: boolean;
  muted?: boolean;
};

export function Card({ children, style, elevated = true, muted = false }: CardProps) {
  return (
    <View
      style={[
        styles.card,
        elevated ? shadow('md') : null,
        muted ? styles.muted : null,
        style,
      ]}>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: `${palette.slate900}0D`,
    overflow: 'hidden',
    padding: theme.spacing.lg,
    ...(Platform.OS === 'web' ? {
      backdropFilter: 'blur(4px)',
      WebkitBackdropFilter: 'blur(4px)',
    } : {}),
  },
  muted: {
    backgroundColor: theme.colors.surfaceMuted,
  },
});
