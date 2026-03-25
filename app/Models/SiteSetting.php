<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Site setting model - data structure only.
 * All caching and business logic is handled by SiteSettingService to avoid DRY violation.
 *
 * @see \App\Services\SiteSettingService
 */
class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];
}
