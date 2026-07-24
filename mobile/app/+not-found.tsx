import { Link, Stack } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { EmptyState, ScreenContainer } from '@/components/ui';
import { theme } from '@/src/theme';

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: 'Oops!' }} />
      <ScreenContainer>
        <View style={styles.content}>
          <EmptyState title="This screen doesn't exist." />
          <Link href="/" style={styles.link}>
            <Text style={styles.linkText}>Go to home screen</Text>
          </Link>
        </View>
      </ScreenContainer>
    </>
  );
}

const styles = StyleSheet.create({
  content: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: theme.spacing.xl,
  },
  link: {
    marginTop: theme.spacing.lg,
    paddingVertical: theme.spacing.md,
  },
  linkText: {
    color: theme.colors.primary,
    fontSize: 15,
    fontWeight: '700',
  },
});
