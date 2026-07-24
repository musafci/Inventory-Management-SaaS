import { Pressable, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

import { buttonGradients, palette, theme } from '@/src/theme';

type ChipOption<T extends string> = {
  label: string;
  value: T;
};

type ChipSelectProps<T extends string> = {
  label?: string;
  options: ChipOption<T>[];
  value: T;
  onChange: (value: T) => void;
};

function SelectChip({
  label,
  selected,
  onPress,
}: {
  label: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={[styles.chip, selected ? styles.chipSelectedShell : null]}>
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

export function ChipSelect<T extends string>({
  label,
  options,
  value,
  onChange,
}: ChipSelectProps<T>) {
  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={styles.row}>
        {options.map((option) => (
          <SelectChip
            key={option.value}
            label={option.label}
            selected={option.value === value}
            onPress={() => onChange(option.value)}
          />
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginBottom: theme.spacing.md,
  },
  label: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    fontWeight: '600',
    marginBottom: theme.spacing.sm,
  },
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.sm,
  },
  chip: {
    backgroundColor: theme.colors.surfaceMuted,
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
