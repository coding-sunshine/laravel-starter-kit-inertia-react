<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum RiskAssessmentStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case UnderReview = 'under_review';
    case Archived = 'archived';
}
