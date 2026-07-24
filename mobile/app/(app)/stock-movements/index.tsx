import { Stack } from 'expo-router';

import { HeaderAction, ListRow, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useInventoryLabels, useStockMovements, useStockMovementsList } from '@/src/hooks/useInventory';
import { canUpdateInventory } from '@/src/permissions';

function formatMovementType(type: string): string {
  return type.replace(/_/g, ' ');
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

export default function StockMovementsScreen() {
  const { permissions } = useAuth();
  const query = useStockMovements();
  const movements = useStockMovementsList();
  const labelsQuery = useInventoryLabels();
  const labels = labelsQuery.data;

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Stock movements',
          headerRight: () => (
            canUpdateInventory(permissions) ? (
              <HeaderAction href="/(app)/stock-movements/new" label="Adjust" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={movements}
        emptyMessage="No stock movements yet."
        hasNextPage={query.hasNextPage}
        isFetchingNextPage={query.isFetchingNextPage}
        isLoading={query.isLoading}
        isRefetching={query.isRefetching}
        keyExtractor={(item) => String(item.id)}
        onEndReached={() => {
          void query.fetchNextPage();
        }}
        onRefresh={() => {
          void query.refetch();
        }}
        renderItem={(item) => {
          const productLabel = labels?.products.get(item.product_id) ?? `Product #${item.product_id}`;
          const warehouseLabel = labels?.warehouses.get(item.warehouse_id) ?? `Warehouse #${item.warehouse_id}`;
          const subtitleParts = [`${productLabel} · ${warehouseLabel}`];
          if (item.note) {
            subtitleParts.push(item.note);
          }

          return (
            <ListRow
              meta={formatDate(item.created_at)}
              showChevron={false}
              subtitle={subtitleParts.join('\n')}
              title={`${formatMovementType(item.type)} · ${item.quantity}`}
            />
          );
        }}
      />
    </>
  );
}
