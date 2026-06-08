import type { FormTemplateSection } from "@/components/templates";
import type { EmployeeFormErrors, EmployeeFormState } from "./employee-form-state";

export type PlatformT = (key: string, options?: Record<string, string | number>) => string;

export interface EmployeeFormSectionContext {
  state: EmployeeFormState;
  errors: EmployeeFormErrors;
  t: PlatformT;
  update: <K extends keyof EmployeeFormState>(field: K, value: EmployeeFormState[K]) => void;
}

export type EmployeeFormSectionBuilder = (context: EmployeeFormSectionContext) => FormTemplateSection[];
