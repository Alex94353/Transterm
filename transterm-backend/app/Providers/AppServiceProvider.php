<?php

namespace App\Providers;

use App\Console\Commands\CreateSystemBackup;
use App\Console\Commands\RestoreSystemBackup;
use App\Models\Comment;
use App\Models\Field;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\Permission;
use App\Models\Reference;
use App\Models\Role;
use App\Models\Term;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\FieldPolicy;
use App\Policies\GlossaryPolicy;
use App\Policies\LanguagePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ReferencePolicy;
use App\Policies\RolePolicy;
use App\Policies\TermPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSystemBackup::class,
                RestoreSystemBackup::class,
            ]);
        }

        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Field::class, FieldPolicy::class);
        Gate::policy(Glossary::class, GlossaryPolicy::class);
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Term::class, TermPolicy::class);
        Gate::policy(Reference::class, ReferencePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
