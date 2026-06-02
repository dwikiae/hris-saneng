export interface AuthUser {
  id: number;
  name: string;
  email: string;
  language_preference?: string | null;
  force_password_reset?: boolean;
  permissions: string[];
}
