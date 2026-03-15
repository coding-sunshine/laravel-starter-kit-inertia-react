<x-mail::message>
# Hold Request

Dear Builder,

My name is **{{ $senderName }}** and I am contacting you with a **hold request** on behalf of a client.

@if($project)
**Project:** {{ $project->title }}
@endif

@if(!empty($payload['lot_number']))
**Lot / Property:** {{ $payload['lot_number'] }}
@endif

@if(!empty($payload['client_name']))
**Client Name:** {{ $payload['client_name'] }}
@endif

@if(!empty($payload['hold_duration']))
**Requested Hold Duration:** {{ $payload['hold_duration'] }}
@endif

We would like to formally request a hold on the above lot/property while our client completes their financial assessment. We will confirm or release the hold within the requested timeframe.

@if($customMessage)
**Additional Notes:**
{{ $customMessage }}
@endif

Please confirm receipt of this hold request and advise if the lot/property is available.

You can reach me directly at {{ $senderEmail }}.

Thank you for your assistance.

Kind regards,
**{{ $senderName }}**
{{ $senderEmail }}

<x-mail::subcopy>
This email was sent via Fusion CRM. If you have any questions, please contact us at {{ $senderEmail }}.
</x-mail::subcopy>
</x-mail::message>
