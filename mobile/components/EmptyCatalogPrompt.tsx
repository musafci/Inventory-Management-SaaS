import { Link } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { EmptyState } from '@/components/ui';
import { theme } from '@/src/theme';

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
      <EmptyState
        body={
          canCreate
            ? `Add at least one ${label} before creating products.`
            : `Ask an admin to add at least one ${label}.`
        }
        title="Catalog setup required"
      />
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
    padding: theme.spacing.xxl,
  },
  links: {
    alignItems: 'center',
    gap: theme.spacing.md,
    marginTop: theme.spacing.lg,
  },
  link: {
    color: theme.colors.primary,
    fontSize: 16,
    fontWeight: '600',
  },
});
