import { Stack } from 'expo-router';

import { CsvImportForm } from '@/components/CsvImportForm';
import { useImportSuppliers } from '@/src/hooks/useImports';

export default function ImportSuppliersScreen() {
  const mutation = useImportSuppliers();

  return (
    <>
      <Stack.Screen options={{ title: 'Import suppliers' }} />
      <CsvImportForm
        title="Import suppliers"
        description="Upload a CSV file to bulk-create suppliers."
        requiredColumns={['name']}
        optionalColumns={['contact_person', 'email', 'phone', 'address']}
        onImport={(csv) => mutation.mutateAsync(csv)}
      />
    </>
  );
}
