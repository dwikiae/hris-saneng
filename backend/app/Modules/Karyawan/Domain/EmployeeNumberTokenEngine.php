<?php

namespace App\Modules\Karyawan\Domain;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;

class EmployeeNumberTokenEngine
{
    public const DEFAULT_FORMAT = 'EMP-{SEQ:3}';

    public const TEMP_CONTRACT_TYPE_ATTRIBUTE = 'number_contract_type';

    private const MAX_SEQUENCE_PADDING = 20;

    /**
     * @var array<int, string>
     */
    private const JOIN_FORMATS = ['DDMMYYYY', 'YYYY', 'MM', 'DD'];

    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    public function resolve(string $format, Employee $employee, Company $company): string
    {
        $normalizedFormat = $this->normalizeFormat($format);
        $this->validate($normalizedFormat);
        $sequence = $this->nextSequence((int) $company->getKey(), $normalizedFormat);

        return $this->render($normalizedFormat, $employee, $sequence);
    }

    /**
     * @return array<int, string>
     */
    public function validate(string $format): array
    {
        $tokens = $this->parseTokens($this->normalizeFormat($format));
        $sequenceCount = 0;
        $used = [];

        foreach ($tokens as $token) {
            $definition = $this->definitionFor($token);

            if ($definition['type'] === 'sequence') {
                $sequenceCount++;
            }

            $used[] = $definition['name'];
        }

        if ($sequenceCount === 0) {
            throw new InvalidArgumentException(__('employee.number_format.sequence_required'));
        }

        if ($sequenceCount > 1) {
            throw new InvalidArgumentException(__('employee.number_format.multiple_sequence'));
        }

        return array_values(array_unique($used));
    }

    /**
     * @return array{preview: string, next_sequence: int, tokens_used: array<int, string>, tokens_available: array<int, array<string, mixed>>}
     */
    public function preview(string $format, Company $company): array
    {
        $normalizedFormat = $this->normalizeFormat($format);
        $tokensUsed = $this->validate($normalizedFormat);
        $nextSequence = $this->nextSequence((int) $company->getKey(), $normalizedFormat);

        $employee = new Employee([
            'company_id' => $company->getKey(),
            'join_date' => now()->toDateString(),
        ]);
        $employee->setRelation('department', new Department(['code' => 'OPS', 'name' => 'Operations']));
        $employee->setAttribute(self::TEMP_CONTRACT_TYPE_ATTRIBUTE, 'pkwt');

        return [
            'preview' => $this->render($normalizedFormat, $employee, $nextSequence),
            'next_sequence' => $nextSequence,
            'tokens_used' => $tokensUsed,
            'tokens_available' => $this->tokensAvailable(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tokensAvailable(): array
    {
        return collect([
            ['token' => '{SEQ:N}', 'key' => 'seq', 'example' => '001', 'requires_employee_data' => false],
            ['token' => '{JOIN:DDMMYYYY}', 'key' => 'join_ddmmyyyy', 'example' => now()->format('dmY'), 'requires_employee_data' => true],
            ['token' => '{JOIN:YYYY}', 'key' => 'join_year', 'example' => now()->format('Y'), 'requires_employee_data' => true],
            ['token' => '{JOIN:MM}', 'key' => 'join_month', 'example' => now()->format('m'), 'requires_employee_data' => true],
            ['token' => '{JOIN:DD}', 'key' => 'join_day', 'example' => now()->format('d'), 'requires_employee_data' => true],
            ['token' => '{YYYY}', 'key' => 'year', 'example' => now()->format('Y'), 'requires_employee_data' => false],
            ['token' => '{MM}', 'key' => 'month', 'example' => now()->format('m'), 'requires_employee_data' => false],
            ['token' => '{DEPT_CODE}', 'key' => 'dept_code', 'example' => 'OPS', 'requires_employee_data' => true],
            ['token' => '{CONTRACT_TYPE}', 'key' => 'contract_type', 'example' => 'PKWT', 'requires_employee_data' => true],
        ])->map(fn (array $token): array => [
            'token' => $token['token'],
            'description' => [
                'id' => Lang::get('employee.number_tokens.'.$token['key'], [], 'id'),
                'en' => Lang::get('employee.number_tokens.'.$token['key'], [], 'en'),
            ],
            'example' => $token['example'],
            'requires_employee_data' => $token['requires_employee_data'],
        ])->values()->all();
    }

    public function normalizeFormat(string $format): string
    {
        $trimmed = trim($format);

        return $trimmed === '' ? self::DEFAULT_FORMAT : $trimmed;
    }

    private function nextSequence(int $companyId, string $format): int
    {
        $max = 0;

        foreach ($this->employees->employeeNumbersForCompanyIncludingArchived($companyId) as $number) {
            $sequence = $this->sequenceFromFormattedNumber($format, $number)
                ?? $this->sequenceFromLastNumericSegment($number);

            if ($sequence !== null) {
                $max = max($max, $sequence);
            }
        }

        return $max + 1;
    }

    private function render(string $format, Employee $employee, int $sequence): string
    {
        return preg_replace_callback('/\{([^{}]+)\}/', function (array $matches) use ($employee, $sequence): string {
            $definition = $this->definitionFor((string) $matches[1]);

            return match ($definition['type']) {
                'sequence' => str_pad((string) $sequence, (int) $definition['padding'], '0', STR_PAD_LEFT),
                'join' => $this->joinDate($employee)->format((string) $definition['php_format']),
                'current_date' => now()->format((string) $definition['php_format']),
                'department' => $this->departmentCode($employee),
                'contract_type' => $this->contractType($employee),
            };
        }, $format) ?? $format;
    }

    /**
     * @return array<int, string>
     */
    private function parseTokens(string $format): array
    {
        if (substr_count($format, '{') !== substr_count($format, '}')) {
            throw new InvalidArgumentException(__('employee.number_format.invalid_syntax'));
        }

        preg_match_all('/\{([^{}]*)\}/', $format, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @return array{name: string, type: string, padding?: int, php_format?: string, token_regex?: string}
     */
    private function definitionFor(string $token): array
    {
        if ($token === 'SEQ') {
            return ['name' => 'SEQ', 'type' => 'sequence', 'padding' => 3, 'token_regex' => '\d+'];
        }

        if (preg_match('/^SEQ:(\d+)$/', $token, $matches) === 1 || preg_match('/^SEQ(\d+)$/', $token, $matches) === 1) {
            $padding = (int) $matches[1];

            if ($padding < 1 || $padding > self::MAX_SEQUENCE_PADDING) {
                throw new InvalidArgumentException(__('employee.number_format.invalid_sequence_padding'));
            }

            return ['name' => 'SEQ', 'type' => 'sequence', 'padding' => $padding, 'token_regex' => '\d+'];
        }

        if (preg_match('/^JOIN:(.+)$/', $token, $matches) === 1) {
            $format = strtoupper((string) $matches[1]);

            if (! in_array($format, self::JOIN_FORMATS, true)) {
                throw new InvalidArgumentException(__('employee.number_format.invalid_join_format'));
            }

            return [
                'name' => 'JOIN',
                'type' => 'join',
                'php_format' => $this->phpDateFormat($format),
                'token_regex' => $format === 'DDMMYYYY' ? '\d{8}' : ($format === 'YYYY' ? '\d{4}' : '\d{2}'),
            ];
        }

        return match ($token) {
            'YYYY' => ['name' => 'YYYY', 'type' => 'current_date', 'php_format' => 'Y', 'token_regex' => '\d{4}'],
            'MM' => ['name' => 'MM', 'type' => 'current_date', 'php_format' => 'm', 'token_regex' => '\d{2}'],
            'DD' => ['name' => 'DD', 'type' => 'current_date', 'php_format' => 'd', 'token_regex' => '\d{2}'],
            'DEPT_CODE' => ['name' => 'DEPT_CODE', 'type' => 'department', 'token_regex' => '[A-Za-z0-9_-]+'],
            'CONTRACT_TYPE' => ['name' => 'CONTRACT_TYPE', 'type' => 'contract_type', 'token_regex' => '[A-Za-z]+'],
            default => throw new InvalidArgumentException(__('employee.number_format.unknown_token', ['token' => $token])),
        };
    }

    private function sequenceFromFormattedNumber(string $format, string $number): ?int
    {
        $pattern = '';
        $offset = 0;
        preg_match_all('/\{([^{}]+)\}/', $format, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => $match) {
            $fullToken = (string) $match[0];
            $position = (int) $match[1];
            $definition = $this->definitionFor((string) $matches[1][$index][0]);
            $pattern .= preg_quote(substr($format, $offset, $position - $offset), '/');
            $pattern .= $definition['type'] === 'sequence'
                ? '(?<seq>\d+)'
                : (string) $definition['token_regex'];
            $offset = $position + strlen($fullToken);
        }

        $pattern .= preg_quote(substr($format, $offset), '/');

        if (preg_match('/^'.$pattern.'$/', $number, $numberMatches) !== 1) {
            return null;
        }

        return isset($numberMatches['seq']) ? (int) $numberMatches['seq'] : null;
    }

    private function sequenceFromLastNumericSegment(string $number): ?int
    {
        if (preg_match_all('/\d+/', $number, $matches) === 0) {
            return null;
        }

        return (int) end($matches[0]);
    }

    private function joinDate(Employee $employee): Carbon
    {
        $value = $employee->getAttribute('join_date');

        if ($value === null || $value === '') {
            throw new InvalidArgumentException(__('employee.number_format.join_required'));
        }

        return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
    }

    private function departmentCode(Employee $employee): string
    {
        $department = $employee->getRelationValue('department');
        $code = $department instanceof Department ? $department->getAttribute('code') : null;

        if (! is_string($code) || trim($code) === '') {
            throw new InvalidArgumentException(__('employee.number_format.department_required'));
        }

        return strtoupper(trim($code));
    }

    private function contractType(Employee $employee): string
    {
        $type = $employee->getAttribute(self::TEMP_CONTRACT_TYPE_ATTRIBUTE);

        if (! is_string($type) || trim($type) === '') {
            throw new InvalidArgumentException(__('employee.number_format.contract_type_required'));
        }

        return strtoupper(trim($type));
    }

    private function phpDateFormat(string $format): string
    {
        return match ($format) {
            'DDMMYYYY' => 'dmY',
            'YYYY' => 'Y',
            'MM' => 'm',
            'DD' => 'd',
        };
    }
}
