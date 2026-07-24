import { Platform, type TextStyle, type ViewStyle } from 'react-native';

export const palette = {
  primary50: '#eef2ff',
  primary100: '#e0e7ff',
  primary500: '#6366f1',
  primary600: '#4f46e5',
  primary700: '#4338ca',

  slate50: '#f8fafc',
  slate100: '#f1f5f9',
  slate200: '#e2e8f0',
  slate300: '#cbd5e1',
  slate400: '#94a3b8',
  slate500: '#64748b',
  slate700: '#334155',
  slate900: '#0f172a',

  emerald50: '#ecfdf5',
  emerald500: '#10b981',
  emerald600: '#059669',

  amber50: '#fffbeb',
  amber500: '#f59e0b',
  amber700: '#b45309',

  rose50: '#fff1f2',
  rose500: '#f43f5e',
  rose600: '#e11d48',

  sky50: '#f0f9ff',
  sky500: '#0ea5e9',
  sky600: '#0284c7',

  violet50: '#f5f3ff',
  violet500: '#8b5cf6',
  violet600: '#7c3aed',

  white: '#ffffff',
  black: '#000000',
} as const;

export const theme = {
  colors: {
    background: palette.slate100,
    surface: palette.white,
    surfaceMuted: palette.slate50,
    border: palette.slate200,
    borderStrong: palette.slate300,
    text: palette.slate900,
    textSecondary: palette.slate500,
    textMuted: palette.slate400,
    primary: palette.primary600,
    primarySoft: palette.primary50,
    primaryText: palette.white,
    success: palette.emerald600,
    successSoft: palette.emerald50,
    warning: palette.amber500,
    warningSoft: palette.amber50,
    danger: palette.rose600,
    dangerSoft: palette.rose50,
    info: palette.sky600,
    infoSoft: palette.sky50,
    overlay: 'rgba(15, 23, 42, 0.5)',
  },
  spacing: {
    xs: 4,
    sm: 8,
    md: 12,
    lg: 16,
    xl: 20,
    xxl: 24,
    xxxl: 32,
  },
  radius: {
    sm: 10,
    md: 14,
    lg: 18,
    xl: 24,
    pill: 999,
  },
  typography: {
    hero: { fontSize: 32, fontWeight: '800', letterSpacing: -0.5 } satisfies TextStyle,
    title: { fontSize: 26, fontWeight: '800', letterSpacing: -0.4 } satisfies TextStyle,
    heading: { fontSize: 18, fontWeight: '700', letterSpacing: -0.2 } satisfies TextStyle,
    body: { fontSize: 15, fontWeight: '400', lineHeight: 22 } satisfies TextStyle,
    bodyStrong: { fontSize: 15, fontWeight: '600' } satisfies TextStyle,
    caption: { fontSize: 13, fontWeight: '500', lineHeight: 18 } satisfies TextStyle,
    label: {
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.6,
      textTransform: 'uppercase',
    } satisfies TextStyle,
    metric: { fontSize: 28, fontWeight: '800', letterSpacing: -0.5 } satisfies TextStyle,
  },
} as const;

type ShadowSize = 'sm' | 'md' | 'lg';

export function shadow(size: ShadowSize): ViewStyle {
  const config = {
    sm: { opacity: 0.05, radius: 6, offsetY: 2 },
    md: { opacity: 0.08, radius: 12, offsetY: 4 },
    lg: { opacity: 0.12, radius: 20, offsetY: 8 },
  }[size];

  if (Platform.OS === 'web') {
    return {
      boxShadow: `0px ${config.offsetY}px ${config.radius}px rgba(15, 23, 42, ${config.opacity})`,
    } as ViewStyle;
  }

  if (Platform.OS === 'android') {
    return {
      elevation: size === 'sm' ? 2 : size === 'md' ? 4 : 8,
    };
  }

  return {
    shadowColor: palette.slate900,
    shadowOffset: { width: 0, height: config.offsetY },
    shadowOpacity: config.opacity,
    shadowRadius: config.radius,
  };
}

export type AccentTone = 'indigo' | 'emerald' | 'amber' | 'sky' | 'violet' | 'rose';

export const accentTones: Record<AccentTone, { soft: string; solid: string; text: string }> = {
  indigo: { soft: palette.primary50, solid: palette.primary600, text: palette.primary700 },
  emerald: { soft: palette.emerald50, solid: palette.emerald500, text: palette.emerald600 },
  amber: { soft: palette.amber50, solid: palette.amber500, text: palette.amber700 },
  sky: { soft: palette.sky50, solid: palette.sky500, text: palette.sky600 },
  violet: { soft: palette.violet50, solid: palette.violet500, text: palette.violet600 },
  rose: { soft: palette.rose50, solid: palette.rose500, text: palette.rose600 },
};
