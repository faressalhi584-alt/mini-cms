<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
});

it('lists posts with their category, author and tags without lazy loading', function () {
    $category = Category::factory()->create(['name' => 'Tutoriels']);
    $tag = Tag::factory()->create(['name' => 'Astuce']);
    $author = User::factory()->create(['name' => 'Amira Ben Salah']);

    $posts = Post::factory(3)->published()->create([
        'category_id' => $category->id,
        'user_id' => $author->id,
    ]);

    $posts->each(fn (Post $post) => $post->tags()->attach($tag));

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Tutoriels')
        ->assertSee('Amira Ben Salah')
        ->assertSee('Astuce');
});

it('keeps posts when their category is deleted', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->create(['category_id' => $category->id]);

    $category->delete();

    $this->assertDatabaseHas('posts', ['id' => $post->id]);
    expect($post->fresh()->category_id)->toBeNull();
});

it('replaces a post tags with sync', function () {
    $post = Post::factory()->create();
    $tags = Tag::factory(3)->create();

    $post->tags()->attach([$tags[0]->id, $tags[1]->id]);
    $post->tags()->sync([$tags[1]->id, $tags[2]->id]);

    expect($post->tags()->count())->toBe(2);
    $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id, 'tag_id' => $tags[0]->id]);
    $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $tags[2]->id]);

    $post->tags()->detach();

    $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
    $this->assertDatabaseHas('tags', ['id' => $tags[1]->id]);
});

it('removes pivot rows when a post is deleted without deleting tags', function () {
    $post = Post::factory()->create();
    $tag = Tag::factory()->create();
    $post->tags()->attach($tag);

    $this->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.index'));

    $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});

it('shows up to three other published posts in the same category', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->published()->create([
        'title' => 'Article principal',
        'category_id' => $category->id,
        'published_at' => '2026-01-01 10:00:00',
    ]);

    Post::factory()->published()->create([
        'title' => 'Voisin récent',
        'category_id' => $category->id,
        'published_at' => '2026-01-05 10:00:00',
    ]);
    Post::factory()->published()->create([
        'title' => 'Voisin intermédiaire',
        'category_id' => $category->id,
        'published_at' => '2026-01-04 10:00:00',
    ]);
    Post::factory()->published()->create([
        'title' => 'Voisin ancien',
        'category_id' => $category->id,
        'published_at' => '2026-01-03 10:00:00',
    ]);
    Post::factory()->published()->create([
        'title' => 'Voisin trop ancien',
        'category_id' => $category->id,
        'published_at' => '2026-01-02 10:00:00',
    ]);
    Post::factory()->create([
        'title' => 'Voisin brouillon',
        'category_id' => $category->id,
    ]);
    Post::factory()->published()->create(['title' => 'Autre catégorie']);

    $response = $this->get(route('posts.show', $post));

    $response->assertOk()
        ->assertSeeInOrder(['Voisin récent', 'Voisin intermédiaire', 'Voisin ancien'])
        ->assertDontSee('Voisin trop ancien')
        ->assertDontSee('Voisin brouillon')
        ->assertDontSee('Autre catégorie');

    $relatedHtml = Str::before(Str::after($response->getContent(), '<h2 id="related-title"'), '</section>');
    $this->assertSame(3, substr_count($relatedHtml, '<article'));
    $this->assertStringNotContainsString('Article principal', $relatedHtml);
});

it('hides the related section without a category or other published posts', function () {
    $uncategorizedPost = Post::factory()->create();
    $category = Category::factory()->create();
    $categorizedPost = Post::factory()->create(['category_id' => $category->id]);

    $this->get(route('posts.show', $uncategorizedPost))
        ->assertOk()
        ->assertDontSee('Dans la même catégorie');

    $this->get(route('posts.show', $categorizedPost))
        ->assertOk()
        ->assertDontSee('Dans la même catégorie');
});
