import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

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
    <Modal animationType="slide" transparent visible={visible} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.sheet}>
          <Text style={styles.title}>{title}</Text>
          <ScrollView>
            <Text style={styles.label}>Amount</Text>
            <TextInput
              value={amount}
              onChangeText={onChangeAmount}
              keyboardType="decimal-pad"
              style={styles.input}
            />

            <Text style={styles.label}>Method</Text>
            <View style={styles.chipRow}>
              {PAYMENT_METHODS.map((option) => (
                <Pressable
                  key={option.value}
                  onPress={() => onChangeMethod(option.value)}
                  style={[styles.chip, method === option.value ? styles.chipSelected : null]}>
                  <Text
                    style={[
                      styles.chipText,
                      method === option.value ? styles.chipTextSelected : null,
                    ]}>
                    {option.label}
                  </Text>
                </Pressable>
              ))}
            </View>

            <Text style={styles.label}>Reference</Text>
            <TextInput
              value={reference}
              onChangeText={onChangeReference}
              placeholder="Optional reference"
              style={styles.input}
            />

            <Text style={styles.label}>Note</Text>
            <TextInput
              value={note}
              onChangeText={onChangeNote}
              placeholder="Optional note"
              style={styles.input}
            />
          </ScrollView>

          <View style={styles.actions}>
            <Pressable onPress={onClose} style={styles.cancelButton}>
              <Text style={styles.cancelText}>Cancel</Text>
            </Pressable>
            <Pressable
              disabled={submitting}
              onPress={onSubmit}
              style={[styles.submitButton, submitting ? styles.submitDisabled : null]}>
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.submitText}>{submitLabel}</Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '85%',
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 20,
    fontWeight: '700',
    marginBottom: 16,
  },
  label: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginTop: 12,
  },
  input: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    backgroundColor: '#e2e8f0',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  chipSelected: {
    backgroundColor: '#2563eb',
  },
  chipText: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: '#fff',
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 16,
  },
  cancelButton: {
    alignItems: 'center',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    flex: 1,
    paddingVertical: 14,
  },
  cancelText: {
    color: '#334155',
    fontSize: 16,
    fontWeight: '600',
  },
  submitButton: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    flex: 1,
    paddingVertical: 14,
  },
  submitDisabled: {
    opacity: 0.7,
  },
  submitText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
});
