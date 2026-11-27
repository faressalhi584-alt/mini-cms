<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->admin()->create());
});

function taxonomyPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Article classe',
        'slug' => 'article-classe',
        'body' => 'Contenu assez long pour passer la validation.',
        'is_published' => '1',
    ], $overrides);
}

it('stores the category and tags selected in the form', function () {
    $category = Category::factory()->create();
    $tags = Tag::factory(2)->create();

    $this->post(route('posts.store'), taxonomyPostData([
        'category_id' => $category->id,
        'tags' => $tags->modelKeys(),
    ]))->assertRedirect();

    $post = Post::where('slug', 'article-classe')->firstOrFail();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'category_id' => $category->id,
    ]);
    $this->assertDatabaseHas('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $tags[0]->id,
    ]);
    $this->assertDatabaseHas('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $tags[1]->id,
    ]);
});

it('rejects a category that does not exist', function () {
    $this->post(route('posts.store'), taxonomyPostData(['category_id' => 999]))
        ->assertRedirect()
        ->assertSessionHasErrors('category_id');

    $this->assertDatabaseMissing('posts', ['slug' => 'article-classe']);
});

it('rejects a tag that does not exist', function () {
    $this->post(route('posts.store'), taxonomyPostData(['tags' => [999]]))
        ->assertRedirect()
        ->assertSessionHasErrors('tags.0');

    $this->assertDatabaseMissing('posts', ['slug' => 'article-classe']);
});

it('replaces selected tags when updating a post', function () {
    $post = Post::factory()->create(['slug' => 'article-classe']);
    $category = Category::factory()->create();
    [$removedTag, $keptTag, $addedTag] = Tag::factory(3)->create()->all();
    $post->tags()->attach([$removedTag->id, $keptTag->id]);

    $this->put(route('posts.update', $post), taxonomyPostData([
        'category_id' => $category->id,
        'tags' => [$keptTag->id, $addedTag->id],
    ]))->assertRedirect(route('posts.show', $post));

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'category_id' => $category->id,
    ]);
    $this->assertDatabaseMissing('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $removedTag->id,
    ]);
    $this->assertDatabaseHas('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $keptTag->id,
    ]);
    $this->assertDatabaseHas('post_tag', [
        'post_id' => $post->id,
        'tag_id' => $addedTag->id,
    ]);
});

it('removes all tags when none are selected on update', function () {
    $post = Post::factory()->create(['slug' => 'article-classe']);
    $post->tags()->attach(Tag::factory(2)->create());

    $this->put(route('posts.update', $post), taxonomyPostData())
        ->assertRedirect(route('posts.show', $post));

    $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
});

it('preselects the post category and tags in the edit form', function () {
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $post = Post::factory()->create(['category_id' => $category->id]);
    $post->tags()->attach($tag);

    $this->get(route('posts.edit', $post))
        ->assertOk()
        ->assertSee('value="'.$category->id.'" selected', false)
        ->assertSee('name="tags[]" value="'.$tag->id.'" checked', false);
});

it('keeps selected categories and tags after a validation error', function () {
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $this->from(route('posts.create'))
        ->post(route('posts.store'), taxonomyPostData([
            'title' => '',
            'category_id' => $category->id,
            'tags' => [$tag->id],
        ]))
        ->assertRedirect(route('posts.create'))
        ->assertSessionHasErrors('title');

    $this->get(route('posts.create'))
        ->assertOk()
        ->assertSee('value="'.$category->id.'" selected', false)
        ->assertSee('name="tags[]" value="'.$tag->id.'" checked', false);
});

it('lists only published posts for a category found by slug', function () {
    $category = Category::factory()->create(['name' => 'Tutoriels', 'slug' => 'tutoriels']);
    Post::factory()->published()->create([
        'title' => 'Article publie',
        'category_id' => $category->id,
        'published_at' => '2026-01-03 10:00:00',
    ]);
    Post::factory()->published()->create([
        'title' => 'Article plus ancien',
        'category_id' => $category->id,
        'published_at' => '2026-01-02 10:00:00',
    ]);
    Post::factory(3)->published()->sequence(
        ['title' => 'Article de page 1', 'published_at' => '2026-01-01 10:00:00'],
        ['title' => 'Article de page 1 bis', 'published_at' => '2025-12-31 10:00:00'],
        ['title' => 'Article de page 2', 'published_at' => '2025-12-30 10:00:00'],
    )->create(['category_id' => $category->id]);
    Post::factory()->create([
        'title' => 'Brouillon de la categorie',
        'category_id' => $category->id,
    ]);
    Post::factory()->published()->create(['title' => 'Article hors categorie']);

    $response = $this->get('/posts/categorie/tutoriels');

    $response
        ->assertOk()
        ->assertSee('Catégorie : Tutoriels')
        ->assertSeeInOrder(['Article publie', 'Article plus ancien'])
        ->assertDontSee('Brouillon de la categorie')
        ->assertDontSee('Article hors categorie')
        ->assertDontSee('Article de page 2');

    expect($response->viewData('posts')->total())->toBe(5)
        ->and($response->viewData('posts')->count())->toBe(4);

    $this->get('/posts/categorie/tutoriels?page=2')
        ->assertOk()
        ->assertSee('Article de page 2');
});

it('returns not found for an unknown category slug', function () {
    $this->get('/posts/categorie/inconnue')->assertNotFound();
});

it('links category names on post cards to their category pages', function () {
    $category = Category::factory()->create(['slug' => 'tutoriels']);
    Post::factory()->published()->create(['category_id' => $category->id]);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('href="'.route('posts.category', ['category' => 'tutoriels']).'"', false);
});

it('counts only published posts for each category on the home page', function () {
    $category = Category::factory()->create(['name' => 'Tutoriels', 'slug' => 'tutoriels']);
    Post::factory(2)->published()->create(['category_id' => $category->id]);
    Post::factory()->create(['category_id' => $category->id]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tutoriels (2)')
        ->assertDontSee('Tutoriels (3)');
});

it('lists only published posts for a tag found by slug', function () {
    $tag = Tag::factory()->create(['name' => 'Astuce', 'slug' => 'astuce']);
    $otherTag = Tag::factory()->create();
    $publishedPost = Post::factory()->published()->create([
        'title' => 'Astuce publiee',
        'published_at' => '2026-01-03 10:00:00',
    ]);
    $olderPost = Post::factory()->published()->create([
        'title' => 'Astuce ancienne',
        'published_at' => '2026-01-02 10:00:00',
    ]);
    $draftPost = Post::factory()->create(['title' => 'Astuce en brouillon']);
    $otherPost = Post::factory()->published()->create(['title' => 'Sans ce tag']);
    $publishedPost->tags()->attach($tag);
    $olderPost->tags()->attach($tag);
    $draftPost->tags()->attach($tag);
    $otherPost->tags()->attach($otherTag);

    $this->get('/posts/tag/astuce')
        ->assertOk()
        ->assertSee('Tag : Astuce')
        ->assertSeeInOrder(['Astuce publiee', 'Astuce ancienne'])
        ->assertDontSee('Astuce en brouillon')
        ->assertDontSee('Sans ce tag');
});

it('returns not found for an unknown tag slug', function () {
    $this->get('/posts/tag/inconnu')->assertNotFound();
});

it('links tag names on post cards to their tag pages', function () {
    $tag = Tag::factory()->create(['slug' => 'astuce']);
    $post = Post::factory()->published()->create();
    $post->tags()->attach($tag);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('href="'.route('posts.tag', ['tag' => 'astuce']).'"', false);
});

it('shows published popular tags in count and name order', function () {
    $popularTag = Tag::factory()->create(['name' => 'Populaire']);
    $firstTieTag = Tag::factory()->create(['name' => 'Alpha']);
    $secondTieTag = Tag::factory()->create(['name' => 'Beta']);
    $hiddenTag = Tag::factory()->create(['name' => 'Invisible']);
    Post::factory(3)->published()->create()->each(fn (Post $post) => $post->tags()->attach($popularTag));
    Post::factory(2)->published()->create()->each(fn (Post $post) => $post->tags()->attach($firstTieTag));
    Post::factory(2)->published()->create()->each(fn (Post $post) => $post->tags()->attach($secondTieTag));
    Post::factory()->create()->tags()->attach($hiddenTag);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Populaire (3)', 'Alpha (2)', 'Beta (2)'])
        ->assertDontSee('Invisible');
});

it('hides the popular tags section when no tag has a published post', function () {
    $tag = Tag::factory()->create(['name' => 'Brouillon seulement']);
    Post::factory()->create()->tags()->attach($tag);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Tags populaires')
        ->assertDontSee('Brouillon seulement');
});
