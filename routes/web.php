<?php

use App\Models\Tag;
use App\Models\Post;
use App\Models\User;
use App\Models\Billing;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/find/{id}', fn (int $id) => Post::find($id));
Route::get('/find-or-fail/{id}', function (int $id) {
    try {
        return Post::findOrFail($id);
    } catch(ModelNotFoundException $e) {
        return $e->getMessage();
    }
});
Route::get('/find-or-fail-with-columns/{id}', fn (int $id) => Post::findOrFail($id, ["id", "title"]));
Route::get('/find-by-slug/{slug}', fn (string $slug) => Post::firstWhere('slug', $slug));
Route::get('/find-many', fn () => /*Post::whereIn('id', [1,2,3])->get()*/Post::find([1,2,3], ['id', 'title']));
Route::get('/paginated/{perPage}', fn (int $perPage=10) => Post::paginate($perPage, ['id', 'title']));
Route::get('/manual-pagination/{perPage}/{offset?}', fn (int $perPage, int $offset = 0) => Post::offset($offset)->limit($perPage)->get());
Route::get('/create', function () {
    $user = User::all()->random(1)->first()->id;
    return Post::create([
        'user_id'   => $user,
        'category_id'   => Category::all()->random(1)->first()->id,
        'title' => "Post para el usuario {$user}",
        'content' => "Nuevo post de pruebas"
    ]);
});
Route::get('/first-or-create', function () {
    return Post::firstOrCreate(
        ['title' => 'Post para un usuario aleatorio'],
        [
            'user_id'   => User::all()->random(1)->first()->id,
            'category_id'   => Category::all()->random(1)->first()->id,
            'title' => "Nuevo post para otro usuario",
            'content' => "Nuevo post de pruebas"
        ]
    );
});
Route::get('/with-relations/{id}', function (int $id) {
    return Post::with('user', 'category', 'tags')->find($id);
});
Route::get('/with-relations-using-load/{id}', function (int $id) {
    $post = Post::findOrFail($id);
    $post->load('user', 'category', 'tags');
    return $post;
});
Route::get('/with-relations-and-columns/{id}', function (int $id) {
    return Post::select(['id','user_id','category_id','title'])
            ->with([
                'user:id,name,email',
                'user.billing:id,user_id,credit_card_number',
                'tags:id,tag',
                'category:id,name'
            ])
            ->find($id);
});
Route::get('/with-count-posts/{id}', function (int $id) {
    return User::select(['id', 'name', 'email'])
            ->withCount('posts')
            ->findOrFail($id);
});
Route::get('/update/{id}', function (int $id) {
    // $post = Post::findOrFail($id);
    // $post->title = "Post actualizado";
    // $post->save();
    // return $post;
    return Post::findOrFail($id)->update([
        'title' => "Post actualizado de nuevo"
    ]);
});
Route::get('/update-or-create/{slug}', function (string $slug) {
    return Post::updateOrCreate(
        ['slug' => $slug],
        [
            'user_id' => User::all()->random(1)->first()->id,
            'category_id' => Category::all()->random(1)->first()->id,
            'title' => 'Post de pruebas',
            'content'   =>  'Nuevo contenido actualizado',
        ]
    );
});
Route::get('/delete-with-tags/{id}', function (int $id) {
    try {
        DB::beginTransaction();
        $post = Post::findOrFail($id);
        $post->tags()->detach();
        $post->delete();
        DB::commit();
    } catch(Exception $e) {
        DB::rollback();
        return $e->getMessage();
    }
});
Route::get('/like/{id}', function (int $id) {
    return Post::findOrFail($id)->increment('likes');
});
Route::get('/chunk/{amount}', function (int $amount) {
    Post::chunk($amount, function (Collection $chunk) {
        sleep(5);
    });
});
Route::get('/create-with-relation', function () {
    try {
        DB::beginTransaction();
        $user = User::firstOrCreate(
            ['name' => 'spike'],
            [
                'name' => 'spike',
                'age'   =>  27,
                'email' => 'spike@spiegel.com',
                'password'  =>  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            ]
        );
        Billing::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'credit_card_number' => '123456789'
            ]
        );
        DB::commit();
        return $user->load('billing:id,user_id,credit_card_number');
    } catch (Exception $e) {
        DB::rollback();
        return $e->getMessage();
    }
});
Route::get('/update-with-relation/{id}', function (int $id) {
    $post = Post::findOrFail($id);
    $post->title = "Post actualizado con relaciones";
    $post->tags()->attach(Tag::all()->random(1)->first()->id);
    $post->save();
});
Route::get('/has-two-tags-or-more', function () {
    return Post::select(['id', 'title'])
            ->withCount('tags')
            ->has('tags', '>=', 2)
            ->get();
});
Route::get('/with-tags-sorted/{id}', function (int $id) {
    return Post::with('sortedTags:id,tag')
            ->find($id);
});
Route::get('/with-where-has-tags', function () {
    return Post::whereHasTagsWithTags()->get();
});
Route::get('/autoload-user-from-post-with-tags/{id}', function (int $id) {
    return Post::with('tags:id,tag')->findOrFail($id);
});
Route::get('/custom-attributes/{id}', function (int $id) {
    return Post::with('user:id,name')->findOrFail($id);
});
Route::get('/by-created-at/{date}', function (string $date) {
    return Post::whereDate('created_at', $date)
            ->get();
});
Route::get('/by-created-at-month-day/{day}/{month}', function (int $day, int $month) {
    return Post::whereMonth('created_at', $month)
            ->whereDay('created_at', $day)
            ->get();
});
Route::get('/between-by-created-at/{start}/{end}', function (string $start, string $end) {
    return Post::whereBetween('created_at', [$start, $end])->get();
});
Route::get('/when-slug', function () {
    return Post::whereMonth('created_at', now()->month)
            ->whereDay('created_at', '>', 5)
            ->when(request()->query('slug'), function (Builder $builder) {
                $builder->whereSlug(request()->query('slug'));
            })
            ->get();
});
Route::get('/subquery', function () {
    return User::where(function (Builder $builder) {
        $builder->where('banned', true)
            ->where('age', '>=', 50);
    })
    ->orWhere(function (Builder $builder) {
        $builder->where('banned', false)
            ->where('age', '<=', 30);
    })
    ->get();
});
Route::get('/global-scope-posts-current-month', function () {
    return Post::count();
});
Route::get('/without-global-scope-posts-current-month', function () {
    return Post::withoutGlobalScope('currentMonth')->count();
});
Route::get('/query-raw', function () {
    return Post::withoutGlobalScope('currentMonth')
            ->with('category')
            ->select([
                'id',
                'category_id',
                'likes',
                'dislikes',
                DB::raw('SUM(likes) as total_likes'),
                DB::raw('SUM(dislikes) as total_dislikes'),
            ])
            ->groupBy('category_id')
            ->get();
});
Route::get('/query-raw-having-raw', function () {
    return Post::withoutGlobalScope('currentMonth')
            ->with('category')
            ->select([
                'id',
                'category_id',
                'likes',
                'dislikes',
                DB::raw('SUM(likes) as total_likes'),
                DB::raw('SUM(dislikes) as total_dislikes'),
            ])
            ->groupBy('category_id')
            ->havingRaw("SUM(likes) < ?", [110])
            ->get();
});
Route::get('/order-by-subqueries', function () {
    return User::select(['id', 'name'])
            ->has('posts')
            ->orderByDesc(
                Post::withoutGlobalScope('currentMonth')
                    ->select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1)
            )
            ->get();
});
Route::get('/select-subqueries', function () {
    return User::select(['id', 'name'])
            ->has('posts')
            ->addSelect([
                'last_post' => Post::withoutGlobalScope('currentMonth')
                    ->select('title')
                    ->whereColumn('user_id', 'users.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1)
            ])
            ->get();
});
Route::get('/multiple-insert', function () {
    $users = new Collection();
    for ($i=1;$i<=20;$i++) {
        $users->push([
            "name" => "usuario {$i}",
            "email" => "usuario{$i}@test.com",
            "password"  => "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",
            "email_verified_at" => now(),
            "created_at" => now(),
            "age" => rand(20, 50)
        ]);
    }
    User::insert($users->toArray());
});
Route::get('/batch-insert', function () {
    $userInstance = new User();
    $columns = [
        "name",
        "email",
        "password",
        "age",
        "banned",
        "email_verified_at",
        "created_at",
    ];
    $users = new Collection();
    for ($i=1;$i<=20;$i++) {
        $users->push([
            "usuario {$i}",
            "usuario{$i}@test.com",
            "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",
            rand(20, 50),
            rand(0, 1),
            now(),
            now(),
        ]);
    }
    $batchSize = 100;
    $batch = batch();
    return $batch->insert($userInstance, $columns, $users->toArray(), $batchSize);
});
Route::get('/batch-update', function () {
    $postInstance = new Post();
    $toUpdate = [
        [
            "id"    => 1,
            "likes"    => ["*", 2],
            "dislikes"    => ["/", 2],
        ],
        [
            "id"    => 2,
            "likes"    => ["-", 2],
            "title"    => "Nuevo título",
        ],
        [
            "id"    => 3,
            "likes"    => ["+", 5],
        ],
        [
            "id"    => 4,
            "likes"    => ["*", 2],
        ],
    ];
    $index = "id";
    $batch = batch();
    return $batch->update($postInstance, $toUpdate, $index);
});
