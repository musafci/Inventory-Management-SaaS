import { type ReactNode } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { shadow, theme } from '@/src/theme';

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
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    overflow: 'hidden',
    padding: theme.spacing.lg,
  },
  muted: {
    backgroundColor: theme.colors.surfaceMuted,
  },
});
