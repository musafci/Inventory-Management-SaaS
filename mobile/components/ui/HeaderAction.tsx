import { Pressable, StyleSheet, Text } from 'react-native';
import { Link, type Href } from 'expo-router';

import { theme } from '@/src/theme';

type HeaderActionProps = {
  label: string;
  href: Href;
};

export function HeaderAction({ label, href }: HeaderActionProps) {
  return (
    <Link href={href} asChild>
      <Pressable style={styles.button}>
        <Text style={styles.label}>{label}</Text>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  button: {
    backgroundColor: theme.colors.primarySoft,
    borderRadius: theme.radius.pill,
    marginRight: theme.spacing.md,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  label: {
    color: theme.colors.primary,
    fontSize: 14,
    fontWeight: '700',
  },
});
