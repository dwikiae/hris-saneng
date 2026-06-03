export interface AuthUser {
  id: number;
  name: string;
  email: string;
  language_preference?: string | null;
  force_password_reset?: boolean;
  permissions: string[];
  roles?: Array<{
    id: number | string;
    code?: string | null;
    name: string;
    company_id?: number | string | null;
    company_name?: string | null;
  }>;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ResetPasswordRequest {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface SetPasswordRequest {
  token: string;
  password: string;
  password_confirmation: string;
}

export interface ChangePasswordRequest {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}
