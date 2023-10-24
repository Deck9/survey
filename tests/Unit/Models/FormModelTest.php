<?php

use App\Models\Form;
use App\Models\User;
use App\Models\FormBlock;
use App\Models\FormSession;
use App\Enums\FormBlockType;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use App\Models\FormSessionResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('published_scope_returns_only_published_forms', function () {
    $unpublishedFormA = Form::factory()->unpublished()->create();
    $unpublishedFormB = Form::factory()->state(['published_at' => Carbon::now()->addDay()])->create();
    $publishedFormA = Form::factory()->create();

    // actions
    $publishedForms = Form::published()->get();

    $this->assertTrue($publishedForms->contains($publishedFormA));
    $this->assertFalse($publishedForms->contains($unpublishedFormA));
    $this->assertFalse($publishedForms->contains($unpublishedFormB));
});

test('can_check_if_a_form_is_published', function () {
    $formA = Form::factory()->create(['published_at' => null]);
    $formB = Form::factory()->create(['published_at' => Carbon::now()]);
    $formC = Form::factory()->create(['published_at' => Carbon::now()->addDays(1)]);

    $this->assertFalse($formA->isPublished);
    $this->assertTrue($formB->isPublished);
    $this->assertFalse($formC->isPublished);
});

test('the_route_attribute_returns_the_valid_url_for_the_model', function () {
    $form = Form::factory()->create();

    $this->assertEquals(url($form->uuid), $form->route());
});

test('can_return_the_total_amount_of_related_blocks', function () {
    $form = Form::factory()->create();
    $form->formBlocks()->saveMany(FormBlock::factory()->count(12)->make());

    $this->assertEquals(12, $form->blocksCount());
});

test('can_return_total_amount_of_snippets_with_action_options', function () {
    $form = Form::factory()->create();
    $form->formBlocks()->saveMany(FormBlock::factory()->count(8)->make([
        'type' => FormBlockType::short,
    ]));
    $form->formBlocks()->saveMany(FormBlock::factory()->count(4)->make([
        'type' => FormBlockType::radio,
    ]));
    $form->formBlocks()->saveMany(FormBlock::factory()->count(3)->make([
        'type' => FormBlockType::none,
    ]));

    $this->assertEquals(12, $form->actionBlocksCount());
});

test('can_return_path_to_avatar_if_available', function () {
    Storage::fake();
    $form = Form::factory()->create();

    $this->assertFalse($form->avatar);

    Storage::put($form->uuid . '/avatar.png', UploadedFile::fake()->image('avatar.png'));
    Storage::put($form->uuid . '/avatar.png', UploadedFile::fake()->image('avatar.png'));
    $form->avatar_path = $form->uuid . '/avatar.png';

    $this->assertEquals(asset('images/' . $form->avatar_path), $form->avatar);
});

test('can_return_a_custom_brand_and_contrast_color', function () {
    $form = Form::factory()->create([
        'brand_color' => '#ffffff',
    ]);

    $this->assertEquals('#ffffff', $form->brandColor());
    $this->assertEquals('#000000', $form->contrastColor);

    $form->brand_color = '#000000';
    $this->assertEquals('#ffffff', $form->contrastColor);
});

test('return_dark_color_if_no_brand_color_is_set', function () {
    $form = Form::factory()->create([
        'brand_color' => null,
    ]);

    $this->assertEquals('#000000', $form->brandColor());
    $this->assertEquals('#ffffff', $form->contrastColor);
});

test('array_presentation_contains_brand_and_contrast_color', function () {
    $form = Form::factory()->make([
        'brand_color' => '#ffffff',
    ]);

    $this->assertEquals('#ffffff', $form->toArray()['brand_color']);
    $this->assertEquals('#000000', $form->toArray()['contrast_color']);
});

test('can_return_total_started_sessions_count', function () {
    $form = Form::factory()->create();
    $sessions = FormSession::factory()->count(4)->create([
        'form_id' => $form->id,
    ]);

    foreach ($sessions as $session) {
        FormSessionResponse::factory()->create([
            'form_session_id' => $session->id,
        ]);
    };

    $this->assertEquals(4, $form->totalSessions);
});

test('form_can_return_legal_attributes', function () {
    $user = User::factory()->create([
        'company_name' => 'Test Corp',
        'company_description' => 'Just a test description',
        'privacy_link' => 'https://privacy',
        'legal_notice_link' => 'https://legal',
        'privacy_contact_person' => 'Philipp',
        'privacy_contact_email' => 'privacy@botreach.co',
    ]);

    $form = Form::factory()->create([
        'user_id' => $user->id,
    ]);

    $json = $form->toArray();
    $this->assertEquals('Test Corp', $json['company_name']);
    $this->assertEquals('Just a test description', $json['company_description']);
    $this->assertEquals('https://privacy', $json['active_privacy_link']);
    $this->assertEquals('https://legal', $json['active_legal_notice_link']);
    $this->assertEquals('Philipp', $json['privacy_contact_person']);
    $this->assertEquals('privacy@botreach.co', $json['privacy_contact_email']);

    $form->update([
        'privacy_link' => 'https://otherPrivacyLink',
        'legal_notice_link' => 'https://otherLink',
    ]);

    $json = $form->toArray();
    $this->assertEquals('https://otherPrivacyLink', $json['active_privacy_link']);
    $this->assertEquals('https://otherLink', $json['active_legal_notice_link']);
});
