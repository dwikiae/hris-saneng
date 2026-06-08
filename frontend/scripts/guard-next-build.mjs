import { execFileSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const frontendRoot = path.resolve(scriptDir, "..").toLowerCase();

function runningProcesses() {
  if (process.platform === "win32") {
    const output = execFileSync(
      "powershell.exe",
      [
        "-NoProfile",
        "-Command",
        "Get-CimInstance Win32_Process -Filter \"Name = 'node.exe'\" | Select-Object ProcessId,CommandLine | ConvertTo-Json -Compress"
      ],
      { encoding: "utf8" }
    ).trim();

    if (!output) {
      return [];
    }

    const parsed = JSON.parse(output);
    return Array.isArray(parsed) ? parsed : [parsed];
  }

  const output = execFileSync("ps", ["-eo", "pid=,command="], { encoding: "utf8" });
  return output
    .split("\n")
    .map((line) => {
      const trimmed = line.trim();
      const match = trimmed.match(/^(\d+)\s+(.+)$/);
      return match ? { ProcessId: Number(match[1]), CommandLine: match[2] } : null;
    })
    .filter(Boolean);
}

function isFrontendNextDev(processInfo) {
  const commandLine = String(processInfo.CommandLine ?? "").toLowerCase();

  return (
    Number(processInfo.ProcessId) !== process.pid &&
    commandLine.includes(frontendRoot) &&
    commandLine.includes("next") &&
    commandLine.includes("dev")
  );
}

const nextDevProcesses = runningProcesses().filter(isFrontendNextDev);

if (nextDevProcesses.length > 0) {
  const processList = nextDevProcesses
    .map((processInfo) => `- PID ${processInfo.ProcessId}: ${processInfo.CommandLine}`)
    .join("\n");

  console.error(
    [
      "Refusing to run production build while frontend next dev is active.",
      "Running next build would overwrite .next and can make localhost render without CSS.",
      "Stop the dev server first, or run npm run build:verify for an isolated verification build.",
      "",
      processList
    ].join("\n")
  );
  process.exit(1);
}
