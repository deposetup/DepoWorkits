<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_title',
        'authorized_person',
        'phone',
        'email',
        'gln_number',
        'its_password_encrypted',
        'status',
    ];

    protected $hidden = [
        'its_password_encrypted',
    ];

    /**
     * İTS şifresi her zaman şifreli (encrypted) saklanır.
     * Müşteri arayüzünde bu alan salt-okunur gösterilir; değişiklik
     * sadece CredentialChangeRequest onay akışıyla, admin tarafından yapılır.
     */
    public function setItsPasswordAttribute(string $value): void
    {
        $this->attributes['its_password_encrypted'] = Crypt::encryptString($value);
    }

    public function getItsPasswordAttribute(): ?string
    {
        return $this->its_password_encrypted
            ? Crypt::decryptString($this->its_password_encrypted)
            : null;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function activeLicense()
    {
        return $this->hasOne(License::class)->where('status', 'active')->latestOfMany('ends_at');
    }

    public function changeRequests()
    {
        return $this->hasMany(CredentialChangeRequest::class);
    }

    public function itsNotifications()
    {
        return $this->hasMany(ItsNotification::class);
    }
}
