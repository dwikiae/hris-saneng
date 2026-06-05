"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Archive, CheckCircle2 } from "lucide-react";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { employeeService } from "@/services/employee.service";
import type { EmployeeContract, EmployeeContractPayload } from "@/types/employee";
import { DetailSection, EmptyTab, SelectInput, TabError, TabSkeleton, TextAreaInput, TextInput } from "./DetailBlocks";
import { FormDialog, formString, nullableString } from "./FormDialog";
import { contractTone, contractTypeLabel, formatDate } from "./detail-utils";

interface EmployeeContractsTabProps {
  employeeId: string;
  enabled: boolean;
  t: (key: string, options?: Record<string, string | number>) => string;
}

export function EmployeeContractsTab({ employeeId, enabled, t }: EmployeeContractsTabProps) {
  const queryClient = useQueryClient();
  const query = useQuery({
    queryKey: ["employees", "detail", employeeId, "contracts"],
    queryFn: () => employeeService.getContracts(employeeId),
    enabled
  });
  const invalidate = async () => {
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "contracts"] });
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "notes"] });
  };
  const createMutation = useMutation({
    mutationFn: (payload: EmployeeContractPayload) => employeeService.createContract(employeeId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const approveMutation = useMutation({
    mutationFn: (contractId: string | number) => employeeService.approveContract(employeeId, contractId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.approved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const archiveMutation = useMutation({
    mutationFn: (contractId: string | number) => employeeService.archiveContract(employeeId, contractId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  if (query.isLoading) {
    return <TabSkeleton />;
  }
  if (query.isError) {
    return <TabError message={t("employeesDetail.api.contracts")} onRetry={() => void query.refetch()} />;
  }

  const contracts = [...(query.data ?? [])].sort((a, b) => String(b.start_date).localeCompare(String(a.start_date)));

  return (
    <DetailSection title={t("employeesDetail.contracts.title")}>
      <div className="mb-4 flex justify-end">
        <PermissionGate permission="employee.update">
          <FormDialog
            title={t("employeesDetail.contracts.newTitle")}
            triggerLabel={t("employeesDetail.contracts.new")}
            onSubmit={(payload) => createMutation.mutateAsync(payload)}
            toPayload={contractPayload}
          >
            <ContractFields t={t} />
          </FormDialog>
        </PermissionGate>
      </div>
      {contracts.length === 0 ? (
        <EmptyTab title={t("employeesDetail.empty.contractsTitle")} description={t("employeesDetail.empty.contractsDescription")} />
      ) : (
        <div className="space-y-3">
          {contracts.map((contract) => (
            <ContractRow
              key={contract.id}
              contract={contract}
              t={t}
              onApprove={(id) => approveMutation.mutate(id)}
              onArchive={(id) => archiveMutation.mutate(id)}
            />
          ))}
        </div>
      )}
    </DetailSection>
  );
}

function ContractRow({
  contract,
  t,
  onApprove,
  onArchive
}: {
  contract: EmployeeContract;
  t: EmployeeContractsTabProps["t"];
  onApprove: (id: string | number) => void;
  onArchive: (id: string | number) => void;
}) {
  return (
    <details
      className={
        contract.status === "active"
          ? "rounded-lg border border-green-200 bg-green-50 p-4"
          : "rounded-lg border border-border bg-background p-4"
      }
    >
      <summary className="flex cursor-pointer list-none flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="font-semibold text-foreground">{contract.contract_number ?? `#${contract.id}`}</p>
          <p className="text-sm text-muted-foreground">
            {contractTypeLabel(contract)} - {formatDate(contract.start_date)} - {formatDate(contract.end_date)}
          </p>
        </div>
        <StatusBadge label={t(`employeesDetail.contractStatus.${contract.status ?? "draft"}`)} tone={contractTone(contract.status)} />
      </summary>
      <div className="mt-4 space-y-3 border-t border-border pt-4 text-sm">
        <p className="text-muted-foreground">{contract.notes ?? t("employeesDetail.values.noNotes")}</p>
        {contract.status === "draft" ? (
          <PermissionGate permission="employee.approve">
            <div className="flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 sm:flex-row sm:items-center sm:justify-between">
              <p className="font-medium text-amber-900">{t("employeesDetail.contracts.pendingApproval")}</p>
              <Button type="button" onClick={() => onApprove(contract.id)}>
                <CheckCircle2 className="mr-2 h-4 w-4" aria-hidden="true" />
                {t("employeesDetail.actions.approve")}
              </Button>
            </div>
          </PermissionGate>
        ) : null}
        <PermissionGate permission="employee.archive">
          <Button type="button" variant="outline" onClick={() => onArchive(contract.id)}>
            <Archive className="mr-2 h-4 w-4" aria-hidden="true" />
            {t("employeesDetail.actions.archive")}
          </Button>
        </PermissionGate>
      </div>
    </details>
  );
}

function ContractFields({ t }: { t: EmployeeContractsTabProps["t"] }) {
  return (
    <>
      <SelectInput label={t("employeesDetail.fields.contractType")} name="contract_type" required>
        <option value="pkwt">PKWT</option>
        <option value="pkwtt">PKWTT</option>
      </SelectInput>
      <TextInput label={t("employeesDetail.fields.contractNumber")} name="contract_number" />
      <TextInput label={t("employeesDetail.fields.startDate")} name="start_date" type="date" required />
      <TextInput label={t("employeesDetail.fields.endDate")} name="end_date" type="date" />
      <TextAreaInput label={t("employeesDetail.fields.notes")} name="notes" />
    </>
  );
}

function contractPayload(formData: FormData): EmployeeContractPayload {
  return {
    contract_type: formString(formData, "contract_type") as "pkwt" | "pkwtt",
    contract_number: nullableString(formData, "contract_number"),
    start_date: formString(formData, "start_date"),
    end_date: nullableString(formData, "end_date"),
    notes: nullableString(formData, "notes")
  };
}
