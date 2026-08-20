<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'year',
        'period_label',
        'file_path',
        'original_filename',
        'file_size',
        'notes',
        'status',
        'viewed_at',
        'uploaded_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'file_size' => 'integer',
        'viewed_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_file_size',
        'month_name',
        'is_viewed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$this->month] ?? "Bulan {$this->month}";
    }

    public function getIsViewedAttribute()
    {
        return !is_null($this->viewed_at);
    }
}
