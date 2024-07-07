<?php
 

namespace Modules\MailClient\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\App\Models\Model;

class MessageLinksClick extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['url'];
}
