import { requestPrivateJson } from "@/services/api-client";
import type { DashboardStatsResponse } from "@/types/dashboard";

export const dashboardService = {
  stats(): Promise<DashboardStatsResponse> {
    return requestPrivateJson<DashboardStatsResponse>("/dashboard/stats");
  },

  approveEmployee(employeeId: number): Promise<unknown> {
    return requestPrivateJson<unknown>(`/employees/${employeeId}/approve`, {
      method: "POST"
    });
  }
};
