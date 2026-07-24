import { Button, ChipSelect, Input, ModalSheet } from '@/components/ui';
import type { PaymentMethod } from '@/src/api/types';
import { PAYMENT_METHODS } from '@/src/constants/paymentMethods';

type PaymentActionModalProps = {
  visible: boolean;
  title: string;
  amount: string;
  method: PaymentMethod;
  reference: string;
  note: string;
  submitting?: boolean;
  submitLabel?: string;
  onChangeAmount: (value: string) => void;
  onChangeMethod: (value: PaymentMethod) => void;
  onChangeReference: (value: string) => void;
  onChangeNote: (value: string) => void;
  onClose: () => void;
  onSubmit: () => void;
};

export function PaymentActionModal({
  visible,
  title,
  amount,
  method,
  reference,
  note,
  submitting = false,
  submitLabel = 'Record payment',
  onChangeAmount,
  onChangeMethod,
  onChangeReference,
  onChangeNote,
  onClose,
  onSubmit,
}: PaymentActionModalProps) {
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
      <Input
        keyboardType="decimal-pad"
        label="Amount"
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
    </ModalSheet>
  );
}
