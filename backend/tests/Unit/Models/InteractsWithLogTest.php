<?php

use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Model;

class InteractsWithLogTestModel extends Model
{
    use InteractsWithLog;

    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'nik',
        'npwp',
        'bank_account_number',
        'salary',
        'allowances',
        'deductions',
    ];

    /**
     * @return array<int, string>
     */
    public function exposedActivityLogAttributes(): array
    {
        return $this->activityLogAttributes();
    }
}

it('excludes sensitive values from activity log attributes', function () {
    $model = new InteractsWithLogTestModel;

    expect($model->exposedActivityLogAttributes())
        ->toBe(['name', 'email']);
});
