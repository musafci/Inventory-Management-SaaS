import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

import { buttonGradients, palette, theme } from '@/src/theme';
import type { Warehouse } from '@/src/api/types';

type WarehouseFilterProps = {
  warehouses: Warehouse[];
  value: number | null;
  onChange: (warehouseId: number | null) => void;
};

function FilterChip({
  label,
  selected,
  onPress,
}: {
  label: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable onPress={onPress} style={[styles.chip, selected ? styles.chipSelectedShell : null]}>
      {selected ? (
        <LinearGradient
          colors={[...buttonGradients.primary]}
          end={{ x: 1, y: 0.5 }}
          start={{ x: 0, y: 0.5 }}
          style={StyleSheet.absoluteFill}
        />
      ) : null}
      <Text style={[styles.chipText, selected ? styles.chipTextSelected : null]}>{label}</Text>
    </Pressable>
  );
}

export function WarehouseFilter({ warehouses, value, onChange }: WarehouseFilterProps) {
  return (
    <View style={styles.container}>
      <Text style={styles.label}>Warehouse</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
        <FilterChip label="All" selected={value === null} onPress={() => onChange(null)} />
        {warehouses.map((warehouse) => (
          <FilterChip
            key={warehouse.id}
            label={warehouse.name}
            selected={value === warehouse.id}
            onPress={() => onChange(warehouse.id)}
          />
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
  },
  label: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.sm,
  },
  row: {
    gap: theme.spacing.sm,
  },
  chip: {
    backgroundColor: theme.colors.surface,
    borderColor: palette.slate200,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    overflow: 'hidden',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  chipSelectedShell: {
    backgroundColor: palette.primary600,
    borderColor: palette.primary600,
  },
  chipText: {
    color: palette.slate700,
    fontSize: 13,
    fontWeight: '600',
    zIndex: 1,
  },
  chipTextSelected: {
    color: theme.colors.primaryText,
  },
});
