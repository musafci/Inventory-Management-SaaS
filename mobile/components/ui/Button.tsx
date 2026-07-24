import { ActivityIndicator, StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import { type Href } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { AnimatedPressable } from './AnimatedPressable';
import { NavPressable } from './NavPressable';
import {
  buttonGradients,
  palette,
  shadow,
  theme,
  type ButtonGradientVariant,
} from '@/src/theme';

type ButtonVariant =
  | ButtonGradientVariant
  | 'secondary'
  | 'ghost';

type ButtonSize = 'default' | 'compact';

type ButtonProps = {
  label: string;
  onPress?: () => void;
  href?: Href;
  disabled?: boolean;
  loading?: boolean;
  variant?: ButtonVariant;
  size?: ButtonSize;
  style?: StyleProp<ViewStyle>;
  testID?: string;
  accessibilityLabel?: string;
};

const GRADIENT_VARIANTS = new Set<ButtonVariant>(['primary', 'danger', 'success', 'warning']);

function isGradientVariant(variant: ButtonVariant): variant is ButtonGradientVariant {
  return GRADIENT_VARIANTS.has(variant);
}

function spinnerColor(variant: ButtonVariant): string {
  if (isGradientVariant(variant)) {
    return theme.colors.primaryText;
  }

  return theme.colors.primary;
}

const sizeStyles = {
  default: {
    base: {
      borderRadius: theme.radius.md,
      minHeight: 44,
      paddingHorizontal: theme.spacing.lg,
      paddingVertical: 10,
    },
    label: {
      fontSize: 14,
    },
  },
  compact: {
    base: {
      alignSelf: 'auto' as const,
      borderRadius: theme.radius.md,
      minHeight: 36,
      paddingHorizontal: 14,
      paddingVertical: 8,
    },
    label: {
      fontSize: 13,
    },
  },
};

export function Button({
  label,
  onPress,
  href,
  disabled = false,
  loading = false,
  variant = 'primary',
  size = 'default',
  style,
  testID,
  accessibilityLabel,
}: ButtonProps) {
  const isDisabled = disabled || loading;
  const isGradient = isGradientVariant(variant);

  const content = loading ? (
    <ActivityIndicator color={spinnerColor(variant)} size="small" />
  ) : (
    <Text style={[styles.label, sizeStyles[size].label, labelStyles[variant]]}>{label}</Text>
  );

  const buttonStyle = [
    styles.base,
    sizeStyles[size].base,
    isGradient ? styles.gradientShell : variantStyles[variant],
    isGradient ? shadow('md') : variant === 'secondary' ? shadow('sm') : null,
    isDisabled ? styles.disabled : null,
    style,
  ];

  const inner = (
    <>
      {isGradient ? (
        isDisabled ? (
          <View style={[StyleSheet.absoluteFill, styles.disabledGradient]} />
        ) : (
          <LinearGradient
            colors={[...buttonGradients[variant]]}
            end={{ x: 1, y: 0.5 }}
            start={{ x: 0, y: 0.5 }}
            style={StyleSheet.absoluteFill}
          />
        )
      ) : null}
      {content}
    </>
  );

  if (href && !isDisabled) {
    return (
      <NavPressable
        accessibilityLabel={accessibilityLabel ?? label}
        accessibilityRole="button"
        href={href}
        style={buttonStyle}
        testID={testID}>
        {inner}
      </NavPressable>
    );
  }

  return (
    <AnimatedPressable
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityRole="button"
      disabled={isDisabled}
      onPress={onPress}
      style={buttonStyle}
      testID={testID}>
      {inner}
    </AnimatedPressable>
  );
}

const styles = StyleSheet.create({
  base: {
    alignItems: 'center',
    alignSelf: 'stretch',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  gradientShell: {
    backgroundColor: palette.primary600,
  },
  disabled: {
    opacity: 0.5,
  },
  disabledGradient: {
    backgroundColor: palette.slate400,
  },
  label: {
    fontWeight: '600',
    textAlign: 'center',
    zIndex: 1,
  },
});

const variantStyles = StyleSheet.create({
  secondary: {
    backgroundColor: theme.colors.surface,
    borderColor: palette.slate200,
    borderWidth: 1,
  },
  ghost: {
    backgroundColor: theme.colors.primarySoft,
    borderColor: palette.primary200,
    borderWidth: 1,
  },
});

const labelStyles = StyleSheet.create({
  primary: {
    color: theme.colors.primaryText,
  },
  secondary: {
    color: palette.slate700,
  },
  ghost: {
    color: palette.primary700,
  },
  danger: {
    color: theme.colors.primaryText,
  },
  success: {
    color: theme.colors.primaryText,
  },
  warning: {
    color: theme.colors.primaryText,
  },
});
