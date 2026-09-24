<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RG44 — notification applicative destinée à un utilisateur.
 *
 * La table s'appelle `notifications_app` : Laravel réserve `notifications`
 * pour son propre système.
 */
class NotificationApp extends Model
{
    use HasFactory;

    protected $table = 'notifications_app';

    protected $primaryKey = 'id_notification';

    protected $fillable = ['message', 'date_envoi', 'lu', 'id_user'];

    protected function casts(): array
    {
        return [
            'date_envoi' => 'datetime',
            'lu'         => 'boolean',
            'id_user'    => 'integer',
        ];
    }

    /** RG44 — destinataire de la notification. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function scopeNonLues(Builder $query): Builder
    {
        return $query->where('lu', false);
    }

    /** Non lues d'abord, puis les plus récentes. */
    public function scopeOrdreAffichage(Builder $query): Builder
    {
        return $query->orderBy('lu')->orderByDesc('date_envoi');
    }
}
