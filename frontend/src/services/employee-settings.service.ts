import { requestPrivateJson } from "@/services/api-client";
import type { EmployeeModuleSettings, EmployeeNumberFormatPreview } from "@/types/employee-settings";

export const employeeSettingsService = {
  get(company: string | number): Promise<EmployeeModuleSettings> {
    return requestPrivateJson<EmployeeModuleSettings>(`/${company}/employees/settings`);
  },
  update(company: string | number, payload: EmployeeModuleSettings): Promise<EmployeeModuleSettings> {
    return requestPrivateJson<EmployeeModuleSettings>(`/${company}/employees/settings`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  previewNumberFormat(company: string | number, format: string): Promise<EmployeeNumberFormatPreview> {
    return requestPrivateJson<EmployeeNumberFormatPreview>(`/${company}/employees/settings/number-format/preview`, {
      method: "POST",
      body: JSON.stringify({ format })
    });
  }
};
