<x-mail::message>
# Price List & Availability Request

Dear Builder,

My name is **{{ $senderName }}** and I am reaching out on behalf of our CRM system regarding your project.

@if($project)
**Project:** {{ $project->title }}
@endif

I would like to request your current **price list and availability** for the above project. Specifically:

- Current pricing for available lots/properties
- Current availability and stock
- Any upcoming releases or stage releases
- Special offers or incentives

@if($customMessage)
**Additional Notes:**
{{ $customMessage }}
@endif

Please feel free to contact me directly at {{ $senderEmail }}.

Thank you for your time, and I look forward to hearing from you.

Kind regards,
**{{ $senderName }}**
{{ $senderEmail }}

<x-mail::subcopy>
This email was sent via Fusion CRM. If you have any questions, please contact us at {{ $senderEmail }}.
</x-mail::subcopy>
</x-mail::message>
