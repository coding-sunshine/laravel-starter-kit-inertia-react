<?php

declare(strict_types=1);

namespace App\Enums;

enum OnboardingStep: string
{
    case SetPassword = 'set_password';
    case SignAgreement = 'sign_agreement';
    case CrmTour = 'crm_tour';
    case UploadContacts = 'upload_contacts';
    case ConnectWebsite = 'connect_website';
    case LaunchFlyer = 'launch_flyer';
    case MeetBdm = 'meet_bdm';

    public function label(): string
    {
        return match ($this) {
            self::SetPassword => 'Set Your Password',
            self::SignAgreement => 'Sign Subscriber Agreement',
            self::CrmTour => 'Complete CRM Tour',
            self::UploadContacts => 'Upload Your Contacts',
            self::ConnectWebsite => 'Connect Your Website',
            self::LaunchFlyer => 'Launch Your First Flyer',
            self::MeetBdm => 'Meet Your BDM',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SetPassword => 'Change your temporary password to something memorable.',
            self::SignAgreement => 'Review and sign the subscriber service agreement.',
            self::CrmTour => 'Take the guided tour to learn the key CRM features.',
            self::UploadContacts => 'Import your first contacts from a CSV file.',
            self::ConnectWebsite => 'Create your first PHP, WordPress, or campaign site.',
            self::LaunchFlyer => 'Create and download your first property flyer.',
            self::MeetBdm => 'Schedule a call with your Business Development Manager.',
        };
    }
}
