export type AuthToken = {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  token_type: string;
};

export type Organization = {
  id: number;
  name: string;
  slug: string;
  email: string | null;
  phone: string | null;
  plan: string;
  status: string;
  trial_ends_at: string | null;
  role?: string;
};

export type User = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  default_organization_id: number | null;
  last_login_at: string | null;
};

export type ImpersonationSession = {
  active: boolean;
  platform_admin_name?: string;
  reason?: string;
  organization_id?: number;
  started_at?: string;
} | null;

export type LoginResponse = {
  user: User;
  organizations: Organization[];
  token: AuthToken;
};

export type MeResponse = {
  user: User;
  organizations: Organization[];
  active_organization_id: number | null;
  permissions: string[];
  impersonation: ImpersonationSession;
};

export type ApiEnvelope<T> = {
  data: T;
  meta?: Record<string, unknown>;
};

export type ApiErrorBody = {
  message: string;
  errors?: Record<string, string[]>;
};
