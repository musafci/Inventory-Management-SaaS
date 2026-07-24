import { Stack } from 'expo-router';

import { CsvImportForm } from '@/components/CsvImportForm';
import { useImportCustomers } from '@/src/hooks/useImports';

export default function ImportCustomersScreen() {
  const mutation = useImportCustomers();

  return (
    <>
      <Stack.Screen options={{ title: 'Import customers' }} />
      <CsvImportForm
        title="Import customers"
        description="Upload a CSV file to bulk-create customers."
        requiredColumns={['name']}
        optionalColumns={['email', 'phone', 'address']}
        onImport={(csv) => mutation.mutateAsync(csv)}
      />
    </>
  );
}
