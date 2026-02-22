<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kuroragi\GeneralHelper\Traits\Blameable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, Blameable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'business_unit_id',
        'is_active',
        'google_id',
        'avatar',
        'email_verified_at',
        'email_otp',
        'email_otp_expires_at',
        'skip_email_verification',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'skip_email_verification' => 'boolean',
        ];
    }

    /**
     * Override: treat user as verified if skip_email_verification is true.
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->skip_email_verification) {
            return true;
        }

        return !is_null($this->email_verified_at);
    }

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>=', now()->toDateString())
            ->latest('ends_at');
    }

    public function pendingSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'pending')
            ->latest();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
