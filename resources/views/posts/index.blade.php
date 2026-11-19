<x-layout>
    <x-slot:title>{{ $heading }} - Mini-CMS</x-slot:title>

    <h1 class="text-3xl font-bold">{{ $heading }}</h1>

    @forelse ($posts as $slug => $post)
        <div class="mt-6">
            <x-post-card :post="$post" />
        </div>
    @empty
        <x-alert type="warning" title="Aucun article" class="mt-6">
            Aucun article ne correspond à cette sélection.
        </x-alert>
    @endforelse
</x-layout>