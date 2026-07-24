import { useState } from 'react';
import { Platform, StyleSheet, TextInput, View, type TextStyle, type TextInputProps } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { theme } from '@/src/theme';
import { appIcon } from '@/src/theme/icons';

type SearchBarProps = TextInputProps;

export function SearchBar(props: SearchBarProps) {
  const [focused, setFocused] = useState(false);

  return (
    <View style={[styles.wrap, focused ? styles.wrapFocused : null]}>
      <SymbolView
        name={appIcon({ ios: 'magnifyingglass', android: 'search', web: 'search' })}
        size={18}
        tintColor={focused ? theme.colors.primary : theme.colors.textMuted}
      />
      <TextInput
        placeholderTextColor={theme.colors.textMuted}
        style={styles.input as TextStyle}
        clearButtonMode="while-editing"
        onFocus={(e) => {
          setFocused(true);
          props.onFocus?.(e);
        }}
        onBlur={(e) => {
          setFocused(false);
          props.onBlur?.(e);
        }}
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
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginHorizontal: theme.spacing.lg,
    marginVertical: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 12,
  },
  wrapFocused: {
    borderColor: theme.colors.primary,
    shadowColor: theme.colors.primary,
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.12,
    shadowRadius: 6,
  },
  input: {
    borderColor: 'transparent',
    borderWidth: 0,
    color: theme.colors.text,
    flex: 1,
    fontSize: 16,
    padding: 0,
    ...(Platform.OS === 'web' ? { outlineStyle: 'none' } : {}),
  },
});
