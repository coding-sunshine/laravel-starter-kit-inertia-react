<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $contractor_id
 * @property string $compliance_type
 * @property string $status
 * @property string|null $reference_number
 * @property \Carbon\Carbon|null $issue_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property string|null $document_url
 * @property string|null $notes
 */
final class ContractorCompliance extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'contractor_compliance';

    protected $fillable = [
        'contractor_id',
        'compliance_type',
        'status',
        'reference_number',
        'issue_date',
        'expiry_date',
        'document_url',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
