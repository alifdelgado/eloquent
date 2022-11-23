<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $created_at = now()->subDays(rand(1, 20));
        $title = $this->faker->text(60);
        return [
            'title' => $title,
            'content'   => $this->faker->text(400),
            'likes' => rand(1, 20),
            'dislikes'  => rand(1, 20),
            'user_id'   => User::all()->random(1)->first()->id,
            'category_id'   => Category::all()->random(1)->first()->id,
            'created_at' => $created_at,
        ];
    }
}
