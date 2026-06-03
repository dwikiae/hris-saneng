<?php

namespace App\Http\Middleware;

use App\Core\Company\Application\CompanyContext;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompany
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly CompanyRepositoryInterface $companies,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->companyContext->clear();

        $identifier = $this->companyIdentifier($request);
        $user = $request->user();

        if ($identifier === null && $user instanceof User && $user->company_id !== null) {
            $identifier = (string) $user->company_id;
        }

        if ($identifier === null) {
            return $this->errorResponse('company.context.required', Response::HTTP_BAD_REQUEST);
        }

        $company = $this->resolveCompany($identifier);

        if (! $company instanceof Company) {
            return $this->errorResponse('company.context.not_found', Response::HTTP_BAD_REQUEST);
        }

        if ($user instanceof User && $user->company_id !== null && (int) $user->company_id !== (int) $company->getKey()) {
            return $this->errorResponse('company.context.not_found', Response::HTTP_BAD_REQUEST);
        }

        $this->companyContext->set($company);
        $request->attributes->set('company', $company);
        $request->attributes->set('company_id', (int) $company->getKey());

        return $next($request);
    }

    private function companyIdentifier(Request $request): ?string
    {
        $routeCompany = $request->route('company');

        if ($routeCompany instanceof Company) {
            return (string) $routeCompany->getKey();
        }

        if (is_string($routeCompany)) {
            return trim($routeCompany) ?: null;
        }

        $headerCompanyId = $request->header('X-Company-Id');

        if (is_string($headerCompanyId)) {
            return trim($headerCompanyId) ?: null;
        }

        return null;
    }

    private function resolveCompany(string $identifier): ?Company
    {
        return $this->companies->findByIdentifier($identifier);
    }

    private function errorResponse(string $message, int $status): Response
    {
        return response()->json([
            'success' => false,
            'data' => [],
            'message' => $message,
            'meta' => [],
        ], $status);
    }
}
