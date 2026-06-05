"use client";

import type { EmployeeDetail } from "@/types/employee";
import { DetailSection, FieldGrid, FieldItem } from "./DetailBlocks";
import { formatDate, lookupName } from "./detail-utils";

interface EmployeeEmploymentTabProps {
  employee: EmployeeDetail;
  t: (key: string) => string;
}

export function EmployeeEmploymentTab({ employee, t }: EmployeeEmploymentTabProps) {
  return (
    <DetailSection title={t("employeesDetail.employment.title")}>
      <FieldGrid>
        <FieldItem label={t("employeesDetail.fields.employeeNumber")} value={employee.employee_number} />
        <FieldItem label={t("employeesDetail.fields.department")} value={lookupName(employee.department)} />
        <FieldItem label={t("employeesDetail.fields.position")} value={lookupName(employee.position)} />
        <FieldItem label={t("employeesDetail.fields.levelGrade")} value={lookupName(employee.employee_level)} />
        <FieldItem label={t("employeesDetail.fields.supervisor")} value={lookupName(employee.supervisor)} />
        <FieldItem label={t("employeesDetail.fields.workLocation")} value={lookupName(employee.work_location)} />
        <FieldItem label={t("employeesDetail.fields.joinDate")} value={formatDate(employee.join_date)} />
        <FieldItem label={t("employeesDetail.fields.probationEndDate")} value={formatDate(employee.probation_end_date)} />
        <FieldItem label={t("employeesDetail.fields.employeeStatus")} value={employee.status} />
      </FieldGrid>
    </DetailSection>
  );
}
