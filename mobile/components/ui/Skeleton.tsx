import { useEffect, useRef } from 'react';
import { Animated, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { theme } from '@/src/theme';

type SkeletonProps = {
  width?: number | string;
  height?: number;
  borderRadius?: number;
  style?: StyleProp<ViewStyle>;
};

export function Skeleton({ width = '100%', height = 16, borderRadius, style }: SkeletonProps) {
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.7, duration: 600, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.3, duration: 600, useNativeDriver: true }),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={[
        styles.skeleton,
        { width: width as any, height, borderRadius: borderRadius ?? theme.radius.sm, opacity },
        style,
      ]}
    />
  );
}

type SkeletonRowProps = {
  showAvatar?: boolean;
  lines?: number;
};

export function SkeletonRow({ showAvatar = false, lines = 2 }: SkeletonRowProps) {
  return (
    <View style={styles.row}>
      {showAvatar ? <Skeleton width={40} height={40} borderRadius={theme.radius.sm} /> : null}
      <View style={styles.rowBody}>
        <Skeleton width="60%" height={16} />
        {Array.from({ length: lines }).map((_, i) => (
          <Skeleton key={i} width={i === 0 ? '80%' : '50%'} height={12} style={styles.rowGap} />
        ))}
      </View>
    </View>
  );
}

type SkeletonCardProps = {
  lines?: number;
};

export function SkeletonCard({ lines = 3 }: SkeletonCardProps) {
  return (
    <View style={styles.card}>
      <Skeleton width="40%" height={14} />
      <Skeleton width="70%" height={28} style={styles.cardGap} />
      {Array.from({ length: lines }).map((_, i) => (
        <Skeleton key={i} width={i === lines - 1 ? '45%' : '90%'} height={12} style={styles.cardLine} />
      ))}
    </View>
  );
}

export function SkeletonMetricRow() {
  return (
    <View style={styles.metricRow}>
      <View style={styles.metricTile}>
        <Skeleton width={34} height={34} borderRadius={theme.radius.sm} />
        <Skeleton width="50%" height={12} style={styles.metricGap} />
        <Skeleton width="40%" height={24} style={styles.metricGap} />
      </View>
      <View style={styles.metricTile}>
        <Skeleton width={34} height={34} borderRadius={theme.radius.sm} />
        <Skeleton width="50%" height={12} style={styles.metricGap} />
        <Skeleton width="40%" height={24} style={styles.metricGap} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  skeleton: {
    backgroundColor: theme.colors.border,
  },
  row: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: `${theme.colors.text}0D`,
    borderRadius: theme.radius.md,
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
    marginHorizontal: theme.spacing.lg,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 14,
  },
  rowBody: {
    flex: 1,
  },
  rowGap: {
    marginTop: 6,
  },
  card: {
    backgroundColor: theme.colors.surface,
    borderColor: `${theme.colors.text}0D`,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    padding: theme.spacing.lg,
  },
  cardGap: {
    marginTop: theme.spacing.md,
  },
  cardLine: {
    marginTop: 8,
  },
  metricRow: {
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
  },
  metricTile: {
    backgroundColor: theme.colors.surface,
    borderColor: `${theme.colors.text}0D`,
    borderRadius: theme.radius.lg,
    borderWidth: StyleSheet.hairlineWidth,
    flex: 1,
    padding: theme.spacing.lg,
  },
  metricGap: {
    marginTop: theme.spacing.sm,
  },
});
