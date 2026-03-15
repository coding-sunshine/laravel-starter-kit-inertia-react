<x-mail::message>
# Request for More Information

Dear Builder,

My name is **{{ $senderName }}** and I am reaching out regarding your project.

@if($project)
**Project:** {{ $project->title }}
@endif

I would like to request **more detailed information** about this project, including:

- Project specifications and inclusions
- Construction timeline and expected completion
- Warranty and build quality guarantees
- Site plan and lot layout
- Nearby amenities and infrastructure

@if($customMessage)
**Specific Questions:**
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
