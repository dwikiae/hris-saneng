export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message: string;
  meta?: Record<string, unknown>;
  errors?: Record<string, string[]>;
}

export function unwrapApiData<T>(payload: ApiResponse<T>): T {
  if (payload.data === undefined) {
    throw new Error("error.empty_response");
  }

  if (payload.data && typeof payload.data === "object" && "data" in payload.data) {
    return (payload.data as { data: T }).data;
  }

  return payload.data;
}
