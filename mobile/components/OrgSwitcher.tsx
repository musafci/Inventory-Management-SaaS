import { useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SymbolView } from 'expo-symbols';

import { AnimatedPressable } from '@/components/ui/AnimatedPressable';
import { useAuth } from '@/src/auth/AuthContext';
import { shadow, theme } from '@/src/theme';

export function OrgSwitcher() {
  const { organizations, organizationId, switchOrganization } = useAuth();
  const [open, setOpen] = useState(false);

  if (organizations.length <= 1) {
    const organization = organizations[0];

    return organization ? (
      <View style={styles.singleWrap}>
        <SymbolView
          name={{ ios: 'building.2.fill', android: 'business', web: 'business' }}
          size={14}
          tintColor={theme.colors.textSecondary}
        />
        <Text style={styles.singleOrg} numberOfLines={1}>
          {organization.name}
        </Text>
      </View>
    ) : null;
  }

  const current = organizations.find((organization) => organization.id === organizationId);

  return (
    <>
      <AnimatedPressable onPress={() => setOpen(true)} style={[styles.trigger, shadow('sm')]}>
        <SymbolView
          name={{ ios: 'building.2.fill', android: 'business', web: 'business' }}
          size={14}
          tintColor={theme.colors.primary}
        />
        <Text style={styles.triggerText} numberOfLines={1}>
          {current?.name ?? 'Select organization'}
        </Text>
        <SymbolView
          name={{ ios: 'chevron.down', android: 'expand_more', web: 'expand_more' }}
          size={14}
          tintColor={theme.colors.textSecondary}
        />
      </AnimatedPressable>

      <Modal animationType="slide" transparent visible={open} onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <View style={[styles.sheet, shadow('lg')]}>
            <Text style={styles.sheetTitle}>Switch organization</Text>
            <ScrollView>
              {organizations.map((organization) => (
                <Pressable
                  key={organization.id}
                  style={[
                    styles.option,
                    organization.id === organizationId && styles.optionActive,
                  ]}
                  onPress={async () => {
                    await switchOrganization(organization.id);
                    setOpen(false);
                  }}>
                  <View style={styles.optionBody}>
                    <Text style={styles.optionText}>{organization.name}</Text>
                    <Text style={styles.optionMeta}>{organization.role ?? organization.plan}</Text>
                  </View>
                  {organization.id === organizationId ? (
                    <SymbolView
                      name={{ ios: 'checkmark.circle.fill', android: 'check_circle', web: 'check_circle' }}
                      size={20}
                      tintColor={theme.colors.primary}
                    />
                  ) : null}
                </Pressable>
              ))}
            </ScrollView>
            <Pressable onPress={() => setOpen(false)} style={styles.closeButton}>
              <Text style={styles.closeButtonText}>Close</Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  singleWrap: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 6,
    maxWidth: 180,
  },
  singleOrg: {
    color: theme.colors.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  trigger: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 6,
    maxWidth: 190,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  triggerText: {
    color: theme.colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: '700',
  },
  backdrop: {
    backgroundColor: theme.colors.overlay,
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: theme.colors.surface,
    borderTopLeftRadius: theme.radius.xl,
    borderTopRightRadius: theme.radius.xl,
    maxHeight: '70%',
    padding: theme.spacing.xl,
  },
  sheetTitle: {
    ...theme.typography.heading,
    color: theme.colors.text,
    marginBottom: theme.spacing.md,
  },
  option: {
    alignItems: 'center',
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: theme.spacing.sm,
    padding: theme.spacing.lg,
  },
  optionActive: {
    backgroundColor: theme.colors.primarySoft,
    borderColor: theme.colors.primary,
  },
  optionBody: {
    flex: 1,
  },
  optionText: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  optionMeta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: 4,
  },
  closeButton: {
    alignItems: 'center',
    marginTop: theme.spacing.sm,
    paddingVertical: theme.spacing.md,
  },
  closeButtonText: {
    color: theme.colors.primary,
    fontSize: 15,
    fontWeight: '700',
  },
});
