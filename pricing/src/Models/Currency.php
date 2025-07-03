<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Currency",
 *     title="Currency",
 *     description="Modèle représentant une devise monétaire",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la devise"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         description="Code ISO 4217 de la devise (e.g., 'USD', 'EUR', 'XOF')"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom de la devise (e.g., 'US Dollar', 'Euro')"
 *     ),
 *     @OA\Property(
 *         property="symbol",
 *         type="string",
 *         description="Symbole de la devise (e.g., '$', '€', 'FCFA')"
 *     ),
 *     @OA\Property(
 *         property="is_default",
 *         type="boolean",
 *         description="Indique si c'est la devise par défaut du système"
 *     ),
 *     @OA\Property(
 *         property="exchange_rate",
 *         type="number",
 *         format="float",
 *         description="Taux de change par rapport à une devise de base (e.g., 1 USD = X EUR)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la devise"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la devise"
 *     )
 * )
 */
class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_default',
        'exchange_rate',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'exchange_rate' => 'decimal:4',
    ];
}
