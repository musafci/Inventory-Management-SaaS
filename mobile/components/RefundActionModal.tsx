import { StyleSheet, Text, View } from 'react-native';

import { Button, ChipSelect, Input, ModalSheet, SectionHeader } from '@/components/ui';
import type { LineItemRow } from '@/components/LineItemsActionModal';
import type { PaymentMethod } from '@/src/api/types';
import { PAYMENT_METHODS } from '@/src/constants/paymentMethods';
import { theme } from '@/src/theme';

type RefundActionModalProps = {
  visible: boolean;
  title: string;
  amount: string;
  method: PaymentMethod;
  reference: string;
  note: string;
  returnItems: LineItemRow[];
  submitting?: boolean;
  onChangeAmount: (value: string) => void;
  onChangeMethod: (value: PaymentMethod) => void;
  onChangeReference: (value: string) => void;
  onChangeNote: (value: string) => void;
  onChangeReturnQuantity: (id: number, quantity: string) => void;
  onClose: () => void;
  onSubmit: () => void;
};

export function RefundActionModal({
  visible,
  title,
  amount,
  method,
  reference,
  note,
  returnItems,
  submitting = false,
  onChangeAmount,
  onChangeMethod,
  onChangeReference,
  onChangeNote,
  onChangeReturnQuantity,
  onClose,
  onSubmit,
}: RefundActionModalProps) {
  return (
    <ModalSheet
      footer={(
        <Button
          label="Process refund"
          loading={submitting}
          variant="danger"
          onPress={onSubmit}
        />
      )}
      title={title}
      visible={visible}
      onClose={onClose}>
      <Input
        keyboardType="decimal-pad"
        label="Refund amount"
        value={amount}
        onChangeText={onChangeAmount}
      />

      <ChipSelect
        label="Method"
        options={PAYMENT_METHODS}
        value={method}
        onChange={onChangeMethod}
      />

      <Input
        label="Reference"
        placeholder="Optional reference"
        value={reference}
        onChangeText={onChangeReference}
      />

      <Input
        label="Note"
        placeholder="Optional note"
        value={note}
        onChangeText={onChangeNote}
      />

      {returnItems.length > 0 ? (
        <>
          <SectionHeader title="Return items" />
          {returnItems.map((item) => (
            <View key={item.id} style={styles.itemRow}>
              <View style={styles.itemBody}>
                <Text style={styles.itemLabel}>{item.label}</Text>
                <Text style={styles.itemMeta}>Max {item.maxQuantity}</Text>
              </View>
              <Input
                keyboardType="number-pad"
                style={styles.qtyInput}
                value={item.quantity}
                onChangeText={(value) => onChangeReturnQuantity(item.id, value)}
              />
            </View>
          ))}
        </>
      ) : null}
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
