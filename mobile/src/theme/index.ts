import { Platform, type TextStyle, type ViewStyle } from 'react-native';

/** Tailwind sky scale — primary brand (sky-400 → sky-600 gradient) */
export const palette = {
  primary50: '#f0f9ff',
  primary100: '#e0f2fe',
  primary200: '#bae6fd',
  primary300: '#7dd3fc',
  primary400: '#38bdf8',
  primary500: '#0ea5e9',
  primary600: '#0284c7',
  primary700: '#0369a1',
  primary800: '#075985',
  primary900: '#0c4a6e',
  primary950: '#082f49',

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

  red500: '#ef4444',
  red600: '#dc2626',

  amber50: '#fffbeb',
  amber200: '#fde68a',
  amber400: '#fbbf24',
  amber500: '#f59e0b',
  amber700: '#b45309',
  amber800: '#92400e',

  rose50: '#fff1f2',
  rose500: '#f43f5e',
  rose600: '#e11d48',

  cyan50: '#ecfeff',
  cyan500: '#06b6d4',
  cyan600: '#0891b2',

  violet50: '#f5f3ff',
  violet500: '#8b5cf6',
  violet600: '#7c3aed',

  white: '#ffffff',
  black: '#000000',
} as const;

/** Brand gradients — matches web `.stat-card-sky` / `.btn-primary` */
export const gradients = {
  /** Horizontal accent bar: sky-400 → sky-600 */
  primary: [palette.primary400, palette.primary600] as const,
  /** Hero banners and buttons */
  primaryHero: [palette.primary400, palette.primary500, palette.primary600] as const,
  /** Auth screen background fade */
  authBackground: [palette.primary400, palette.primary600, palette.slate100] as const,
} as const;

/** Matches web `.btn-primary`, `.btn-danger`, `.btn-success`, `.btn-warning` */
export const buttonGradients = {
  primary: [palette.primary400, palette.primary600] as const,
  danger: [palette.red600, palette.red500] as const,
  success: [palette.emerald600, palette.emerald500] as const,
  warning: [palette.amber500, palette.amber400] as const,
} as const;

export type ButtonGradientVariant = keyof typeof buttonGradients;

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
    primaryLight: palette.primary400,
    primarySoft: palette.primary50,
    primaryText: palette.white,
    success: palette.emerald600,
    successSoft: palette.emerald50,
    warning: palette.amber500,
    warningSoft: palette.amber50,
    danger: palette.rose600,
    dangerSoft: palette.rose50,
    info: palette.cyan600,
    infoSoft: palette.cyan50,
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

export type AccentTone = 'sky' | 'emerald' | 'amber' | 'cyan' | 'violet' | 'rose';

/** @deprecated Use `sky` — kept as alias for existing `tone="indigo"` call sites */
export type LegacyAccentTone = AccentTone | 'indigo';

export const accentTones: Record<AccentTone, { soft: string; solid: string; text: string }> = {
  sky: { soft: palette.primary50, solid: palette.primary600, text: palette.primary700 },
  emerald: { soft: palette.emerald50, solid: palette.emerald500, text: palette.emerald600 },
  amber: { soft: palette.amber50, solid: palette.amber500, text: palette.amber700 },
  cyan: { soft: palette.cyan50, solid: palette.cyan500, text: palette.cyan600 },
  violet: { soft: palette.violet50, solid: palette.violet500, text: palette.violet600 },
  rose: { soft: palette.rose50, solid: palette.rose500, text: palette.rose600 },
};

export function resolveAccentTone(tone: LegacyAccentTone): AccentTone {
  return tone === 'indigo' ? 'sky' : tone;
}

export function accentFor(tone: LegacyAccentTone = 'sky') {
  return accentTones[resolveAccentTone(tone)];
}
