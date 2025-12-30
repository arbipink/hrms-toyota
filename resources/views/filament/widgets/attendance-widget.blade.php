<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <h2 class="text-xl font-bold">Today's Attendance</h2>
                <span class="text-gray-500">{{ \Carbon\Carbon::now()->format('l, d F Y') }}</span>
            </div>

            <div class="flex items-center gap-4">
                @if(!$todayAttendance)
                    <x-filament::button 
                        wire:click="clockIn"
                        color="success"
                        size="lg"
                    >
                        Clock In Now
                    </x-filament::button>

                @elseif(!$todayAttendance->clock_out_time)
                    <div class="text-right mr-4">
                        <div class="text-sm font-medium">Clocked In at:</div>
                        <div class="text-lg font-bold text-primary-600">
                            {{ \Carbon\Carbon::parse($todayAttendance->clock_in_time)->format('H:i') }}
                        </div>
                        <div class="text-xs {{ $todayAttendance->status === 'LATE' ? 'text-danger-600' : 'text-success-600' }}">
                            {{ $todayAttendance->status }}
                        </div>
                    </div>

                    <x-filament::button 
                        wire:click="clockOut"
                        color="danger"
                        size="lg"
                    >
                        Clock Out
                    </x-filament::button>

                @else
                    <div class="flex gap-6 text-center">
                        <div>
                            <div class="text-xs text-gray-500">In</div>
                            <div class="font-bold">{{ \Carbon\Carbon::parse($todayAttendance->clock_in_time)->format('H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Out</div>
                            <div class="font-bold">{{ \Carbon\Carbon::parse($todayAttendance->clock_out_time)->format('H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Status</div>
                            <span class="@if($todayAttendance->status === 'LATE') text-red-600 @else text-green-600 @endif font-bold">
                                {{ $todayAttendance->status }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>