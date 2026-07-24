import { StyleSheet, TextInput, View, type TextInputProps } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { theme } from '@/src/theme';

type SearchBarProps = TextInputProps;

export function SearchBar(props: SearchBarProps) {
  return (
    <View style={styles.wrap}>
      <SymbolView
        name={{ ios: 'magnifyingglass', android: 'search', web: 'search' }}
        size={18}
        tintColor={theme.colors.textMuted}
      />
      <TextInput
        placeholderTextColor={theme.colors.textMuted}
        style={styles.input}
        clearButtonMode="while-editing"
        {...props}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginHorizontal: theme.spacing.lg,
    marginVertical: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 12,
  },
  input: {
    color: theme.colors.text,
    flex: 1,
    fontSize: 16,
    padding: 0,
  },
});
