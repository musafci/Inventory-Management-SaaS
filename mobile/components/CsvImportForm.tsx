import * as DocumentPicker from 'expo-document-picker';
import { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import type { CsvImportResult } from '@/src/api/imports';
import { useNetwork } from '@/src/network/NetworkContext';

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
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.description}>{description}</Text>

      <View style={styles.card}>
        <Text style={styles.sectionTitle}>Required columns</Text>
        <Text style={styles.columns}>{requiredColumns.join(', ')}</Text>
        {optionalColumns.length > 0 ? (
          <>
            <Text style={[styles.sectionTitle, styles.sectionSpacing]}>Optional columns</Text>
            <Text style={styles.columns}>{optionalColumns.join(', ')}</Text>
          </>
        ) : null}
      </View>

      {!isConnected ? (
        <View style={styles.offlineBanner}>
          <Text style={styles.offlineText}>You are offline. Connect to import CSV files.</Text>
        </View>
      ) : null}

      <Pressable
        disabled={isImporting || !isConnected}
        onPress={() => {
          void pickAndImport();
        }}
        style={[styles.button, isImporting || !isConnected ? styles.buttonDisabled : null]}>
        {isImporting ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>Choose CSV file</Text>
        )}
      </Pressable>

      {fileName ? (
        <Text style={styles.fileName}>Selected: {fileName}</Text>
      ) : null}

      {result ? (
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Results</Text>
          <Text style={styles.resultLine}>Imported: {result.imported}</Text>
          <Text style={styles.resultLine}>Failed: {result.failed}</Text>
          {result.errors.map((error) => (
            <View key={`row-${error.row}`} style={styles.errorRow}>
              <Text style={styles.errorTitle}>Row {error.row}</Text>
              <Text style={styles.errorMessage}>{error.messages.join(' ')}</Text>
            </View>
          ))}
        </View>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 24,
    fontWeight: '700',
  },
  description: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 16,
    marginTop: 8,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    padding: 16,
  },
  sectionTitle: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  sectionSpacing: {
    marginTop: 12,
  },
  columns: {
    color: '#0f172a',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
  offlineBanner: {
    backgroundColor: '#fef3c7',
    borderColor: '#fcd34d',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    padding: 12,
  },
  offlineText: {
    color: '#92400e',
    fontSize: 14,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 12,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  fileName: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 12,
  },
  resultLine: {
    color: '#0f172a',
    fontSize: 15,
    marginTop: 6,
  },
  errorRow: {
    borderTopColor: '#e2e8f0',
    borderTopWidth: 1,
    marginTop: 10,
    paddingTop: 10,
  },
  errorTitle: {
    color: '#b91c1c',
    fontSize: 13,
    fontWeight: '700',
  },
  errorMessage: {
    color: '#64748b',
    fontSize: 13,
    lineHeight: 18,
    marginTop: 4,
  },
});
