interface PageHeaderProps {
  title: string;
  description?: string;
}

export function PageHeader({ title, description }: PageHeaderProps) {
  return (
    <div className="mb-6">
      <h1>{title}</h1>
      {description ? (
        <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{description}</p>
      ) : null}
    </div>
  );
}
