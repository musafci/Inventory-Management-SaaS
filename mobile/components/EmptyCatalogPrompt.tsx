import { Link } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

type EmptyCatalogPromptProps = {
  missingCategories: boolean;
  missingUnits: boolean;
  canCreate?: boolean;
};

export function EmptyCatalogPrompt({
  missingCategories,
  missingUnits,
  canCreate = true,
}: EmptyCatalogPromptProps) {
  const parts: string[] = [];

  if (missingCategories) {
    parts.push('category');
  }

  if (missingUnits) {
    parts.push('unit');
  }

  const label = parts.join(' and ');

  return (
    <View style={styles.container}>
      <Text style={styles.helper}>
        Add at least one {label} before creating products.
      </Text>
      {canCreate ? (
        <View style={styles.links}>
          {missingCategories ? (
            <Link href="/(app)/categories/new" style={styles.link}>
              Add category
            </Link>
          ) : null}
          {missingUnits ? (
            <Link href="/(app)/units/new" style={styles.link}>
              Add unit
            </Link>
          ) : null}
        </View>
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
  links: {
    alignItems: 'center',
    gap: 12,
  },
  link: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '600',
  },
});
