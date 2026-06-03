import type { ApiResponse } from "@/types/api";
import { unwrapApiData } from "@/types/api";

const defaultApiBaseUrl = "/api/v1";

export function apiBaseUrl(): string {
  return (process.env.NEXT_PUBLIC_API_BASE_URL ?? process.env.NEXT_PUBLIC_API_URL ?? defaultApiBaseUrl).replace(/\/$/, "");
}

export function publicPath(path: string): string {
  const baseUrl = apiBaseUrl();

  if (baseUrl.endsWith("/api/v1")) {
    return `${baseUrl}/public${path}`;
  }

  return `${baseUrl}/api/v1/public${path}`;
}

export function privatePath(path: string): string {
  const baseUrl = apiBaseUrl();

  if (baseUrl.endsWith("/api/v1")) {
    return `${baseUrl}${path}`;
  }

  return `${baseUrl}/api/v1${path}`;
}

function authHeaders(): HeadersInit {
  if (typeof window === "undefined") {
    return {};
  }

  const token = window.localStorage.getItem("dictive_hr_token");

  return token ? { Authorization: `Bearer ${token}` } : {};
}

export function privateHeaders(): HeadersInit {
  return authHeaders();
}

export async function requestJson<T>(url: string, init?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...init?.headers
    }
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  return unwrapApiData(payload);
}

export async function requestPrivateJson<T>(path: string, init?: RequestInit): Promise<T> {
  return requestJson<T>(privatePath(path), {
    ...init,
    headers: {
      ...authHeaders(),
      ...init?.headers
    }
  });
}

export async function requestPrivateBlob(path: string, init?: RequestInit): Promise<Blob> {
  const response = await fetch(privatePath(path), {
    ...init,
    headers: {
      ...authHeaders(),
      ...init?.headers
    }
  });

  if (!response.ok) {
    throw new Error(response.statusText);
  }

  return response.blob();
}

export async function requestPrivateMultipart<T>(path: string, formData: FormData): Promise<T> {
  const response = await fetch(privatePath(path), {
    method: "POST",
    headers: {
      Accept: "application/json",
      ...authHeaders()
    },
    body: formData
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  return unwrapApiData(payload);
}

export async function requestMultipart<T>(url: string, formData: FormData): Promise<T> {
  const response = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json"
    },
    body: formData
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  return unwrapApiData(payload);
}
