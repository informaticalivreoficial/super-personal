<?php

namespace App\Models;

use App\Support\Cropper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Post extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'posts'; 

    protected $fillable = [ 
        'autor',
        'type',
        'title',
        'content',
        'slug',
        'tags',
        'views',
        'category',
        'comments',
        'highlight',
        'cat_pai',        
        'status',
        'menu',
        'thumb_caption',
        'publish_at'
    ];

    protected $casts = [
        'status' => 'boolean',
        'comments' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();        
    }

    protected static function booted()
    {
        static::saving(function ($post) {
            $post->setSlug();
        });

        static::deleting(function ($post) {
            // Só deleta as imagens no forceDelete
            if ($post->isForceDeleting()) {
                Storage::disk('public')->deleteDirectory("posts/{$post->type}/{$post->id}");
                $post->images()->delete();
            }
        });
    }

    /**
     * Scopes
    */
    public function scopePostson($query)
    {
        return $query->where('status', 1);
    }
    
    public function scopePostsoff($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Relacionamentos
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'autor', 'id');
    }
    
    public function category()
    {
        return $this->hasOne(CatPost::class, 'id', 'category');
    }
    
    public function categoryObject()
    {
        return $this->hasOne(CatPost::class, 'id', 'category');
    }
    
    public function userObject()
    {
        return $this->hasOne(User::class, 'id', 'autor');
    }
    
    public function images()
    {
        return $this->hasMany(PostGb::class, 'post', 'id')->orderBy('cover', 'ASC');
    }

    public function countimages()
    {
        return $this->hasMany(PostGb::class, 'post', 'id')->count();
    }

    /**
     * Accerssors and Mutators
     */

    public function getContentWebAttribute()
    {
        return Str::words($this->content, '20', ' ...');
    }

    public function cover()
    {
        $images = $this->images();
        $cover = $images->where('cover', 1)->first(['path']) ??
                $images->first(['path']);

        if (!$cover || empty($cover->path)) {
            return asset('theme/images/image.jpg');
        }

        return Storage::url(Cropper::thumb($cover['path'], 720, 480));
    }    

    public function nocover()
    {
        $images = $this->images();

        // Pega capa, se não existir usa a primeira imagem
        $cover = $images->where('cover', 1)->first(['path'])
            ?? $images->first(['path']);

        if (empty($cover['path']) || !Storage::disk()->exists($cover['path'])) {
            return asset('theme/images/image.jpg');
        }
        
        return Storage::url($cover['path']);
    }  

    public function setPublishAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['publish_at'] = null;
            return;
        }

        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
            $this->attributes['publish_at'] = $value->format('Y-m-d');
            return;
        }

        // Tenta d/m/Y (vindo do front)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $this->attributes['publish_at'] = \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            return;
        }

        // Qualquer outro formato (Y-m-d, Y-m-d H:i:s, etc.) — vindo do seeder/factory
        $this->attributes['publish_at'] = \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function getPublishAtAttribute($value)
    {
        return $value 
            ? Carbon::parse($value)->format('d/m/Y') 
            : null;
    }
 
    
    public function setSlug()
    {
        if (!empty($this->title)) {
    
            $baseSlug = Str::slug($this->title);
            $slug = $baseSlug;
            $count = 1;
    
            while (
                Post::where('slug', $slug)
                    ->where('id', '!=', $this->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);
                $count++;
            }
    
            $this->attributes['slug'] = $slug;
        }
    }   
    
}
