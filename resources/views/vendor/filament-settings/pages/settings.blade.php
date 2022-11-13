<x-filament::page>
    <form wire:submit.prevent="submit">

        {{ $this->form }}

        <x-tables::button type="submit" class="mt-2">
            @lang('Сохранить')
        </x-tables::button>
    </form>
</x-filament::page>
