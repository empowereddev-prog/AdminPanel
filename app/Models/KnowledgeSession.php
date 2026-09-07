<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class KnowledgeSession extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function articleSuggestions()
    {
        return $this->hasMany(ArticleSuggestion::class, 'knowledge_session_id', 'id');
    }

    protected static function booted(): void
    {
        static::saved(function (KnowledgeSession $session) {
            \App\Services\DeepLinkService::persistCanonicalUrl($session);
        });
    }

    public function getCanonicalUrlAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }
        if (!$this->id) {
            return null;
        }

        return \App\Services\DeepLinkService::canonicalUrl('article', (int) $this->id);
    }
}
