<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Questionnaire extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'contact_id',
        'title',
        'answers',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'answers' => 'array',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return HasMany<SurveyQuestion, $this>
     */
    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }
}

