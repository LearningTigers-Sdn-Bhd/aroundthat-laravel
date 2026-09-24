<?php

use App\Actions\Tags\FindOrCreateTag;
use App\Enums\TagStatus;
use App\Models\Business;
use App\Models\Tag;
use Illuminate\Validation\ValidationException;

test('spellings with the same slug find the same tag', function () {
    $halal = Tag::factory()->create(['name' => 'Halal']);

    $found = app(FindOrCreateTag::class)->handle(Business::factory()->create(), '  HALAL ');

    expect($found->is($halal))->toBeTrue();
    expect(Tag::count())->toBe(1);
});

test('a new name becomes a pending tag of the business that typed it', function () {
    $business = Business::factory()->create();

    $tag = app(FindOrCreateTag::class)->handle($business, 'Pet   friendly');

    expect($tag->name)->toBe('Pet friendly');
    expect($tag->slug)->toBe('pet-friendly');
    expect($tag->status)->toBe(TagStatus::Pending);
    expect($tag->created_by_business_id)->toBe($business->id);
});

test('a merged tag leads to the tag it was merged into', function () {
    $target = Tag::factory()->create(['name' => 'Halal']);
    $duplicate = Tag::factory()->create(['name' => 'Halal certified']);
    $duplicate->forceFill(['merged_into_id' => $target->id])->save();

    expect(app(FindOrCreateTag::class)->handle(Business::factory()->create(), 'halal certified')->is($target))->toBeTrue();
});

test('a tag needs a letter or a digit', function () {
    app(FindOrCreateTag::class)->handle(Business::factory()->create(), '🍜 !!');
})->throws(ValidationException::class, 'A tag must contain a letter or a digit.');

test('a business picks approved tags and only its own pending ones', function () {
    $business = Business::factory()->create();
    $approved = Tag::factory()->create();
    $own = Tag::factory()->pendingFrom($business)->create();
    Tag::factory()->pendingFrom()->create();
    Tag::factory()->rejected()->create();
    Tag::factory()->inactive()->create();

    expect(Tag::pickableBy($business)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$approved->id, $own->id])->sort()->values()->all());
});
