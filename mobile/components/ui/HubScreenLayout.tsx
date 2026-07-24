import { type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { ScreenHeader } from './ScreenHeader';
import { ScreenScrollView } from './ScreenScrollView';

type HubScreenLayoutProps = {
  title: string;
  description: string;
  eyebrow?: string;
  children: ReactNode;
};

export function HubScreenLayout({ title, description, eyebrow, children }: HubScreenLayoutProps) {
  return (
    <ScreenScrollView>
      <ScreenHeader eyebrow={eyebrow} title={title} subtitle={description} />
      <View style={styles.cards}>{children}</View>
    </ScreenScrollView>
  );
}

const styles = StyleSheet.create({
  cards: {
    gap: 4,
  },
});
