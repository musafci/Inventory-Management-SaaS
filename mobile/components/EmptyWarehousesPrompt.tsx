import { Link } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

type EmptyWarehousesPromptProps = {
  message: string;
  canCreate?: boolean;
};

export function EmptyWarehousesPrompt({ message, canCreate = true }: EmptyWarehousesPromptProps) {
  return (
    <View style={styles.container}>
      <Text style={styles.helper}>{message}</Text>
      {canCreate ? (
        <Link href="/(app)/warehouses/new" style={styles.link}>
          Add warehouse
        </Link>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  helper: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 16,
    textAlign: 'center',
  },
  link: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '600',
  },
});
