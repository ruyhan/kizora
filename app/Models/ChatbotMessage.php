<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['conversation_id', 'role', 'content', 'products', 'created_at'];

    protected $casts = ['products' => 'array', 'created_at' => 'datetime'];

    public function conversation()
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
