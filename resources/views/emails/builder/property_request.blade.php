<x-mail::message>
# Property Request

Dear Builder,

My name is **{{ $senderName }}** and I am reaching out with a **specific property request** on behalf of a client.

@if($project)
**Project:** {{ $project->title }}
@endif

Our client is looking for a property matching the following requirements:

@if(!empty($payload['bedrooms']))
- **Bedrooms:** {{ $payload['bedrooms'] }}
@endif

@if(!empty($payload['bathrooms']))
- **Bathrooms:** {{ $payload['bathrooms'] }}
@endif

@if(!empty($payload['budget']))
- **Budget:** ${{ number_format((float) $payload['budget'], 0) }}
@endif

@if(!empty($payload['land_size']))
- **Preferred Land Size:** {{ $payload['land_size'] }}
@endif

@if(!empty($payload['preferred_stage']))
- **Preferred Stage:** {{ $payload['preferred_stage'] }}
@endif

@if($customMessage)
**Client Requirements:**
{{ $customMessage }}
@endif

Could you please advise if you have any properties matching these requirements, along with pricing and availability?

You can reach me directly at {{ $senderEmail }}.

Thank you for your time, and I look forward to working with you.

Kind regards,
**{{ $senderName }}**
{{ $senderEmail }}

<x-mail::subcopy>
This email was sent via Fusion CRM. If you have any questions, please contact us at {{ $senderEmail }}.
</x-mail::subcopy>
</x-mail::message>
