"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { MessageSquare, ShieldCheck } from "lucide-react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { connectChatSocket, chatLogService } from "@/services/platform.service";
import { useAuthStore } from "@/stores/auth.store";
import type { ChatActivityItem, ChatNoteItem } from "@/types/platform";

interface ChatLogProps {
  module: string;
  recordId: string | number;
  activityEndpoint?: string;
  notesEndpoint?: string;
  createNoteEndpoint?: string;
  realtimeChannel?: string;
  initialActivities?: ChatActivityItem[];
  initialNotes?: ChatNoteItem[];
}

type ChatTab = "activity" | "notes";

export function ChatLog({
  module,
  recordId,
  activityEndpoint,
  notesEndpoint,
  createNoteEndpoint,
  realtimeChannel,
  initialActivities = [],
  initialNotes = []
}: ChatLogProps) {
  const { t } = useTranslation("platform");
  const [tab, setTab] = useState<ChatTab>("activity");
  const [noteBody, setNoteBody] = useState("");
  const [liveNotes, setLiveNotes] = useState<ChatNoteItem[]>([]);
  const canViewSensitive = useAuthStore((state) => state.hasPermission(`${module}.view_sensitive`));
  const activitiesQuery = useQuery({
    queryKey: ["chat-log", module, recordId, "activity"],
    queryFn: () => chatLogService.activities(activityEndpoint ?? ""),
    enabled: Boolean(activityEndpoint)
  });
  const notesQuery = useQuery({
    queryKey: ["chat-log", module, recordId, "notes"],
    queryFn: () => chatLogService.notes(notesEndpoint ?? ""),
    enabled: Boolean(notesEndpoint)
  });
  const mutation = useMutation({
    mutationFn: (body: string) => chatLogService.createNote(createNoteEndpoint ?? "", body),
    onSuccess: (note) => {
      setLiveNotes((current) => [note, ...current]);
      setNoteBody("");
      platformToast.success(t("platformBehavior.chat.noteSaved"));
    },
    onError: () => platformToast.error(t("platformBehavior.chat.noteFailed"))
  });
  const activities = activitiesQuery.data?.items ?? initialActivities;
  const notes = [...liveNotes, ...(notesQuery.data?.items ?? initialNotes)];

  useEffect(() => {
    if (!realtimeChannel) {
      return undefined;
    }

    return connectChatSocket(realtimeChannel, (note) => {
      setLiveNotes((current) => [note, ...current]);
    });
  }, [realtimeChannel]);

  const createNote = () => {
    if (!noteBody.trim()) {
      platformToast.warning(t("platformBehavior.chat.noteRequired"));
      return;
    }

    if (createNoteEndpoint) {
      mutation.mutate(noteBody);
      return;
    }

    setLiveNotes((current) => [
      {
        id: `local-${Date.now()}`,
        actor: t("account.name"),
        body: noteBody,
        createdAt: t("platformBehavior.chat.justNow")
      },
      ...current
    ]);
    setNoteBody("");
  };

  return (
    <div className="rounded-lg border border-border bg-background">
      <div className="flex border-b border-border">
        <button
          type="button"
          className={tab === "activity" ? "border-b-2 border-primary px-4 py-3 text-sm font-semibold text-primary" : "px-4 py-3 text-sm font-semibold text-muted-foreground"}
          onClick={() => setTab("activity")}
        >
          {t("platformBehavior.chat.activityTab")}
        </button>
        <button
          type="button"
          className={tab === "notes" ? "border-b-2 border-primary px-4 py-3 text-sm font-semibold text-primary" : "px-4 py-3 text-sm font-semibold text-muted-foreground"}
          onClick={() => setTab("notes")}
        >
          {t("platformBehavior.chat.notesTab")}
        </button>
      </div>

      {tab === "activity" ? (
        <div className="divide-y divide-border">
          {activities.map((activity) => (
            <div key={activity.id} className="p-4">
              <div className="flex gap-3">
                <ShieldCheck className="mt-0.5 h-4 w-4 text-primary" aria-hidden="true" />
                <div className="min-w-0">
                  <p className="text-sm font-medium text-foreground">
                    {activity.actor} · {activity.action}
                  </p>
                  <p className="text-xs text-muted-foreground">{activity.createdAt}</p>
                  {activity.description ? <p className="mt-1 text-sm text-muted-foreground">{activity.description}</p> : null}
                  {activity.changes?.map((change) => (
                    <p key={`${activity.id}-${change.field}`} className="mt-1 text-xs text-muted-foreground">
                      {change.field}:{" "}
                      {change.sensitive && !canViewSensitive ? (
                        <PermissionGate permission={`${module}.view_sensitive`} fallback="redacted">
                          {change.newValue}
                        </PermissionGate>
                      ) : (
                        change.newValue
                      )}
                    </p>
                  ))}
                </div>
              </div>
            </div>
          ))}
          <Button type="button" variant="ghost" className="m-3" disabled={!activitiesQuery.data?.nextCursor}>
            {t("platformBehavior.chat.loadMore")}
          </Button>
        </div>
      ) : (
        <div className="space-y-4 p-4">
          <textarea
            className="min-h-24 w-full rounded-md border border-input px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            value={noteBody}
            placeholder={t("platformBehavior.chat.notePlaceholder")}
            onChange={(event) => setNoteBody(event.target.value)}
          />
          <Button type="button" disabled={mutation.isPending} onClick={createNote}>
            <MessageSquare className="mr-2 h-4 w-4" aria-hidden="true" />
            {t("platformBehavior.chat.saveNote")}
          </Button>
          <div className="divide-y divide-border rounded-lg border border-border">
            {notes.map((note) => (
              <div key={note.id} className="p-3">
                <p className="text-sm font-medium text-foreground">
                  {note.actor} · {note.createdAt}
                </p>
                <p className="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">{note.body}</p>
              </div>
            ))}
          </div>
          <Button type="button" variant="ghost" disabled={!notesQuery.data?.nextCursor}>
            {t("platformBehavior.chat.loadMore")}
          </Button>
        </div>
      )}
    </div>
  );
}
