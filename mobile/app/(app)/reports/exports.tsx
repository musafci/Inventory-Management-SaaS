import { Stack } from 'expo-router';
import { useState } from 'react';
import { Alert, Share, StyleSheet, View } from 'react-native';

import {
  Button,
  Card,
  ErrorState,
  ListRow,
  LoadingState,
  PaginatedListScreen,
  ScreenContainer,
  SectionHeader,
  StatusBadge,
} from '@/components/ui';
import { ApiError } from '@/src/api/client';
import type { ReportExportType } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  pollReportExport,
  useCreateReportExport,
  useDownloadReportExport,
  useReportExports,
} from '@/src/hooks/useReports';
import { theme } from '@/src/theme';

const EXPORT_TYPES: { type: ReportExportType; label: string }[] = [
  { type: 'stock_valuation', label: 'Stock valuation' },
  { type: 'low_stock', label: 'Low stock' },
  { type: 'sales_summary', label: 'Sales summary' },
  { type: 'purchase_summary', label: 'Purchase summary' },
];

function formatExportType(type: string): string {
  return type.replace(/_/g, ' ');
}

function exportStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status === 'completed') return 'success';
  if (status === 'failed') return 'danger';
  if (status === 'processing' || status === 'pending') return 'warning';
  return 'default';
}

export default function ReportExportsScreen() {
  const { organizationId } = useAuth();
  const query = useReportExports();
  const createMutation = useCreateReportExport();
  const downloadMutation = useDownloadReportExport();
  const [processingId, setProcessingId] = useState<number | null>(null);

  const handleCreate = (type: ReportExportType) => {
    void (async () => {
      try {
        await createMutation.mutateAsync(type);
        Alert.alert('Export queued', 'Your export has been queued. Refresh to check status.');
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not queue export.';
        Alert.alert('Export failed', message);
      }
    })();
  };

  const handleDownload = (exportId: number) => {
    if (organizationId === null) {
      return;
    }

    void (async () => {
      setProcessingId(exportId);
      try {
        const exportRecord = await pollReportExport(exportId, organizationId);

        if (exportRecord.status === 'failed') {
          Alert.alert('Export failed', exportRecord.error_message ?? 'Export failed.');
          return;
        }

        const csv = await downloadMutation.mutateAsync(exportId);
        await Share.share({
          message: csv,
          title: `report-export-${exportId}.csv`,
        });
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Could not download export.';
        Alert.alert('Download failed', message);
      } finally {
        setProcessingId(null);
        void query.refetch();
      }
    })();
  };

  const exports = query.data ?? [];

  return (
    <>
      <Stack.Screen options={{ title: 'Report exports' }} />
      <Card style={styles.actions}>
        <SectionHeader title="Queue new export" />
        <View style={styles.buttonRow}>
          {EXPORT_TYPES.map((item) => (
            <Button
              key={item.type}
              disabled={createMutation.isPending}
              label={item.label}
              variant="ghost"
              onPress={() => handleCreate(item.type)}
            />
          ))}
        </View>
      </Card>

      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : query.isError ? (
        <ScreenContainer><ErrorState message="Could not load exports." /></ScreenContainer>
      ) : (
        <PaginatedListScreen
          data={exports}
          emptyMessage="No exports yet."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => String(item.id)}
          onRefresh={() => {
            void query.refetch();
          }}
          renderItem={(item) => (
            <ListRow
              right={
                item.status === 'completed' || item.status === 'pending' || item.status === 'processing' ? (
                  <Button
                    disabled={processingId === item.id}
                    label={processingId === item.id ? '…' : 'Download'}
                    variant="ghost"
                    onPress={() => handleDownload(item.id)}
                  />
                ) : (
                  <StatusBadge label={item.status} tone={exportStatusTone(item.status)} />
                )
              }
              showChevron={false}
              subtitle={
                item.error_message
                  ? item.error_message
                  : `${item.status}${item.created_at ? ` · ${item.created_at}` : ''}`
              }
              title={formatExportType(item.type)}
            />
          )}
        />
      )}
    </>
  );
}

const styles = StyleSheet.create({
  actions: {
    marginHorizontal: theme.spacing.xl,
    marginTop: theme.spacing.lg,
  },
  buttonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.sm,
  },
});
