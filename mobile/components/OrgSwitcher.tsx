import { useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';

export function OrgSwitcher() {
  const { organizations, organizationId, switchOrganization } = useAuth();
  const [open, setOpen] = useState(false);

  if (organizations.length <= 1) {
    const organization = organizations[0];

    return organization ? (
      <Text style={styles.singleOrg} numberOfLines={1}>
        {organization.name}
      </Text>
    ) : null;
  }

  const current = organizations.find((organization) => organization.id === organizationId);

  return (
    <>
      <Pressable onPress={() => setOpen(true)} style={styles.trigger}>
        <Text style={styles.triggerText} numberOfLines={1}>
          {current?.name ?? 'Select organization'}
        </Text>
      </Pressable>

      <Modal animationType="slide" transparent visible={open} onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.sheet}>
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
                  <Text style={styles.optionText}>{organization.name}</Text>
                  <Text style={styles.optionMeta}>{organization.role ?? organization.plan}</Text>
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
  singleOrg: {
    color: '#64748b',
    fontSize: 13,
    maxWidth: 160,
  },
  trigger: {
    backgroundColor: '#f1f5f9',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  triggerText: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '600',
    maxWidth: 160,
  },
  backdrop: {
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '70%',
    padding: 20,
  },
  sheetTitle: {
    color: '#0f172a',
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 12,
  },
  option: {
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 10,
    padding: 14,
  },
  optionActive: {
    backgroundColor: '#eef2ff',
    borderColor: '#6366f1',
  },
  optionText: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '600',
  },
  optionMeta: {
    color: '#64748b',
    fontSize: 12,
    marginTop: 4,
  },
  closeButton: {
    alignItems: 'center',
    marginTop: 8,
    paddingVertical: 12,
  },
  closeButtonText: {
    color: '#6366f1',
    fontSize: 15,
    fontWeight: '600',
  },
});
