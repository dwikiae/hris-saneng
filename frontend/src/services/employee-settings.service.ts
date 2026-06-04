import { requestPrivateJson } from "@/services/api-client";
import type { EmployeeModuleSettings } from "@/types/employee-settings";

export const employeeSettingsService = {
  get(company: string | number): Promise<EmployeeModuleSettings> {
    return requestPrivateJson<EmployeeModuleSettings>(`/${company}/employees/settings`);
  },
  update(company: string | number, payload: EmployeeModuleSettings): Promise<EmployeeModuleSettings> {
    return requestPrivateJson<EmployeeModuleSettings>(`/${company}/employees/settings`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  }
};
