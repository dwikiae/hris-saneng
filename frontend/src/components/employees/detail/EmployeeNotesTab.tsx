"use client";

import { useQueryClient } from "@tanstack/react-query";
import { ChatLog } from "@/components/platform/ChatLog";
import { employeeNoteToChat, employeeService } from "@/services/employee.service";

interface EmployeeNotesTabProps {
  employeeId: string;
}

export function EmployeeNotesTab({ employeeId }: EmployeeNotesTabProps) {
  const queryClient = useQueryClient();

  return (
    <ChatLog
      module="employee"
      recordId={employeeId}
      loadNotes={async () => {
        const notes = await employeeService.getNotes(employeeId);
        return { items: notes.map(employeeNoteToChat) };
      }}
      createNote={async (body) => {
        const note = await employeeService.createNote(employeeId, body);
        await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "notes"] });
        return employeeNoteToChat(note);
      }}
    />
  );
}
