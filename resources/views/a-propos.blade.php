<x-layout>
    <x-slot:title>À propos - Mini-CMS</x-slot:title>

    <h1 class="mb-6 text-3xl font-bold">À propos</h1>

    <div class="max-w-2xl space-y-4">
        <p class="leading-relaxed text-gray-700">
            Mini-CMS est le projet fil rouge de l'Atelier Framework Côté Serveur, 3e année MDW, ISET Sidi Bouzid.
        </p>

        <x-alert type="info" title="Informations">
            Auteur : {{ $auteur }} — Groupe : {{ $groupe }}
        </x-alert>

        <x-alert type="success" title="Session 04">
            Les composants Blade réutilisables sont maintenant en place.
        </x-alert>
    </div>
</x-layout>