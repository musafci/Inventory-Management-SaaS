import { Platform, FlatList, type FlatListProps } from 'react-native';

const DEFAULT_LIST_PERFORMANCE = {
  initialNumToRender: 15,
  maxToRenderPerBatch: 10,
  windowSize: 7,
  updateCellsBatchingPeriod: 50,
  removeClippedSubviews: Platform.OS !== 'web',
} as const;

export function OptimizedFlatList<ItemT>(props: FlatListProps<ItemT>) {
  return <FlatList {...DEFAULT_LIST_PERFORMANCE} {...props} />;
}
