import { Stack } from 'expo-router';
import { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';

import { ApiError } from '@/src/api/client';
import type { ReportExportType } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  pollReportExport,
  useCreateReportExport,
  useDownloadReportExport,
  useReportExports,
} from '@/src/hooks/useReports';

const EXPORT_TYPES: { type: ReportExportType; label: string }[] = [
  { type: 'stock_valuation', label: 'Stock valuation' },
  { type: 'low_stock', label: 'Low stock' },
  { type: 'sales_summary', label: 'Sales summary' },
  { type: 'purchase_summary', label: 'Purchase summary' },
];

function formatExportType(type: string): string {
  return type.replace(/_/g, ' ');
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
      <View style={styles.container}>
        <View style={styles.actions}>
          <Text style={styles.actionsTitle}>Queue new export</Text>
          <View style={styles.buttonRow}>
            {EXPORT_TYPES.map((item) => (
              <Pressable
                key={item.type}
                disabled={createMutation.isPending}
                onPress={() => handleCreate(item.type)}
                style={styles.queueButton}>
                <Text style={styles.queueButtonText}>{item.label}</Text>
              </Pressable>
            ))}
          </View>
        </View>

        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : query.isError ? (
          <View style={styles.centered}>
            <Text style={styles.error}>Could not load exports.</Text>
          </View>
        ) : (
          <OptimizedFlatList
            data={exports}
            keyExtractor={(item) => String(item.id)}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No exports yet.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{formatExportType(item.type)}</Text>
                  <Text style={styles.meta}>
                    {item.status}
                    {item.created_at ? ` · ${item.created_at}` : ''}
                  </Text>
                  {item.error_message ? (
                    <Text style={styles.errorText}>{item.error_message}</Text>
                  ) : null}
                </View>
                {item.status === 'completed' || item.status === 'pending' || item.status === 'processing' ? (
                  <Pressable
                    disabled={processingId === item.id}
                    onPress={() => handleDownload(item.id)}
                    style={styles.downloadButton}>
                    <Text style={styles.downloadText}>
                      {processingId === item.id ? '…' : 'Download'}
                    </Text>
                  </Pressable>
                ) : null}
              </View>
            )}
          />
        )}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
  },
  actions: {
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    padding: 16,
  },
  actionsTitle: {
    color: '#0f172a',
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 12,
  },
  buttonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  queueButton: {
    backgroundColor: '#eff6ff',
    borderColor: '#bfdbfe',
    borderRadius: 8,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  queueButtonText: {
    color: '#2563eb',
    fontSize: 13,
    fontWeight: '600',
  },
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  row: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBody: {
    flex: 1,
  },
  name: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
    textTransform: 'capitalize',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
    textTransform: 'capitalize',
  },
  errorText: {
    color: '#b91c1c',
    fontSize: 12,
    marginTop: 4,
  },
  downloadButton: {
    backgroundColor: '#2563eb',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  downloadText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '600',
  },
});
