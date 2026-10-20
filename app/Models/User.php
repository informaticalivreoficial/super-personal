<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'password', 'remember_token',
        'gender',
        'cpf',
        'rg',
        'rg_expedition',
        'birthday',
        'naturalness',
        'civil_status',
        'avatar',
        // Address
        'zipcode', 'street', 'number', 'complement', 'neighborhood', 'state', 'city',
        // Contact
        'phone', 'cell_phone', 'whatsapp', 'skype', 'telegram', 'email', 'additional_email',
        // Social
        'facebook', 'twitter', 'instagram', 'linkedin',
        'status',
        'information',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_change_expires_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    /**
     * Cache do tenant (treinador) resolvido nesta requisição.
     */
    private bool $tenantResolved = false;

    private ?int $tenantId = null;

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->avatar && Storage::disk()->exists($user->avatar)) {
                Storage::disk()->delete($user->avatar);
            }
        });
    }

    /**
     * Relacionamentos
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Identifica o papel do usuário pela coluna `role` (fonte de verdade do SaaS).
     * Os helpers legados do starter (isSuperAdmin/isAdmin/isManager/isEmployee),
     * que dependiam do spatie/laravel-permission, foram removidos: tudo migrou
     * para `users.role` (enum UserRole).
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }

    /**
     * Dados obrigatórios para o treinador operar o painel.
     *
     * O CPF identifica o pagador junto ao parceiro de pagamentos (o gateway
     * recusa a cobrança sem `customer.tax_id`) e o celular é o contato do
     * tenant. Lista vazia = cadastro completo.
     *
     * @return array<int, string> rótulos pt-BR dos campos faltantes
     */
    public function missingProfileFields(): array
    {
        $missing = [];

        if (preg_replace('/\D/', '', (string) $this->cpf) === '') {
            $missing[] = 'CPF';
        }

        if (preg_replace('/\D/', '', (string) $this->cell_phone) === '') {
            $missing[] = 'celular';
        }

        return $missing;
    }

    /**
     * Troca de e-mail com validação: `pending_email` guarda o endereço novo
     * até o titular confirmar o código enviado para ele. As colunas do fluxo
     * (`pending_email`, `email_change_code`, `email_change_expires_at`,
     * `email_change_attempts`) não são fillable — quem escreve é o
     * `ProfileService`.
     */
    public function hasPendingEmailChange(): bool
    {
        return filled($this->pending_email);
    }

    /** Limpa o fluxo de troca de e-mail (confirmado/cancelado/expirado). */
    public function clearEmailChange(): void
    {
        $this->pending_email = null;
        $this->email_change_code = null;
        $this->email_change_expires_at = null;
        $this->email_change_attempts = 0;
    }

    /**
     * Resolve o tenant (treinador) do usuário autenticado, com cache por requisição.
     *
     * @return ?int null = administrador da plataforma (sem filtro de tenant);
     *              0   = perfil não encontrado (nada visível);
     *              >0  = id do treinador (tenant).
     */
    public function resolveTenantId(): ?int
    {
        if ($this->isPlatformAdmin()) {
            return null;
        }

        if ($this->tenantResolved) {
            return $this->tenantId;
        }

        $this->tenantResolved = true;

        $this->tenantId = match (true) {
            $this->isTeacher() => (int) Teacher::query()->where('user_id', $this->id)->value('id') ?: 0,
            default => (int) $this->student()->withoutGlobalScope('tenant')->value('teacher_id') ?: 0,
        };

        return $this->tenantId;
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 1);
    }

    public function scopeUnavailable($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Accerssors and Mutators
     */
    public function getUrlAvatarAttribute(): string
    {
        if (! empty($this->avatar) && Storage::disk()->exists($this->avatar)) {
            return Storage::disk()->url($this->avatar);
        }

        return asset('images/image.jpg');
    }

    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = (! empty($value) ? $this->clearField($value) : null);
    }

    public function getCpfAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $v = $this->clearField($value);

        if (strlen($v) === 11) {
            return substr($v, 0, 3).'.'.substr($v, 3, 3).'.'.substr($v, 6, 3).'-'.substr($v, 9, 2);
        }

        return $value;
    }

    public function setCellPhoneAttribute($value)
    {
        $this->attributes['cell_phone'] = (! empty($value) ? $this->clearField($value) : null);
    }

    public function getCellPhoneAttribute($value): ?string
    {
        return $this->formatPhone($value);
    }

    public function setWhatsappAttribute($value)
    {
        $this->attributes['whatsapp'] = (! empty($value) ? $this->clearField($value) : null);
    }

    public function getWhatsappAttribute($value): ?string
    {
        return $this->formatPhone($value);
    }

    public function setBirthdayAttribute($value)
    {
        $this->attributes['birthday'] = (! empty($value) ? $this->convertStringToDate($value) : null);
    }

    public function getBirthdayAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    public function setZipcodeAttribute($value)
    {
        $this->attributes['zipcode'] = (! empty($value) ? $this->clearField($value) : null);
    }

    public function getZipcodeAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        return substr($value, 0, 5).'-'.substr($value, 5, 3);
    }

    private function convertStringToDouble(?string $param)
    {
        if (empty($param)) {
            return null;
        }

        return str_replace(',', '.', str_replace('.', '', $param));
    }

    private function convertStringToDate(?string $param): ?string
    {
        if (empty($param)) {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y', $param)->format('Y-m-d');
    }

    private function clearField(?string $param)
    {
        if (empty($param)) {
            return null;
        }

        return str_replace(['.', '-', '/', '(', ')', ' '], '', $param);
    }

    private function formatPhone(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $v = $this->clearField($value);

        if (strlen($v) === 11) {
            return "({$v[0]}{$v[1]}) ".substr($v, 2, 5).'-'.substr($v, 7, 4);
        }

        if (strlen($v) === 10) {
            return "({$v[0]}{$v[1]}) ".substr($v, 2, 4).'-'.substr($v, 6, 4);
        }

        return $value;
    }
}
