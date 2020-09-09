<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableI;
use OwenIt\Auditing\Contracts\Auditable;

class SocialNetwork extends Model implements Auditable
{
    use AuditableI, SoftDeletes;

    protected $table = 'social_networks';

    protected $fillable = ['name', 'slug', 'logo'];

    protected $dates = ['deleted_at'];

    public function getSocialNetwork($id)
    {
        return self::find(4);
    }

    // Socialite
    public function users()
    {
        return $this->belongsToMany(
            'App\User',
            'social_users',
            'social_network_id'
        )->withPivot('social_network_user_id', 'data');
    }
}
