<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;

class MonitoringAppRule extends Model
{
    public const CATEGORIES = ['productive', 'neutral', 'unproductive'];

    public const MATCH_TYPES = ['app', 'title'];

    public const CATEGORY_COLORS = [
        'productive'   => 'success',
        'neutral'      => 'secondary',
        'unproductive' => 'danger',
    ];

    public const CATEGORY_LABELS = [
        'productive'   => 'Productive',
        'neutral'      => 'Neutral',
        'unproductive' => 'Unproductive',
    ];

    protected $fillable = ['pattern', 'match_type', 'category'];
}
