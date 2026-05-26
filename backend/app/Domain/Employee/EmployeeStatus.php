<?php

namespace App\Domain\Employee;

enum EmployeeStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Archived = 'archived';
}
