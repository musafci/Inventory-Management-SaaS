import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { Animated, StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';
import { LinearGradient } from 'expo-linear-gradient';

import { palette, shadow, theme } from '@/src/theme';

type ToastVariant = 'success' | 'error' | 'info' | 'warning';

type ToastMessage = {
  id: string;
  text: string;
  variant: ToastVariant;
};

type ToastContextValue = {
  show: (text: string, variant?: ToastVariant) => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);

const variantConfig: Record<ToastVariant, { bg: readonly [string, string]; icon: string; tint: string }> = {
  success: { bg: ['#059669', '#10b981'], icon: 'checkmark.circle.fill', tint: '#ffffff' },
  error: { bg: ['#dc2626', '#ef4444'], icon: 'xmark.circle.fill', tint: '#ffffff' },
  warning: { bg: ['#d97706', '#f59e0b'], icon: 'exclamationmark.triangle.fill', tint: '#ffffff' },
  info: { bg: ['#0284c7', '#0ea5e9'], icon: 'info.circle.fill', tint: '#ffffff' },
};

let toastIdCounter = 0;

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<ToastMessage[]>([]);
  const animatedValues = useRef<Map<string, Animated.Value>>(new Map());

  const remove = useCallback((id: string) => {
    const anim = animatedValues.current.get(id);
    if (anim) {
      Animated.timing(anim, { toValue: 0, duration: 200, useNativeDriver: true }).start(() => {
        setToasts((current) => current.filter((t) => t.id !== id));
        animatedValues.current.delete(id);
      });
    } else {
      setToasts((current) => current.filter((t) => t.id !== id));
    }
  }, []);

  const show = useCallback(
    (text: string, variant: ToastVariant = 'success') => {
      const id = `toast-${++toastIdCounter}`;
      const anim = new Animated.Value(0);
      animatedValues.current.set(id, anim);

      setToasts((current) => [...current.slice(-2), { id, text, variant }]);

      Animated.timing(anim, { toValue: 1, duration: 250, useNativeDriver: true }).start();

      setTimeout(() => remove(id), 3500);
    },
    [remove],
  );

  const value = useMemo(() => ({ show }), [show]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      {toasts.length > 0 ? (
        <View style={styles.container} pointerEvents="box-none">
          {toasts.map((toast) => {
            const anim = animatedValues.current.get(toast.id);
            const config = variantConfig[toast.variant];
            return (
              <Animated.View
                key={toast.id}
                style={[
                  styles.toast,
                  {
                    opacity: anim,
                    transform: [
                      {
                        translateY: anim
                          ? anim.interpolate({ inputRange: [0, 1], outputRange: [-20, 0] })
                          : 0,
                      },
                    ],
                  },
                ]}>
                <LinearGradient
                  colors={[...config.bg]}
                  end={{ x: 1, y: 0.5 }}
                  start={{ x: 0, y: 0.5 }}
                  style={styles.gradient}>
                  <SymbolView name={config.icon as any} size={18} tintColor={config.tint} />
                  <Text style={styles.text} numberOfLines={2}>
                    {toast.text}
                  </Text>
                </LinearGradient>
              </Animated.View>
            );
          })}
        </View>
      ) : null}
    </ToastContext.Provider>
  );
}

export function useToast(): ToastContextValue {
  const context = useContext(ToastContext);

  if (context === null) {
    throw new Error('useToast must be used within ToastProvider');
  }

  return context;
}

const styles = StyleSheet.create({
  container: {
    bottom: 100,
    left: theme.spacing.lg,
    position: 'absolute',
    right: theme.spacing.lg,
    zIndex: 9999,
  },
  toast: {
    borderRadius: theme.radius.md,
    marginBottom: theme.spacing.sm,
    overflow: 'hidden',
    ...shadow('lg'),
  },
  gradient: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.sm,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 14,
  },
  text: {
    color: palette.white,
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
});
