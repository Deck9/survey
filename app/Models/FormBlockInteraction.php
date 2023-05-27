<?php

namespace App\Models;

use Hashids\Hashids;
use Ramsey\Uuid\Uuid;
use App\Models\BaseModel;
use App\Enums\FormBlockInteractionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormBlockInteraction extends BaseModel
{
    use HasFactory;

    public const TEMPLATE_ATTRIBUTES = [
        'type',
        'name',
        'is_editable',
        'is_disabled',
        'label',
        'options',
        'message',
        'sequence',
    ];

    protected $guarded = [];

    protected $casts = [
        'form_block_id' => 'integer',
        'type' => FormBlockInteractionType::class,
        'options' => 'array',
        'is_editable' => 'boolean',
        'is_disabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->uuid = Uuid::uuid4();

            if (!$model->sequence) {
                $model->sequence = self::where('form_block_id', $model->form_block_id)->count();
            }
        });

        self::created(function ($model) {
            $model->update([
                'uuid' => (new Hashids())->encode($model->id),
            ]);
        });

        self::deleted(function ($model) {
            ;
            $model->formBlock->updateInteractionSequence(
                self::where("form_block_id", $model->form_block_id)
                    ->where('type', $model->type)
                    ->pluck('id')
                    ->toArray()
            );
        });
    }

    public function formBlock()
    {
        return $this->belongsTo(FormBlock::class, 'form_block_id');
    }

    public function formSessionResponses()
    {
        return $this->hasMany(FormSessionResponse::class, 'form_block_interaction_id');
    }

    public function getResponsesCountAttribute()
    {
        return $this->formSessionResponses->count();
    }

    public function toTemplate()
    {
        return $this->only(self::TEMPLATE_ATTRIBUTES);
    }
}
