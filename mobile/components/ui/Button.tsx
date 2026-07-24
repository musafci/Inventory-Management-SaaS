import { ActivityIndicator, StyleSheet, Text, type StyleProp, type ViewStyle } from 'react-native';

import { AnimatedPressable } from './AnimatedPressable';
import { shadow, theme } from '@/src/theme';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';

type ButtonProps = {
  label: string;
  onPress?: () => void;
  disabled?: boolean;
  loading?: boolean;
  variant?: ButtonVariant;
  style?: StyleProp<ViewStyle>;
  testID?: string;
  accessibilityLabel?: string;
};

export function Button({
  label,
  onPress,
  disabled = false,
  loading = false,
  variant = 'primary',
  style,
  testID,
  accessibilityLabel,
}: ButtonProps) {
  const isDisabled = disabled || loading;

  return (
    <AnimatedPressable
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityRole="button"
      disabled={isDisabled}
      onPress={onPress}
      style={[
        styles.base,
        variantStyles[variant],
        isDisabled ? styles.disabled : null,
        variant === 'primary' ? shadow('sm') : null,
        style,
      ]}
      testID={testID}>
      {loading ? (
        <ActivityIndicator color={variant === 'primary' ? theme.colors.primaryText : theme.colors.primary} />
      ) : (
        <Text style={[styles.label, labelStyles[variant]]}>{label}</Text>
      )}
    </AnimatedPressable>
  );
}

const styles = StyleSheet.create({
  base: {
    alignItems: 'center',
    borderRadius: theme.radius.md,
    justifyContent: 'center',
    minHeight: 50,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: theme.spacing.md,
  },
  disabled: {
    opacity: 0.55,
  },
  label: {
    fontSize: 16,
    fontWeight: '700',
  },
});

const variantStyles = StyleSheet.create({
  primary: {
    backgroundColor: theme.colors.primary,
  },
  secondary: {
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.primary,
    borderWidth: 1.5,
  },
  ghost: {
    backgroundColor: theme.colors.primarySoft,
  },
  danger: {
    backgroundColor: theme.colors.dangerSoft,
  },
});

const labelStyles = StyleSheet.create({
  primary: {
    color: theme.colors.primaryText,
  },
  secondary: {
    color: theme.colors.primary,
  },
  ghost: {
    color: theme.colors.primary,
  },
  danger: {
    color: theme.colors.danger,
  },
});
