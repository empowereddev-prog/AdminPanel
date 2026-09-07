<?php

namespace App\Services;

use App\Models\KnowledgeSession;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VideoContent;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DeepLinkService
{
    public const TYPE_ARTICLE = 'article';
    public const TYPE_PODCAST = 'podcast';

    public const STATUS_OK = 'ok';
    public const STATUS_NOT_FOUND = 'not_found';
    public const STATUS_UNPUBLISHED = 'unpublished';
    public const STATUS_FORBIDDEN_ROLE = 'forbidden_role';
    public const STATUS_SUBSCRIPTION_REQUIRED = 'subscription_required';

    public static function canonicalUrl(string $type, int $id): string
    {
        $type = self::normalizeType($type);
        $base = config('deeplink.public_base_url');

        return $base . '/d/' . $type . '/' . $id;
    }

    public static function customSchemeUrl(string $type, int $id): string
    {
        $type = self::normalizeType($type);

        return config('deeplink.scheme') . '://' . $type . '/' . $id;
    }

    public static function persistCanonicalUrl(KnowledgeSession|VideoContent $model): void
    {
        $type = $model instanceof KnowledgeSession ? self::TYPE_ARTICLE : self::TYPE_PODCAST;
        $url = self::canonicalUrl($type, (int) $model->id);
        $stored = $model->getAttributes()['canonical_url'] ?? null;
        if ($stored === $url) {
            return;
        }
        $model->setAttribute('canonical_url', $url);
        $model->saveQuietly();
    }

    public static function normalizeType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, config('deeplink.types'), true) ? $type : '';
    }

    /**
     * @return array{
     *   http_status: int,
     *   status: string,
     *   type: string|null,
     *   id: int|null,
     *   title: string|null,
     *   teaser: string|null,
     *   banner: string|null,
     *   user_type: string|null,
     *   canonical_url: string|null,
     *   custom_scheme_url: string|null,
     *   published: bool
     * }
     */
    public function resolve(string $type, $id, ?User $viewer = null, bool $includeTeaser = true): array
    {
        $empty = [
            'http_status' => 404,
            'status' => self::STATUS_NOT_FOUND,
            'type' => null,
            'id' => null,
            'title' => null,
            'teaser' => null,
            'banner' => null,
            'user_type' => null,
            'canonical_url' => null,
            'custom_scheme_url' => null,
            'published' => false,
        ];

        $type = self::normalizeType($type);
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($type === '' || $id === false || $id < 1) {
            return $empty;
        }

        $model = $this->findContent($type, $id);
        if (!$model) {
            $empty['type'] = $type;
            $empty['id'] = $id;
            return $empty;
        }

        $audience = $this->normalizeAudience($model->user_type ?? null);
        $canonical = $model->canonical_url ?: self::canonicalUrl($type, $id);
        $published = ($model->status ?? null) === 'active';

        if (!$published) {
            return [
                'http_status' => 404,
                'status' => self::STATUS_UNPUBLISHED,
                'type' => $type,
                'id' => $id,
                'title' => null,
                'teaser' => null,
                'banner' => null,
                'user_type' => null,
                'canonical_url' => $canonical,
                'custom_scheme_url' => self::customSchemeUrl($type, $id),
                'published' => false,
            ];
        }

        $payload = [
            'http_status' => 200,
            'status' => self::STATUS_OK,
            'type' => $type,
            'id' => $id,
            'title' => $includeTeaser ? ($model->title ?: 'Empowered Health') : null,
            'teaser' => $includeTeaser ? $this->teaser($model->description ?? '') : null,
            'banner' => $includeTeaser ? $this->bannerUrl($model) : null,
            'user_type' => $audience,
            'canonical_url' => $canonical,
            'custom_scheme_url' => self::customSchemeUrl($type, $id),
            'published' => true,
        ];

        if ($viewer) {
            if (!$this->audienceAllows($audience, $viewer, $model)) {
                $payload['http_status'] = 403;
                $payload['status'] = self::STATUS_FORBIDDEN_ROLE;
                $payload['title'] = null;
                $payload['teaser'] = null;
                $payload['banner'] = null;
                return $payload;
            }

            if ($this->requiresSubscription($viewer) && !$this->hasActiveSubscription($viewer)) {
                $payload['http_status'] = 403;
                $payload['status'] = self::STATUS_SUBSCRIPTION_REQUIRED;
                return $payload;
            }
        }

        return $payload;
    }

    public function findContent(string $type, int $id): KnowledgeSession|VideoContent|null
    {
        if ($type === self::TYPE_ARTICLE) {
            return KnowledgeSession::query()->find($id);
        }

        return VideoContent::query()->find($id);
    }

    public function normalizeAudience(?string $userType): string
    {
        $userType = strtolower(trim((string) $userType));
        if (in_array($userType, ['all', 'both', ''], true)) {
            return 'all';
        }
        if (in_array($userType, ['staff', 'teacher'], true)) {
            return 'staff';
        }
        if ($userType === 'child') {
            return 'child';
        }
        if ($userType === 'parent') {
            return 'parent';
        }

        return 'all';
    }

    public function audienceAllows(string $contentAudience, User $viewer, KnowledgeSession|VideoContent $model): bool
    {
        $viewerKind = $this->viewerKind($viewer);

        if ($contentAudience === 'all') {
            $allowed = true;
        } elseif ($contentAudience === 'child') {
            $allowed = $viewerKind === 'child';
        } elseif ($contentAudience === 'parent') {
            $allowed = in_array($viewerKind, ['parent', 'staff'], true);
        } elseif ($contentAudience === 'staff') {
            $allowed = $viewerKind === 'staff';
        } else {
            $allowed = true;
        }

        if (!$allowed) {
            return false;
        }

        if ($viewerKind === 'child' && !$this->ageAllows($model->age ?? null, $viewer)) {
            return false;
        }

        if ($model instanceof VideoContent && !$this->schoolAllows($model, $viewer)) {
            return false;
        }

        return true;
    }

    public function viewerKind(User $viewer): string
    {
        $type = strtolower((string) $viewer->user_type);
        $role = (string) $viewer->user_role_id;

        if ($type === 'child' || $role === '4') {
            return 'child';
        }
        if (in_array($type, ['teacher', 'staff'], true) || $role === '5') {
            return 'staff';
        }
        if (in_array($role, ['1', '2'], true) || $type === 'admin') {
            return 'staff';
        }

        return 'parent';
    }

    public function requiresSubscription(User $viewer): bool
    {
        if ($this->viewerKind($viewer) !== 'parent') {
            return false;
        }

        return empty($viewer->school_id);
    }

    public function hasActiveSubscription(User $viewer): bool
    {
        $ownerId = $viewer->id;
        if ($this->viewerKind($viewer) === 'child' && $viewer->parent_id) {
            $ownerId = $viewer->parent_id;
        }

        return Subscription::query()
            ->where('user_id', $ownerId)
            ->whereIn('status', ['successful', 'cancelled'])
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();
    }

    protected function ageAllows(?string $ageRange, User $child): bool
    {
        if (!$ageRange || strpos($ageRange, '-') === false || empty($child->dob)) {
            return true;
        }

        try {
            $age = Carbon::parse($child->dob)->age;
        } catch (\Throwable $e) {
            return true;
        }

        [$min, $max] = array_map('intval', explode('-', $ageRange, 2));

        return $age >= $min && $age <= $max;
    }

    protected function schoolAllows(VideoContent $video, User $viewer): bool
    {
        $schoolIds = $video->school_id;
        if ($schoolIds === null || $schoolIds === '' || $schoolIds === []) {
            return true;
        }
        if (is_string($schoolIds)) {
            $decoded = json_decode($schoolIds, true);
            $schoolIds = is_array($decoded)
                ? $decoded
                : preg_split('/\s*,\s*/', $schoolIds, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($schoolIds)) {
            $schoolIds = [$schoolIds];
        }
        $schoolIds = array_values(array_filter(array_map('intval', $schoolIds)));
        if ($schoolIds === []) {
            return true;
        }
        $viewerSchool = $viewer->school_id ? (int) $viewer->school_id : null;
        if ($this->viewerKind($viewer) === 'child' && !$viewerSchool && $viewer->parent_id) {
            $parent = User::query()->find($viewer->parent_id);
            $viewerSchool = $parent?->school_id ? (int) $parent->school_id : null;
        }

        if (!$viewerSchool) {
            return false;
        }

        return in_array($viewerSchool, $schoolIds, true);
    }

    protected function teaser(?string $html): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html))) ?? '');
        if ($text === '') {
            return null;
        }

        return Str::limit($text, 240);
    }

    protected function bannerUrl(KnowledgeSession|VideoContent $model): ?string
    {
        $filename = $model instanceof VideoContent
            ? ($model->thumbnail ?: $model->banner_image)
            : $model->banner_image;
        $folder = 'assets/images';
        if (!$filename) {
            return null;
        }
        try {
            if (function_exists('getImagePathUrl')) {
                $url = getImagePathUrl($filename, $folder);
                if ($url) {
                    return $url;
                }
            }
        } catch (\Throwable $e) {
        }

        return asset($folder . '/' . ltrim((string) $filename, '/'));
    }
}
