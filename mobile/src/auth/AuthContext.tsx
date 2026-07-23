import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

import * as authApi from '@/src/api/auth';
import { ApiError } from '@/src/api/client';
import type { ImpersonationSession, MeResponse, Organization, User } from '@/src/api/types';
import * as authStorage from '@/src/auth/storage';
import {
  registerDevicePushToken,
  unregisterDevicePushToken,
} from '@/src/notifications/pushToken';

type AuthContextValue = {
  isLoading: boolean;
  isAuthenticated: boolean;
  user: User | null;
  organizations: Organization[];
  organizationId: number | null;
  permissions: string[];
  impersonation: ImpersonationSession;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  switchOrganization: (organizationId: number) => Promise<void>;
  refreshProfile: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

async function resolveOrganizationId(
  organizations: Organization[],
  preferredOrganizationId: number | null,
): Promise<number | null> {
  if (organizations.length === 0) {
    return null;
  }

  if (
    preferredOrganizationId !== null
    && organizations.some((organization) => organization.id === preferredOrganizationId)
  ) {
    return preferredOrganizationId;
  }

  return organizations[0]?.id ?? null;
}

function applyMeState(
  me: MeResponse,
  fallbackOrganizationId: number | null,
): {
  organizationId: number | null;
  permissions: string[];
  impersonation: ImpersonationSession;
} {
  const organizationId = me.active_organization_id ?? fallbackOrganizationId;

  return {
    organizationId,
    permissions: organizationId !== null ? me.permissions : [],
    impersonation: me.impersonation,
  };
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [isLoading, setIsLoading] = useState(true);
  const [user, setUser] = useState<User | null>(null);
  const [organizations, setOrganizations] = useState<Organization[]>([]);
  const [organizationId, setOrganizationId] = useState<number | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [impersonation, setImpersonation] = useState<ImpersonationSession>(null);

  const refreshProfile = useCallback(async () => {
    const storedOrganizationId = await authStorage.getOrganizationId();
    const accessToken = await authStorage.getAccessToken();

    if (!accessToken) {
      setUser(null);
      setOrganizations([]);
      setOrganizationId(null);
      setPermissions([]);
      setImpersonation(null);

      return;
    }

    try {
      const me = await authApi.fetchMe(storedOrganizationId);
      const nextOrganizationId = me.active_organization_id
        ?? await resolveOrganizationId(me.organizations, storedOrganizationId);

      if (nextOrganizationId !== null) {
        await authStorage.saveOrganizationId(nextOrganizationId);
      }

      setUser(me.user);
      setOrganizations(me.organizations);

      const applied = applyMeState(me, nextOrganizationId);
      setOrganizationId(applied.organizationId);
      setPermissions(applied.permissions);
      setImpersonation(applied.impersonation);
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        const refreshToken = await authStorage.getRefreshToken();

        if (refreshToken) {
          const refreshed = await authApi.refresh(refreshToken);
          await authStorage.saveTokens(
            refreshed.token.access_token,
            refreshed.token.refresh_token,
          );

          const preferredOrganizationId = await resolveOrganizationId(
            refreshed.organizations,
            refreshed.user.default_organization_id,
          );

          if (preferredOrganizationId !== null) {
            await authStorage.saveOrganizationId(preferredOrganizationId);
          }

          setUser(refreshed.user);
          setOrganizations(refreshed.organizations);

          const me = await authApi.fetchMe(preferredOrganizationId);
          const applied = applyMeState(me, preferredOrganizationId);
          setOrganizationId(applied.organizationId);
          setPermissions(applied.permissions);
          setImpersonation(applied.impersonation);

          return;
        }
      }

      await authStorage.clearAuthStorage();
      setUser(null);
      setOrganizations([]);
      setOrganizationId(null);
      setPermissions([]);
      setImpersonation(null);

      throw error;
    }
  }, []);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const accessToken = await authStorage.getAccessToken();

        if (!accessToken) {
          return;
        }

        const storedOrganizationId = await authStorage.getOrganizationId();
        const me = await authApi.fetchMe(storedOrganizationId);
        const nextOrganizationId = me.active_organization_id
          ?? await resolveOrganizationId(me.organizations, storedOrganizationId);

        if (nextOrganizationId !== null) {
          await authStorage.saveOrganizationId(nextOrganizationId);
        }

        if (cancelled) {
          return;
        }

        setUser(me.user);
        setOrganizations(me.organizations);

        const applied = applyMeState(me, nextOrganizationId);
        setOrganizationId(applied.organizationId);
        setPermissions(applied.permissions);
        setImpersonation(applied.impersonation);

        void registerDevicePushToken(applied.organizationId);
      } catch {
        await authStorage.clearAuthStorage();
      } finally {
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const result = await authApi.login(email, password);

    await authStorage.saveTokens(
      result.token.access_token,
      result.token.refresh_token,
    );

    const preferredOrganizationId = await resolveOrganizationId(
      result.organizations,
      result.user.default_organization_id,
    );

    if (preferredOrganizationId !== null) {
      await authStorage.saveOrganizationId(preferredOrganizationId);
    }

    setUser(result.user);
    setOrganizations(result.organizations);

    const me = await authApi.fetchMe(preferredOrganizationId);
    const applied = applyMeState(me, preferredOrganizationId);
    setOrganizationId(applied.organizationId);
    setPermissions(applied.permissions);
    setImpersonation(applied.impersonation);

    void registerDevicePushToken(applied.organizationId);
  }, []);

  const logout = useCallback(async () => {
    const accessToken = await authStorage.getAccessToken();

    if (accessToken) {
      try {
        await unregisterDevicePushToken();
        await authApi.logout(accessToken);
      } catch {
        // Ignore network errors during logout.
      }
    }

    await authStorage.clearAuthStorage();
    setUser(null);
    setOrganizations([]);
    setOrganizationId(null);
    setPermissions([]);
    setImpersonation(null);
  }, []);

  const switchOrganization = useCallback(async (nextOrganizationId: number) => {
    await authStorage.saveOrganizationId(nextOrganizationId);

    const me = await authApi.fetchMe(nextOrganizationId);
    setUser(me.user);
    setOrganizations(me.organizations);

    const applied = applyMeState(me, nextOrganizationId);
    setOrganizationId(applied.organizationId);
    setPermissions(applied.permissions);
    setImpersonation(applied.impersonation);

    void registerDevicePushToken(applied.organizationId);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      isLoading,
      isAuthenticated: user !== null,
      user,
      organizations,
      organizationId,
      permissions,
      impersonation,
      login,
      logout,
      switchOrganization,
      refreshProfile,
    }),
    [
      impersonation,
      isLoading,
      login,
      logout,
      organizationId,
      organizations,
      permissions,
      refreshProfile,
      switchOrganization,
      user,
    ],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (context === null) {
    throw new Error('useAuth must be used within AuthProvider');
  }

  return context;
}
