import { Stack } from 'expo-router';

import { CsvImportForm } from '@/components/CsvImportForm';
import { useImportProducts } from '@/src/hooks/useImports';

export default function ImportProductsScreen() {
  const mutation = useImportProducts();

  return (
    <>
      <Stack.Screen options={{ title: 'Import products' }} />
      <CsvImportForm
        title="Import products"
        description="Upload a CSV file to bulk-create products. Category and unit names must match existing records."
        requiredColumns={['name', 'sku', 'category', 'unit', 'cost_price', 'selling_price']}
        optionalColumns={['barcode', 'tax_rate', 'reorder_point', 'is_active']}
        onImport={(csv) => mutation.mutateAsync(csv)}
      />
    </>
  );
}
