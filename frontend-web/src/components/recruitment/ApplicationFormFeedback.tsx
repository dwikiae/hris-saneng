import { useTranslation } from "next-i18next/pages";

interface FeedbackProps {
  errors: string[];
  apiMessage: string | null;
  isSuccess: boolean;
}

export default function ApplicationFormFeedback({
  errors,
  apiMessage,
  isSuccess,
}: FeedbackProps) {
  const { t } = useTranslation("recruitment");

  if (isSuccess) {
    return (
      <p className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {t("form.success")}
      </p>
    );
  }

  if (errors.length === 0 && !apiMessage) {
    return null;
  }

  return (
    <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {errors.map((error) => (
        <p key={error}>{t(error)}</p>
      ))}
      {apiMessage ? <p>{t(apiMessage, { defaultValue: apiMessage })}</p> : null}
    </div>
  );
}
