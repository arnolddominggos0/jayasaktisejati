    <?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class SlaRule extends Model
    {
        protected $table = 'sla_rules';

        protected $fillable = [
            'mode',
            'activity',
            'pol_id',
            'pod_id',
            'target_days',
            'is_active',
            'valid_from',
            'valid_to',
        ];

        protected $casts = [
            'is_active'  => 'boolean',
            'valid_from' => 'date',
            'valid_to'   => 'date',
        ];
    }
