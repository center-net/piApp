<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * الحقول القابلة للإسناد الجماعي
     */
    protected $fillable = [
        'name',
        'pi_username',
        'public_key',
        'email',
        'meta',
        'password',
        'pi_id',
        'pi_access_token',
        'pi_refresh_token',
        'last_login_at',
    ];

    /**
     * الحقول المخفية عند التسلسل
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pi_access_token',
        'pi_refresh_token',
    ];

    /**
     * تحويلات الحقول
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'meta' => 'array',
        'password' => 'hashed',
        'pi_access_token' => 'encrypted',
        'pi_refresh_token' => 'encrypted',
    ];

    /**
     * تفعيل الـ timestamps
     */
    public $timestamps = true;

    /**
     * الحقول القابلة للبحث
     */
    protected $searchable = [
        'name',
        'pi_username',
        'email',
    ];

    // ===== العلاقات =====

    /**
     * الحصول على جميع tokens المستخدم
     */
    public function tokens()
    {
        return $this->hasMany(\Laravel\Sanctum\PersonalAccessToken::class);
    }

    // ===== الـ Accessors و Mutators =====

    /**
     * التحقق من أن المستخدم مصرح (authenticated)
     */
    public function isAuthenticated(): bool
    {
        return !is_null($this->id);
    }

    /**
     * التحقق من ارتباط المستخدم بـ Pi
     */
    public function isPiLinked(): bool
    {
        return !is_null($this->pi_id) || !is_null($this->public_key);
    }

    /**
     * الحصول على اسم المستخدم الكامل
     */
    public function getDisplayName(): string
    {
        return $this->pi_username ?? $this->name ?? 'مستخدم';
    }

    /**
     * الحصول على آخر وقت تسجيل دخول بصيغة مقروءة
     */
    public function getLastLoginFormatted(): string
    {
        return $this->last_login_at?->format('Y-m-d H:i:s') ?? 'لم يسجل دخول من قبل';
    }

    // ===== الـ Scopes =====

    /**
     * البحث عن مستخدمين مرتبطين بـ Pi
     */
    public function scopePiLinked($query)
    {
        return $query->whereNotNull('public_key');
    }

    /**
     * البحث عن مستخدمين بواسطة اسم المستخدم
     */
    public function scopeByUsername($query, $username)
    {
        return $query->where('pi_username', $username)
                     ->orWhere('name', $username);
    }

    /**
     * البحث عن مستخدمين نشيطين (سجلوا دخول في آخر 30 يوم)
     */
    public function scopeActive($query)
    {
        return $query->where('last_login_at', '>=', now()->subDays(30));
    }

    /**
     * البحث عن مستخدمين بحسب البريد الإلكتروني
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    // ===== الـ Events =====

    /**
     * عند إنشاء مستخدم جديد
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // إنشاء معرف فريد إذا لم يكن موجود
            if (!$user->pi_id) {
                $user->pi_id = 'pi_' . \Illuminate\Support\Str::random(16);
            }
        });

        static::updated(function ($user) {
            // يمكن إضافة logic عند تحديث المستخدم هنا
        });
    }
}
