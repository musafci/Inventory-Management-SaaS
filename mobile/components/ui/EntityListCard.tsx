import { StyleSheet, Text, View } from 'react-native';
import { Link, type Href } from 'expo-router';

import { TextAction } from './TextAction';
import { shadow, theme } from '@/src/theme';

type EntityListCardProps = {
  title: string;
  subtitle?: string;
  editHref?: Href;
  canEdit?: boolean;
  canDelete?: boolean;
  onDelete?: () => void;
};

export function EntityListCard({
  title,
  subtitle,
  editHref,
  canEdit = false,
  canDelete = false,
  onDelete,
}: EntityListCardProps) {
  return (
    <View style={[styles.card, shadow('sm')]}>
      <View style={styles.body}>
        <Text style={styles.title} numberOfLines={1}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle} numberOfLines={2}>{subtitle}</Text> : null}
      </View>
      <View style={styles.actions}>
        {canEdit && editHref ? (
          <Link href={editHref}>
            <Text style={styles.editLink}>Edit</Text>
          </Link>
        ) : null}
        {canDelete && onDelete ? (
          <TextAction label="Delete" onPress={onDelete} tone="danger" />
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
    marginHorizontal: theme.spacing.lg,
    padding: theme.spacing.lg,
  },
  body: {
    flex: 1,
    minWidth: 0,
  },
  title: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
    fontSize: 16,
  },
  subtitle: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
  actions: {
    alignItems: 'flex-end',
    gap: theme.spacing.sm,
  },
  editLink: {
    color: theme.colors.primary,
    fontSize: 14,
    fontWeight: '700',
  },
});
