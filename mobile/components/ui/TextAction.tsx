import { Pressable, StyleSheet, Text } from 'react-native';

import { theme } from '@/src/theme';

type TextActionProps = {
  label: string;
  onPress?: () => void;
  tone?: 'primary' | 'danger' | 'muted';
};

export function TextAction({ label, onPress, tone = 'primary' }: TextActionProps) {
  return (
    <Pressable hitSlop={8} onPress={onPress}>
      <Text style={[styles.label, toneStyles[tone]]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  label: {
    fontSize: 14,
    fontWeight: '700',
  },
});

const toneStyles = StyleSheet.create({
  primary: {
    color: theme.colors.primary,
  },
  danger: {
    color: theme.colors.danger,
  },
  muted: {
    color: theme.colors.textSecondary,
  },
});
