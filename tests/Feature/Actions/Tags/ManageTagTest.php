<?php

use App\Actions\Tags\ManageTag;
use App\Enums\TagStatus;
use App\Models\Activity;
use App\Models\Outlet;
use App\Models\Tag;
use Illuminate\Validation\ValidationException;

test('rejecting a tag takes it off its outlets and logs it on each', function () {
    $tag = Tag::factory()->pendingFrom()->create(['name' => 'Cheapest in town']);
    $keep = Tag::factory()->create(['name' => 'Halal']);
    $outlet = Outlet::factory()->create();
    $outlet->tags()->attach([$tag->id, $keep->id]);

    app(ManageTag::class)->reject($tag, 'Not a factual label.');

    expect($tag->refresh()->status)->toBe(TagStatus::Rejected);
    expect($outlet->tags()->pluck('tags.id')->all())->toBe([$keep->id]);

    $activity = Activity::forSubject($outlet)->where('event', 'tag_rejected')->sole();
    expect($activity->reason)->toBe('Not a factual label.');
    expect($activity->properties->get('tags'))->toEqual(['old' => ['Cheapest in town', 'Halal'], 'new' => ['Halal']]);
});

test('merging moves outlets to the target without duplicating it', function () {
    $target = Tag::factory()->create(['name' => 'Halal']);
    $duplicate = Tag::factory()->pendingFrom()->create(['name' => 'Halal food']);
    $both = Outlet::factory()->create();
    $both->tags()->attach([$target->id, $duplicate->id]);
    $only = Outlet::factory()->create();
    $only->tags()->attach($duplicate->id);

    app(ManageTag::class)->merge($duplicate, $target);

    expect($duplicate->refresh()->merged_into_id)->toBe($target->id);
    expect($duplicate->is_active)->toBeFalse();
    expect($both->tags()->pluck('tags.id')->all())->toBe([$target->id]);
    expect($only->tags()->pluck('tags.id')->all())->toBe([$target->id]);
});

test('a tag cannot be merged into itself or a rejected tag', function (Closure $target) {
    $tag = Tag::factory()->create();

    app(ManageTag::class)->merge($tag, $target($tag));
})->with([
    'itself' => [fn (Tag $tag) => $tag],
    'rejected' => [fn () => Tag::factory()->rejected()->create()],
])->throws(ValidationException::class);

test('only pending tags can be approved', function () {
    app(ManageTag::class)->approve(Tag::factory()->create());
})->throws(ValidationException::class, 'This tag is not waiting for review.');
