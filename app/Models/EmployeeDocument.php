<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'employment_detail_id',
        'document_type',
        'file_path',
        'issue_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Document belongs to an employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Document may belong to a specific employment period.
     */
    public function employmentDetail()
    {
        return $this->belongsTo(EmploymentDetail::class, 'employment_detail_id');
    }
}
