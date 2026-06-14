<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'type', 'description'];

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class);
    }

    public function helpArticles()
    {
        return $this->hasMany(HelpArticle::class);
    }
}
