import { StyleSheet, Text, View } from 'react-native';

import { Button, Input, ModalSheet } from '@/components/ui';
import { theme } from '@/src/theme';

export type LineItemRow = {
  id: number;
  label: string;
  maxQuantity: number;
  quantity: string;
};

type LineItemsActionModalProps = {
  visible: boolean;
  title: string;
  items: LineItemRow[];
  note: string;
  submitting?: boolean;
  submitLabel: string;
  onChangeQuantity: (id: number, quantity: string) => void;
  onChangeNote: (note: string) => void;
  onClose: () => void;
  onSubmit: () => void;
};

export function LineItemsActionModal({
  visible,
  title,
  items,
  note,
  submitting = false,
  submitLabel,
  onChangeQuantity,
  onChangeNote,
  onClose,
  onSubmit,
}: LineItemsActionModalProps) {
  return (
    <ModalSheet
      footer={(
        <Button
          label={submitLabel}
          loading={submitting}
          onPress={onSubmit}
        />
      )}
      title={title}
      visible={visible}
      onClose={onClose}>
      {items.map((item) => (
        <View key={item.id} style={styles.itemRow}>
          <View style={styles.itemBody}>
            <Text style={styles.itemLabel}>{item.label}</Text>
            <Text style={styles.itemMeta}>Max {item.maxQuantity}</Text>
          </View>
          <Input
            keyboardType="number-pad"
            style={styles.qtyInput}
            value={item.quantity}
            onChangeText={(value) => onChangeQuantity(item.id, value)}
          />
        </View>
      ))}
      <Input
        label="Note"
        multiline
        placeholder="Optional note"
        value={note}
        onChangeText={onChangeNote}
      />
    </ModalSheet>
  );
}

const styles = StyleSheet.create({
  itemRow: {
    alignItems: 'center',
    borderBottomColor: theme.colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    paddingVertical: theme.spacing.md,
  },
  itemBody: {
    flex: 1,
    paddingRight: theme.spacing.md,
  },
  itemLabel: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  itemMeta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
  qtyInput: {
    marginBottom: 0,
    minWidth: 72,
    textAlign: 'center',
  },
});
