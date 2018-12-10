<?php
/**
 * Theme model
 *
 * Handle Front-end Themes
 *
 * @author  Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @author  Faton Sopa <faton.sopa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use Illuminate\Support\Facades\Event;
use Route;
use Illuminate\Database\Eloquent\Model;
use Accio\App\Traits;

class ThemeModel extends Model
{
    use
      Traits\ThemeTrait,
      Traits\CollectionTrait;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setActiveTheme();

        Event::fire('theme:construct', [$this]);
    }

    /**
     * Handle callback of insert, update, delete.
     * */
    protected static function boot()
    {
        parent::boot();

        self::saving(
            function ($theme) {
                Event::fire('theme:saving', [$theme]);
            }
        );

        self::saved(
            function ($theme) {
                Event::fire('theme:saved', [$theme]);
            }
        );

        self::updating(
            function ($theme) {
                Event::fire('theme:updating', [$theme]);
            }
        );

        self::creating(
            function ($theme) {
                Event::fire('theme:creating', [$theme]);
            }
        );

        self::created(
            function ($theme) {
                Event::fire('theme:created', [$theme]);
            }
        );

        self::updated(
            function ($theme) {
                Event::fire('theme:updated', [$theme]);
            }
        );

        self::deleting(
            function ($theme) {
                Event::fire('theme:deleting', [$theme]);
            }
        );

        self::deleted(
            function ($theme) {
                Event::fire('theme:deleted', [$theme]);
            }
        );
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('theme:destruct', [$this]);
    }
}