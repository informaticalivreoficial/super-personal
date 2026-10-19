<?php

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Enums\PayoutAccountType;
use App\Enums\PixKeyType;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherPayoutAccountFactory extends Factory
{
    protected $model = TeacherPayoutAccount::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'type' => PayoutAccountType::PIX,
            'label' => null,
            'pix_key' => fake()->numerify('###########'),
            'pix_key_type' => PixKeyType::CPF,
            'bank_name' => null,
            'bank_code' => null,
            'agency' => null,
            'account' => null,
            'account_digit' => null,
            'account_type' => null,
            'holder_name' => null,
            'holder_document' => null,
            'is_default' => false,
        ];
    }

    public function pix(): static
    {
        return $this->state(fn () => [
            'type' => PayoutAccountType::PIX,
            'pix_key' => fake()->numerify('###########'),
            'pix_key_type' => PixKeyType::CPF,
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'type' => PayoutAccountType::BANK_ACCOUNT,
            'pix_key' => null,
            'pix_key_type' => null,
            'bank_name' => 'Banco do Brasil',
            'bank_code' => '001',
            'agency' => '1234',
            'account' => '56789',
            'account_digit' => '0',
            'account_type' => BankAccountType::CHECKING,
            'holder_name' => fake()->name(),
            'holder_document' => fake()->numerify('###########'),
        ]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn () => ['teacher_id' => $teacher->id]);
    }
}
