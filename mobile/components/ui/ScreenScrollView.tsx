import { type ReactNode } from 'react';
import {
  RefreshControl,
  ScrollView,
  StyleSheet,
  type ScrollViewProps,
} from 'react-native';

import { theme } from '@/src/theme';

type ScreenScrollViewProps = ScrollViewProps & {
  children: ReactNode;
  refreshing?: boolean;
  onRefresh?: () => void;
};

export function ScreenScrollView({
  children,
  refreshing,
  onRefresh,
  contentContainerStyle,
  ...props
}: ScreenScrollViewProps) {
  return (
    <ScrollView
      contentContainerStyle={[styles.content, contentContainerStyle]}
      refreshControl={
        onRefresh ? (
          <RefreshControl refreshing={refreshing ?? false} onRefresh={onRefresh} tintColor={theme.colors.primary} />
        ) : undefined
      }
      showsVerticalScrollIndicator={false}
      {...props}>
      {children}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    backgroundColor: theme.colors.background,
    flexGrow: 1,
    paddingBottom: theme.spacing.xxxl,
    paddingHorizontal: theme.spacing.xl,
    paddingTop: theme.spacing.lg,
  },
});
