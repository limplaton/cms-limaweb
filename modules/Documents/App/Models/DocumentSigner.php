<?php
 

namespace Modules\Documents\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Models\Model;
use Modules\Documents\Database\Factories\DocumentSignerFactory;

class DocumentSigner extends Model
{
    use HasFactory;

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['document'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'sent_at', 'send_email'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'document_id' => 'int',
        'signed_at' => 'datetime',
        'sent_at' => 'datetime',
        'send_email' => 'bool',
    ];

    /**
     * Get the document the signers belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(\Modules\Documents\App\Models\Document::class);
    }

    /**
     * Check whether the signer has signed the document
     */
    public function hasSignature(): bool
    {
        return ! is_null($this->signature) && ! is_null($this->signed_at) && ! is_null($this->sign_ip);
    }

    /**
     * Check whether the signer is missing the signature
     */
    public function missingSignature(): bool
    {
        return ! $this->hasSignature();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DocumentSignerFactory
    {
        return DocumentSignerFactory::new();
    }
}
