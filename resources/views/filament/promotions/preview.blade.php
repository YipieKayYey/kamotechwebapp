<div class="space-y-4 p-4">
    <div class="relative bg-gray-900 rounded-lg overflow-hidden" style="height: 300px;">
        @if($promotion->background_image)
            <img src="{{ $promotion->background_image_url }}" alt="Preview" class="absolute inset-0 w-full h-full object-cover opacity-50">
        @else
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-blue-700"></div>
        @endif
        
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-white text-center p-8">
            @if($promotion->welcome_text)
                <p class="text-lg mb-2">{{ $promotion->welcome_text }}</p>
            @endif
            
            <h1 class="text-3xl font-bold mb-4">{{ $promotion->title }}</h1>
            
            @if($promotion->subtitle)
                <p class="text-xl mb-6">{{ $promotion->subtitle }}</p>
            @endif
            
            <div class="flex gap-4">
                <span class="px-6 py-3 bg-orange-500 text-white rounded-lg font-semibold">
                    {{ $promotion->primary_button_text }}
                </span>
                
                @if($promotion->secondary_button_text)
                    <span class="px-6 py-3 border-2 border-white text-white rounded-lg font-semibold">
                        {{ $promotion->secondary_button_text }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <strong>Status:</strong>
            <span class="ml-2">
                @if($promotion->isValid())
                    <span class="text-green-600">✓ Currently Active</span>
                @else
                    <span class="text-red-600">✗ Not Active</span>
                @endif
            </span>
        </div>
        
        <div>
            <strong>Validity:</strong>
            <span class="ml-2">{{ $promotion->start_date->format('M j, Y') }} - {{ $promotion->end_date->format('M j, Y') }}</span>
        </div>
        
        @if($promotion->formatted_discount)
            <div>
                <strong>Discount:</strong>
                <span class="ml-2 font-bold text-orange-600">{{ $promotion->formatted_discount }}</span>
            </div>
        @endif
        
        @if($promotion->promo_code)
            <div>
                <strong>Promo Code:</strong>
                <span class="ml-2 font-mono bg-gray-100 px-2 py-1 rounded">{{ $promotion->promo_code }}</span>
            </div>
        @endif
    </div>
</div>
