import { type ReactNode } from 'react';
import { Modal, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from './Button';
import { shadow, theme } from '@/src/theme';

type ModalSheetProps = {
  visible: boolean;
  title: string;
  children: ReactNode;
  footer?: ReactNode;
  onClose: () => void;
};

export function ModalSheet({ visible, title, children, footer, onClose }: ModalSheetProps) {
  return (
    <Modal animationType="slide" transparent visible={visible} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={[styles.sheet, shadow('lg')]}>
          <Text style={styles.title}>{title}</Text>
          <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
            {children}
          </ScrollView>
          {footer ? (
            <View style={styles.footerRow}>
              <Button
                label="Cancel"
                onPress={onClose}
                style={styles.footerButton}
                variant="secondary"
              />
              <View style={styles.footerButton}>{footer}</View>
            </View>
          ) : (
            <Button label="Cancel" onPress={onClose} variant="secondary" />
          )}
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    backgroundColor: theme.colors.overlay,
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: theme.colors.surface,
    borderTopLeftRadius: theme.radius.xl,
    borderTopRightRadius: theme.radius.xl,
    maxHeight: '88%',
    padding: theme.spacing.xl,
  },
  title: {
    ...theme.typography.heading,
    color: theme.colors.text,
    marginBottom: theme.spacing.md,
  },
  content: {
    paddingBottom: theme.spacing.md,
  },
  footerRow: {
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginTop: theme.spacing.sm,
  },
  footerButton: {
    flex: 1,
  },
});
