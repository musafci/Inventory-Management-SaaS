import { Link, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

type HubCardProps = {
  href: Href;
  title: string;
  body: string;
  testID?: string;
};

export function HubCard({ href, title, body, testID }: HubCardProps) {
  return (
    <Link href={href} asChild>
      <Pressable
        accessibilityHint={`Opens ${title}`}
        accessibilityLabel={title}
        accessibilityRole="button"
        style={styles.card}
        testID={testID}>
        <Text style={styles.cardTitle}>{title}</Text>
        <Text style={styles.cardBody}>{body}</Text>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  cardTitle: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  cardBody: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
});
