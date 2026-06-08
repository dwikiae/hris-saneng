import type { ChangeEvent } from "react";
import { Input } from "@/components/ui/input";
import type { MasterDataOption } from "@/types/employee";
import type { WilayahCity, WilayahCountry, WilayahProvince } from "@/types/employee-settings";
import { fieldId } from "./employee-form-tabs";

export interface SelectOption {
  value: string;
  label: string;
}

interface ControlProps {
  field: string;
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
}

export function TextField({ field, value, onChange, disabled, type = "text" }: ControlProps & { type?: string }) {
  return (
    <Input
      id={fieldId(field)}
      type={type}
      value={value}
      disabled={disabled}
      onChange={(event) => onChange(event.target.value)}
    />
  );
}

export function FileField({ field, onChange }: { field: string; onChange: (file: File | null) => void }) {
  return (
    <Input
      id={fieldId(field)}
      type="file"
      accept="image/*"
      onChange={(event: ChangeEvent<HTMLInputElement>) => onChange(event.target.files?.[0] ?? null)}
    />
  );
}

export function TextAreaField({ field, value, onChange, disabled }: ControlProps) {
  return (
    <textarea
      id={fieldId(field)}
      className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
      value={value}
      disabled={disabled}
      onChange={(event) => onChange(event.target.value)}
    />
  );
}

export function SelectField({
  field,
  value,
  options,
  placeholder,
  onChange,
  disabled
}: ControlProps & { options: SelectOption[]; placeholder: string }) {
  return (
    <select
      id={fieldId(field)}
      value={value}
      disabled={disabled}
      className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
      onChange={(event) => onChange(event.target.value)}
    >
      <option value="">{placeholder}</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );
}

export function CheckboxField({
  field,
  checked,
  label,
  onChange
}: {
  field: string;
  checked: boolean;
  label: string;
  onChange: (checked: boolean) => void;
}) {
  return (
    <label className="flex items-center gap-2 rounded-md border border-border p-3 text-sm">
      <input id={fieldId(field)} type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
      <span>{label}</span>
    </label>
  );
}

export function optionsFromMaster(items: MasterDataOption[]): SelectOption[] {
  return items.map((item) => ({ value: String(item.id), label: item.name }));
}

export function optionsFromProvinces(items: WilayahProvince[]): SelectOption[] {
  return items.map((item) => ({ value: item.code, label: item.name }));
}

export function optionsFromCities(items: WilayahCity[]): SelectOption[] {
  return items.map((item) => ({ value: item.code, label: item.name }));
}

export function optionsFromCountries(items: WilayahCountry[]): SelectOption[] {
  return items.map((item) => ({ value: item.code, label: item.name }));
}
