import type { EmployeeMasterKind } from "@/types/employee-settings";

export type EmployeeSettingsSectionSlug =
  | "departemen"
  | "jabatan"
  | "level-grade"
  | "tipe-kontrak"
  | "lokasi-kerja"
  | "agama"
  | "bank"
  | "jenis-dokumen"
  | "pendidikan"
  | "pengaturan";

export interface EmployeeSettingsSection {
  slug: EmployeeSettingsSectionSlug;
  labelKey: string;
  entityKey: string;
  masterKind?: EmployeeMasterKind;
}

export const employeeSettingsGroups: Array<{ labelKey: string; items: EmployeeSettingsSection[] }> = [
  {
    labelKey: "employeesSettings.nav.masterData",
    items: [
      { slug: "departemen", labelKey: "employeesSettings.sections.departemen", entityKey: "departemen" },
      { slug: "jabatan", labelKey: "employeesSettings.sections.jabatan", entityKey: "jabatan" },
      {
        slug: "level-grade",
        labelKey: "employeesSettings.sections.levelGrade",
        entityKey: "levelGrade",
        masterKind: "employee-levels"
      },
      { slug: "tipe-kontrak", labelKey: "employeesSettings.sections.tipeKontrak", entityKey: "tipeKontrak" },
      {
        slug: "lokasi-kerja",
        labelKey: "employeesSettings.sections.lokasiKerja",
        entityKey: "lokasiKerja",
        masterKind: "work-locations"
      },
      { slug: "agama", labelKey: "employeesSettings.sections.agama", entityKey: "agama" },
      { slug: "bank", labelKey: "employeesSettings.sections.bank", entityKey: "bank" },
      { slug: "jenis-dokumen", labelKey: "employeesSettings.sections.jenisDokumen", entityKey: "jenisDokumen" },
      { slug: "pendidikan", labelKey: "employeesSettings.sections.pendidikan", entityKey: "pendidikan" }
    ]
  },
  {
    labelKey: "employeesSettings.nav.configuration",
    items: [{ slug: "pengaturan", labelKey: "employeesSettings.sections.pengaturan", entityKey: "pengaturan" }]
  }
];

export const defaultEmployeeSettingsSection = "departemen";

export const employeeSettingsSections = employeeSettingsGroups.flatMap((group) => group.items);

export function findEmployeeSettingsSection(slug: string | null): EmployeeSettingsSection {
  return (
    employeeSettingsSections.find((section) => section.slug === slug) ??
    employeeSettingsSections.find((section) => section.slug === defaultEmployeeSettingsSection)!
  );
}
