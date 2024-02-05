<div class="px-6 flex inline-flex justify-items-center">
    <p class="px-3">Account Status: </p>
    <x-filament::badge size="lg" color="success">
        Active
    </x-filament::badge>
</div>
<div class="flex inline-flex justify-items-center px-6">
    <p class="px-3">Member Since: </p>
    <x-filament::badge size="lg" color="primary">
        {{$this->getUser()->created_at->format('d M Y')}}
    </x-filament::badge>
</div>
<hr class="h-px my-4 bg-gray-200 border-0 dark:bg-gray-700">


