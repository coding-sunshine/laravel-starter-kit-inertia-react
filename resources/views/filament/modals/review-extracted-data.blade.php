@php
    $data = $record->extracted_data ?? [];
    $type = $record->type;
@endphp

<div class="space-y-6">
    <!-- Document Information -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-semibold mb-3">Document Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-medium text-gray-700">File Name:</span>
                <p class="text-gray-900">{{ $record->file_name }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700">Processing Type:</span>
                <p class="text-gray-900 capitalize">{{ $record->type }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700">File Size:</span>
                <p class="text-gray-900">{{ $record->formatted_file_size }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700">Processed At:</span>
                <p class="text-gray-900">{{ $record->processing_completed_at?->format('M j, Y g:i A') ?? 'Processing...' }}</p>
            </div>
        </div>
    </div>

    <!-- Extracted Data -->
    <div class="bg-white border rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-4">Extracted {{ ucfirst($type) }} Information</h3>

        @if($type === 'project')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="font-medium text-gray-700">Project Title:</span>
                    <p class="text-gray-900">{{ $data['title'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Estate:</span>
                    <p class="text-gray-900">{{ $data['estate'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Developer:</span>
                    <p class="text-gray-900">{{ $data['developer'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Stage:</span>
                    <p class="text-gray-900">{{ $data['stage'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Project Type:</span>
                    <p class="text-gray-900">{{ $data['projecttype'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Total Lots:</span>
                    <p class="text-gray-900">{{ $data['total_lots'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Price Range:</span>
                    <p class="text-gray-900">
                        @if(isset($data['min_price']) && isset($data['max_price']))
                            ${{ number_format($data['min_price']) }} - ${{ number_format($data['max_price']) }}
                        @else
                            Not found
                        @endif
                    </p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Location:</span>
                    <p class="text-gray-900">{{ $data['location'] ?? 'Not found' }}</p>
                </div>
                @if(isset($data['description']))
                <div class="md:col-span-2">
                    <span class="font-medium text-gray-700">Description:</span>
                    <p class="text-gray-900 mt-1">{{ $data['description'] }}</p>
                </div>
                @endif
            </div>
        @else {{-- lot --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="font-medium text-gray-700">Lot Title:</span>
                    <p class="text-gray-900">{{ $data['title'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Project:</span>
                    <p class="text-gray-900">{{ $data['project_title'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Price:</span>
                    <p class="text-gray-900">${{ isset($data['price']) ? number_format($data['price']) : 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Land Price:</span>
                    <p class="text-gray-900">${{ isset($data['land_price']) ? number_format($data['land_price']) : 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Bedrooms:</span>
                    <p class="text-gray-900">{{ $data['bedrooms'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Bathrooms:</span>
                    <p class="text-gray-900">{{ $data['bathrooms'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Land Size:</span>
                    <p class="text-gray-900">{{ $data['land_size'] ?? 'Not found' }}</p>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Stage:</span>
                    <p class="text-gray-900">{{ $data['stage'] ?? 'Not found' }}</p>
                </div>
            </div>
        @endif

        <!-- Features -->
        @if(isset($data['features']) && is_array($data['features']) && count($data['features']) > 0)
        <div class="mt-6">
            <span class="font-medium text-gray-700">Features:</span>
            <div class="mt-2">
                @foreach($data['features'] as $feature)
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2 mb-2">{{ $feature }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Confidence Level -->
        @if(isset($data['confidence']))
        <div class="mt-4 pt-4 border-t">
            <span class="font-medium text-gray-700">AI Confidence Level:</span>
            <span class="ml-2 px-2 py-1 rounded text-sm
                @if($data['confidence'] === 'High') bg-green-100 text-green-800
                @elseif($data['confidence'] === 'Medium') bg-yellow-100 text-yellow-800
                @else bg-red-100 text-red-800
                @endif">
                {{ $data['confidence'] }}
            </span>
        </div>
        @endif
    </div>

    <!-- Actions Info -->
    <div class="bg-blue-50 p-4 rounded-lg">
        <h4 class="font-medium text-blue-900 mb-2">Next Steps</h4>
        <p class="text-blue-800 text-sm">
            Review the extracted information above. If the data looks correct, click "Approve & Create" to
            create a new {{ $type }} in the system. If the data is incorrect or incomplete, click "Reject"
            to mark this document as rejected.
        </p>
    </div>
</div>