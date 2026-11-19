@props(['post'])

<article {{ $attributes->merge(['class' => 'rounded border border-gray-200 bg-white p-5']) }}>
    @if ($post['featured'])
        <span class="mb-2 inline-block rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">À la une</span>
    @endif

    <h2 class="text-xl font-semibold">
        <a href="{{ route('posts.show', $post['slug']) }}" class="text-red-600 hover:underline">
            {{ $post['title'] }}
        </a>
    </h2>

    <p class="mt-2 text-gray-700">{{ $post['excerpt'] }}</p>
    <p class="mt-3 text-sm text-gray-500">
        {{ $post['author'] }} ·
        <a href="{{ route('posts.archive', $post['year']) }}" class="underline">{{ $post['date'] }}</a>
    </p>
</article>
