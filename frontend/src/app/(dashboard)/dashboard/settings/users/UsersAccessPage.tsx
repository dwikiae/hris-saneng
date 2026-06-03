"use client";

import { useQuery } from "@tanstack/react-query";
import { Plus, UserCircle } from "lucide-react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { AccessBadges } from "@/components/settings/access/AccessBadges";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { ListPageTemplate } from "@/components/templates";
import { companyService } from "@/services/company.service";
import { roleService } from "@/services/role.service";
import { userService } from "@/services/user.service";
import type { PlatformRole, PlatformUser } from "@/types/access";

function tone(status?: string | null) {
  return status === "active" ? "success" as const : status === "pending" ? "warning" as const : "neutral" as const;
}

export function UsersAccessPage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const searchParams = useSearchParams();
  const activeTab = searchParams.get("tab") === "roles" ? "roles" : "users";
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [companyId, setCompanyId] = useState("");
  const [roleId, setRoleId] = useState("");
  const usersQuery = useQuery({ queryKey: ["settings", "users", search, status, companyId, roleId], queryFn: () => userService.list({ search, status, companyId, roleId, perPage: 20 }) });
  const rolesQuery = useQuery({ queryKey: ["settings", "roles", search, companyId], queryFn: () => roleService.list({ search, companyId, perPage: 20 }) });
  const companiesQuery = useQuery({ queryKey: ["settings", "companies", "lookup"], queryFn: () => companyService.list({ perPage: 100 }) });
  const switchTab = (tab: "users" | "roles") => router.push(`/dashboard/settings/users?tab=${tab}`);

  return (
    <div className="space-y-6">
      <SettingsNav />
      <div className="flex gap-2 border-b border-border pb-3">
        <button type="button" className={activeTab === "users" ? "border-b-2 border-primary px-3 py-2 text-sm font-semibold text-primary" : "px-3 py-2 text-sm font-semibold text-muted-foreground"} onClick={() => switchTab("users")}>{t("settingsAccess.tabs.users")}</button>
        <button type="button" className={activeTab === "roles" ? "border-b-2 border-primary px-3 py-2 text-sm font-semibold text-primary" : "px-3 py-2 text-sm font-semibold text-muted-foreground"} onClick={() => switchTab("roles")}>{t("settingsAccess.tabs.roles")}</button>
      </div>
      {activeTab === "users" ? (
        <ListPageTemplate
          title={t("settingsAccess.users.title")}
          description={t("settingsAccess.users.description")}
          searchValue={search}
          onSearchChange={setSearch}
          filters={<AccessFilters companies={companiesQuery.data?.items ?? []} roles={rolesQuery.data?.items ?? []} status={status} companyId={companyId} roleId={roleId} setStatus={setStatus} setCompanyId={setCompanyId} setRoleId={setRoleId} />}
          activeFilterCount={[status, companyId, roleId].filter(Boolean).length}
          onResetFilters={() => { setStatus(""); setCompanyId(""); setRoleId(""); }}
          actions={[{ id: "new", label: t("settingsAccess.actions.newUser"), href: "/dashboard/settings/users/new", icon: <Plus className="h-4 w-4" />, variant: "primary" }]}
          summaryItems={[
            { label: t("settingsAccess.summary.totalUsers"), value: usersQuery.data?.meta?.total ?? "-" },
            { label: t("settingsAccess.summary.active"), value: usersQuery.data?.meta?.active ?? "-" },
            { label: t("settingsAccess.summary.pendingInvitation"), value: usersQuery.data?.meta?.pending_invitation ?? "-" }
          ]}
          columns={[
            { key: "name", header: t("settingsAccess.columns.name"), cell: (user) => <UserName user={user} /> },
            { key: "email", header: t("settingsAccess.columns.email"), cell: (user) => user.email },
            { key: "roles", header: t("settingsAccess.columns.roles"), cell: (user) => <AccessBadges items={user.roles ?? []} emptyLabel="-" /> },
            { key: "companies", header: t("settingsAccess.columns.companies"), cell: (user) => <AccessBadges items={user.companies ?? []} emptyLabel="-" /> },
            { key: "status", header: t("settingsAccess.columns.status"), cell: (user) => <StatusBadge label={t(`settingsAccess.status.${user.status ?? "unknown"}`)} tone={tone(user.status)} /> },
            { key: "actions", header: t("settingsAccess.columns.actions"), cell: (user) => <OpenButton href={`/dashboard/settings/users/${user.id}`} /> }
          ]}
          data={usersQuery.data?.items ?? []}
          getRowId={(user: PlatformUser) => String(user.id)}
          onRowClick={(user) => router.push(`/dashboard/settings/users/${user.id}`)}
          emptyState={{ title: t("settingsAccess.empty.usersTitle"), description: t("settingsAccess.empty.usersDescription") }}
          isLoading={usersQuery.isLoading}
          error={usersQuery.isError ? new Error(t("settingsAccess.api.usersNotReady")) : null}
        />
      ) : (
        <ListPageTemplate
          title={t("settingsAccess.roles.title")}
          description={t("settingsAccess.roles.description")}
          searchValue={search}
          onSearchChange={setSearch}
          filters={<CompanyFilter companies={companiesQuery.data?.items ?? []} companyId={companyId} setCompanyId={setCompanyId} />}
          activeFilterCount={companyId ? 1 : 0}
          onResetFilters={() => setCompanyId("")}
          actions={[{ id: "new", label: t("settingsAccess.actions.newRole"), href: "/dashboard/settings/roles/new", icon: <Plus className="h-4 w-4" />, variant: "primary" }]}
          columns={[
            { key: "name", header: t("settingsAccess.columns.roleName"), cell: (role) => role.name },
            { key: "company", header: t("settingsAccess.columns.company"), cell: (role) => role.company?.name ?? "-" },
            { key: "users", header: t("settingsAccess.columns.userCount"), cell: (role) => role.userCount ?? "-" },
            { key: "permissions", header: t("settingsAccess.columns.permissionCount"), cell: (role) => role.permissionCount ?? "-" },
            { key: "actions", header: t("settingsAccess.columns.actions"), cell: (role) => <OpenButton href={`/dashboard/settings/roles/${role.id}`} /> }
          ]}
          data={rolesQuery.data?.items ?? []}
          getRowId={(role: PlatformRole) => String(role.id)}
          onRowClick={(role) => router.push(`/dashboard/settings/roles/${role.id}`)}
          emptyState={{ title: t("settingsAccess.empty.rolesTitle"), description: t("settingsAccess.empty.rolesDescription") }}
          isLoading={rolesQuery.isLoading}
          error={rolesQuery.isError ? new Error(t("settingsAccess.api.rolesNotReady")) : null}
        />
      )}
    </div>
  );
}

function UserName({ user }: { user: PlatformUser }) {
  return (
    <div className="flex items-center gap-3">
      <Avatar className="h-9 w-9">
        {user.avatarUrl ? <AvatarImage src={user.avatarUrl} alt={user.name} /> : null}
        <AvatarFallback><UserCircle className="h-4 w-4" /></AvatarFallback>
      </Avatar>
      <span className="font-medium text-foreground">{user.name}</span>
    </div>
  );
}

function OpenButton({ href }: { href: string }) {
  const { t } = useTranslation("platform");
  return <Button asChild size="sm" variant="outline"><Link href={href}>{t("settingsAccess.actions.open")}</Link></Button>;
}

function CompanyFilter({ companies, companyId, setCompanyId }: { companies: Array<{ id: string | number; name: string }>; companyId: string; setCompanyId: (value: string) => void }) {
  const { t } = useTranslation("platform");
  return <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={companyId} onChange={(event) => setCompanyId(event.target.value)}><option value="">{t("settingsAccess.filters.companyAll")}</option>{companies.map((company) => <option key={company.id} value={company.id}>{company.name}</option>)}</select>;
}

function AccessFilters(props: { companies: Array<{ id: string | number; name: string }>; roles: Array<{ id: string | number; name: string }>; status: string; companyId: string; roleId: string; setStatus: (value: string) => void; setCompanyId: (value: string) => void; setRoleId: (value: string) => void }) {
  const { t } = useTranslation("platform");
  return (
    <>
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.status} onChange={(event) => props.setStatus(event.target.value)}>
        <option value="">{t("settingsAccess.filters.statusAll")}</option>
        <option value="active">{t("settingsAccess.status.active")}</option>
        <option value="pending">{t("settingsAccess.status.pending")}</option>
        <option value="inactive">{t("settingsAccess.status.inactive")}</option>
      </select>
      <CompanyFilter companies={props.companies} companyId={props.companyId} setCompanyId={props.setCompanyId} />
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.roleId} onChange={(event) => props.setRoleId(event.target.value)}>
        <option value="">{t("settingsAccess.filters.roleAll")}</option>
        {props.roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
      </select>
    </>
  );
}
