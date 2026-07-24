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
    <Modal animationType="slide" transparent visible={visible} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.sheet}>
          <Text style={styles.title}>{title}</Text>
          <ScrollView style={styles.scroll}>
            {items.map((item) => (
              <View key={item.id} style={styles.itemRow}>
                <View style={styles.itemBody}>
                  <Text style={styles.itemLabel}>{item.label}</Text>
                  <Text style={styles.itemMeta}>Max {item.maxQuantity}</Text>
                </View>
                <TextInput
                  value={item.quantity}
                  onChangeText={(value) => onChangeQuantity(item.id, value)}
                  keyboardType="number-pad"
                  style={styles.qtyInput}
                />
              </View>
            ))}
            <Text style={styles.fieldLabel}>Note</Text>
            <TextInput
              value={note}
              onChangeText={onChangeNote}
              placeholder="Optional note"
              style={styles.noteInput}
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
  scroll: {
    maxHeight: 420,
  },
  itemRow: {
    alignItems: 'center',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    flexDirection: 'row',
    paddingVertical: 12,
  },
  itemBody: {
    flex: 1,
    paddingRight: 12,
  },
  itemLabel: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '600',
  },
  itemMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  qtyInput: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: 8,
    borderWidth: 1,
    fontSize: 16,
    minWidth: 72,
    paddingHorizontal: 10,
    paddingVertical: 8,
    textAlign: 'center',
  },
  fieldLabel: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginTop: 16,
  },
  noteInput: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    minHeight: 80,
    paddingHorizontal: 12,
    paddingVertical: 10,
    textAlignVertical: 'top',
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
