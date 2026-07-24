import * as DocumentPicker from 'expo-document-picker';
import { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { Button, Card, ScreenScrollView, SectionHeader } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import type { CsvImportResult } from '@/src/api/imports';
import { useNetwork } from '@/src/network/NetworkContext';
import { theme } from '@/src/theme';

type CsvImportFormProps = {
  title: string;
  description: string;
  requiredColumns: string[];
  optionalColumns?: string[];
  onImport: (csv: string) => Promise<CsvImportResult>;
};

export function CsvImportForm({
  title,
  description,
  requiredColumns,
  optionalColumns = [],
  onImport,
}: CsvImportFormProps) {
  const { isConnected } = useNetwork();
  const [fileName, setFileName] = useState<string | null>(null);
  const [result, setResult] = useState<CsvImportResult | null>(null);
  const [isImporting, setIsImporting] = useState(false);

  const pickAndImport = async () => {
    if (!isConnected) {
      Alert.alert('Offline', 'CSV import requires an internet connection.');
      return;
    }

    try {
      const picked = await DocumentPicker.getDocumentAsync({
        type: ['text/csv', 'text/comma-separated-values', 'application/csv', 'text/plain'],
        copyToCacheDirectory: true,
      });

      if (picked.canceled || !picked.assets[0]) {
        return;
      }

      const asset = picked.assets[0];
      const response = await fetch(asset.uri);
      const csv = await response.text();

      if (!csv.trim()) {
        Alert.alert('Empty file', 'The selected CSV file is empty.');
        return;
      }

      setIsImporting(true);
      setResult(null);
      setFileName(asset.name);

      const importResult = await onImport(csv);
      setResult(importResult);

      if (importResult.failed === 0) {
        Alert.alert('Import complete', `${importResult.imported} row(s) imported successfully.`);
      } else {
        Alert.alert(
          'Import finished with errors',
          `${importResult.imported} imported, ${importResult.failed} failed.`,
        );
      }
    } catch (error) {
      const message = error instanceof ApiError ? error.message : 'Import failed.';
      Alert.alert('Import failed', message);
    } finally {
      setIsImporting(false);
    }
  };

  return (
    <ScreenScrollView>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.description}>{description}</Text>

      <Card style={styles.card}>
        <SectionHeader title="Required columns" />
        <Text style={styles.columns}>{requiredColumns.join(', ')}</Text>
        {optionalColumns.length > 0 ? (
          <>
            <View style={styles.sectionSpacing}>
              <SectionHeader title="Optional columns" />
            </View>
            <Text style={styles.columns}>{optionalColumns.join(', ')}</Text>
          </>
        ) : null}
      </Card>

      {!isConnected ? (
        <View style={styles.offlineBanner}>
          <Text style={styles.offlineText}>You are offline. Connect to import CSV files.</Text>
        </View>
      ) : null}

      <Button
        disabled={!isConnected}
        label="Choose CSV file"
        loading={isImporting}
        onPress={() => void pickAndImport()}
      />

      {fileName ? (
        <Text style={styles.fileName}>Selected: {fileName}</Text>
      ) : null}

      {result ? (
        <Card style={styles.card}>
          <SectionHeader title="Results" />
          <Text style={styles.resultLine}>Imported: {result.imported}</Text>
          <Text style={styles.resultLine}>Failed: {result.failed}</Text>
          {result.errors.map((error) => (
            <View key={`row-${error.row}`} style={styles.errorRow}>
              <Text style={styles.errorTitle}>Row {error.row}</Text>
              <Text style={styles.errorMessage}>{error.messages.join(' ')}</Text>
            </View>
          ))}
        </Card>
      ) : null}
    </ScreenScrollView>
  );
}

const styles = StyleSheet.create({
  title: {
    ...theme.typography.title,
    color: theme.colors.text,
    marginBottom: theme.spacing.sm,
  },
  description: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.lg,
  },
  card: {
    marginBottom: theme.spacing.lg,
  },
  sectionSpacing: {
    marginTop: theme.spacing.md,
  },
  columns: {
    ...theme.typography.body,
    color: theme.colors.text,
    marginTop: theme.spacing.sm,
  },
  offlineBanner: {
    backgroundColor: theme.colors.warningSoft,
    borderColor: theme.colors.warning,
    borderRadius: theme.radius.sm,
    borderWidth: 1,
    marginBottom: theme.spacing.lg,
    padding: theme.spacing.md,
  },
  offlineText: {
    color: theme.colors.warning,
    fontSize: 14,
  },
  fileName: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.md,
  },
  resultLine: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
    marginTop: theme.spacing.sm,
  },
  errorRow: {
    borderTopColor: theme.colors.border,
    borderTopWidth: 1,
    marginTop: theme.spacing.md,
    paddingTop: theme.spacing.md,
  },
  errorTitle: {
    color: theme.colors.danger,
    fontSize: 13,
    fontWeight: '700',
  },
  errorMessage: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.xs,
  },
});
