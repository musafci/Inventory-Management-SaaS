import { StyleSheet, Text, View } from 'react-native';
import { Link, type Href } from 'expo-router';

import { theme } from '@/src/theme';

type SectionHeaderProps = {
  title: string;
  actionLabel?: string;
  actionHref?: Href;
};

export function SectionHeader({ title, actionLabel, actionHref }: SectionHeaderProps) {
  return (
    <View style={styles.row}>
      <Text style={styles.title}>{title}</Text>
      {actionLabel && actionHref ? (
        <Link href={actionHref} style={styles.action}>{actionLabel}</Link>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.md,
    marginTop: theme.spacing.xl,
  },
  title: {
    ...theme.typography.heading,
    color: theme.colors.text,
  },
  action: {
    color: theme.colors.primary,
    fontSize: 14,
    fontWeight: '700',
  },
});
