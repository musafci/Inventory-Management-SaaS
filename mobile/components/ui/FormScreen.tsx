import { type ReactNode } from 'react';
import { StyleSheet } from 'react-native';

import { Card } from './Card';
import { ScreenScrollView } from './ScreenScrollView';
import { theme } from '@/src/theme';

type FormScreenProps = {
  children: ReactNode;
  footer?: ReactNode;
};

export function FormScreen({ children, footer }: FormScreenProps) {
  return (
    <ScreenScrollView contentContainerStyle={styles.content}>
      <Card style={styles.card}>{children}</Card>
      {footer}
    </ScreenScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    paddingTop: theme.spacing.lg,
  },
  card: {
    marginBottom: theme.spacing.lg,
  },
});
