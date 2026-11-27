<nav {{ $attributes->merge(['class' => 'mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-4']) }}>
    <a href="{{ route('home') }}" class="mr-2 whitespace-nowrap text-lg font-bold">Mini-CMS</a>
    <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Accueil</x-nav-link>
    <x-nav-link href="{{ route('posts.index') }}" :active="request()->routeIs('posts.*')">Articles</x-nav-link>
    <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">À propos</x-nav-link>
    <x-nav-link href="{{ route('time') }}" :active="request()->routeIs('time')">Heure</x-nav-link>
    @can('manage-categories')
        <x-nav-link href="{{ route('admin.categories.index') }}" :active="request()->routeIs('admin.categories.*')">Catégories</x-nav-link>
    @endcan
    @can('manage-tags')
        <x-nav-link href="{{ route('admin.tags.index') }}" :active="request()->routeIs('admin.tags.*')">Tags</x-nav-link>
    @endcan
    @can('viewAny', App\Models\User::class)
        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Utilisateurs</x-nav-link>
    @endcan

    @auth
        <x-nav-link href="{{ route('my-posts') }}" :active="request()->routeIs('my-posts')">Mes articles</x-nav-link>
        <span class="ml-auto whitespace-nowrap text-sm text-gray-600">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">Déconnexion</button>
        </form>
    @else
        <div class="ml-auto flex items-center gap-2">
            <x-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')">Connexion</x-nav-link>
            <x-nav-link href="{{ route('register') }}" :active="request()->routeIs('register')">Inscription</x-nav-link>
        </div>
    @endauth
</nav>
