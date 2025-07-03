<?php

namespace IJIDeals\Internationalization\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Language",
 *     title="Language",
 *     description="Modèle représentant une langue disponible dans l'application",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la langue"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         description="Code ISO de la langue (e.g., 'en', 'fr')"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom de la langue (e.g., 'English', 'Français')"
 *     ),
 *     @OA\Property(
 *         property="is_default",
 *         type="boolean",
 *         description="Indique si c'est la langue par défaut de l'application"
 *     ),
 *     @OA\Property(
 *         property="direction",
 *         type="string",
 *         description="Direction d'écriture de la langue (e.g., 'ltr' pour gauche à droite, 'rtl' pour droite à gauche)",
 *         enum={"ltr", "rtl"}
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="boolean",
 *         description="Statut de la langue (true pour actif, false pour inactif)"
 *     ),
 *     @OA\Property(
 *         property="flag_icon",
 *         type="string",
 *         nullable=true,
 *         description="Chemin ou classe CSS de l'icône de drapeau associée à la langue"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la langue"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la langue"
 *     )
 * )
 * App\\Models\\Language
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property string $direction
 * @property bool $status
 * @property string|null $flag_icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|Language active()
 *
 * @mixin \Eloquent
 */
class Language extends Model
{
    // TODO: Remove this model from app/Models. Now located in packages/localization/src/Models/Language.php
    // TODO: Create new package 'localization' for all language logic (modularité)
    // TODO: Use Localization\Traits\HasLanguages for language logic

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_default',
        'direction',
        'status',
        'flag_icon',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($language) {
            if (is_null($language->status)) {
                $language->status = true; // Set default status to active
            }
        });
    }

    /**
     * Get the active languages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Get the flag icon class.
     */
    public function getFlagIcon(): ?string
    {
        return $this->flag_icon;
    }
}
