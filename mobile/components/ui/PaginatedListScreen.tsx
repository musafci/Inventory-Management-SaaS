import { type ComponentType, type ReactElement, type ReactNode } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';

import { EmptyState } from './EmptyState';
import { ScreenContainer } from './ScreenContainer';
import { SearchBar } from './SearchBar';
import { SkeletonRow } from './Skeleton';
import { OptimizedFlatList } from '@/components/OptimizedFlatList';
import { theme } from '@/src/theme';

type PaginatedListScreenProps<T> = {
  data: T[];
  keyExtractor: (item: T) => string;
  renderItem: (item: T) => ReactElement | null;
  isLoading: boolean;
  isRefetching: boolean;
  onRefresh: () => void;
  emptyMessage?: string | null;
  search?: string;
  onSearchChange?: (value: string) => void;
  searchPlaceholder?: string;
  searchAccessibilityLabel?: string;
  hasNextPage?: boolean;
  isFetchingNextPage?: boolean;
  onEndReached?: () => void;
  ListHeaderComponent?: ComponentType<any> | ReactElement | null;
};

export function PaginatedListScreen<T>({
  data,
  keyExtractor,
  renderItem,
  isLoading,
  isRefetching,
  onRefresh,
  emptyMessage = 'No items yet.',
  search,
  onSearchChange,
  searchPlaceholder,
  searchAccessibilityLabel,
  hasNextPage,
  isFetchingNextPage,
  onEndReached,
  ListHeaderComponent,
}: PaginatedListScreenProps<T>) {
  const showSearch = onSearchChange !== undefined;

  return (
    <ScreenContainer>
      {showSearch ? (
        <SearchBar
          accessibilityLabel={searchAccessibilityLabel}
          placeholder={searchPlaceholder}
          value={search ?? ''}
          onChangeText={onSearchChange}
          autoCapitalize="none"
        />
      ) : null}

      {isLoading ? (
        <View style={styles.skeletonList}>
          {Array.from({ length: 6 }).map((_, i) => (
            <SkeletonRow key={i} lines={1} />
          ))}
        </View>
      ) : (
        <OptimizedFlatList
          data={data}
          keyExtractor={keyExtractor}
          contentContainerStyle={styles.listContent}
          ListHeaderComponent={ListHeaderComponent}
          refreshControl={(
            <RefreshControl
              refreshing={isRefetching}
              tintColor={theme.colors.primary}
              onRefresh={onRefresh}
            />
          )}
          onEndReached={() => {
            if (hasNextPage && !isFetchingNextPage) {
              onEndReached?.();
            }
          }}
          onEndReachedThreshold={0.4}
          ListEmptyComponent={<EmptyState title={emptyMessage ?? 'No items'} />}
          ListFooterComponent={
            isFetchingNextPage ? (
              <ActivityIndicator color={theme.colors.primary} style={styles.footerLoader} />
            ) : null
          }
          renderItem={({ item }) => renderItem(item)}
        />
      )}
    </ScreenContainer>
  );
}

const styles = StyleSheet.create({
  skeletonList: {
    flex: 1,
  },
  listContent: {
    paddingBottom: theme.spacing.xl,
  },
  footerLoader: {
    marginVertical: theme.spacing.lg,
  },
});
