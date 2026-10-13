<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use HasFactory;

    /**
     * Slugs proibidos na landing pública (/treinador/{slug}) — evita colisão
     * com rotas/termos do SaaS e URLs que parecem oficiais da plataforma.
     */
    public const RESERVED_SLUGS = [
        'admin', 'auth', 'login', 'register', 'api', 'app', 'assets', 'config',
        'configuracoes', 'aluno', 'alunos', 'treinador', 'treinadores', 'painel',
        'planos', 'pagamentos', 'site', 'www', 'suporte', 'health', 'up',
    ];

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'landing_title',
        'bio',
        'phone',
        'whatsapp',
        'document',
        'birth_date',
        'profile_photo',
        'logo',
        'favicon',
        'metaimg',
        'facebook',
        'twitter',
        'instagram',
        'youtube',
        'linkedin',
        'display_address',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'state',
        'city',
        'cref',
        'specialty',
        'active',
        'public_profile',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
        'public_profile' => 'boolean',
        'display_address' => 'boolean',
        'views' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Teacher $teacher) {
            if ($teacher->slug === null || $teacher->slug === '') {
                // Slug nunca gerado (criação ou slug limpo no form): deriva do
                // nome com unicidade + palavras reservadas.
                $teacher->slug = static::makeUniqueSlug($teacher->name, $teacher->id);
            } else {
                $teacher->slug = Str::slug($teacher->slug);
            }
        });
    }

    /**
     * Slug único a partir do nome (ou do slug fornecido).
     * Reservado ou duplicado ganha sufixo -2, -3... (ex.: admin-2).
     */
    public static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'perfil';
        $slug = $base;
        $i = 2;

        while (in_array($slug, static::RESERVED_SLUGS, true)
            || static::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * A landing pública só existe com opt-in E perfil ativo.
     */
    public function landingVisible(): bool
    {
        return (bool) $this->public_profile && (bool) $this->active;
    }

    /**
     * URL pública da landing (centraliza a estrutura /treinador/{slug}).
     */
    public function getLandingUrlAttribute(): string
    {
        return route('web.treinador', $this->slug);
    }

    /**
     * Foto do perfil em URL utilizável (caminho no disco public ou URL cheia).
     * Sem foto: null — a view usa as iniciais do nome.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->profile_photo);
    }

    /**
     * Imagens da landing (logo = cabeçalho, favicon = aba, metaimg = og:image).
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->logo);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->favicon);
    }

    public function getMetaimgUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->metaimg);
    }

    /**
     * CEP armazenado só com dígitos (padrão users/config) e exibido
     * como #####-### quando tem 8 dígitos.
     */
    public function setZipcodeAttribute($value): void
    {
        $this->attributes['zipcode'] = ! empty($value)
            ? preg_replace('/\D+/', '', (string) $value)
            : null;
    }

    public function getZipcodeAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return strlen($value) === 8
            ? substr($value, 0, 5).'-'.substr($value, 5)
            : $value;
    }

    /**
     * Caminho no disco public vira URL; URL/absolute passa direto.
     */
    private function toPublicUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function trainingPlans(): HasMany
    {
        return $this->hasMany(TrainingPlan::class);
    }

    public function trainingWeeks(): HasMany
    {
        return $this->hasMany(TrainingWeek::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
