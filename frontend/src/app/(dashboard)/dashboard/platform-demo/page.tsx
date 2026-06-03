"use client";

import { Plus, Save } from "lucide-react";
import { Suspense } from "react";
import { useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { ApprovalPanel } from "@/components/platform/ApprovalPanel";
import { ApprovalTimeline } from "@/components/platform/ApprovalTimeline";
import { ChatLog } from "@/components/platform/ChatLog";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { DocumentUpload } from "@/components/platform/DocumentUpload";
import { ExportButton } from "@/components/platform/ExportButton";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { EmptyState } from "@/components/shared/EmptyState";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { DetailPageTemplate, FormPageTemplate, ListPageTemplate, WizardShell } from "@/components/templates";
import { useAuthStore } from "@/stores/auth.store";
import type { AuthUser } from "@/types/auth";
import type { PlatformDocument } from "@/types/platform";

interface DemoRow {
  id: string;
  name: string;
  owner: string;
  status: string;
}

const rows: DemoRow[] = [
  { id: "EMP-001", name: "Ayu Pratama", owner: "HR Manager", status: "pending" },
  { id: "EMP-002", name: "Bima Santoso", owner: "People Ops", status: "ready" }
];

export default function PlatformDemoPage() {
  const { t } = useTranslation("platform");
  const setUser = useAuthStore((state) => state.setUser);
  const previousUser = useRef<AuthUser | null>(null);
  const [documents, setDocuments] = useState<PlatformDocument[]>([
    {
      id: "doc-1",
      name: "contract-preview.pdf",
      mimeType: "application/pdf",
      sizeBytes: 420000,
      uploadedBy: "Admin Dictive",
      uploadedAt: "2026-06-03"
    }
  ]);

  useEffect(() => {
    previousUser.current = useAuthStore.getState().user;
    setUser({
      id: 99,
      name: "Demo Admin",
      email: "demo@example.test",
      permissions: ["employee.approve", "employee.view_sensitive"]
    });

    return () => setUser(previousUser.current);
  }, [setUser]);

  return (
    <div className="space-y-10 pb-24">
      <ListPageTemplate
        title={t("platformDemo.list.title")}
        description={t("platformDemo.list.description")}
        actions={[
          { id: "new", label: t("platformDemo.actions.new"), icon: <Plus className="h-4 w-4" /> }
        ]}
        columns={[
          { key: "name", header: t("platformDemo.columns.name"), cell: (row) => row.name },
          { key: "owner", header: t("platformDemo.columns.owner"), cell: (row) => row.owner },
          {
            key: "status",
            header: t("platformDemo.columns.status"),
            cell: (row) => <StatusBadge label={row.status} tone={row.status === "pending" ? "warning" : "success"} />
          }
        ]}
        data={rows}
        getRowId={(row) => row.id}
        emptyState={{ title: t("platformDemo.empty.title"), description: t("platformDemo.empty.description") }}
        isLoading={false}
        summaryItems={[
          { label: t("platformDemo.summary.total"), value: rows.length },
          { label: t("platformDemo.summary.pending"), value: 1 }
        ]}
      />

      <Suspense fallback={null}>
        <DetailPageTemplate
          backUrl="/dashboard"
          backLabel={t("platformDemo.detail.back")}
          breadcrumbs={[{ label: t("nav.dashboard"), href: "/dashboard" }, { label: t("platformDemo.title") }]}
          title={t("platformDemo.detail.title")}
          subtitle={t("platformDemo.detail.subtitle")}
          entityId="EMP-001"
          status={{ label: t("platformDemo.detail.status"), tone: "warning" }}
          approvalSlot={
            <ApprovalPanel
              module="employee"
              status="pending"
              entityName="EMP-001"
              approveEndpoint="/demo/approve"
              rejectEndpoint="/demo/reject"
            />
          }
          tabs={[
            {
              slug: "documents",
              label: t("platformDemo.tabs.documents"),
              content: (
                <DocumentUpload
                  uploadEndpoint="/demo/documents"
                  documents={documents}
                  uploadFile={async (file, onProgress) => {
                    onProgress(100);
                    return {
                      id: `demo-${Date.now()}`,
                      name: file.name,
                      mimeType: file.type,
                      sizeBytes: file.size,
                      uploadedBy: "Demo Admin",
                      uploadedAt: "2026-06-03"
                    };
                  }}
                  onUploaded={(document) => setDocuments((current) => [document, ...current])}
                />
              )
            }
          ]}
          notesContent={
            <div className="space-y-6">
              <ApprovalTimeline
                items={[
                  {
                    id: "tl-1",
                    actor: "HR Manager",
                    action: t("platformDemo.timeline.requested"),
                    createdAt: "2026-06-03 09:10",
                    note: t("platformDemo.timeline.note")
                  }
                ]}
              />
              <ChatLog
                module="employee"
                recordId="EMP-001"
                realtimeChannel="private-demo.employee.EMP-001"
                initialActivities={[
                  {
                    id: "act-1",
                    actor: "System",
                    action: t("platformDemo.chat.updated"),
                    createdAt: "2026-06-03 09:20",
                    changes: [{ field: "salary", newValue: "15000000", sensitive: true }]
                  }
                ]}
                initialNotes={[
                  {
                    id: "note-1",
                    actor: "Demo Admin",
                    body: t("platformDemo.chat.note"),
                    createdAt: "2026-06-03 09:25"
                  }
                ]}
              />
            </div>
          }
        />
      </Suspense>

      <FormPageTemplate
        title={t("platformDemo.form.title")}
        description={t("platformDemo.form.description")}
        backUrl="/dashboard/platform-demo"
        backLabel={t("platformDemo.detail.back")}
        isDirty
        isSubmitting={false}
        footerActions={[{ id: "save", label: t("platformDemo.actions.save"), icon: <Save className="h-4 w-4" />, variant: "primary" }]}
        sections={[
          {
            id: "identity",
            title: t("platformDemo.form.section"),
            fields: [
              { id: "demo-name", label: t("platformDemo.columns.name"), content: <Input defaultValue="Ayu Pratama" />, required: true },
              { id: "demo-owner", label: t("platformDemo.columns.owner"), content: <Input defaultValue="HR Manager" /> }
            ]
          }
        ]}
      />

      <WizardShell
        title={t("platformDemo.wizard.title")}
        currentStepId="platform"
        steps={[
          { id: "platform", label: t("platformDemo.wizard.stepOne"), title: t("platformDemo.wizard.stepOne"), content: <EmptyState title={t("platformDemo.empty.title")} /> },
          { id: "role", label: t("platformDemo.wizard.stepTwo"), title: t("platformDemo.wizard.stepTwo"), content: <EmptyState title={t("platformDemo.empty.title")} /> }
        ]}
      />

      <section className="space-y-4">
        <h2 className="text-xl font-semibold text-foreground">{t("platformDemo.behaviors.title")}</h2>
        <div className="flex flex-wrap gap-3">
          <PermissionGate permission="missing.permission" fallback="disabled">
            <Button type="button">{t("platformDemo.behaviors.disabled")}</Button>
          </PermissionGate>
          <PermissionGate permission="missing.permission" fallback="redacted">
            {t("platformDemo.behaviors.secret")}
          </PermissionGate>
          <ConfirmDialog
            title={t("platformBehavior.confirm.demoTitle")}
            description={t("platformBehavior.confirm.demoDescription", { entity: "EMP-001" })}
            confirmLabel={t("platformBehavior.confirm.demoConfirm")}
            confirmVariant="danger"
            onConfirm={() => platformToast.warning(t("platformBehavior.confirm.demoToast"))}
          >
            <Button type="button" variant="destructive">{t("platformDemo.behaviors.confirm")}</Button>
          </ConfirmDialog>
          <ExportButton endpoint="/demo/export" filename="platform-demo" />
        </div>
      </section>
    </div>
  );
}
